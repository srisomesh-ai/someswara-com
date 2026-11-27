<?php
require __DIR__ . '/lib.php';
$set = settings();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$org = $h($set['org_name']);
$years = max(3, min(5, (int)$set['track_years']));
$open = is_setup() && !empty($set['public_form']);
$initial = $h(mb_strtoupper(mb_substr(trim($set['org_name']), 0, 1)) ?: 'S');

// The motto as a headline: if it has two sentences, the second one gets the highlight.
$motto = trim((string)$set['motto']);
$parts = preg_split('/(?<=[.!?])\s+/', $motto, 2);
$mottoHtml = count($parts) === 2 ? $h($parts[0]) . ' <span class="hl">' . $h($parts[1]) . '</span>' : '<span class="hl">' . $h($motto) . '</span>';

// Score weights from Settings, shown as percentages.
$wsum = max(1, $set['w_marks'] + $set['w_attendance'] + $set['w_skills']);
$w = ['Marks' => round($set['w_marks'] * 100 / $wsum), 'Attendance' => round($set['w_attendance'] * 100 / $wsum), 'Skills' => round($set['w_skills'] * 100 / $wsum)];
$w = array_filter($w);

// Example growth line for the chart, one point per year.
$pts = array_slice([62, 69, 76, 82, 89], 0, $years);
$season = (int)date('n') >= 6 ? date('Y') . '–' . substr((string)(date('Y') + 1), 2) : (date('Y') - 1) . '–' . date('y');
header('X-Frame-Options: DENY');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $org ?> | <?= $h($motto) ?></title>
<meta name="description" content="<?= $h($set['goal']) ?>">
<meta property="og:title" content="<?= $org ?> | <?= $h($motto) ?>">
<meta property="og:description" content="<?= $h($set['goal']) ?>">
<meta name="theme-color" content="#070B1A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<style>
:root{
  --ink:#070B1A;--ink2:#0D1430;--navy:#111C44;--blue:#2E5BFF;--blue2:#5B8CFF;--sky:#9DB8FF;
  --gold:#F5B841;--gold2:#FFD27A;--paper:#FAFAF7;--white:#fff;--line:#E7E6E0;--text:#10131C;--muted:#5D6371;
  --r:22px;--display:"Plus Jakarta Sans",Inter,system-ui,sans-serif
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{margin:0;background:var(--paper);color:var(--text);font:16px/1.65 Inter,system-ui,sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:inherit}
img,svg{display:block}
.wrap{max-width:1180px;margin:0 auto;padding:0 24px}
h1,h2,h3{font-family:var(--display);margin:0;letter-spacing:-.025em}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:54px;padding:0 28px;border-radius:999px;font-weight:700;text-decoration:none;font-size:16px;transition:transform .2s,box-shadow .2s,background .2s;white-space:nowrap}
.btn:hover{transform:translateY(-2px)}
.btn-gold{background:linear-gradient(135deg,var(--gold2),var(--gold));color:var(--ink);box-shadow:0 10px 30px -8px rgba(245,184,65,.6)}
.btn-gold:hover{box-shadow:0 16px 40px -8px rgba(245,184,65,.75)}
.btn-ghost{color:#fff;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.04)}
.btn-ghost:hover{background:rgba(255,255,255,.1)}
.btn svg{transition:transform .2s}
.btn:hover svg{transform:translateX(3px)}
.eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:13px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--blue)}
.eyebrow:before{content:"";width:22px;height:2px;background:currentColor;border-radius:2px}
.h2{font-size:clamp(32px,4.6vw,52px);line-height:1.08;margin:16px 0 18px;color:var(--ink)}
.lede{font-size:clamp(17px,1.6vw,19px);color:var(--muted);max-width:640px;margin:0}

