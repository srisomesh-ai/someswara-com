<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>AI Call Agent | Bharat GPS Tracker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@400;500&display=swap">
<style>
:root{--accent:#C8102E;--accent-dark:#8E0B20;--accent-soft:#F3E3E5;--bg:#F6F6F3;--card:#FFFFFF;--line:#E4E4DF;--line2:#C9C9C3;--text:#1A1A1A;--muted:#5A5F66;--soft:#EEEEEA}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.45 Inter,"Noto Sans Telugu",system-ui,sans-serif}
a{color:var(--text)}
h1{margin:0;font-size:26px;font-weight:700}
h2{margin:0;font-size:18px;font-weight:700}
button,input,select{font:inherit;color:var(--text)}
button{cursor:pointer}
button[disabled]{opacity:.55;cursor:default}
.hide{display:none!important}
.btn{min-height:44px;padding:0 18px;border-radius:8px;border:1px solid var(--line2);background:#fff;font-weight:600}
.btn-primary{min-height:52px;width:100%;border-radius:10px;border:none;background:var(--accent);color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;gap:10px}
.btn-outline{border-color:var(--accent);color:var(--accent-dark);font-weight:700}
.field{display:flex;flex-direction:column;gap:6px}
.field label,.lbl{font-size:13px;font-weight:600;color:var(--muted)}
.field input,.field select{min-height:44px;padding:0 12px;border-radius:8px;border:1px solid var(--line2);background:#fff;width:100%}
.hint{font-size:13px;color:var(--muted)}
.err{font-size:14px;color:#A01225;font-weight:600;min-height:20px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:22px}

.auth{max-width:420px;margin:8vh auto;padding:0 16px}
.auth .card{display:flex;flex-direction:column;gap:14px}

.shell{display:flex;flex-wrap:wrap;min-height:100vh}
.side{flex:1 1 220px;background:#fff;border-right:1px solid var(--line);padding:24px 16px;display:flex;flex-direction:column;gap:4px}
.brand{display:flex;align-items:center;gap:10px;padding:0 8px 20px}
.brand .logo{width:34px;height:34px;border-radius:8px;background:var(--accent);display:flex;align-items:center;justify-content:center;flex:none}
.brand b{display:block;font-size:15px}
.brand span{font-size:12px;color:var(--muted)}
.nav{display:flex;flex-direction:column;gap:4px}
.nav button{display:block;width:100%;text-align:left;padding:11px 12px;border-radius:8px;border:none;background:transparent;font-weight:500;white-space:nowrap}
.nav button.on{background:var(--accent-soft);color:var(--accent-dark);font-weight:600}
.main{flex:999 1 560px;min-width:0;padding:28px 32px 40px;display:flex;flex-direction:column;gap:24px}
.head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px}
.head .sub{color:var(--muted);font-size:14px;margin-top:4px}
.head .right{display:flex;flex-wrap:wrap;align-items:center;gap:12px}
.mode{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:#FFF1D6;color:#6B4700;font-weight:600;font-size:13px}
.mode i{width:8px;height:8px;border-radius:50%;background:#B97A00}
.row{display:flex;flex-wrap:wrap;gap:20px}
.row>.card{flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:16px}
.two{display:flex;flex-wrap:wrap;gap:12px}
.two>.field{flex:1 1 140px}
.rule{height:1px;background:var(--line)}
.inline{display:flex;flex-wrap:wrap;gap:10px}
.inline input{flex:1 1 160px;min-width:0;min-height:44px;padding:0 12px;border-radius:8px;border:1px solid var(--line2)}
.thead{display:flex;align-items:center;justify-content:space-between;gap:12px}
.lines{display:flex;flex-direction:column;gap:10px;min-height:150px;max-height:380px;overflow-y:auto}
.line{max-width:88%;border-radius:12px;padding:10px 14px}
.line small{display:block;font-size:12px;font-weight:700}
.line.agent{align-self:flex-start;background:var(--accent-soft)}
.line.agent small{color:var(--accent-dark)}
.line.caller{align-self:flex-end;background:var(--soft)}
.line.caller small{color:var(--muted)}
.line.flag{outline:2px solid var(--accent)}
.empty{color:var(--muted);font-size:14px}
.status{font-size:14px;font-weight:600;color:var(--accent-dark);min-height:20px}
.tiles{display:flex;flex-wrap:wrap;gap:16px}
.tile{flex:1 1 160px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px}
.tile b{display:block;font-size:30px;font-weight:700;margin-top:6px}
.scroll{overflow-x:auto}
table{width:100%;min-width:680px;border-collapse:collapse;font-size:14px}
th{text-align:left;color:var(--muted);font-weight:600;padding:10px 12px;border-bottom:1px solid var(--line)}
td{padding:12px;border-bottom:1px solid var(--soft);vertical-align:top}
tr:last-child td{border-bottom:none}
.pill{display:inline-block;padding:4px 10px;border-radius:999px;font-weight:600;font-size:13px;background:var(--soft);color:#3A3F45;white-space:nowrap}
.pill.good{background:#DDF3E4;color:#14532D}
.pill.bad{background:var(--accent-soft);color:var(--accent-dark)}
.link{border:none;background:none;padding:8px 0;font-weight:600;text-decoration:underline;min-height:44px}
.toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-size:14px;max-width:90vw;z-index:9}
.form{display:flex;flex-direction:column;gap:14px;max-width:460px}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
.seg{display:flex;border:1px solid var(--line2);border-radius:10px;overflow:hidden}
.seg button{flex:1 1 0;min-height:48px;border:none;background:#fff;font-weight:600}
.seg button.on{background:var(--accent);color:#fff;font-weight:700}
.check{display:flex;align-items:center;gap:12px;min-height:44px;font-weight:500}
.check input{width:22px;height:22px;accent-color:var(--accent)}
.btn-send,.btn-save{min-height:48px;padding:0 22px;border-radius:10px;border:none;background:var(--accent);color:#fff;font-weight:700}
.btn-save{min-height:44px;border-radius:8px}
.mark{display:block;margin-top:6px;min-height:44px;padding:0;border:none;background:none;font-size:13px;font-weight:600;color:var(--accent-dark);text-decoration:underline;text-align:left}
.stack{display:flex;flex-direction:column;gap:12px}
.hint14{font-size:14px;color:var(--muted)}
textarea{font:inherit;color:var(--text);width:100%;padding:12px;border-radius:8px;border:1px solid var(--line2);resize:vertical;background:#fff}
.item{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:8px;background:var(--bg)}
.item span{flex:1 1 auto;min-width:0;overflow-wrap:anywhere}
.txt{min-height:44px;padding:0 12px;border:none;background:none;font-weight:600;text-decoration:underline}
.txt.red{color:var(--accent-dark);text-decoration:none}
.ex{border:1px solid var(--line);border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:10px}
.ex.dashed{border-style:dashed;border-color:var(--line2)}
.pair{display:flex;flex-wrap:wrap;gap:12px}
.pair>div{flex:1 1 240px;min-width:0}
.cap{font-size:12px;font-weight:700;color:var(--muted)}
.cap.red{color:var(--accent-dark)}
.bubble{margin-top:4px;padding:10px 12px;border-radius:8px;background:var(--soft);overflow-wrap:anywhere}
.bubble.red{background:var(--accent-soft)}
.opt{display:flex;align-items:flex-start;gap:12px}
.opt input{width:22px;height:22px;margin-top:2px;flex:none;accent-color:var(--accent)}
.opt b{font-weight:600}
.opt small{display:block;font-size:14px;color:var(--muted)}
.steps{margin:0;padding-left:20px;display:flex;flex-direction:column;gap:6px}
.tag{display:inline-block;margin-top:6px;padding:3px 9px;border-radius:999px;background:#DDE8F7;color:#123C73;font-weight:600;font-size:12px}
.prod{display:flex;flex-direction:column;gap:8px;padding:14px;border-radius:8px;background:var(--bg)}
.prod .top{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;font-weight:700;font-size:16px}
.prod .top span{flex:1 1 auto;min-width:0;overflow-wrap:anywhere}
.prod .l{font-size:14px;overflow-wrap:anywhere}
.prod .l i{font-style:normal;color:var(--muted)}
.prod .acts{display:flex;gap:4px;margin-left:-12px}
.chips{display:flex;gap:6px;flex-wrap:wrap}
.chips .tag{margin-top:0}
.picks{display:flex;gap:8px;flex-wrap:wrap}
.pick{min-height:44px;padding:0 16px;border-radius:999px;border:1px solid var(--line2);background:#fff;font-weight:600}
.pick.on{border-color:#123C73;background:#DDE8F7;color:#123C73}
.badge{padding:4px 10px;border-radius:999px;background:#FFF1D6;color:#6B4700;font-weight:600;font-size:13px;white-space:nowrap}
@media (max-width:760px){
  .side{border-right:none;border-bottom:1px solid var(--line);padding:16px 12px 8px}
  .brand{padding-bottom:12px}
  .nav{flex-direction:row;overflow-x:auto;padding-bottom:8px}
  .nav button{width:auto}
  .main{padding:20px 16px 32px}
}
</style>
</head>
<body>

<!-- Set up / log in -->
<div id="v-auth" class="auth hide">
  <div class="card">
    <div class="brand" style="padding:0">
      <div class="logo" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></div>
      <div><b>Bharat GPS Tracker</b><span>AI Call Agent</span></div>
    </div>
    <h2 id="auth-title">Admin login</h2>
    <p id="auth-hint" class="hint hide" style="margin:0">First visit. Choose the admin password for this dashboard.</p>
    <form id="auth-form" class="form">
      <div class="field"><label for="a-pw">Password</label><input id="a-pw" type="password" autocomplete="current-password" required></div>
      <div id="a-key-wrap" class="field hide"><label for="a-key">Sarvam API key (you can add it later in Settings)</label><input id="a-key" autocomplete="off"></div>
      <div id="auth-err" class="err" role="alert"></div>
      <button id="auth-btn" class="btn-primary" type="submit">Log in</button>
    </form>
  </div>
</div>

<!-- Dashboard -->
<div id="v-app" class="shell hide">
  <div class="side">
    <div class="brand">
      <div class="logo" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></div>
      <div><b>Bharat GPS Tracker</b><span>AI Call Agent</span></div>
    </div>
    <nav class="nav" aria-label="Dashboard sections">
      <button type="button" data-view="overview" class="on">Overview</button>
      <button type="button" data-view="overview" data-scroll="test">Test calls</button>
      <button type="button" data-view="history">Call history</button>
      <button type="button" data-view="soon" data-step="4" data-name="Leads">Leads</button>
      <button type="button" data-view="training">Training</button>
      <button type="button" data-view="products">Products and prices</button>
      <button type="button" data-view="settings">Voice and language</button>
      <button type="button" data-view="phone">Phone line</button>
      <button type="button" data-view="gps">GPS servers</button>
      <button type="button" data-view="soon" data-step="4" data-name="Staff transfer">Staff transfer</button>
      <button type="button" data-view="settings">Settings</button>
    </nav>
  </div>

  <main class="main">

    <!-- Overview -->
    <div id="view-overview" style="display:flex;flex-direction:column;gap:24px">
      <div class="head">
        <div><h1>Overview</h1><div class="sub">Test calls made from this dashboard</div></div>
        <div class="right">
          <span class="mode"><i aria-hidden="true"></i>Test mode: not answering customers</span>
          <button type="button" class="btn" id="go-live">Go live</button>
        </div>
      </div>

      <div class="row" id="test">
        <div class="card">
          <h2>Start a test call</h2>
          <div class="field">
            <span class="lbl">How do you want to test?</span>
            <div class="seg" role="group" aria-label="Test mode">
              <button type="button" id="mode-talk" class="on" aria-pressed="true">Talk</button>
              <button type="button" id="mode-type" aria-pressed="false">Type</button>
            </div>
            <div class="hint">Type is for noisy places and for training. Talk is for checking how she sounds.</div>
          </div>
          <div class="two">
            <div class="field"><label for="lang">Language</label>
              <select id="lang"><option value="te">Telugu</option><option value="hi">Hindi</option><option disabled>English (later)</option></select></div>
            <div class="field"><label for="voice">Voice</label><select id="voice"></select></div>
          </div>
          <label class="check hide" id="speak-wrap"><input id="speak-on" type="checkbox" checked> Also play her replies in her voice</label>
          <button type="button" class="btn-primary" id="talk">
            <svg id="talk-icon" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0"/><path d="M12 18v3"/></svg>
            <span id="talk-label">Talk to the agent here</span></button>
          <div class="hint" id="talk-hint">Uses this device's microphone. No phone number needed.</div>
          <div id="call-err" class="err" role="alert"></div>
          <div class="rule"></div>
          <div class="field">
            <label for="myphone">Or ring a phone</label>
            <div class="inline">
              <input id="myphone" type="tel" placeholder="Your mobile number" disabled>
              <button type="button" class="btn btn-outline" id="call-me">Call me now</button>
            </div>
            <div class="hint">Real phone line, so you hear exactly what a customer hears. Opens in step 3.</div>
          </div>
        </div>

        <div class="card">
          <div class="thead"><h2>Live transcript</h2><span class="hint" id="speed"></span></div>
          <div class="status" id="status" role="status"></div>
          <div class="lines" id="lines"><div class="empty">Start a test call and the conversation appears here.</div></div>
          <div class="stack" style="margin-top:auto">
            <form class="inline hide" id="type-form">
              <label class="sr" for="typed">Type what the customer says</label>
              <input id="typed" type="text" autocomplete="off" placeholder="Type what the customer says">
              <button type="submit" class="btn-send" id="send">Send</button>
            </form>
            <div><button type="button" class="btn" id="end" disabled>End call</button></div>
          </div>
        </div>
      </div>

      <div class="tiles">
        <div class="tile"><span class="lbl">Calls answered today</span><b id="s-calls">0</b></div>
        <div class="tile"><span class="lbl">Leads captured</span><b id="s-leads">0</b></div>
        <div class="tile"><span class="lbl">Sent to staff</span><b id="s-staff">0</b></div>
        <div class="tile"><span class="lbl">Average call length</span><b id="s-avg">0:00</b></div>
      </div>

      <div class="card" style="display:flex;flex-direction:column;gap:14px">
        <div class="thead"><h2>Recent calls</h2><button type="button" class="link" data-goto="history">See all calls</button></div>
        <div class="scroll"><table>
          <thead><tr><th scope="col">Time</th><th scope="col">Caller</th><th scope="col">Asked about</th><th scope="col">Result</th><th scope="col">Length</th><th scope="col">Review</th></tr></thead>
          <tbody id="recent"></tbody>
        </table></div>
      </div>
    </div>

    <!-- Call history -->
    <div id="view-history" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head"><div><h1>Call history</h1><div class="sub">The last 50 calls</div></div></div>
      <div class="card hide" id="detail" style="display:flex;flex-direction:column;gap:14px">
        <div class="thead"><h2 id="detail-title">Call</h2><button type="button" class="btn" id="detail-close">Close</button></div>
        <div class="lines" id="detail-lines" style="max-height:none"></div>
      </div>
      <div class="card"><div class="scroll"><table>
        <thead><tr><th scope="col">Time</th><th scope="col">Caller</th><th scope="col">Asked about</th><th scope="col">Result</th><th scope="col">Length</th><th scope="col">Review</th></tr></thead>
        <tbody id="all"></tbody>
      </table></div></div>
    </div>

    <!-- Settings -->
    <div id="view-settings" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head"><div><h1>Settings</h1><div class="sub">Voice, language and keys</div></div>
        <div class="right"><button type="button" class="btn" id="logout">Log out</button></div></div>
      <div class="card">
        <form id="set-form" class="form">
          <div class="field"><label for="set-lang">Language</label>
            <select id="set-lang"><option value="te">Telugu</option><option value="hi">Hindi</option><option disabled>English (later)</option></select></div>
          <div class="field"><label for="set-voice">Voice</label><select id="set-voice"></select></div>
          <div class="field"><label for="set-key">Sarvam API key</label>
            <input id="set-key" autocomplete="off" placeholder="Paste your Sarvam API key">
            <span class="hint" id="key-state"></span></div>
          <div class="field"><label for="set-pw">New admin password</label>
            <input id="set-pw" type="password" autocomplete="new-password" placeholder="Leave empty to keep the current one"></div>
          <div id="set-err" class="err" role="alert"></div>
          <button class="btn-primary" type="submit">Save</button>
        </form>
      </div>
    </div>

    <!-- Training -->
    <div id="view-training" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head">
        <div><h1>Training</h1><div class="sub">What you save here is used from the very next call</div></div>
        <div class="right"><button type="button" class="btn-save" id="t-save">Save changes</button></div>
      </div>
      <div class="card stack">
        <h2>1. Greeting</h2>
        <label class="lbl" for="t-greet">The first thing she says when the call connects <span id="t-greet-lang"></span></label>
        <textarea id="t-greet" rows="3"></textarea>
      </div>
      <div class="card stack">
        <h2>2. Rules</h2>
        <div class="hint14">Write in simple English or Telugu, one rule per line. She follows every rule on every call.</div>
        <div id="t-rules" class="stack"></div>
        <form class="inline" id="t-rule-form">
          <label class="sr" for="t-rule">New rule</label>
          <input id="t-rule" type="text" autocomplete="off" placeholder="Type a new rule">
          <button type="submit" class="btn">Add rule</button>
        </form>
      </div>
      <div class="card stack">
        <h2>3. Example answers</h2>
        <div class="hint14">Show her how your best staff member would answer. She copies the style, not the exact words.</div>
        <div id="t-examples" class="stack"></div>
        <form class="ex dashed" id="t-ex-form">
          <div class="pair">
            <div class="field"><label for="t-q">When the customer says</label><input id="t-q" type="text" autocomplete="off" placeholder="Type what the customer says"></div>
            <div class="field"><label for="t-a">She should reply</label><input id="t-a" type="text" autocomplete="off" placeholder="Type the reply in your own words"></div>
          </div>
          <button type="submit" class="btn" style="align-self:flex-start">Add example</button>
        </form>
      </div>
      <div class="card stack">
        <div class="thead"><h2>4. Corrections from test calls</h2><span class="badge hide" id="t-badge"></span></div>
        <div class="hint14">Every answer you mark as wrong in a test call comes here. Type what she should have said and it becomes a new example.</div>
        <div id="t-corr" class="stack"></div>
      </div>
      <div class="card stack">
        <h2>5. Train by voice</h2>
        <div class="hint14">Call her and act as a customer. When she says something wrong, tell her what to do instead. She repeats what she understood, and saves it as a rule when you say yes.</div>
        <label class="check"><input id="tv-on" type="checkbox"> <b style="font-weight:600">Let her learn from my calls</b></label>
        <div class="field">
          <label for="tv-nums">Mobile numbers that can teach her, one per line</label>
          <textarea id="tv-nums" rows="2" inputmode="tel" placeholder="Your mobile number"></textarea>
          <span class="hint">Only these numbers can change her rules. Talk tests from this dashboard can teach her too.</span>
        </div>
        <div><button type="button" class="btn-save" id="tv-save">Save</button></div>
      </div>
    </div>

    <!-- Products and prices -->
    <div id="view-products" class="hide" style="display:flex;flex-direction:column;gap:20px">
      <div class="head"><div><h1>Products and prices</h1><div class="sub">She says only the products and prices on this page. Website prices are not used on calls.</div></div></div>
      <div class="card stack">
        <h2>1. Your products</h2>
        <div class="hint14">She picks from this list by the vehicle and the need the customer tells her.</div>
        <div id="pp-list" class="stack"></div>
      </div>
      <form class="card stack" id="pp-form" style="gap:14px">
        <h2>2. Add a product</h2>
        <div class="field"><label for="pp-name">Product name</label><input id="pp-name" type="text" autocomplete="off" maxlength="80" placeholder="Truck GPS tracker"></div>
        <div class="field">
          <label for="pp-price">Price she should say, in rupees</label>
          <input id="pp-price" type="text" inputmode="numeric" autocomplete="off" placeholder="5500" style="width:160px">
          <span class="hint">She says exactly this number. Nothing is added to it.</span>
        </div>
        <div class="field">
          <span class="lbl">Which vehicles it fits</span>
          <div class="picks" id="pp-fits" role="group" aria-label="Which vehicles it fits"></div>
        </div>
        <div class="field"><label for="pp-for">Best for (the purpose)</label><input id="pp-for" type="text" autocomplete="off" maxlength="200" placeholder="Commercial use, tracking drivers and trips"></div>
        <div class="field"><label for="pp-inc">What the price includes</label><input id="pp-inc" type="text" autocomplete="off" maxlength="200" placeholder="1 year subscription, 5 years warranty, fitting by our technician"></div>
        <div class="field">
          <label for="pp-feat">Features, one per line</label>
          <textarea id="pp-feat" rows="3" placeholder="Live location on mobile app&#10;Fuel and mileage report"></textarea>
          <span class="hint">English or Telugu. She says them in the customer's language.</span>
        </div>
        <div><button type="submit" class="btn-save">Save product</button></div>
      </form>
      <div class="card stack">
        <h2>3. Other charges and notes</h2>
        <div class="hint14">Anything about money that is the same for every product: renewal after the first year, fitting outside the city, payment, delivery.</div>
        <textarea id="pp-notes" rows="4" aria-label="Other charges and notes" maxlength="1500"></textarea>
        <div><button type="button" class="btn-save" id="pp-notes-save">Save</button></div>
      </div>
    </div>

    <!-- GPS servers -->
    <div id="view-gps" class="hide" style="display:flex;flex-direction:column;gap:20px">
      <div class="head"><div><h1>GPS servers</h1><div class="sub">She looks up a vehicle number here during a call. She only reads; she cannot change anything on the servers.</div></div></div>
      <form class="card stack" id="gs-form" style="gap:14px">
        <h2>1. Connect your three servers</h2>
        <div class="hint14">Paste the API key of each server. Keys are saved on your website only and are shown hidden after saving.</div>
        <div id="gs-rows" class="stack" style="gap:14px"></div>
        <div><button type="submit" class="btn-save" id="gs-save">Save and check</button></div>
      </form>
      <div class="card stack" style="gap:14px">
        <h2>2. Try a vehicle number</h2>
        <div class="hint14">Type any vehicle number to see exactly what she will find on a call.</div>
        <form class="inline" id="gs-try">
          <label class="sr" for="gs-q">Vehicle number</label>
          <input id="gs-q" type="text" autocomplete="off" autocapitalize="characters" placeholder="AP 31 TX 4521">
          <button type="submit" class="btn" id="gs-go">Search</button>
        </form>
        <div id="gs-out" class="prod hide" style="gap:6px" role="status"></div>
      </div>
      <div class="card stack" style="gap:10px">
        <h2>What she does on a renewal call</h2>
        <div class="hint14">Step one only. Nothing else changes yet.</div>
        <div class="stack" style="gap:8px">
          <div class="line caller">Renewal చేయాలి. AP 31 TX 4521.</div>
          <div class="line agent">ఓకే సర్, AP 31 TX 4521, correct ఏనా?</div>
          <div class="line caller">అవును.</div>
          <div class="line agent">ఒక్క నిమిషం సర్, check చేస్తున్నాను.</div>
          <div class="hint" style="align-self:center">She searches all three servers</div>
          <div class="line agent">మీ vehicle దొరికింది సర్. ఈ నెల ఇరవై ఎనిమిదికి expire అవుతుంది. మూడు నెలలు, ఆరు నెలలు, లేదా ఒక సంవత్సరం, ఏ plan కావాలి?</div>
        </div>
        <div class="hint14">If the number is not on any server, she says she could not find it, asks them to say it once more, and after a second miss says the team will call back.</div>
      </div>
    </div>

    <!-- Phone line -->
    <div id="view-phone" class="hide" style="display:flex;flex-direction:column;gap:20px">
      <div class="head"><div><h1>Phone line</h1><div class="sub">Connect a phone number so real calls reach the agent</div></div></div>
      <div class="card stack">
        <div class="thead"><h2>1. Connect your phone company</h2><span class="pill" id="p-status">No phone call received yet</span></div>
        <div class="field">
          <span class="lbl">Which phone company?</span>
          <div class="seg" role="group" aria-label="Phone company">
            <button type="button" id="p-vobiz" class="on" aria-pressed="true">Vobiz</button>
            <button type="button" id="p-exotel" aria-pressed="false">Exotel</button>
          </div>
        </div>
        <div class="field">
          <label for="p-url" id="p-url-label">Answer URL for your Vobiz application</label>
          <div class="inline">
            <input id="p-url" type="text" readonly style="background:var(--bg);font-size:14px">
            <button type="button" class="btn-save" id="p-copy">Copy</button>
          </div>
          <span class="hint">Keep this address private. Anyone who has it can send calls to the agent.</span>
        </div>
        <ol class="steps" id="p-steps"></ol>
        <div><button type="button" class="btn" id="p-new">Make a new address</button></div>
      </div>
      <div class="card stack">
        <h2>2. Who she answers</h2>
        <label class="opt"><input id="p-test" type="radio" name="p-mode" value="test" checked>
          <span><b>Test numbers only</b><small>Other callers hear a short message that the team will call back.</small></span></label>
        <div class="field" style="padding-left:34px">
          <label for="p-nums">Your test mobile numbers, one per line</label>
          <textarea id="p-nums" rows="2" inputmode="tel" placeholder="Your mobile number"></textarea>
        </div>
        <label class="opt"><input id="p-all" type="radio" name="p-mode" value="all">
          <span><b>Everyone who calls this number</b><small>Switch to this only when she is ready for customers.</small></span></label>
        <div><button type="button" class="btn-save" id="p-save">Save</button></div>
      </div>
    </div>

    <!-- Not built yet -->
    <div id="view-soon" class="hide" style="display:flex;flex-direction:column;gap:24px">
      <div class="head"><div><h1 id="soon-title">Coming soon</h1></div></div>
      <div class="card"><p id="soon-text" style="margin:0"></p></div>
    </div>

  </main>
</div>

<div id="toast" class="toast hide" role="status"></div>

<script>
const $ = (id) => document.getElementById(id);
const esc = (s) => String(s == null ? '' : s);

async function api(action, body) {
  const opt = { method: body ? 'POST' : 'GET', credentials: 'same-origin', headers: {} };
  let url = 'api.php?action=' + action;
  if (body && body.__get) { url += '&' + new URLSearchParams(body.__get); opt.method = 'GET'; }
  else if (body) { opt.headers['X-IVR'] = '1'; opt.body = body instanceof FormData ? body : new URLSearchParams(body); }
  // Never wait forever: give up after a minute and say so.
  const stop = new AbortController(); opt.signal = stop.signal;
  const timer = setTimeout(() => stop.abort(), 80000);
  let r;
  try { r = await fetch(url, opt); }
  catch (e) { throw new Error(e.name === 'AbortError' ? 'No answer from the server after 80 seconds. Please try again.' : 'Could not reach the server. Check the internet connection.'); }
  finally { clearTimeout(timer); }
  let d;
  try { d = await r.json(); } catch (e) { throw new Error('Server error (' + r.status + ')'); }
  if (!r.ok) { const e = new Error(d.error || 'Something went wrong'); e.code = r.status; throw e; }
  return d;
}

let toastTimer;
function toast(msg) {
  const t = $('toast'); t.textContent = msg; t.classList.remove('hide');
  clearTimeout(toastTimer); toastTimer = setTimeout(() => t.classList.add('hide'), 3500);
}

// ---------- login ----------
let needSetup = false;
async function boot() {
  const s = await api('state');
  if (s.logged_in) return showApp();
  needSetup = !s.setup;
  $('auth-title').textContent = needSetup ? 'Set up the dashboard' : 'Admin login';
  $('auth-btn').textContent = needSetup ? 'Create admin login' : 'Log in';
  $('auth-hint').classList.toggle('hide', !needSetup);
  $('a-key-wrap').classList.toggle('hide', !needSetup);
  $('a-pw').autocomplete = needSetup ? 'new-password' : 'current-password';
  $('v-auth').classList.remove('hide');
}
$('auth-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  $('auth-err').textContent = '';
  try {
    const body = { password: $('a-pw').value };
    if (needSetup) body.sarvam_key = $('a-key').value;
    await api(needSetup ? 'setup' : 'login', body);
    $('a-pw').value = '';
    $('v-auth').classList.add('hide');
    showApp();
  } catch (err) { $('auth-err').textContent = err.message; }
});
$('logout').addEventListener('click', async () => { await endCall(); await api('logout', {}); location.reload(); });

// ---------- views ----------
function show(view) {
  for (const v of ['overview', 'history', 'settings', 'training', 'products', 'phone', 'gps', 'soon']) $('view-' + v).classList.toggle('hide', v !== view);
}
document.querySelectorAll('.nav button').forEach((b) => b.addEventListener('click', () => {
  document.querySelectorAll('.nav button').forEach((x) => x.classList.remove('on'));
  b.classList.add('on');
  show(b.dataset.view);
  if (b.dataset.view === 'training') loadTraining();
  if (b.dataset.view === 'phone') loadPhone();
  if (b.dataset.view === 'products') loadProducts();
  if (b.dataset.view === 'gps') loadGps();
  if (b.dataset.view === 'soon') {
    $('soon-title').textContent = b.dataset.name;
    $('soon-text').textContent = b.dataset.name + ' is step ' + b.dataset.step + ' of the build. It is not built yet.';
  }
  if (b.dataset.scroll) $(b.dataset.scroll).scrollIntoView({ behavior: 'smooth' });
  else window.scrollTo(0, 0);
}));
document.querySelector('[data-goto="history"]').addEventListener('click', () => document.querySelector('.nav [data-view="history"]').click());
$('go-live').addEventListener('click', () => toast('Go live opens after the phone line is connected (step 3).'));
$('call-me').addEventListener('click', () => toast('Calling a phone opens after the phone line is connected (step 3).'));

// ---------- overview ----------
const mmss = (s) => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
const when = (ts) => new Date(ts * 1000).toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });

function cell(text) { const td = document.createElement('td'); td.textContent = text; return td; }
function fillTable(tbody, calls) {
  tbody.textContent = '';
  if (!calls.length) {
    const tr = document.createElement('tr'); const td = cell('No calls yet.'); td.colSpan = 6; td.className = 'empty';
    tr.appendChild(td); tbody.appendChild(tr); return;
  }
  for (const c of calls) {
    const tr = document.createElement('tr');
    tr.appendChild(cell(when(c.started)));
    tr.appendChild(cell(c.phone ? (c.from || 'Phone call') : 'Browser test'));
    tr.appendChild(cell(c.asked || 'Caller did not speak'));
    const res = document.createElement('td'); const pill = document.createElement('span');
    pill.className = 'pill' + (c.flagged ? ' bad' : ''); pill.textContent = c.flagged ? 'Wrong answer marked' : !c.phone ? 'Test call' : c.answered ? 'Phone call' : 'Not answered, call back';
    res.appendChild(pill); tr.appendChild(res);
    tr.appendChild(cell(mmss(c.seconds)));
    const rev = document.createElement('td'); const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'link'; btn.textContent = 'View';
    btn.addEventListener('click', () => openCall(c.id));
    rev.appendChild(btn); tr.appendChild(rev);
    tbody.appendChild(tr);
  }
}

function fillVoices(sel, voices, current) {
  sel.textContent = '';
  for (const v of voices) {
    const o = document.createElement('option'); o.value = v;
    const parts = v.split('_'), name = parts[0];
    o.textContent = name.charAt(0).toUpperCase() + name.slice(1) + (parts.length > 1 ? ' (' + parts[parts.length - 1] + ')' : '');
    if (v === current) o.selected = true; sel.appendChild(o);
  }
}

let keyMask = '';
$('set-key').addEventListener('focus', (e) => { if (e.target.value === keyMask) e.target.value = ''; });
$('set-key').addEventListener('blur', (e) => { if (e.target.value.trim() === '') e.target.value = keyMask; });

async function loadOverview() {
  const d = await api('overview');
  $('s-calls').textContent = d.stats.calls;
  $('s-leads').textContent = d.stats.leads;
  $('s-staff').textContent = d.stats.staff;
  $('s-avg').textContent = mmss(d.stats.avg_seconds);
  fillTable($('recent'), d.calls.slice(0, 5));
  fillTable($('all'), d.calls);
  fillVoices($('voice'), d.voices, d.voice);
  fillVoices($('set-voice'), d.voices, d.voice);
  $('lang').value = d.lang; $('set-lang').value = d.lang;
  // Show the saved key masked, so it is clear it is still stored. The full key never leaves the server.
  keyMask = d.has_key ? '••••••••••••' + d.key_hint : '';
  $('set-key').value = keyMask;
  $('key-state').textContent = d.has_key ? 'Key saved on the server, ending in ' + d.key_hint + '. Paste a new key only to replace it.' : 'No key saved yet. Test calls need one.';
}
async function showApp() {
  $('v-app').classList.remove('hide');
  try { await loadOverview(); } catch (err) { toast(err.message); }
}

async function saveVoice(v) { try { await api('save_settings', { voice: v }); $('voice').value = v; $('set-voice').value = v; } catch (err) { toast(err.message); } }
$('voice').addEventListener('change', (e) => saveVoice(e.target.value));
// Changing the language switches her speaking style, greeting and the list of voices. A call in progress keeps its language.
async function saveLang(v) {
  try { await api('save_settings', { lang: v }); await loadOverview(); toast('Language changed. Pick a voice and start a new test.'); }
  catch (err) { toast(err.message); }
}
$('lang').addEventListener('change', (e) => saveLang(e.target.value));
$('set-lang').addEventListener('change', (e) => saveLang(e.target.value));

$('set-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  $('set-err').textContent = '';
  try {
    const typed = $('set-key').value.trim();
    await api('save_settings', { voice: $('set-voice').value, sarvam_key: typed === keyMask ? '' : typed, password: $('set-pw').value });
    $('set-key').value = ''; $('set-pw').value = '';
    await loadOverview();
    toast('Saved.');
  } catch (err) { $('set-err').textContent = err.message; }
});

function addLine(box, who, text, flagged, opt) {
  const empty = box.querySelector('.empty'); if (empty) empty.remove();
  const div = document.createElement('div'); div.className = 'line ' + who + (flagged ? ' flag' : '');
  const s = document.createElement('small'); s.textContent = who === 'agent' ? 'Agent' : (opt && opt.typed ? 'Caller (typed)' : 'Caller');
  div.appendChild(s); div.appendChild(document.createTextNode(esc(text)));
  if (opt && opt.turn != null) {
    // Under each of her replies in a live test: mark it wrong, then correct it on the Training page.
    const b = document.createElement('button'); b.type = 'button'; b.className = 'mark'; b.textContent = 'Mark wrong and correct it';
    b.addEventListener('click', async () => {
      if (b.dataset.done) return document.querySelector('.nav [data-view="training"]').click();
      try {
        await api('call_flag', { call_id: flagId, turn: opt.turn });
        div.classList.add('flag'); b.dataset.done = '1'; b.textContent = 'Marked wrong. Correct it in Training';
      } catch (err) { toast(err.message); }
    });
    div.appendChild(b);
  }
  box.appendChild(div); box.scrollTop = box.scrollHeight;
  return div;
}

async function openCall(id) {
  try {
    const d = await api('call_get', { __get: { id } });
    document.querySelector('.nav [data-view="history"]').click();
    $('detail-title').textContent = 'Call on ' + when(d.call.started) + ', ' + mmss(d.call.seconds);
    $('detail-lines').textContent = '';
    for (const l of d.call.lines) addLine($('detail-lines'), l.who, l.text, l.flagged, { typed: l.typed });
    $('detail').classList.remove('hide');
    $('detail').scrollIntoView({ behavior: 'smooth' });
  } catch (err) { toast(err.message); }
}
$('detail-close').addEventListener('click', () => $('detail').classList.add('hide'));

// ---------- browser test call ----------
// One turn at a time. Talk: the agent speaks, then the microphone listens until the caller pauses.
// Type: the caller's line is typed, and her reply is shown (and spoken, if the tick box is on).
let ctx = null, stream = null, proc = null, callId = null, flagId = null, mode = 'talk', busy = false;
let phase = 'idle';            // idle | busy | speaking | listening
let pre = [], rec = [], inSpeech = false, silentMs = 0, speechMs = 0, noise = 0.005;

// While she is thinking, count the seconds so it is clear the call is alive and how long it is taking.
let thinkTimer = null;
function setPhase(p, msg) {
  phase = p; $('status').textContent = msg || '';
  clearInterval(thinkTimer); thinkTimer = null;
  if (msg === 'Thinking') {
    const t0 = Date.now();
    thinkTimer = setInterval(() => { $('status').textContent = 'Thinking, ' + Math.round((Date.now() - t0) / 1000) + ' s'; }, 1000);
  }
}

function onAudio(e) {
  if (phase !== 'listening') return;
  const buf = new Float32Array(e.inputBuffer.getChannelData(0));
  let sum = 0; for (let i = 0; i < buf.length; i++) sum += buf[i] * buf[i];
  const rms = Math.sqrt(sum / buf.length);
  const ms = buf.length / ctx.sampleRate * 1000;
  const limit = Math.max(0.015, noise * 3.5);
  if (!inSpeech) {
    pre.push(buf); if (pre.length > 4) pre.shift();
    if (rms > limit) { inSpeech = true; rec = pre.slice(); pre = []; speechMs = ms; silentMs = 0; }
    else noise = noise * 0.95 + rms * 0.05;
    return;
  }
  rec.push(buf);
  if (rms > limit) { speechMs += ms; silentMs = 0; } else silentMs += ms;
  if (silentMs >= 700 || rec.length * ms >= 25000) {
    // Drop most of the trailing silence so the upload is smaller.
    const chunks = rec.slice(0, rec.length - Math.max(0, Math.floor((silentMs - 170) / ms))), enough = speechMs >= 250;
    inSpeech = false; rec = []; pre = [];
    if (enough) sendTurn(chunks);
  }
}

function toWav(chunks, rate) {
  let len = 0; for (const c of chunks) len += c.length;
  const all = new Float32Array(len); let o = 0; for (const c of chunks) { all.set(c, o); o += c.length; }
  const ratio = rate / 16000, n = Math.floor(all.length / ratio);
  const view = new DataView(new ArrayBuffer(44 + n * 2));
  const str = (p, s) => { for (let i = 0; i < s.length; i++) view.setUint8(p + i, s.charCodeAt(i)); };
  str(0, 'RIFF'); view.setUint32(4, 36 + n * 2, true); str(8, 'WAVE'); str(12, 'fmt ');
  view.setUint32(16, 16, true); view.setUint16(20, 1, true); view.setUint16(22, 1, true);
  view.setUint32(24, 16000, true); view.setUint32(28, 32000, true); view.setUint16(32, 2, true); view.setUint16(34, 16, true);
  str(36, 'data'); view.setUint32(40, n * 2, true);
  for (let i = 0; i < n; i++) {
    const a = Math.floor(i * ratio), b = Math.max(a + 1, Math.floor((i + 1) * ratio));
    let s = 0; for (let j = a; j < b && j < all.length; j++) s += all[j];
    s = Math.max(-1, Math.min(1, s / (b - a)));
    view.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
  }
  return new Blob([view], { type: 'audio/wav' });
}

function showSpeed(startedAt, ms) {
  if (!startedAt) return;
  const sec = (n) => (n / 1000).toFixed(1);
  let t = 'Reply speed ' + sec(performance.now() - startedAt) + ' s';
  if (ms) {
    const net = Math.max(0, performance.now() - startedAt - ms.hear - ms.think - ms.speak);
    t += ' (' + (ms.hear ? 'hear ' + sec(ms.hear) + ', ' : '') + 'think ' + sec(ms.think) + ', speak ' + sec(ms.speak) + ', network ' + sec(net) + ')';
  }
  $('speed').textContent = t;
}

async function speak(b64, startedAt, ms) {
  const bin = atob(b64); const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  const audio = await ctx.decodeAudioData(bytes.buffer);
  if (!callId) return;
  const node = ctx.createBufferSource(); node.buffer = audio; node.connect(ctx.destination);
  showSpeed(startedAt, ms);
  setPhase('speaking', 'Agent is speaking');
  node.onended = () => {
    if (!callId) return;
    if (mode === 'type') setPhase('typing', '');
    else { pre = []; setPhase('listening', 'Listening. Speak now.'); }
  };
  node.start();
}

// ---------- live call ----------
// When the live call server answers, Talk uses it: audio streams both ways, she starts speaking as soon
// as her first sentence is ready, and talking over her stops her. If it does not answer, Talk falls back
// to the one-turn-at-a-time way above.
let live = null;

function pcm16(buf, rate) {
  const ratio = rate / 16000, n = Math.floor(buf.length / ratio), out = new Int16Array(n);
  for (let i = 0; i < n; i++) {
    const a = Math.floor(i * ratio), b = Math.max(a + 1, Math.floor((i + 1) * ratio));
    let s = 0; for (let j = a; j < b && j < buf.length; j++) s += buf[j];
    s = Math.max(-1, Math.min(1, s / (b - a)));
    out[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
  }
  return out.buffer;
}

function liveStopAudio() {
  for (const n of live.sources) { try { n.onended = null; n.stop(); } catch (e) {} }
  live.sources = []; live.queueEnd = 0;
}

function onLiveAudio(e) {
  if (!live || live.ws.readyState !== 1) return;
  const buf = new Float32Array(e.inputBuffer.getChannelData(0));
  let sum = 0; for (let i = 0; i < buf.length; i++) sum += buf[i] * buf[i];
  const rms = Math.sqrt(sum / buf.length), ms = buf.length / ctx.sampleRate * 1000;
  const talking = live.sources.length > 0;      // while she speaks, only a clearly louder voice interrupts her
  const limit = talking ? Math.max(0.05, noise * 8) : Math.max(0.015, noise * 3.5);
  if (!inSpeech) {
    pre.push(buf); if (pre.length > 4) pre.shift();
    if (rms > limit) live.loud++; else { live.loud = 0; if (!talking) noise = noise * 0.95 + rms * 0.05; }
    if (live.loud >= (talking ? 3 : 1)) {
      inSpeech = true; live.loud = 0; speechMs = ms; silentMs = 0; live.spokenMs = 0; live.accept = false;
      liveStopAudio();
      live.ws.send(JSON.stringify({ type: 'speech_start' }));
      for (const p of pre) live.ws.send(pcm16(p, ctx.sampleRate));
      pre = [];
      setPhase('listening', 'Listening');
    }
    return;
  }
  live.ws.send(pcm16(buf, ctx.sampleRate));
  live.spokenMs += ms;
  if (rms > limit) { speechMs += ms; silentMs = 0; } else silentMs += ms;
  if (silentMs >= 600 || live.spokenMs >= 25000) {
    inSpeech = false; pre = [];
    if (speechMs >= 250) { live.t0 = performance.now(); live.ws.send(JSON.stringify({ type: 'speech_end' })); setPhase('busy', 'Thinking'); }
    else { live.accept = true; live.ws.send(JSON.stringify({ type: 'speech_cancel' })); setPhase('listening', 'Listening. Speak now.'); }
  }
}

async function livePlay(b64) {
  const bin = atob(b64); const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  const audio = await ctx.decodeAudioData(bytes.buffer);
  if (!live || !live.accept) return;
  const node = ctx.createBufferSource(); node.buffer = audio; node.connect(ctx.destination);
  const at = Math.max(ctx.currentTime + 0.02, live.queueEnd);   // sentences play one after another without a gap
  node.start(at); live.queueEnd = at + audio.duration; live.sources.push(node);
  setPhase('speaking', 'Agent is speaking');
  node.onended = () => {
    if (!live) return;
    live.sources = live.sources.filter((x) => x !== node);
    if (!live.sources.length && !inSpeech && phase === 'speaking') setPhase('listening', 'Listening. Speak now.');
  };
}

function onLiveMessage(ev) {
  if (!live) return;
  let m; try { m = JSON.parse(ev.data); } catch (e) { return; }
  if (m.type === 'ready') {
    callId = flagId = m.call_id;
    addLine($('lines'), 'agent', m.text);
    $('talk-label').textContent = 'Live call in progress'; $('end').disabled = false;
  } else if (m.type === 'heard') {
    live.accept = true;                          // audio from here on belongs to the new reply
    addLine($('lines'), 'caller', m.text);
  } else if (m.type === 'audio') {
    if (!live.accept) return;
    if (m.ms) showSpeed(live.t0, m.ms);
    live.chain = live.chain.then(() => livePlay(m.audio)).catch(() => {});
  } else if (m.type === 'reply_done') {
    addLine($('lines'), 'agent', m.text, false, { turn: m.turn });
  } else if (m.type === 'nothing') {
    live.accept = true; setPhase('listening', 'Did not catch that. Please say it again.');
  } else if (m.type === 'error') {
    live.accept = true; $('call-err').textContent = m.message; setPhase('listening', 'Listening. Speak now.');
  }
}

// Returns true when the live server took the call, false to fall back to the turn-by-turn way.
async function startLive() {
  let t;
  try { t = await api('live_token', {}); }
  catch (err) { if (err.code === 400) throw err; return false; }
  const ws = new WebSocket(t.url + '?token=' + t.token);
  const opened = await new Promise((res) => {
    const timer = setTimeout(() => res(false), 5000);
    ws.onopen = () => { clearTimeout(timer); res(true); };
    ws.onerror = ws.onclose = () => { clearTimeout(timer); res(false); };
  });
  if (!opened) { ws.onopen = ws.onerror = ws.onclose = null; try { ws.close(); } catch (e) {} return false; }
  live = { ws, t0: 0, queueEnd: 0, sources: [], loud: 0, spokenMs: 0, accept: true, chain: Promise.resolve() };
  $('lines').textContent = ''; $('speed').textContent = '';
  ws.onmessage = onLiveMessage;
  ws.onerror = null;
  ws.onclose = () => { if (live) { if (!$('call-err').textContent && !callId) $('call-err').textContent = 'The live call server closed the call.'; endCall(); } };
  proc.onaudioprocess = onLiveAudio;
  setPhase('busy', 'Connecting');
  return true;
}

async function sendTurn(chunks) {
  const t0 = performance.now();
  setPhase('busy', 'Thinking');
  try {
    const fd = new FormData();
    fd.append('call_id', callId);
    fd.append('audio', toWav(chunks, ctx.sampleRate), 'caller.wav');
    const d = await api('call_turn', fd);
    if (!callId) return;
    if (!d.reply) return setPhase('listening', 'Did not catch that. Please say it again.');
    addLine($('lines'), 'caller', d.heard);
    addLine($('lines'), 'agent', d.reply, false, { turn: d.turn });
    await speak(d.audio, t0, d.ms);
  } catch (err) {
    $('call-err').textContent = err.message;
    if (callId) setPhase('listening', 'Listening. Speak now.');
  }
}

function setMode(m) {
  if (callId) return;
  mode = m;
  $('mode-talk').classList.toggle('on', m === 'talk'); $('mode-talk').setAttribute('aria-pressed', m === 'talk');
  $('mode-type').classList.toggle('on', m === 'type'); $('mode-type').setAttribute('aria-pressed', m === 'type');
  $('speak-wrap').classList.toggle('hide', m !== 'type');
  $('talk-icon').classList.toggle('hide', m !== 'talk');
  $('talk-label').textContent = m === 'talk' ? 'Talk to the agent here' : 'Start typing test';
  $('talk-hint').textContent = m === 'talk' ? "Uses this device's microphone. No phone number needed."
    : "No microphone needed. Type the customer's lines and read her replies.";
}
$('mode-talk').addEventListener('click', () => setMode('talk'));
$('mode-type').addEventListener('click', () => setMode('type'));

async function startCall() {
  $('call-err').textContent = '';
  const typing = mode === 'type', sound = !typing || $('speak-on').checked;
  if (!typing && (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia)) { $('call-err').textContent = 'This browser cannot use the microphone.'; return; }
  $('talk').disabled = true; $('mode-talk').disabled = true; $('mode-type').disabled = true; $('talk-label').textContent = 'Connecting';
  try {
    if (!typing) stream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, channelCount: 1 } });
    if (sound) { ctx = new (window.AudioContext || window.webkitAudioContext)(); await ctx.resume(); }
    if (!typing) {
      const src = ctx.createMediaStreamSource(stream);
      proc = ctx.createScriptProcessor(4096, 1, 1);
      const mute = ctx.createGain(); mute.gain.value = 0;
      proc.onaudioprocess = onAudio;
      src.connect(proc); proc.connect(mute); mute.connect(ctx.destination);
      setPhase('busy', 'Connecting');
      if (await startLive()) return;
    }

    setPhase('busy', 'Connecting');
    const d = await api('call_start', typing ? { mode: 'type', speak: sound ? '1' : '0' } : { mode: 'talk' });
    callId = flagId = d.call_id;
    $('lines').textContent = ''; $('speed').textContent = '';
    addLine($('lines'), 'agent', d.text);
    $('talk-label').textContent = typing ? 'Typing test in progress' : 'Call in progress';
    $('end').disabled = false;
    $('type-form').classList.toggle('hide', !typing);
    if (d.audio && ctx) await speak(d.audio); else setPhase('typing', '');
    if (typing) $('typed').focus();
  } catch (err) {
    $('call-err').textContent = err.name === 'NotAllowedError' ? 'Microphone permission was refused. Allow it and try again.' : err.message;
    await endCall();
  }
}

$('type-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = $('typed').value.trim();
  if (!callId || busy || !text) return;
  busy = true; $('send').disabled = true; $('call-err').textContent = '';
  $('typed').value = '';
  addLine($('lines'), 'caller', text, false, { typed: true });
  const t0 = performance.now();
  setPhase('busy', 'Thinking');
  try {
    const d = await api('call_text', { call_id: callId, text, speak: ctx && $('speak-on').checked ? '1' : '0' });
    if (callId) {
      addLine($('lines'), 'agent', d.reply, false, { turn: d.turn });
      if (d.audio && ctx) await speak(d.audio, t0, d.ms);
      else { showSpeed(t0, d.ms); setPhase('typing', ''); }
    }
  } catch (err) {
    $('call-err').textContent = err.message;
    if (callId) setPhase('typing', '');
  }
  busy = false; $('send').disabled = false;
  if (callId) $('typed').focus();
});

async function endCall() {
  const id = callId; callId = null;
  const wasLive = !!live;
  if (live) { const w = live.ws; liveStopAudio(); live = null; try { w.send(JSON.stringify({ type: 'end' })); w.close(); } catch (e) {} }
  if (proc) { proc.onaudioprocess = null; proc.disconnect(); proc = null; }
  if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
  if (ctx) { try { await ctx.close(); } catch (e) {} ctx = null; }
  inSpeech = false; rec = []; pre = []; busy = false;
  setPhase('idle', id ? 'Call ended.' : '');
  $('talk').disabled = false; $('mode-talk').disabled = false; $('mode-type').disabled = false; $('send').disabled = false;
  $('type-form').classList.add('hide');
  setMode(mode);
  $('end').disabled = true;
  if (id) {
    // A live call is closed and saved by the live server itself; give it a moment before refreshing the list.
    try { if (wasLive) await new Promise((r) => setTimeout(r, 700)); else await api('call_end', { call_id: id }); await loadOverview(); } catch (e) {}
  }
}

$('talk').addEventListener('click', startCall);
$('end').addEventListener('click', endCall);

// ---------- phone line ----------
// The same secret key works for both phone companies; only the address and the steps differ.
const PROVIDERS = {
  vobiz: { field: 'vobiz_url', label: 'Answer URL for your Vobiz application', steps: [
    'In Vobiz, open Applications and create a new application.',
    'Paste the address above as the Answer URL, method POST, and save.',
    "Open your number's settings, attach it to that application, and call it."] },
  exotel: { field: 'url', label: 'Address for the Exotel Voicebot applet', steps: [
    'In Exotel, open App Bazaar and create a new app.',
    'Add the Voicebot applet and paste the address above.',
    'Assign the app to your test number and call it.'] },
};
let phoneInfo = null, provider = 'vobiz';
try { if (localStorage.getItem('ivr-provider') === 'exotel') provider = 'exotel'; } catch (e) {}
function showProvider() {
  const P = PROVIDERS[provider];
  for (const k of ['vobiz', 'exotel']) { $('p-' + k).classList.toggle('on', k === provider); $('p-' + k).setAttribute('aria-pressed', k === provider); }
  $('p-url-label').textContent = P.label;
  if (phoneInfo) $('p-url').value = phoneInfo[P.field];
  $('p-steps').textContent = '';
  for (const t of P.steps) { const li = document.createElement('li'); li.textContent = t; $('p-steps').appendChild(li); }
}
for (const k of ['vobiz', 'exotel']) $('p-' + k).addEventListener('click', () => {
  provider = k;
  try { localStorage.setItem('ivr-provider', k); } catch (e) {}
  showProvider();
});

function renderPhone(p) {
  phoneInfo = p; showProvider();
  $('p-status').textContent = p.last ? 'Last phone call: ' + when(p.last) : 'No phone call received yet';
  $('p-test').checked = p.mode !== 'all'; $('p-all').checked = p.mode === 'all';
  $('p-nums').value = p.tests;
}
async function loadPhone() { try { renderPhone(await api('phone_get')); } catch (err) { toast(err.message); } }
$('p-copy').addEventListener('click', async () => {
  try { await navigator.clipboard.writeText($('p-url').value); }
  catch (e) { $('p-url').select(); document.execCommand('copy'); }
  toast('Address copied.');
});
$('p-new').addEventListener('click', async () => {
  if (!confirm('Make a new address? The old one stops working, and you must paste the new one at your phone company.')) return;
  try { renderPhone(await api('phone_newkey', {})); toast('New address made. Paste it at your phone company.'); } catch (err) { toast(err.message); }
});
$('p-save').addEventListener('click', async () => {
  try { renderPhone(await api('phone_save', { mode: $('p-all').checked ? 'all' : 'test', tests: $('p-nums').value })); toast('Saved.'); }
  catch (err) { toast(err.message); }
});

// ---------- training ----------
let tr = { greeting: '', rules: [], examples: [], corrections: [] };
function el(tag, cls, text) { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
function txtBtn(label, red, fn) { const b = el('button', 'txt' + (red ? ' red' : ''), label); b.type = 'button'; b.addEventListener('click', fn); return b; }
function pairBox(capA, a, capB, b) {
  const pair = el('div', 'pair');
  const left = el('div'); left.appendChild(el('div', 'cap', capA)); left.appendChild(el('div', 'bubble', a));
  const right = el('div'); right.appendChild(el('div', 'cap red', capB)); right.appendChild(el('div', 'bubble red', b));
  pair.appendChild(left); pair.appendChild(right);
  return pair;
}

function renderTraining() {
  $('t-greet').value = tr.greeting;
  $('t-greet-lang').textContent = tr.lang === 'hi' ? '(Hindi calls)' : '(Telugu calls)';

  const rules = $('t-rules'); rules.textContent = '';
  if (!tr.rules.length) rules.appendChild(el('div', 'empty', 'No rules yet.'));
  tr.rules.forEach((r, i) => {
    const row = el('div', 'item'); const body = el('span', ''); body.appendChild(el('div', '', r));
    const at = tr.learned && tr.learned[r];   // she learned this one on a call
    if (at) body.appendChild(el('span', 'tag', 'Learned on a call, ' + new Date(at * 1000).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })));
    row.appendChild(body);
    row.appendChild(txtBtn('Remove', true, () => { tr.rules.splice(i, 1); saveTraining('Rule removed.'); }));
    rules.appendChild(row);
  });

  $('tv-on').checked = !!tr.train_on; $('tv-nums').value = tr.train_nums || '';

  const exs = $('t-examples'); exs.textContent = '';
  if (!tr.examples.length) exs.appendChild(el('div', 'empty', 'No examples yet.'));
  tr.examples.forEach((x, i) => {
    const card = el('div', 'ex');
    card.appendChild(pairBox('When the customer says', x.q, 'She replies like this', x.a));
    const acts = el('div'); acts.style.display = 'flex'; acts.style.gap = '6px';
    acts.appendChild(txtBtn('Edit', false, () => {
      $('t-q').value = x.q; $('t-a').value = x.a; tr.examples.splice(i, 1); renderTraining();
      $('t-a').focus(); toast('Change it below and tap Add example.');
    }));
    acts.appendChild(txtBtn('Remove', true, () => { tr.examples.splice(i, 1); saveTraining('Example removed.'); }));
    card.appendChild(acts); exs.appendChild(card);
  });

  const corr = $('t-corr'); corr.textContent = '';
  const n = tr.corrections.length;
  $('t-badge').classList.toggle('hide', !n); $('t-badge').textContent = n + ' waiting for you';
  if (!n) corr.appendChild(el('div', 'empty', 'Nothing waiting. Mark a wrong reply in a test call and it comes here.'));
  tr.corrections.forEach((c) => {
    const card = el('div', 'ex');
    const q = el('div'); q.appendChild(el('div', 'cap', 'Customer said')); q.appendChild(el('div', '', c.q || '(start of the call)')); card.appendChild(q);
    const said = el('div'); said.appendChild(el('div', 'cap red', 'She said (marked wrong)')); said.appendChild(el('div', 'bubble red', c.said)); card.appendChild(said);
    const f = el('div', 'field'); const lab = el('label', '', 'What she should have said'); lab.htmlFor = 'fix-' + c.id;
    const ta = el('textarea'); ta.id = 'fix-' + c.id; ta.rows = 2; ta.placeholder = 'Type the right answer';
    f.appendChild(lab); f.appendChild(ta); card.appendChild(f);
    const acts = el('div', 'inline');
    const save = el('button', 'btn btn-outline', 'Save as example'); save.type = 'button';
    save.addEventListener('click', () => fixCorrection('correction_save', { id: c.id, answer: ta.value }, 'Saved as a new example.'));
    const ign = el('button', 'btn', 'Ignore'); ign.type = 'button';
    ign.addEventListener('click', () => fixCorrection('correction_ignore', { id: c.id }, 'Ignored.'));
    acts.appendChild(save); acts.appendChild(ign); card.appendChild(acts);
    corr.appendChild(card);
  });
}

async function loadTraining() {
  try { tr = await api('training_get'); renderTraining(); } catch (err) { toast(err.message); }
}
async function saveTraining(msg) {
  try {
    tr = await api('training_save', { greeting: $('t-greet').value, rules: JSON.stringify(tr.rules), examples: JSON.stringify(tr.examples) });
    renderTraining(); toast(msg || 'Saved. She uses it from the next call.');
  } catch (err) { toast(err.message); }
}
async function fixCorrection(action, body, msg) {
  try { const greet = $('t-greet').value; tr = await api(action, body); renderTraining(); $('t-greet').value = greet; toast(msg); }
  catch (err) { toast(err.message); }
}
$('t-save').addEventListener('click', () => saveTraining());
$('tv-save').addEventListener('click', async () => {
  try { const greet = $('t-greet').value; tr = await api('train_save', { on: $('tv-on').checked ? '1' : '0', nums: $('tv-nums').value }); renderTraining(); $('t-greet').value = greet; toast('Saved.'); }
  catch (err) { toast(err.message); }
});
// ---------- GPS servers ----------
const GS_STATE = { ok: ['Connected', 'good'], badkey: ['Key not accepted', 'bad'], down: ['Could not reach it', 'bad'], nokey: ['No key yet', ''], unknown: ['Not checked yet', ''] };
function renderGps(list) {
  const box = $('gs-rows'); box.textContent = '';
  list.forEach((sv) => {
    const f = el('div', 'field'), top = el('div', 'thead'); top.style.flexWrap = 'wrap';
    const lab = el('label', '', 'Server ' + sv.id + ': ' + sv.host); lab.htmlFor = 'gs-' + sv.id;
    const st = GS_STATE[sv.state] || GS_STATE.unknown;
    top.appendChild(lab); top.appendChild(el('span', 'pill' + (st[1] ? ' ' + st[1] : ''), st[0]));
    const inp = el('input'); inp.id = 'gs-' + sv.id; inp.type = 'text'; inp.autocomplete = 'off'; inp.spellcheck = false;
    inp.placeholder = 'Paste the API key'; inp.value = sv.has_key ? '••••••••••••••••••••' + sv.hint : '';
    inp.addEventListener('focus', () => { if (inp.value.includes('•')) inp.select(); });
    f.appendChild(top); f.appendChild(inp); box.appendChild(f);
  });
}
async function loadGps() {
  try { renderGps((await api('gps_get')).servers); } catch (err) { toast(err.message); }
}
$('gs-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const b = $('gs-save'); b.disabled = true; b.textContent = 'Checking the servers';
  try {
    const d = await api('gps_save', { k1: $('gs-1').value, k2: $('gs-2').value, k3: $('gs-3').value });
    renderGps(d.servers);
    const ok = d.servers.filter((x) => x.state === 'ok').length;
    toast(ok === d.servers.length ? 'All three servers are connected.' : ok + ' of ' + d.servers.length + ' servers connected. Check the ones marked in red.');
  } catch (err) { toast(err.message); }
  b.disabled = false; b.textContent = 'Save and check';
});
$('gs-try').addEventListener('submit', async (e) => {
  e.preventDefault();
  const q = $('gs-q').value.trim(); if (!q) return;
  const b = $('gs-go'), out = $('gs-out'); b.disabled = true; b.textContent = 'Searching';
  try {
    const r = await api('gps_find', { __get: { q } });
    out.textContent = ''; out.classList.remove('hide');
    const head = (t) => { const d = el('div', '', t); d.style.fontWeight = '700'; d.style.fontSize = '16px'; out.appendChild(d); };
    if (r.status === 'found') {
      head(r.name || r.number);
      out.appendChild(ppLine('Found on:', 'Server ' + r.server + ', ' + r.host));
      const d = r.days, left = d > 1 ? d + ' days left' : d === 1 ? 'expires tomorrow' : d === 0 ? 'expires today' : 'expired ' + (-d) + (d === -1 ? ' day ago' : ' days ago');
      out.appendChild(ppLine('Expiry date:', r.expiry ? r.expiry_text + ', ' + left : 'Not recorded on the server'));
      out.appendChild(ppLine('Last signal:', r.last || 'Not known'));
    } else if (r.status === 'many') { head(r.number); out.appendChild(ppLine('', r.count + ' vehicles match this. Type the full vehicle number.'));
    } else if (r.status === 'off') { head('No server connected yet'); out.appendChild(ppLine('', 'Paste the API keys above and tap Save and check.'));
    } else if (r.status === 'down') { head(r.number); out.appendChild(ppLine('', 'Not found, but one of the servers could not be checked just now. Tap Save and check above to see which.'));
    } else { head(r.number || q); out.appendChild(ppLine('', 'Not found on any connected server.')); }
  } catch (err) { toast(err.message); }
  b.disabled = false; b.textContent = 'Search';
});

// ---------- products and prices ----------
let pp = { products: [], notes: '', fits: [] }, ppEdit = '', ppFits = [];
const rupees = (n) => '₹' + Number(n).toLocaleString('en-IN');
function ppLine(label, text) { const d = el('div', 'l'); d.appendChild(el('i', '', label + ' ')); d.appendChild(document.createTextNode(text)); return d; }
function renderPicks() {
  const box = $('pp-fits'); box.textContent = '';
  pp.fits.forEach((f) => {
    const on = ppFits.includes(f), b = el('button', 'pick' + (on ? ' on' : ''), f + (on ? ' ✓' : ''));
    b.type = 'button'; b.setAttribute('aria-pressed', on ? 'true' : 'false');
    b.addEventListener('click', () => { ppFits = on ? ppFits.filter((x) => x !== f) : ppFits.concat(f); renderPicks(); });
    box.appendChild(b);
  });
}
function renderProducts() {
  const list = $('pp-list'); list.textContent = '';
  if (!pp.products.length) list.appendChild(el('div', 'empty', 'No products yet. Until you add one, she says no price and offers to send the details on WhatsApp.'));
  pp.products.forEach((p) => {
    const card = el('div', 'prod');
    const top = el('div', 'top'); top.appendChild(el('span', '', p.name)); top.appendChild(el('div', '', rupees(p.price))); card.appendChild(top);
    if (p.fits && p.fits.length) { const c = el('div', 'chips'); p.fits.forEach((f) => c.appendChild(el('span', 'tag', f))); card.appendChild(c); }
    if (p.for) card.appendChild(ppLine('Best for:', p.for));
    if (p.inc) card.appendChild(ppLine('Price includes:', p.inc));
    if (p.feat && p.feat.length) card.appendChild(ppLine('Features:', p.feat.map((x) => x.replace(/\.$/, '')).join('. ') + '.'));
    const acts = el('div', 'acts');
    acts.appendChild(txtBtn('Edit', false, () => {
      ppEdit = p.id; $('pp-name').value = p.name; $('pp-price').value = p.price; $('pp-for').value = p.for || ''; $('pp-inc').value = p.inc || '';
      $('pp-feat').value = (p.feat || []).join('\n'); ppFits = (p.fits || []).slice(); renderPicks();
      $('pp-form').scrollIntoView({ behavior: 'smooth' }); toast('Change it below and tap Save product.');
    }));
    acts.lastChild.style.textDecoration = 'none';
    acts.appendChild(txtBtn('Remove', true, async () => {
      try { pp = await api('product_remove', { id: p.id }); if (ppEdit === p.id) ppEdit = ''; renderProducts(); toast('Product removed.'); } catch (err) { toast(err.message); }
    }));
    card.appendChild(acts); list.appendChild(card);
  });
}
async function loadProducts() {
  try {
    pp = await api('products_get'); ppEdit = ''; ppFits = []; renderProducts(); renderPicks(); $('pp-notes').value = pp.notes;
    if (pp.imported > 0) toast(pp.imported + ' products added from the website. Check each price, then edit or remove.');
    if (pp.imported < 0) toast('Could not read the website products just now. Open this page again in a minute.');
  } catch (err) { toast(err.message); }
}
$('pp-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  try {
    pp = await api('product_save', { id: ppEdit, name: $('pp-name').value, price: $('pp-price').value, fits: JSON.stringify(ppFits),
      for: $('pp-for').value, inc: $('pp-inc').value, feat: $('pp-feat').value });
    ppEdit = ''; ppFits = []; $('pp-form').reset(); renderProducts(); renderPicks(); toast('Saved. She uses it from the next call.');
  } catch (err) { toast(err.message); }
});
$('pp-notes-save').addEventListener('click', async () => {
  try { pp = await api('product_notes', { notes: $('pp-notes').value }); renderProducts(); $('pp-notes').value = pp.notes; toast('Saved. She uses it from the next call.'); } catch (err) { toast(err.message); }
});

$('t-rule-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const v = $('t-rule').value.trim(); if (!v) return;
  tr.rules.push(v); $('t-rule').value = ''; saveTraining('Rule added.');
});
$('t-ex-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const q = $('t-q').value.trim(), a = $('t-a').value.trim();
  if (!q || !a) return toast('Fill both boxes.');
  tr.examples.push({ q, a }); $('t-q').value = ''; $('t-a').value = ''; saveTraining('Example added.');
});

boot().catch((err) => { document.body.textContent = err.message; });
</script>
</body>
</html>
