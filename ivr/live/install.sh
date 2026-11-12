#!/usr/bin/env bash
# Bharat GPS Tracker: installs or updates the live call server on a VPS.
#   curl -fsSL https://someswara.com/ivr/live/install.sh | bash
# Safe to run again: it updates the server file and restarts only the call server.
# It adds one nginx site for the call address and does not touch any other site.
# Remove everything later with:  curl -fsSL https://someswara.com/ivr/live/install.sh | bash -s -- remove
set -euo pipefail

DOMAIN="${DOMAIN:-call.someswara.com}"
SRC="${SRC:-https://someswara.com/ivr/live}"
DIR=/opt/bgt-call
PORT=8790
UNIT=/etc/systemd/system/bgt-call.service

say() { echo; echo "==> $*"; }
stop() { echo; echo "STOPPED: $*"; exit 1; }
[ "$(id -u)" = 0 ] || stop "run this as root."

if [ -d /etc/nginx/sites-enabled ]; then
  SITE="/etc/nginx/sites-available/$DOMAIN.conf"; LINK="/etc/nginx/sites-enabled/$DOMAIN.conf"
else
  SITE="/etc/nginx/conf.d/$DOMAIN.conf"; LINK=""
fi

if [ "${1:-}" = remove ]; then
  say "Removing the live call server"
  systemctl disable --now bgt-call 2>/dev/null || true
  rm -f "$UNIT" "$SITE" ${LINK:+"$LINK"}
  systemctl daemon-reload
  nginx -t && systemctl reload nginx
  rm -rf "$DIR"
  echo "Removed. The security certificate for $DOMAIN is left in place; it expires by itself."
  exit 0
fi

say "Checking that $DOMAIN points to this server"
MYIP="$(curl -fsS -4 --max-time 10 ifconfig.me || true)"
DNSIP="$(getent ahostsv4 "$DOMAIN" | awk '{print $1; exit}' || true)"
[ -n "$DNSIP" ] || stop "$DOMAIN does not exist yet. Add the A record (Name: call, Points to: ${MYIP:-the IP of this server}), wait a few minutes, and run this again."
[ "$DNSIP" = "$MYIP" ] || stop "$DOMAIN points to $DNSIP but this server is $MYIP. Correct the A record, wait a few minutes, and run this again."
echo "OK: $DOMAIN -> $DNSIP"

say "Installing Node.js and certbot (other software is not changed)"
export DEBIAN_FRONTEND=noninteractive NEEDRESTART_SUSPEND=1
apt-get update -qq
apt-get install -y -qq nodejs npm certbot python3-certbot-nginx >/dev/null
NODE_MAJOR="$(node -p 'process.versions.node.split(".")[0]')"
[ "$NODE_MAJOR" -ge 20 ] || stop "Node.js $NODE_MAJOR is too old (20 or newer is needed)."
echo "OK: Node.js $(node -v)"

say "Installing the call server in $DIR"
id bgtcall >/dev/null 2>&1 || useradd --system --home-dir "$DIR" --shell /usr/sbin/nologin bgtcall
mkdir -p "$DIR"
curl -fsSL "$SRC/server.js" -o "$DIR/server.js.new"
node --check "$DIR/server.js.new" || stop "the downloaded server file is not valid. Nothing was changed."
mv "$DIR/server.js.new" "$DIR/server.js"
[ -d "$DIR/node_modules/ws" ] || (cd "$DIR" && npm install --silent --no-audit --no-fund --no-save ws@8)
chown -R bgtcall:bgtcall "$DIR"

cat > "$UNIT" <<EOF
[Unit]
Description=Bharat GPS Tracker live call server
After=network-online.target
Wants=network-online.target

[Service]
User=bgtcall
WorkingDirectory=$DIR
Environment=PORT=$PORT
ExecStart=/usr/bin/env node $DIR/server.js
Restart=always
RestartSec=3
NoNewPrivileges=true
ProtectSystem=full
ProtectHome=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable -q bgt-call
systemctl restart bgt-call
sleep 2
curl -fsS --max-time 5 "http://127.0.0.1:$PORT/health" >/dev/null || stop "the call server did not start. See: journalctl -u bgt-call -n 30"
echo "OK: call server running"

say "Adding the address $DOMAIN to nginx"
if [ ! -f "$SITE" ]; then
  cat > "$SITE" <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    location / {
        proxy_pass http://127.0.0.1:$PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_read_timeout 3600s;
    }
}
EOF
  [ -z "$LINK" ] || ln -sf "$SITE" "$LINK"
  if ! nginx -t 2>/dev/null; then
    rm -f "$SITE" ${LINK:+"$LINK"}
    stop "nginx did not accept the new site, so it was taken out again. Your other sites are untouched."
  fi
  systemctl reload nginx
fi
echo "OK: nginx site in place"

say "Getting the security certificate"
if ! grep -q ssl_certificate "$SITE"; then
  certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --register-unsafely-without-email --redirect
fi

say "Final check"
if curl -fsS --max-time 10 "https://$DOMAIN/health" | grep -q ok; then
  echo
  echo "DONE. The live call server is running at https://$DOMAIN"
  echo "Open the dashboard and start a Talk test."
else
  stop "https://$DOMAIN/health did not answer. See: journalctl -u bgt-call -n 30"
fi