/* ---------- nav ---------- */
.nav{position:fixed;inset:0 0 auto;z-index:50;transition:background .3s,box-shadow .3s,backdrop-filter .3s}
.nav.solid{background:rgba(7,11,26,.78);backdrop-filter:saturate(1.6) blur(14px);-webkit-backdrop-filter:saturate(1.6) blur(14px);box-shadow:0 1px 0 rgba(255,255,255,.06)}
.nav .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;height:72px}
.logo{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none;font-family:var(--display);font-weight:700;font-size:17px;min-width:0}
.logo i{flex:none;width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--blue2),var(--blue));display:flex;align-items:center;justify-content:center;font-style:normal;font-weight:800;box-shadow:inset 0 1px 0 rgba(255,255,255,.3)}
.logo span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.links{display:flex;align-items:center;gap:30px}
.links a{color:rgba(255,255,255,.72);text-decoration:none;font-size:15px;font-weight:500;transition:color .2s}
.links a:hover{color:#fff}
.nav .btn{min-height:42px;padding:0 20px;font-size:14px}

/* ---------- hero ---------- */
.hero{position:relative;background:var(--ink);color:#fff;padding:150px 0 120px;overflow:hidden;isolation:isolate}
.hero:before{content:"";position:absolute;inset:0;z-index:-2;background:
  radial-gradient(900px 520px at 78% 18%,rgba(46,91,255,.42),transparent 60%),
  radial-gradient(700px 480px at 8% 92%,rgba(245,184,65,.13),transparent 60%),
  linear-gradient(180deg,#070B1A 0%,#0A1130 100%)}
.hero:after{content:"";position:absolute;inset:0;z-index:-1;opacity:.35;
  background-image:linear-gradient(rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px);
  background-size:56px 56px;mask-image:radial-gradient(ellipse 70% 60% at 50% 40%,#000 30%,transparent 80%);-webkit-mask-image:radial-gradient(ellipse 70% 60% at 50% 40%,#000 30%,transparent 80%)}
.hero .wrap{display:flex;flex-wrap:wrap;align-items:center;gap:64px}
.hero-copy{flex:1 1 520px;min-width:0}
.badge{display:inline-flex;align-items:center;gap:10px;padding:7px 16px 7px 8px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);font-size:14px;font-weight:500;color:rgba(255,255,255,.88);margin-bottom:28px}
.badge b{background:var(--gold);color:var(--ink);border-radius:999px;padding:2px 10px;font-size:12px;letter-spacing:.04em}
.hero h1{font-size:clamp(44px,6.6vw,84px);line-height:1.02;font-weight:800;letter-spacing:-.04em;margin-bottom:26px}
.hl{background:linear-gradient(100deg,var(--gold2) 0%,var(--gold) 45%,#FF9F6B 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero p.lede{color:rgba(255,255,255,.74);margin-bottom:38px;max-width:580px}
.cta{display:flex;flex-wrap:wrap;gap:14px}
.assure{display:flex;flex-wrap:wrap;gap:10px 24px;margin-top:34px;font-size:14px;color:rgba(255,255,255,.62)}
.assure span{display:inline-flex;align-items:center;gap:8px}
.assure svg{color:var(--gold)}

/* profile card */
.hero-art{flex:1 1 380px;display:flex;justify-content:center;position:relative;min-width:0}
.pcard{width:100%;max-width:410px;background:linear-gradient(160deg,rgba(255,255,255,.12),rgba(255,255,255,.04));border:1px solid rgba(255,255,255,.14);border-radius:28px;padding:26px;backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);box-shadow:0 40px 80px -30px rgba(0,0,0,.7),inset 0 1px 0 rgba(255,255,255,.15);animation:float 7s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.ptop{display:flex;align-items:center;gap:14px}
.avatar{width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,var(--gold2),#FF9F6B);display:flex;align-items:center;justify-content:center;font-family:var(--display);font-weight:800;color:var(--ink);font-size:18px;flex:none}
.ptop b{display:block;font-size:17px;font-family:var(--display)}
.ptop small{color:rgba(255,255,255,.6);font-size:13px}
.ptag{margin-left:auto;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--sky);background:rgba(91,140,255,.16);padding:5px 10px;border-radius:999px;white-space:nowrap}
.pmid{display:flex;align-items:center;gap:22px;margin:24px 0 18px}
.ring{position:relative;width:112px;height:112px;flex:none}
.ring svg{width:112px;height:112px;transform:rotate(-90deg)}
.ring .val{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:var(--display)}
.ring .val b{font-size:32px;line-height:1}
.ring .val small{font-size:11px;color:rgba(255,255,255,.6);letter-spacing:.08em;text-transform:uppercase;margin-top:4px}
.bars{flex:1;display:flex;flex-direction:column;gap:11px;min-width:0}
.bar{font-size:13px;color:rgba(255,255,255,.7)}
.bar div{display:flex;justify-content:space-between}
.bar i{display:block;height:6px;border-radius:6px;background:rgba(255,255,255,.1);margin-top:5px;overflow:hidden}
.bar i:after{content:"";display:block;height:100%;width:var(--v);border-radius:6px;background:linear-gradient(90deg,var(--blue2),var(--sky));transform-origin:left;animation:grow 1.6s .4s cubic-bezier(.2,.8,.2,1) both}
@keyframes grow{from{transform:scaleX(0)}}
.spark{border-top:1px solid rgba(255,255,255,.1);padding-top:16px}
.spark .row{display:flex;justify-content:space-between;align-items:baseline;font-size:13px;color:rgba(255,255,255,.6)}
.spark .row b{color:#7BE0A6;font-size:14px}
.spark svg{width:100%;height:58px;margin-top:6px}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.chip{font-size:12px;font-weight:600;padding:6px 11px;border-radius:999px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)}
.chip.g{background:rgba(245,184,65,.16);border-color:rgba(245,184,65,.35);color:var(--gold2)}
.floaty{position:absolute;background:#fff;color:var(--ink);border-radius:16px;padding:12px 16px;box-shadow:0 20px 40px -12px rgba(0,0,0,.5);font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;animation:float 6s ease-in-out infinite}
.floaty i{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-style:normal;font-size:16px}
.f1{top:-46px;right:-14px;animation-delay:-2s}
.f1 i{background:#E5F7EC}
.f2{bottom:-38px;left:-22px;animation-delay:-4s}
.f2 i{background:#FFF3DA}
.example{position:absolute;bottom:-84px;left:0;right:0;text-align:center;font-size:12px;color:rgba(255,255,255,.42)}

/* ---------- pillars strip ---------- */
.strip{background:var(--white);border-bottom:1px solid var(--line);position:relative;z-index:2}
.strip .wrap{display:grid;grid-template-columns:repeat(4,1fr)}
.pillar{padding:34px 26px;border-left:1px solid var(--line)}
.pillar:first-child{border-left:none}
.pillar b{display:block;font-family:var(--display);font-size:clamp(30px,3.4vw,40px);font-weight:800;color:var(--ink);letter-spacing:-.03em;line-height:1.1}
.pillar b em{font-style:normal;color:var(--blue)}
.pillar span{font-size:14px;color:var(--muted);font-weight:500}

/* ---------- sections ---------- */
section{padding:120px 0;position:relative}
.center{text-align:center}
.center .lede{margin:0 auto}

/* goal */
.goal .wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:72px;align-items:center}
.manifesto{font-family:var(--display);font-size:clamp(24px,2.6vw,32px);line-height:1.35;font-weight:700;color:var(--ink);letter-spacing:-.02em;margin:0}
.manifesto em{font-style:normal;color:var(--blue)}
.goalpts{display:grid;gap:18px;margin-top:34px}
.gp{display:flex;gap:16px;align-items:flex-start}
.gp i{flex:none;width:44px;height:44px;border-radius:13px;background:#EDF1FF;display:flex;align-items:center;justify-content:center;color:var(--blue)}
.gp b{display:block;color:var(--ink);font-size:16px}
.gp span{color:var(--muted);font-size:15px}
.promise{position:relative;border-radius:var(--r);padding:48px 40px;background:linear-gradient(150deg,var(--navy),var(--ink));color:#fff;overflow:hidden}
.promise:before{content:"";position:absolute;width:420px;height:420px;right:-160px;top:-160px;background:radial-gradient(circle,rgba(46,91,255,.55),transparent 65%)}
.promise .q{position:relative;font-family:var(--display);font-size:110px;line-height:.6;color:var(--gold);height:46px}
.promise p{position:relative;font-family:var(--display);font-size:clamp(28px,3.2vw,38px);line-height:1.15;font-weight:800;margin:0 0 22px;letter-spacing:-.03em}
.promise small{position:relative;display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.65);font-size:14px}
.promise small:before{content:"";width:28px;height:2px;background:var(--gold)}

/* journey */
.journey{background:var(--white);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.steps{display:grid;grid-template-columns:repeat(5,1fr);gap:18px;margin-top:64px;position:relative}
.steps:before{content:"";position:absolute;top:31px;left:10%;right:10%;height:2px;background:linear-gradient(90deg,var(--blue),var(--sky),var(--gold))}
.step{position:relative;text-align:center;padding:0 6px}
.num{position:relative;width:64px;height:64px;margin:0 auto 22px;border-radius:50%;background:var(--white);border:2px solid var(--blue);display:flex;align-items:center;justify-content:center;font-family:var(--display);font-weight:800;font-size:20px;color:var(--blue);box-shadow:0 0 0 8px var(--white)}
.step:last-child .num{background:linear-gradient(135deg,var(--gold2),var(--gold));border-color:var(--gold);color:var(--ink)}
.when{font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--blue)}
.step h3{font-size:22px;margin:6px 0 8px;color:var(--ink)}
.step p{margin:0;font-size:15px;color:var(--muted)}

/* future */
.future{background:var(--ink);color:#fff;overflow:hidden;isolation:isolate}
.future:before{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(800px 500px at 15% 0%,rgba(46,91,255,.35),transparent 60%),radial-gradient(600px 400px at 100% 100%,rgba(245,184,65,.12),transparent 60%)}
.future .eyebrow{color:var(--gold)}
.future .h2{color:#fff}
.future .lede{color:rgba(255,255,255,.68)}
.fgrid{display:grid;grid-template-columns:1.6fr 1fr;gap:24px;margin-top:56px}
.glass{background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.1);border-radius:var(--r);padding:30px}
.glass h3{font-size:19px;margin-bottom:4px}
.glass .sub{font-size:14px;color:rgba(255,255,255,.55);margin-bottom:18px}
.chart{width:100%;height:auto}
.chart .line{stroke-dasharray:1200;stroke-dashoffset:1200;transition:stroke-dashoffset 2.2s cubic-bezier(.4,.1,.2,1)}
.chart .pt,.chart .lbl{opacity:0;transition:opacity .5s}
.in .chart .line{stroke-dashoffset:0}
.in .chart .pt,.in .chart .lbl{opacity:1}
.legend{display:flex;flex-wrap:wrap;gap:20px;margin-top:14px;font-size:13px;color:rgba(255,255,255,.6)}
.legend i{display:inline-block;width:16px;height:3px;border-radius:2px;vertical-align:middle;margin-right:8px}
.weights{display:flex;flex-direction:column;gap:18px;margin-top:8px}
.wt div{display:flex;justify-content:space-between;font-weight:600;font-size:15px}
.wt div span{color:var(--gold2);font-family:var(--display)}
.wt i{display:block;height:8px;border-radius:8px;background:rgba(255,255,255,.08);margin-top:8px;overflow:hidden}
.wt i:after{content:"";display:block;height:100%;width:var(--v);background:linear-gradient(90deg,var(--gold),var(--gold2));border-radius:8px;transform:scaleX(0);transform-origin:left;transition:transform 1.4s .3s cubic-bezier(.2,.8,.2,1)}
.in .wt i:after{transform:scaleX(1)}
.note{margin-top:22px;padding-top:20px;border-top:1px solid rgba(255,255,255,.1);font-size:14px;color:rgba(255,255,255,.6)}
.note b{color:#fff}

/* follow */
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:56px}
.card{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:30px 26px;transition:transform .3s,box-shadow .3s,border-color .3s}
.card:hover{transform:translateY(-6px);box-shadow:0 28px 50px -24px rgba(17,28,68,.28);border-color:#D5DCF5}
.ic{width:54px;height:54px;border-radius:16px;background:linear-gradient(135deg,#EDF1FF,#DCE5FF);display:flex;align-items:center;justify-content:center;color:var(--blue);margin-bottom:22px}
.card h3{font-size:20px;margin-bottom:8px;color:var(--ink)}
.card p{margin:0;color:var(--muted);font-size:15px}

/* opportunities */
.opps{background:var(--white);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.ogrid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-top:52px}
.opp{border-radius:20px;padding:26px 22px;background:var(--paper);border:1px solid var(--line);transition:background .3s,color .3s,transform .3s}
.opp:hover{background:var(--ink);color:#fff;transform:translateY(-4px)}
.opp:hover p{color:rgba(255,255,255,.65)}
.opp .e{font-size:30px;line-height:1;margin-bottom:16px}
.opp h3{font-size:18px;margin-bottom:6px}
.opp p{margin:0;font-size:14px;color:var(--muted);transition:color .3s}

/* audiences */
.aud{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:56px}
.apanel{border-radius:var(--r);padding:40px;position:relative;overflow:hidden}
.apanel.s{background:linear-gradient(150deg,#2E5BFF,#1838B5);color:#fff}
.apanel.p{background:var(--white);border:1px solid var(--line)}
.apanel .tag{display:inline-block;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:6px 12px;border-radius:999px;margin-bottom:18px}
.apanel.s .tag{background:rgba(255,255,255,.16)}
.apanel.p .tag{background:#FFF3DA;color:#8A5A00}
.apanel h3{font-size:clamp(24px,2.4vw,30px);margin-bottom:22px}
.apanel.p h3{color:var(--ink)}
.apanel ul{list-style:none;margin:0;padding:0;display:grid;gap:14px}
.apanel li{display:flex;gap:12px;align-items:flex-start;font-size:16px}
.apanel li svg{flex:none;margin-top:3px}
.apanel.s li svg{color:var(--gold2)}
.apanel.p li svg{color:var(--blue)}

/* trust */
.trust .wrap{display:grid;grid-template-columns:.9fr 1.1fr;gap:64px;align-items:start}
.tlist{display:grid;gap:16px}
.titem{display:flex;gap:18px;background:var(--white);border:1px solid var(--line);border-radius:18px;padding:22px 24px}
.titem i{flex:none;width:46px;height:46px;border-radius:14px;background:#E8F7EE;color:#14874A;display:flex;align-items:center;justify-content:center}
.titem b{display:block;color:var(--ink);font-family:var(--display);font-size:17px;margin-bottom:2px}
.titem span{color:var(--muted);font-size:15px}

/* faq */
.faq{background:var(--white);border-top:1px solid var(--line)}
.qs{max-width:820px;margin:48px auto 0}
details{border-bottom:1px solid var(--line)}
summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:20px;padding:24px 4px;font-family:var(--display);font-weight:700;font-size:clamp(17px,1.8vw,20px);color:var(--ink)}
summary::-webkit-details-marker{display:none}
summary:after{content:"+";flex:none;width:36px;height:36px;border-radius:50%;background:var(--paper);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:500;color:var(--blue);transition:transform .3s}
details[open] summary:after{transform:rotate(45deg)}
details p{margin:0 4px 24px;color:var(--muted);font-size:16px;max-width:720px}

/* final */
.final{padding:0 0 120px;background:var(--white)}
.finalbox{position:relative;border-radius:32px;padding:clamp(56px,8vw,96px) 32px;text-align:center;color:#fff;overflow:hidden;background:var(--ink);isolation:isolate}
.finalbox:before{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(600px 360px at 50% 0%,rgba(46,91,255,.6),transparent 70%),radial-gradient(500px 300px at 50% 120%,rgba(245,184,65,.3),transparent 70%)}
.finalbox h2{font-size:clamp(34px,5vw,60px);line-height:1.05;max-width:760px;margin:0 auto 20px}
.finalbox p{color:rgba(255,255,255,.72);font-size:18px;max-width:560px;margin:0 auto 36px}
footer{background:var(--ink);color:rgba(255,255,255,.55);padding:40px 0;font-size:14px}
footer .wrap{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px}
footer .logo{font-size:15px}
footer .logo i{width:30px;height:30px;border-radius:9px;font-size:14px}
footer nav{display:flex;flex-wrap:wrap;gap:22px}
footer a{text-decoration:none}
footer a:hover{color:#fff}

/* reveal */
.rv{opacity:0;transform:translateY(28px);transition:opacity .9s cubic-bezier(.2,.7,.2,1),transform .9s cubic-bezier(.2,.7,.2,1)}
.rv.in{opacity:1;transform:none}
.d1{transition-delay:.08s}.d2{transition-delay:.16s}.d3{transition-delay:.24s}.d4{transition-delay:.32s}
@media (prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important}.rv{opacity:1;transform:none}.chart .line{stroke-dashoffset:0}.chart .pt,.chart .lbl{opacity:1}.wt i:after{transform:none}}

/* responsive */
@media (max-width:1020px){
  .links{display:none}
  .goal .wrap,.trust .wrap{grid-template-columns:1fr;gap:48px}
  .fgrid{grid-template-columns:1fr}
  .cards{grid-template-columns:repeat(2,1fr)}
  .ogrid{grid-template-columns:repeat(3,1fr)}
  .steps{grid-template-columns:1fr;gap:0;max-width:560px}
  .steps:before{display:none}
  .step:not(:last-child):after{content:"";position:absolute;left:31px;top:64px;bottom:0;width:2px;background:linear-gradient(180deg,var(--blue),var(--sky))}
  .step{display:grid;grid-template-columns:64px 1fr;column-gap:22px;text-align:left;padding:0 0 34px}
  .step:last-child{padding-bottom:0}
  .num{margin:0;grid-row:span 3}
  .step h3{margin-top:2px}
}
@media (max-width:760px){
  .wrap{padding:0 18px}
  section{padding:84px 0}
  .hero{padding:120px 0 110px}
  .hero .wrap{gap:76px}
  .strip .wrap{grid-template-columns:1fr 1fr}
  .pillar{padding:24px 18px;border-left:none;border-top:1px solid var(--line)}
  .pillar:nth-child(-n+2){border-top:none}
  .pillar:nth-child(even){border-left:1px solid var(--line)}
  .aud{grid-template-columns:1fr}
  .apanel{padding:30px 24px}
  .cards{grid-template-columns:1fr}
  .ogrid{grid-template-columns:1fr 1fr}
  .promise{padding:38px 26px}
  .glass{padding:22px 18px}
  .f1{right:-4px}
  .f2{left:-4px}
  .hero{padding-bottom:140px}
  .nav .btn{min-height:38px;padding:0 14px;font-size:13px}
  .chart .cv{font-size:28px}
  .chart .cx{font-size:22px}
  .final{padding-bottom:84px}
  .cta .btn{flex:1 1 220px}
}
</style>
</head>
<body>

<header class="nav" id="nav">
  <div class="wrap">
    <a class="logo" href="#top"><i><?= $initial ?></i><span><?= $org ?></span></a>
    <nav class="links">
      <a href="#goal">Our goal</a><a href="#journey">Journey</a><a href="#future">Your future</a><a href="#faq">FAQ</a>
    </nav>
    <?php if ($open): ?><a class="btn btn-gold" href="register.php">Register now</a><?php endif; ?>
  </div>
</header>

<!-- HERO -->
<div class="hero" id="top">
  <div class="wrap">
    <div class="hero-copy">
      <?php if ($open): ?><div class="badge rv"><b>OPEN</b> Registrations for <?= $season ?></div><?php endif; ?>
      <h1 class="rv d1"><?= $mottoHtml ?></h1>
      <p class="lede rv d2"><?= $h($set['goal']) ?></p>
      <div class="cta rv d3">
        <?php if ($open): ?><a class="btn btn-gold" href="register.php">Start your journey <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a><?php endif; ?>
        <a class="btn btn-ghost" href="#journey">See how it works</a>
      </div>
      <div class="assure rv d4">
        <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Takes 2 minutes</span>
        <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg><?= $years ?> years of guidance</span>
        <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Your data stays private</span>
      </div>
    </div>

    <div class="hero-art rv d2" aria-hidden="true">
      <div class="pcard">
        <div class="ptop">
          <div class="avatar">YOU</div>
          <div><b>Your growth profile</b><small>Year <?= $years ?> · Final review</small></div>
          <span class="ptag">Top 10%</span>
        </div>
        <div class="pmid">
          <div class="ring">
            <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="10"/><circle id="ringArc" cx="60" cy="60" r="50" fill="none" stroke="url(#rg)" stroke-width="10" stroke-linecap="round" stroke-dasharray="314.16" stroke-dashoffset="314.16"/><defs><linearGradient id="rg" x1="0" x2="1"><stop offset="0" stop-color="#FFD27A"/><stop offset="1" stop-color="#F5B841"/></linearGradient></defs></svg>
            <div class="val"><b id="ringNum">0</b><small>Score</small></div>
          </div>
          <div class="bars">
            <div class="bar"><div><span>Marks</span><span>91%</span></div><i style="--v:91%"></i></div>
            <div class="bar"><div><span>Attendance</span><span>96%</span></div><i style="--v:96%"></i></div>
            <div class="bar"><div><span>Skills</span><span>8.5/10</span></div><i style="--v:85%"></i></div>
          </div>
        </div>
        <div class="spark">
          <div class="row"><span>Growth since Year 1</span><b>▲ +<?= end($pts) - $pts[0] ?> pts</b></div>
          <svg viewBox="0 0 300 58" preserveAspectRatio="none"><defs><linearGradient id="sg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#5B8CFF" stop-opacity=".45"/><stop offset="1" stop-color="#5B8CFF" stop-opacity="0"/></linearGradient></defs><path d="M0 50 C40 46 60 40 90 38 S150 30 180 24 S250 12 300 6 L300 58 L0 58Z" fill="url(#sg)"/><path d="M0 50 C40 46 60 40 90 38 S150 30 180 24 S250 12 300 6" fill="none" stroke="#9DB8FF" stroke-width="2.5"/></svg>
        </div>
        <div class="chips"><span class="chip g">★ Scholarship ready</span><span class="chip">State-level quiz</span><span class="chip">Leadership</span></div>
      </div>
      <div class="floaty f1"><i>📈</i>Consistent growth</div>
      <div class="floaty f2"><i>🎓</i>Shortlisted for opportunity</div>
      <div class="example">Illustration of a growth profile</div>
    </div>
  </div>
</div>

<!-- PILLARS -->
<div class="strip">
  <div class="wrap">
    <div class="pillar rv"><b><em><?= $years ?></em> years</b><span>of guided growth</span></div>
    <div class="pillar rv d1"><b><em>4</em> areas</b><span>followed every term</span></div>
    <div class="pillar rv d2"><b><em>1</em> profile</b><span>that tells your whole story</span></div>
    <div class="pillar rv d3"><b><em>100%</em> consent</b><span>no data kept without it</span></div>
  </div>
</div>

<!-- GOAL -->
<section class="goal" id="goal">
  <div class="wrap">
    <div>
      <span class="eyebrow rv">Our goal</span>
      <h2 class="h2 rv d1">Every talent seen.<br>Every student guided.</h2>
      <p class="manifesto rv d2">One exam never tells the whole story. We follow you for <em><?= $years ?> years</em>, so your consistency, your improvement and your talents speak louder than any single mark sheet.</p>
      <div class="goalpts">
        <div class="gp rv d2"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/></svg></i><div><b>Find hidden talent</b><span>Students who grow steadily are noticed, not just the toppers.</span></div></div>
        <div class="gp rv d3"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M5 10l7-7 7 7"/></svg></i><div><b>Guide every step</b><span>Early support when marks slip, recognition when they rise.</span></div></div>
        <div class="gp rv d4"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16M7 16V9m5 7V5m5 11v-4"/></svg></i><div><b>Open the right doors</b><span>Your record puts you first in line for real opportunities.</span></div></div>
      </div>
    </div>
    <div class="promise rv d2">
      <div class="q">“</div>
      <p><?= $h($motto) ?></p>
      <small>Our promise to every student at <?= $org ?></small>
    </div>
  </div>
</section>

<!-- JOURNEY -->
<section class="journey" id="journey">
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">The journey</span>
      <h2 class="h2 rv d1">How we shape your future</h2>
      <p class="lede rv d2">One clear path, from the day you join to the day the right opportunity finds you.</p>
    </div>
    <div class="steps">
      <div class="step rv"><div class="num">1</div><span class="when">Day one</span><h3>Join</h3><p>Register in two minutes with your studies, interests and dreams.</p></div>
      <div class="step rv d1"><div class="num">2</div><span class="when">Every term</span><h3>Track</h3><p>Marks, attendance, skills and achievements, recorded term by term.</p></div>
      <div class="step rv d2"><div class="num">3</div><span class="when">Every year</span><h3>Grow</h3><p>We watch your trend and guide you early when you need it.</p></div>
      <div class="step rv d3"><div class="num">4</div><span class="when">Year 2 onwards</span><h3>Stand out</h3><p>Consistent growth lifts you to the top of our candidate list.</p></div>
      <div class="step rv d4"><div class="num">★</div><span class="when">When you're ready</span><h3>Your future</h3><p>Scholarships, courses and jobs reach you first.</p></div>
    </div>
  </div>
</section>

<!-- FUTURE -->
<section class="future" id="future">
  <div class="wrap">
    <span class="eyebrow rv">How we show the future</span>
    <h2 class="h2 rv d1">Watch your growth<br>become your advantage</h2>
    <p class="lede rv d2">Every term adds a point to your growth line. Over <?= $years ?> years it becomes clear proof of who you are, and that is what opens doors.</p>
    <div class="fgrid">
      <div class="glass rv" id="chartBox">
        <h3>A growth line, year by year</h3>
        <div class="sub">Illustration: how a student's score can rise over <?= $years ?> years</div>
        <?php
        $n = count($pts); $x0 = 52; $x1 = 552; $yb = 262; $yt = 36;
        $X = fn($i) => round($x0 + ($x1 - $x0) * $i / max(1, $n - 1), 1);
        $Y = fn($v) => round($yb - ($v - 40) / 60 * ($yb - $yt), 1);
        $line = ''; foreach ($pts as $i => $v) $line .= ($i ? ' L' : 'M') . $X($i) . ' ' . $Y($v);
        $area = $line . ' L' . $X($n - 1) . ' ' . $yb . ' L' . $X(0) . ' ' . $yb . ' Z';
        ?>
        <svg class="chart" viewBox="0 0 600 320" role="img" aria-label="Illustration: score rising from <?= $pts[0] ?> in year 1 to <?= end($pts) ?> in year <?= $n ?>">
          <defs><linearGradient id="af" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#F5B841" stop-opacity=".32"/><stop offset="1" stop-color="#F5B841" stop-opacity="0"/></linearGradient>
          <linearGradient id="lg" x1="0" x2="1"><stop offset="0" stop-color="#5B8CFF"/><stop offset="1" stop-color="#FFD27A"/></linearGradient></defs>
          <?php foreach ([40, 60, 80, 100] as $g): ?><line x1="<?= $x0 ?>" x2="<?= $x1 ?>" y1="<?= $Y($g) ?>" y2="<?= $Y($g) ?>" stroke="rgba(255,255,255,.08)"/><text x="<?= $x0 - 12 ?>" y="<?= $Y($g) + 5 ?>" class="cx" fill="rgba(255,255,255,.4)" font-size="15" text-anchor="end"><?= $g ?></text><?php endforeach; ?>
          <line x1="<?= $x0 ?>" x2="<?= $x1 ?>" y1="<?= $Y(70) ?>" y2="<?= $Y(70) ?>" stroke="rgba(255,255,255,.3)" stroke-dasharray="6 7"/>
          <text class="lbl cx" x="<?= $x1 ?>" y="<?= $Y(70) + 28 ?>" fill="rgba(255,255,255,.5)" font-size="15" text-anchor="end">Average</text>
          <path class="lbl" d="<?= $area ?>" fill="url(#af)"/>
          <path class="line" d="<?= $line ?>" fill="none" stroke="url(#lg)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
          <?php foreach ($pts as $i => $v): $last = $i === $n - 1; ?>
            <g class="pt" style="transition-delay:<?= .4 + $i * .35 ?>s">
              <?php if ($last): ?><circle cx="<?= $X($i) ?>" cy="<?= $Y($v) ?>" r="18" fill="#F5B841" opacity=".18"/><?php endif; ?>
              <circle cx="<?= $X($i) ?>" cy="<?= $Y($v) ?>" r="<?= $last ? 9 : 6.5 ?>" fill="<?= $last ? '#F5B841' : '#070B1A' ?>" stroke="<?= $last ? '#FFD27A' : '#9DB8FF' ?>" stroke-width="3.5"/>
              <text x="<?= $X($i) ?>" y="<?= $Y($v) - 24 ?>" class="cv" fill="#fff" font-size="21" font-weight="700" text-anchor="middle" font-family="Plus Jakarta Sans, Inter, sans-serif"><?= $v ?></text>
            </g>
            <text x="<?= $X($i) ?>" y="302" class="cx" fill="rgba(255,255,255,.55)" font-size="16" text-anchor="middle">Year <?= $i + 1 ?></text>
          <?php endforeach; ?>
        </svg>
        <div class="legend"><span><i style="background:linear-gradient(90deg,#5B8CFF,#FFD27A)"></i>Your growth line</span><span><i style="background:rgba(255,255,255,.35)"></i>Average</span></div>
      </div>
      <div class="glass rv d2" id="wBox">
        <h3>What makes your score</h3>
        <div class="sub">Every term, out of 100</div>
        <div class="weights">
          <?php foreach ($w as $k => $v): ?><div class="wt"><div><?= $k ?><span><?= $v ?>%</span></div><i style="--v:<?= $v ?>%"></i></div><?php endforeach; ?>
        </div>
        <div class="note">Your overall score gives the <b>most weight to your latest results</b>, so steady improvement always counts in your favour.</div>
      </div>
    </div>
  </div>
</section>

<!-- WHAT WE FOLLOW -->
<section>
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">What we follow</span>
      <h2 class="h2 rv d1">The whole student,<br>not just marks</h2>
    </div>
    <div class="cards">
      <div class="card rv"><div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg></div><h3>Education</h3><p>Your school, college, course and subjects as you move up year by year.</p></div>
      <div class="card rv d1"><div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></div><h3>Performance</h3><p>Marks and rank every term, so your improvement is clear to see.</p></div>
      <div class="card rv d2"><div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M3 9h18M8 2v4M16 2v4"/><path d="m9 15 2 2 4-4"/></svg></div><h3>Attendance</h3><p>Showing up every day proves the commitment colleges and employers value.</p></div>
      <div class="card rv d3"><div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 8 5-3 5 3-1.5-8"/></svg></div><h3>Skills & achievements</h3><p>Sports, competitions, certificates and talents beyond the classroom.</p></div>
    </div>
  </div>
</section>

<!-- OPPORTUNITIES -->
<section class="opps">
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">Where it leads</span>
      <h2 class="h2 rv d1">Opportunities that find you</h2>
      <p class="lede rv d2">When an opportunity comes to us, we already know who is ready. Your record speaks for you.</p>
    </div>
    <div class="ogrid">
      <div class="opp rv"><div class="e">🎓</div><h3>Scholarships</h3><p>Support for students who earn it.</p></div>
      <div class="opp rv d1"><div class="e">📚</div><h3>Higher studies</h3><p>The right course and college for you.</p></div>
      <div class="opp rv d2"><div class="e">💼</div><h3>Internships</h3><p>Real experience while you learn.</p></div>
      <div class="opp rv d3"><div class="e">🚀</div><h3>Jobs</h3><p>Careers that match your strengths.</p></div>
      <div class="opp rv d4"><div class="e">🧭</div><h3>Guidance</h3><p>Advice at every turning point.</p></div>
    </div>
  </div>
</section>

<!-- STUDENTS / PARENTS -->
<section>
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">Why join</span>
      <h2 class="h2 rv d1">Built for students.<br>Trusted by parents.</h2>
    </div>
    <div class="aud">
      <div class="apanel s rv">
        <span class="tag">For students</span>
        <h3>Your effort, finally visible</h3>
        <ul>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Every good term counts, not just one exam day</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Your talents outside class are recorded too</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Be first in line when opportunities come</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Guidance from people who know your journey</li>
        </ul>
      </div>
      <div class="apanel p rv d2">
        <span class="tag">For parents</span>
        <h3>Peace of mind, for years</h3>
        <ul>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Someone is watching your child's progress closely</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Problems are noticed early, before they grow</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>Nothing is kept without your consent</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>You can ask us to correct or delete details anytime</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- TRUST -->
<section class="trust" style="padding-top:0">
  <div class="wrap">
    <div>
      <span class="eyebrow rv">Your data, safe</span>
      <h2 class="h2 rv d1">Trust comes first</h2>
      <p class="lede rv d2">Your details are yours. We keep them only to help you grow, and only for as long as we follow your journey.</p>
    </div>
    <div class="tlist">
      <div class="titem rv"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg></i><div><b>Consent first</b><span>Nothing is kept without your agreement, and a parent's for students under 18.</span></div></div>
      <div class="titem rv d1"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></i><div><b>Private and protected</b><span>Only our team can see your records. They are never sold.</span></div></div>
      <div class="titem rv d2"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg></i><div><b>Your right to delete</b><span>Ask us anytime to correct or remove your details.</span></div></div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq" id="faq">
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">Questions</span>
      <h2 class="h2 rv d1">Everything you need to know</h2>
    </div>
    <div class="qs rv d2">
      <details><summary>Who can join?</summary><p>Any school or college student who wants their progress followed and wants to be considered for future opportunities. Students under 18 join with a parent or guardian's consent.</p></details>
      <details><summary>Why <?= $years ?> years?</summary><p>Real potential shows over time. Following you for <?= $years ?> years shows your consistency and improvement, which tell far more about you than a single result.</p></details>
      <details><summary>What do you record?</summary><p>Your personal and education details, and every term your marks, attendance, skills rating, rank, achievements and remarks.</p></details>
      <details><summary>Who can see my details?</summary><p>Only the <?= $org ?> team. Your details are not public and are never sold.</p></details>
      <details><summary>Can I leave or delete my data?</summary><p>Yes. Contact us at any time and we will correct or delete your details.</p></details>
      <details><summary>How do I register?</summary><p>Tap "Register" and fill in a short form. It takes about two minutes. Our team checks your details and gets in touch.</p></details>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="final">
  <div class="wrap">
    <div class="finalbox rv">
      <h2>Your future starts with <span class="hl">one step</span></h2>
      <p>Join <?= $org ?> today and let your progress speak for you, year after year.</p>
      <?php if ($open): ?>
        <a class="btn btn-gold" href="register.php">Register now, it's quick <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      <?php else: ?>
        <p style="margin:0">Registrations will open soon.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <a class="logo" href="#top"><i><?= $initial ?></i><span><?= $org ?></span></a>
    <nav><a href="#goal">Our goal</a><a href="#journey">Journey</a><a href="#faq">FAQ</a><?php if ($open): ?><a href="register.php">Register</a><?php endif; ?></nav>
    <span>© <?= date('Y') ?> <?= $org ?></span>
  </div>
</footer>

<script>
// Solid nav after scrolling.
const nav = document.getElementById('nav');
const onScroll = () => nav.classList.toggle('solid', scrollY > 30);
addEventListener('scroll', onScroll, {passive: true}); onScroll();

// Reveal on scroll (the chart and weight bars animate when their box comes in).
const io = 'IntersectionObserver' in window ? new IntersectionObserver(es => es.forEach(e => {
  if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
}), {threshold: .15, rootMargin: '0px 0px -40px 0px'}) : null;
document.querySelectorAll('.rv').forEach(el => io ? io.observe(el) : el.classList.add('in'));

// Score ring in the hero card counts up.
const target = <?= (int)end($pts) ?>, arc = document.getElementById('ringArc'), num = document.getElementById('ringNum');
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const setRing = v => { num.textContent = Math.round(v); arc.style.strokeDashoffset = 314.16 * (1 - v / 100); };
if (reduce) setRing(target); else {
  const t0 = performance.now() + 500, dur = 1800;
  const tick = t => { const p = Math.min(1, Math.max(0, (t - t0) / dur)); setRing(target * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(tick); };
  requestAnimationFrame(tick);
}
</script>
</body>
</html>
