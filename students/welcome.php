<?php
require __DIR__ . '/lib.php';
$set = settings();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$org = $h($set['org_name']);
$years = max(3, min(5, (int)$set['track_years']));
$open = is_setup() && !empty($set['public_form']);

// The motto as the headline: the second sentence (if any) is shown in gold.
$motto = trim((string)$set['motto']);
$parts = preg_split('/(?<=[.!?])\s+/', $motto, 2);
$mottoHtml = count($parts) === 2 ? $h($parts[0]) . '<br><span class="hl">' . $h($parts[1]) . '</span>' : '<span class="hl">' . $h($motto) . '</span>';
$season = (int)date('n') >= 6 ? date('Y') . '–' . substr((string)(date('Y') + 1), 2) : (date('Y') - 1) . '–' . date('y');

// Score weights from Settings, as percentages.
$wsum = max(1, $set['w_marks'] + $set['w_attendance'] + $set['w_skills']);
$w = array_filter(['Marks' => round($set['w_marks'] * 100 / $wsum), 'Activities & attendance' => round($set['w_attendance'] * 100 / $wsum), 'Tasks & challenges' => round($set['w_skills'] * 100 / $wsum)]);

$gate = '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 28V14a11 11 0 0 1 22 0v14"/><path d="M3 28h26"/><path d="M16 4v24"/><path d="M10 28v-9M22 28v-9"/><path d="M10 15a6 6 0 0 1 12 0"/></svg>';
$check = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
$arrow = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
header('X-Frame-Options: DENY');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $org ?> | <?= $h($motto) ?></title>
<meta name="description" content="<?= $h($set['goal']) ?>">
<meta property="og:title" content="<?= $org ?>: <?= $h($motto) ?>">
<meta property="og:description" content="<?= $h($set['goal']) ?>">
<meta name="theme-color" content="#08091A">
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="#F5B841"/><g transform="translate(4 4) scale(.75)" fill="none" stroke="#08091A" stroke-width="3" stroke-linecap="round"><path d="M5 28V14a11 11 0 0 1 22 0v14M3 28h26M16 4v24"/></g></svg>') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<style>
:root{
  --ink:#08091A;--ink2:#10132E;--navy:#141A44;--blue:#3B5BFF;--sky:#A9BBFF;
  --gold:#F5B841;--gold2:#FFD889;--gold3:#C98A12;--paper:#FBFAF6;--white:#fff;--line:#E9E6DC;--text:#12131C;--muted:#5E6270;
  --r:22px;--display:"Plus Jakarta Sans",Inter,system-ui,sans-serif
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{margin:0;background:var(--paper);color:var(--text);font:16px/1.65 Inter,system-ui,sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:inherit}
svg{display:block}
.wrap{max-width:1180px;margin:0 auto;padding:0 24px}
h1,h2,h3{font-family:var(--display);margin:0;letter-spacing:-.025em}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:56px;padding:0 30px;border-radius:999px;font-weight:700;text-decoration:none;font-size:16px;transition:transform .2s,box-shadow .2s,background .2s;white-space:nowrap}
.btn:hover{transform:translateY(-2px)}
.btn svg{transition:transform .2s}
.btn:hover svg{transform:translateX(3px)}
.btn-gold{background:linear-gradient(135deg,var(--gold2),var(--gold) 60%,#E9A21F);color:var(--ink);box-shadow:0 12px 34px -10px rgba(245,184,65,.75),inset 0 1px 0 rgba(255,255,255,.5)}
.btn-gold:hover{box-shadow:0 18px 44px -10px rgba(245,184,65,.9),inset 0 1px 0 rgba(255,255,255,.5)}
.btn-ghost{color:#fff;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.05)}
.btn-ghost:hover{background:rgba(255,255,255,.1)}
.btn-dark{background:var(--ink);color:#fff}
.eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:13px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--gold3)}
.eyebrow:before{content:"";width:22px;height:2px;background:currentColor;border-radius:2px}
.h2{font-size:clamp(32px,4.6vw,54px);line-height:1.06;margin:16px 0 18px;color:var(--ink)}
.lede{font-size:clamp(17px,1.6vw,19px);color:var(--muted);max-width:660px;margin:0}
.hl{background:linear-gradient(100deg,var(--gold2) 0%,var(--gold) 50%,#FFB067 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.center{text-align:center}
.center .lede{margin:0 auto}
section{padding:120px 0;position:relative}

/* nav */
.nav{position:fixed;inset:0 0 auto;z-index:50;transition:background .3s,box-shadow .3s}
.nav.solid{background:rgba(8,9,26,.8);backdrop-filter:saturate(1.6) blur(14px);-webkit-backdrop-filter:saturate(1.6) blur(14px);box-shadow:0 1px 0 rgba(255,255,255,.07)}
.nav .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;height:74px}
.logo{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none;font-family:var(--display);font-weight:800;font-size:19px;letter-spacing:-.02em;min-width:0}
.logo i{flex:none;width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--gold2),var(--gold));color:var(--ink);display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px -6px rgba(245,184,65,.7)}
.logo i svg{width:24px;height:24px}
.logo span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.links{display:flex;gap:30px}
.links a{color:rgba(255,255,255,.72);text-decoration:none;font-size:15px;font-weight:500;transition:color .2s}
.links a:hover{color:#fff}
.nav .btn{min-height:44px;padding:0 22px;font-size:14px}

/* hero */
.hero{position:relative;background:var(--ink);color:#fff;padding:150px 0 130px;overflow:hidden;isolation:isolate}
.hero:before{content:"";position:absolute;inset:0;z-index:-2;background:
  radial-gradient(700px 520px at 74% 30%,rgba(245,184,65,.22),transparent 62%),
  radial-gradient(900px 600px at 90% -10%,rgba(59,91,255,.38),transparent 60%),
  radial-gradient(600px 500px at 0% 100%,rgba(59,91,255,.18),transparent 60%),
  linear-gradient(180deg,#08091A,#0D1030)}
.stars{position:absolute;inset:0;z-index:-1;background-image:radial-gradient(1.2px 1.2px at 20px 30px,rgba(255,255,255,.55),transparent),radial-gradient(1px 1px at 120px 80px,rgba(255,255,255,.4),transparent),radial-gradient(1.4px 1.4px at 200px 160px,rgba(255,216,137,.6),transparent),radial-gradient(1px 1px at 300px 40px,rgba(255,255,255,.35),transparent);background-size:340px 220px;opacity:.6;mask-image:linear-gradient(180deg,#000 40%,transparent)}
.hero .wrap{display:flex;flex-wrap:wrap;align-items:center;gap:56px}
.hero-copy{flex:1 1 540px;min-width:0}
.badge{display:inline-flex;align-items:center;gap:10px;padding:7px 16px 7px 8px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);font-size:14px;font-weight:500;color:rgba(255,255,255,.9);margin-bottom:26px}
.badge b{background:var(--gold);color:var(--ink);border-radius:999px;padding:2px 10px;font-size:12px;letter-spacing:.04em}
.hero h1{font-size:clamp(38px,5.2vw,66px);line-height:1.04;font-weight:800;letter-spacing:-.04em;margin-bottom:24px}
.hero .lede{color:rgba(255,255,255,.76);margin-bottom:36px}
.cta{display:flex;flex-wrap:wrap;gap:14px}
.who{display:flex;flex-wrap:wrap;gap:10px;margin-top:34px}
.who a{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);font-size:14px;font-weight:600;color:rgba(255,255,255,.85);text-decoration:none;transition:background .2s}
.who a:hover{background:rgba(255,255,255,.12)}
.who span{color:rgba(255,255,255,.55);font-size:14px;align-self:center;margin-right:2px}

/* the gate illustration */
.hero-art{flex:1 1 400px;display:flex;justify-content:center;min-width:0;position:relative}
.gatebox{position:relative;width:100%;max-width:440px;aspect-ratio:1/1.08}
.gatebox svg.g{width:100%;height:100%;overflow:visible}
.glow{position:absolute;left:50%;top:44%;width:62%;height:62%;transform:translate(-50%,-50%);background:radial-gradient(circle,rgba(255,216,137,.75),rgba(245,184,65,.25) 45%,transparent 70%);filter:blur(10px);animation:pulse 4s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:.75;transform:translate(-50%,-50%) scale(1)}50%{opacity:1;transform:translate(-50%,-50%) scale(1.07)}}
.door-l,.door-r{transition:transform 2.2s cubic-bezier(.6,.05,.2,1) .6s}
.loaded .door-l{transform:scaleX(.32)}
.loaded .door-r{transform:scaleX(.32)}
.tag{position:absolute;display:flex;align-items:center;gap:10px;background:#fff;color:var(--ink);border-radius:16px;padding:11px 15px;box-shadow:0 22px 44px -14px rgba(0,0,0,.6);font-size:13px;font-weight:600;animation:float 6s ease-in-out infinite;white-space:nowrap}
.tag i{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-style:normal;font-size:16px;flex:none}
.tag small{display:block;font-weight:500;color:var(--muted);font-size:12px}
.t1{top:8%;left:-6%}.t1 i{background:#FFF1D2}
.t2{top:40%;right:-8%;animation-delay:-2s}.t2 i{background:#E6EBFF}
.t3{bottom:6%;left:2%;animation-delay:-4s}.t3 i{background:#E5F7EC}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}

/* strip */
.strip{background:var(--white);border-bottom:1px solid var(--line)}
.strip .wrap{display:grid;grid-template-columns:repeat(4,1fr)}
.pillar{padding:32px 24px;border-left:1px solid var(--line)}
.pillar:first-child{border-left:none}
.pillar b{display:block;font-family:var(--display);font-size:clamp(24px,2.6vw,32px);font-weight:800;color:var(--ink);letter-spacing:-.03em;line-height:1.15}
.pillar b em{font-style:normal;color:var(--gold3)}
.pillar span{font-size:14px;color:var(--muted);font-weight:500}

/* promise */
.promise .wrap{display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:center}
.bigquote{font-family:var(--display);font-size:clamp(28px,3.4vw,44px);line-height:1.18;font-weight:800;color:var(--ink);letter-spacing:-.03em;margin:0}
.bigquote em{font-style:normal;color:var(--gold3)}
.pcard{border-radius:28px;background:linear-gradient(155deg,var(--navy),var(--ink));color:#fff;padding:40px;position:relative;overflow:hidden;box-shadow:0 40px 80px -40px rgba(8,9,26,.6)}
.pcard:before{content:"";position:absolute;width:380px;height:380px;right:-140px;top:-140px;background:radial-gradient(circle,rgba(245,184,65,.4),transparent 65%)}
.pcard h3{position:relative;font-size:22px;margin-bottom:22px}
.plist{position:relative;display:grid;gap:16px}
.pi{display:flex;gap:14px;align-items:flex-start}
.pi i{flex:none;width:40px;height:40px;border-radius:12px;background:rgba(245,184,65,.16);color:var(--gold2);display:flex;align-items:center;justify-content:center;font-style:normal;font-size:18px}
.pi b{display:block;font-size:16px}
.pi span{color:rgba(255,255,255,.65);font-size:15px}

/* path */
.path{background:var(--white);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.steps{display:grid;grid-template-columns:repeat(5,1fr);gap:18px;margin-top:64px;position:relative}
.steps:before{content:"";position:absolute;top:33px;left:10%;right:10%;height:3px;border-radius:3px;background:linear-gradient(90deg,var(--blue),var(--sky) 50%,var(--gold))}
.step{position:relative;text-align:center;padding:0 4px}
.num{position:relative;width:68px;height:68px;margin:0 auto 22px;border-radius:50%;background:var(--white);border:2.5px solid var(--blue);display:flex;align-items:center;justify-content:center;font-family:var(--display);font-weight:800;font-size:15px;color:var(--blue);box-shadow:0 0 0 8px var(--white);line-height:1.1;text-align:center}
.step:last-child .num{background:linear-gradient(135deg,var(--gold2),var(--gold));border-color:var(--gold);color:var(--ink)}
.step:last-child .num svg{width:30px;height:30px}
.when{font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--blue)}
.step:last-child .when{color:var(--gold3)}
.step h3{font-size:21px;margin:6px 0 8px;color:var(--ink)}
.step p{margin:0;font-size:15px;color:var(--muted)}

/* what you do */
.dgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:56px}
.dcard{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:30px 26px;transition:transform .3s,box-shadow .3s,border-color .3s;position:relative;overflow:hidden}
.dcard:hover{transform:translateY(-6px);box-shadow:0 30px 54px -26px rgba(20,26,68,.3);border-color:#E3D4AE}
.dcard .ic{width:56px;height:56px;border-radius:17px;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:20px;background:linear-gradient(135deg,#FFF4D9,#FFE7AE)}
.dcard h3{font-size:20px;margin-bottom:8px;color:var(--ink)}
.dcard p{margin:0;color:var(--muted);font-size:15px}
.dcard.feature{grid-column:span 1;background:linear-gradient(155deg,#1A2266,var(--ink));color:#fff;border-color:transparent}
.dcard.feature p{color:rgba(255,255,255,.7)}
.dcard.feature h3{color:#fff}
.dcard.feature .ic{background:rgba(245,184,65,.18)}

/* global */
.global{background:var(--ink);color:#fff;overflow:hidden;isolation:isolate}
.global:before{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(800px 500px at 50% 0%,rgba(59,91,255,.32),transparent 60%),radial-gradient(700px 400px at 50% 110%,rgba(245,184,65,.16),transparent 60%)}
.global .eyebrow{color:var(--gold)}
.global .h2{color:#fff}
.global .lede{color:rgba(255,255,255,.7)}
.mapbox{margin:56px auto 0;max-width:1000px;position:relative}
.map{width:100%;height:auto;overflow:visible}
.map .arc{fill:none;stroke:url(#arcg);stroke-width:2.4;stroke-linecap:round;stroke-dasharray:700;stroke-dashoffset:700;transition:stroke-dashoffset 2.4s cubic-bezier(.4,.1,.2,1)}
.in .map .arc{stroke-dashoffset:0}
.in .map .arc.a2{transition-delay:.3s}.in .map .arc.a3{transition-delay:.6s}.in .map .arc.a4{transition-delay:.9s}
.map .hub{animation:ping 2.6s ease-out infinite;transform-box:fill-box;transform-origin:center}
@keyframes ping{0%{transform:scale(.6);opacity:.9}100%{transform:scale(2.6);opacity:0}}
.countries{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:36px}
.country{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:22px;text-align:center}
.country b{display:block;font-family:var(--display);font-size:22px}
.country span{font-size:14px;color:rgba(255,255,255,.6)}
.country.home{border-color:rgba(245,184,65,.45);background:rgba(245,184,65,.08)}
.fine{margin:26px auto 0;text-align:center;font-size:13px;color:rgba(255,255,255,.45);max-width:640px}

/* growth chart */
.fgrid{display:grid;grid-template-columns:1.6fr 1fr;gap:24px;margin-top:56px}
.panel{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:30px}
.panel h3{font-size:19px;color:var(--ink)}
.panel .sub{font-size:14px;color:var(--muted);margin:4px 0 18px}
.chart{width:100%;height:auto}
.chart .line{stroke-dasharray:1200;stroke-dashoffset:1200;transition:stroke-dashoffset 2.2s cubic-bezier(.4,.1,.2,1)}
.chart .pt,.chart .lbl{opacity:0;transition:opacity .5s}
.in .chart .line{stroke-dashoffset:0}
.in .chart .pt,.in .chart .lbl{opacity:1}
.weights{display:flex;flex-direction:column;gap:18px}
.wt div{display:flex;justify-content:space-between;font-weight:600;font-size:15px;color:var(--ink)}
.wt div span{color:var(--gold3);font-family:var(--display)}
.wt i{display:block;height:9px;border-radius:9px;background:#F1EEE4;margin-top:8px;overflow:hidden}
.wt i:after{content:"";display:block;height:100%;width:var(--v);background:linear-gradient(90deg,var(--gold),var(--gold2));border-radius:9px;transform:scaleX(0);transform-origin:left;transition:transform 1.4s .3s cubic-bezier(.2,.8,.2,1)}
.in .wt i:after{transform:scaleX(1)}
.note{margin-top:22px;padding-top:20px;border-top:1px solid var(--line);font-size:14px;color:var(--muted)}
.note b{color:var(--ink)}

/* audiences */
.aud{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:56px}
.ap{border-radius:var(--r);padding:36px 30px;display:flex;flex-direction:column;background:var(--white);border:1px solid var(--line)}
.ap.s{background:linear-gradient(155deg,#2B47E0,#16248A);color:#fff;border-color:transparent;box-shadow:0 40px 70px -40px rgba(43,71,224,.8)}
.ap .tg{align-self:flex-start;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:6px 12px;border-radius:999px;margin-bottom:16px;background:#FFF1D2;color:#8A5A00}
.ap.s .tg{background:rgba(255,255,255,.16);color:#fff}
.ap h3{font-size:clamp(22px,2.2vw,27px);margin-bottom:20px;color:var(--ink)}
.ap.s h3{color:#fff}
.ap ul{list-style:none;margin:0 0 28px;padding:0;display:grid;gap:13px;flex:1}
.ap li{display:flex;gap:11px;align-items:flex-start;font-size:15.5px}
.ap li svg{flex:none;margin-top:3px;color:var(--gold3)}
.ap.s li svg{color:var(--gold2)}
.ap .btn{align-self:stretch;min-height:50px}

/* trust + faq */
.trust{padding-top:0}
.tgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.ti{display:flex;gap:16px;background:var(--white);border:1px solid var(--line);border-radius:18px;padding:22px}
.ti i{flex:none;width:46px;height:46px;border-radius:14px;background:#E8F7EE;color:#14874A;display:flex;align-items:center;justify-content:center}
.ti b{display:block;color:var(--ink);font-family:var(--display);font-size:17px;margin-bottom:2px}
.ti span{color:var(--muted);font-size:15px}
.faq{background:var(--white);border-top:1px solid var(--line)}
.qs{max-width:840px;margin:48px auto 0}
details{border-bottom:1px solid var(--line)}
summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:20px;padding:24px 4px;font-family:var(--display);font-weight:700;font-size:clamp(17px,1.8vw,20px);color:var(--ink)}
summary::-webkit-details-marker{display:none}
summary:after{content:"+";flex:none;width:36px;height:36px;border-radius:50%;background:var(--paper);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:500;color:var(--gold3);transition:transform .3s}
details[open] summary:after{transform:rotate(45deg)}
details p{margin:0 4px 24px;color:var(--muted);font-size:16px;max-width:740px}

/* final */
.final{padding:0 0 120px;background:var(--white)}
.finalbox{position:relative;border-radius:32px;padding:clamp(60px,8vw,100px) 28px;text-align:center;color:#fff;overflow:hidden;background:var(--ink);isolation:isolate}
.finalbox:before{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(520px 340px at 50% 110%,rgba(245,184,65,.5),transparent 70%),radial-gradient(600px 340px at 50% -10%,rgba(59,91,255,.45),transparent 70%)}
.finalbox .fg{width:64px;height:64px;margin:0 auto 26px;border-radius:20px;background:linear-gradient(135deg,var(--gold2),var(--gold));color:var(--ink);display:flex;align-items:center;justify-content:center;box-shadow:0 14px 34px -10px rgba(245,184,65,.8)}
.finalbox .fg svg{width:36px;height:36px}
.finalbox h2{font-size:clamp(36px,5.4vw,66px);line-height:1.04;max-width:820px;margin:0 auto 20px}
.finalbox p{color:rgba(255,255,255,.75);font-size:18px;max-width:580px;margin:0 auto 36px}
footer{background:var(--ink);color:rgba(255,255,255,.55);padding:40px 0;font-size:14px}
footer .wrap{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px}
footer .logo{font-size:16px}
footer .logo i{width:32px;height:32px;border-radius:10px}
footer .logo i svg{width:20px;height:20px}
footer nav{display:flex;flex-wrap:wrap;gap:22px}
footer a{text-decoration:none}
footer a:hover{color:#fff}

/* reveal */
.rv{opacity:0;transform:translateY(28px);transition:opacity .9s cubic-bezier(.2,.7,.2,1),transform .9s cubic-bezier(.2,.7,.2,1)}
.rv.in{opacity:1;transform:none}
.d1{transition-delay:.08s}.d2{transition-delay:.16s}.d3{transition-delay:.24s}.d4{transition-delay:.32s}
@media (prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important}.rv{opacity:1;transform:none}.chart .line,.map .arc{stroke-dashoffset:0}.chart .pt,.chart .lbl{opacity:1}.wt i:after{transform:none}}

/* responsive */
@media (max-width:1020px){
  .links{display:none}
  .promise .wrap{grid-template-columns:1fr;gap:44px}
  .fgrid{grid-template-columns:1fr}
  .dgrid{grid-template-columns:1fr 1fr}
  .aud{grid-template-columns:1fr}
  .tgrid{grid-template-columns:1fr}
  .steps{grid-template-columns:1fr;gap:0;max-width:560px}
  .steps:before{display:none}
  .step{display:grid;grid-template-columns:68px 1fr;column-gap:22px;text-align:left;padding:0 0 34px}
  .step:not(:last-child):after{content:"";position:absolute;left:33px;top:68px;bottom:0;width:3px;background:linear-gradient(180deg,var(--blue),var(--sky))}
  .step:last-child{padding-bottom:0}
  .num{margin:0;grid-row:span 3}
  .step h3{margin-top:2px}
}
@media (max-width:760px){
  .wrap{padding:0 18px}
  section{padding:84px 0}
  .hero{padding:118px 0 96px}
  .hero .wrap{gap:40px}
  .hero-art{flex-basis:100%}
  .gatebox{max-width:280px}
  .t1{left:-14%;top:4%}.t2{right:-16%}.t3{left:-10%;bottom:2%}
  .tag{padding:8px 11px;font-size:12px}.tag i{width:26px;height:26px;font-size:13px}.tag small{display:none}
  .strip .wrap{grid-template-columns:1fr 1fr}
  .pillar{padding:22px 16px;border-left:none;border-top:1px solid var(--line)}
  .pillar:nth-child(-n+2){border-top:none}
  .pillar:nth-child(even){border-left:1px solid var(--line)}
  .dgrid{grid-template-columns:1fr}
  .countries{grid-template-columns:1fr 1fr;gap:12px}
  .pcard{padding:30px 24px}
  .panel{padding:22px 18px}
  .ap{padding:30px 22px}
  .nav .btn{min-height:38px;padding:0 14px;font-size:13px}
  .cta .btn{flex:1 1 220px}
  .final{padding-bottom:84px}
  .chart .cv{font-size:28px}.chart .cx{font-size:22px}
  .map .ml{font-size:26px}
}
</style>
</head>
<body>

<header class="nav" id="nav">
  <div class="wrap">
    <a class="logo" href="#top"><i><?= $gate ?></i><span><?= $org ?></span></a>
    <nav class="links"><a href="#promise">Our promise</a><a href="#path">Your path</a><a href="#global">Global network</a><a href="#faq">FAQ</a></nav>
    <?php if ($open): ?><a class="btn btn-gold" href="register.php">Join now</a><?php endif; ?>
  </div>
</header>

<!-- HERO -->
<div class="hero" id="top">
  <div class="stars"></div>
  <div class="wrap">
    <div class="hero-copy">
      <div class="badge rv"><b><?= $open ? 'OPEN' : 'SOON' ?></b> 10th class admissions <?= $season ?></div>
      <h1 class="rv d1"><?= $mottoHtml ?></h1>
      <p class="lede rv d2"><?= $h($set['goal']) ?></p>
      <div class="cta rv d3">
        <?php if ($open): ?><a class="btn btn-gold" href="register.php">Join <?= $org ?> today <?= $arrow ?></a><?php endif; ?>
        <a class="btn btn-ghost" href="#path">See your path</a>
      </div>
      <div class="who rv d4"><span>I am a</span><a href="#students">🎒 Student</a><a href="#parents">👪 Parent</a><a href="#teachers">🍎 Teacher</a></div>
    </div>

    <div class="hero-art rv d2" aria-hidden="true">
      <div class="gatebox" id="gate">
        <div class="glow"></div>
        <svg class="g" viewBox="0 0 400 430">
          <defs>
            <linearGradient id="gold" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#FFE3A3"/><stop offset=".5" stop-color="#F5B841"/><stop offset="1" stop-color="#B8770C"/></linearGradient>
            <linearGradient id="road" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#FFD889" stop-opacity=".9"/><stop offset="1" stop-color="#FFD889" stop-opacity="0"/></linearGradient>
            <radialGradient id="sun"><stop offset="0" stop-color="#FFF6DD"/><stop offset=".55" stop-color="#FFD889"/><stop offset="1" stop-color="#F5B841" stop-opacity="0"/></radialGradient>
          </defs>
          <!-- light through the gate -->
          <circle cx="200" cy="200" r="88" fill="url(#sun)"/>
          <path d="M175 400 L192 230 L208 230 L225 400 Z" fill="url(#road)" opacity=".55"/>
          <!-- doors -->
          <g style="transform-origin:118px 270px" class="door-l"><path d="M118 270 V150 A82 82 0 0 1 199 96 V270 Z" fill="#141A44" stroke="url(#gold)" stroke-width="3.5"/><path d="M136 270V132M154 270V114M172 270V103M190 270V97" stroke="url(#gold)" stroke-width="2.6"/><path d="M118 200 H199M118 232 H199" stroke="url(#gold)" stroke-width="2.6"/><circle cx="190" cy="216" r="4" fill="url(#gold)"/></g>
          <g style="transform-origin:282px 270px" class="door-r"><path d="M282 270 V150 A82 82 0 0 0 201 96 V270 Z" fill="#141A44" stroke="url(#gold)" stroke-width="3.5"/><path d="M264 270V132M246 270V114M228 270V103M210 270V97" stroke="url(#gold)" stroke-width="2.6"/><path d="M282 200 H201M282 232 H201" stroke="url(#gold)" stroke-width="2.6"/><circle cx="210" cy="216" r="4" fill="url(#gold)"/></g>
          <!-- arch -->
          <path d="M104 150 A96 96 0 0 1 296 150" fill="none" stroke="url(#gold)" stroke-width="14"/>
          <path d="M84 150 A116 116 0 0 1 316 150" fill="none" stroke="url(#gold)" stroke-width="4" opacity=".7"/>
          <path d="M200 30 l9 14 h-18 z" fill="url(#gold)"/><circle cx="200" cy="24" r="7" fill="url(#gold)"/>
          <!-- pillars -->
          <rect x="72" y="140" width="40" height="134" rx="4" fill="#141A44" stroke="url(#gold)" stroke-width="5"/>
          <rect x="288" y="140" width="40" height="134" rx="4" fill="#141A44" stroke="url(#gold)" stroke-width="5"/>
          <path d="M84 156V258M100 156V258M300 156V258M316 156V258" stroke="url(#gold)" stroke-width="2" opacity=".6"/>
          <rect x="64" y="128" width="56" height="16" rx="4" fill="url(#gold)"/><rect x="280" y="128" width="56" height="16" rx="4" fill="url(#gold)"/>
          <circle cx="92" cy="116" r="10" fill="url(#gold)"/><circle cx="308" cy="116" r="10" fill="url(#gold)"/>
          <rect x="60" y="270" width="64" height="20" rx="5" fill="url(#gold)"/><rect x="276" y="270" width="64" height="20" rx="5" fill="url(#gold)"/>
          <path d="M48 290 H352" stroke="url(#gold)" stroke-width="5" stroke-linecap="round"/>
          <!-- path of steps -->
          <path d="M150 420 Q200 350 200 296" fill="none" stroke="#FFD889" stroke-width="3" stroke-dasharray="2 10" stroke-linecap="round" opacity=".8"/>
          <circle cx="160" cy="404" r="6" fill="#A9BBFF"/><circle cx="185" cy="355" r="6" fill="#C9B8FF"/><circle cx="199" cy="318" r="6" fill="#FFD889"/>
        </svg>
        <div class="tag t1"><i>🏆</i><div>Challenge completed<small>+12 points this term</small></div></div>
        <div class="tag t2"><i>🌍</i><div>Global network<small>USA · UK · Australia</small></div></div>
        <div class="tag t3"><i>⭐</i><div>Selected: top students<small>Your path is open</small></div></div>
      </div>
    </div>
  </div>
</div>

<!-- STRIP -->
<div class="strip">
  <div class="wrap">
    <div class="pillar rv"><b>From <em>10th</em> class</b><span>start early, grow further</span></div>
    <div class="pillar rv d1"><b><em><?= $years ?></em> years</b><span>of guidance and mentoring</span></div>
    <div class="pillar rv d2"><b>Tasks & <em>challenges</em></b><span>prove your talent, not just marks</span></div>
    <div class="pillar rv d3"><b><em>Global</em> network</b><span>USA · UK · Australia · India</span></div>
  </div>
</div>

<!-- PROMISE -->
<section class="promise" id="promise">
  <div class="wrap">
    <div>
      <span class="eyebrow rv">Our promise</span>
      <p class="bigquote rv d1" style="margin-top:18px">You bring the <em>dream.</em><br>We build the <em>path.</em></p>
      <p class="lede rv d2" style="margin-top:22px">Most students are judged on one exam. At <?= $org ?>, we watch you grow for <?= $years ?> years: your marks, your activities, the tasks you complete and the challenges you win. The best students are chosen, guided and connected to real opportunities, at home and around the world.</p>
    </div>
    <div class="pcard rv d2">
      <h3>What you get when you join</h3>
      <div class="plist">
        <div class="pi"><i>🎯</i><div><b>A clear goal</b><span>Know where you are heading from 10th class itself.</span></div></div>
        <div class="pi"><i>🧭</i><div><b>Mentors who know you</b><span>Guidance that grows with you, year after year.</span></div></div>
        <div class="pi"><i>📈</i><div><b>Your growth, on record</b><span>Every mark, activity and challenge counts for you.</span></div></div>
        <div class="pi"><i>🌍</i><div><b>Doors to the world</b><span>Access to our network across the USA, UK, Australia and India.</span></div></div>
      </div>
    </div>
  </div>
</section>

<!-- PATH -->
<section class="path" id="path">
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">Your path to the gate</span>
      <h2 class="h2 rv d1">Join today. In <?= $years ?> years,<br>we show you your destiny.</h2>
      <p class="lede rv d2">A simple path, from the day you join in 10th class to the day your gate opens.</p>
    </div>
    <div class="steps">
      <div class="step rv"><div class="num">10th</div><span class="when">Day one</span><h3>Join</h3><p>Register in two minutes with your school, marks and dreams.</p></div>
      <div class="step rv d1"><div class="num">Every<br>term</div><span class="when">Show your talent</span><h3>Take on tasks</h3><p>Answer questionnaires, finish tasks and accept challenges online.</p></div>
      <div class="step rv d2"><div class="num">Every<br>year</div><span class="when">Grow with mentors</span><h3>Rise</h3><p>Your marks, activities and progress are followed and guided.</p></div>
      <div class="step rv d3"><div class="num">Top<br>%</div><span class="when">Get selected</span><h3>Stand out</h3><p>The most consistent, talented students are chosen as the best.</p></div>
      <div class="step rv d4"><div class="num"><?= $gate ?></div><span class="when">Your destiny</span><h3>Walk through</h3><p>Careers, higher studies and opportunities in India and abroad.</p></div>
    </div>
  </div>
</section>

<!-- WHAT YOU'LL DO -->
<section>
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">More than marks</span>
      <h2 class="h2 rv d1">Show us who you really are</h2>
      <p class="lede rv d2">Marks matter, but they are only one part of you. Through <?= $org ?> you prove your talent in many ways.</p>
    </div>
    <div class="dgrid">
      <div class="dcard rv"><div class="ic">📝</div><h3>Questionnaires</h3><p>Tell us about your interests, strengths and dreams, so we understand where you fit best.</p></div>
      <div class="dcard rv d1"><div class="ic">🧩</div><h3>Tasks</h3><p>Real tasks set by our team. Upload your work and show how you think and create.</p></div>
      <div class="dcard feature rv d2"><div class="ic">🏆</div><h3>Challenges</h3><p>Compete, solve and stand out. Every challenge you win moves you closer to the gate.</p></div>
      <div class="dcard rv"><div class="ic">📊</div><h3>Marks</h3><p>Your results from 10th class onwards, so steady improvement is always seen.</p></div>
      <div class="dcard rv d1"><div class="ic">⚽</div><h3>Activities</h3><p>Sports, arts, leadership, service: the talents no report card shows.</p></div>
      <div class="dcard rv d2"><div class="ic">🎖️</div><h3>Achievements</h3><p>Certificates, prizes and milestones, all in one profile that speaks for you.</p></div>
    </div>
  </div>
</section>

<!-- GLOBAL -->
<section class="global" id="global">
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">Our best feature</span>
      <h2 class="h2 rv d1">A gate that opens<br>to the <span class="hl">whole world</span></h2>
      <p class="lede rv d2">Over many years we have built strong connections across the USA, UK and Australia for job profiles and other opportunities. Our best students get access to this network.</p>
    </div>
    <div class="mapbox rv" id="map">
      <svg class="map" viewBox="0 0 1000 470" role="img" aria-label="Connections from India to the USA, UK and Australia">
        <defs>
          <linearGradient id="arcg" x1="0" x2="1"><stop offset="0" stop-color="#FFD889"/><stop offset="1" stop-color="#A9BBFF"/></linearGradient>
          <pattern id="dots" width="14" height="14" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.6" fill="rgba(255,255,255,.16)"/></pattern>
        </defs>
        <!-- simplified continents -->
        <g fill="url(#dots)">
          <path d="M70 90 C140 60 260 60 300 100 C330 140 290 190 250 210 C220 230 210 260 180 250 C130 230 90 190 80 150 Z"/>
          <path d="M230 270 C270 260 300 290 300 330 C300 380 270 430 250 440 C235 410 225 360 220 320 Z"/>
          <path d="M440 80 C480 60 540 70 560 100 C560 130 520 140 490 150 C460 150 440 120 440 80 Z"/>
          <path d="M460 170 C520 160 570 180 580 230 C585 290 560 350 520 380 C490 360 470 300 460 250 Z"/>
          <path d="M560 70 C650 50 800 60 870 100 C900 140 860 190 800 200 C740 210 700 250 660 240 C620 220 590 170 570 140 Z"/>
          <path d="M790 320 C840 300 900 310 920 350 C920 390 870 410 830 400 C800 390 780 360 790 320 Z"/>
        </g>
        <!-- arcs from India -->
        <path class="arc a1" d="M690 225 Q450 -30 200 140"/>
        <path class="arc a2" d="M690 225 Q590 30 480 105"/>
        <path class="arc a3" d="M690 225 Q820 210 855 355"/>
        <!-- nodes -->
        <?php foreach ([['USA', 200, 140, 'end', -14], ['UK', 480, 105, 'middle', -20], ['Australia', 855, 355, 'start', 18]] as [$n, $x, $y, $a, $dx]): ?>
          <circle cx="<?= $x ?>" cy="<?= $y ?>" r="9" fill="#A9BBFF"/><circle cx="<?= $x ?>" cy="<?= $y ?>" r="16" fill="none" stroke="#A9BBFF" stroke-opacity=".5" stroke-width="2"/>
          <text class="ml" x="<?= $x + ($a === 'middle' ? 0 : $dx) ?>" y="<?= $a === 'middle' ? $y - 26 : $y + 6 ?>" fill="#fff" font-size="20" font-weight="700" text-anchor="<?= $a ?>" font-family="Plus Jakarta Sans, Inter, sans-serif"><?= $n ?></text>
        <?php endforeach; ?>
        <circle class="hub" cx="690" cy="225" r="14" fill="#F5B841"/>
        <circle cx="690" cy="225" r="11" fill="#F5B841" stroke="#FFF1CC" stroke-width="3"/>
        <text class="ml" x="690" y="270" fill="#FFD889" font-size="20" font-weight="800" text-anchor="middle" font-family="Plus Jakarta Sans, Inter, sans-serif">India</text>
      </svg>
    </div>
    <div class="countries">
      <div class="country rv"><b>USA</b><span>Job profiles & studies</span></div>
      <div class="country rv d1"><b>UK</b><span>Job profiles & studies</span></div>
      <div class="country rv d2"><b>Australia</b><span>Job profiles & studies</span></div>
      <div class="country home rv d3"><b>India</b><span>Careers at home</span></div>
    </div>
    <p class="fine">Opportunities are offered to selected students based on their performance and the eligibility rules of each opportunity and country.</p>
  </div>
</section>

<!-- GROWTH -->
<section>
  <div class="wrap">
    <span class="eyebrow rv">How we show the future</span>
    <h2 class="h2 rv d1">Watch your effort<br>become your destiny</h2>
    <p class="lede rv d2">Every term adds to your growth line. Over <?= $years ?> years it becomes clear proof of who you are, and that is what opens the gate.</p>
    <div class="fgrid">
      <div class="panel rv" id="chartBox">
        <h3>Your growth line</h3>
        <div class="sub">Illustration: how a student's score can rise from 10th class</div>
        <?php
        $labels = array_slice(['10th', 'Inter 1', 'Inter 2', 'Degree 1', 'Degree 2'], 0, $years);
        $pts = array_slice([60, 68, 75, 83, 90], 0, $years);
        $n = count($pts); $x0 = 56; $x1 = 548; $yb = 262; $yt = 36;
        $X = fn($i) => round($x0 + ($x1 - $x0) * $i / max(1, $n - 1), 1);
        $Y = fn($v) => round($yb - ($v - 40) / 60 * ($yb - $yt), 1);
        $line = ''; foreach ($pts as $i => $v) $line .= ($i ? ' L' : 'M') . $X($i) . ' ' . $Y($v);
        $area = $line . ' L' . $X($n - 1) . ' ' . $yb . ' L' . $X(0) . ' ' . $yb . ' Z';
        ?>
        <svg class="chart" viewBox="0 0 600 320" role="img" aria-label="Illustration: score rising from <?= $pts[0] ?> to <?= end($pts) ?>">
          <defs><linearGradient id="af" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#F5B841" stop-opacity=".28"/><stop offset="1" stop-color="#F5B841" stop-opacity="0"/></linearGradient>
          <linearGradient id="lg" x1="0" x2="1"><stop offset="0" stop-color="#3B5BFF"/><stop offset="1" stop-color="#F5B841"/></linearGradient></defs>
          <?php foreach ([40, 60, 80, 100] as $g): ?><line x1="<?= $x0 ?>" x2="<?= $x1 ?>" y1="<?= $Y($g) ?>" y2="<?= $Y($g) ?>" stroke="#ECE8DC"/><text class="cx" x="<?= $x0 - 12 ?>" y="<?= $Y($g) + 5 ?>" fill="#9A9DA8" font-size="15" text-anchor="end"><?= $g ?></text><?php endforeach; ?>
          <line x1="<?= $x0 ?>" x2="<?= $x1 ?>" y1="<?= $Y(70) ?>" y2="<?= $Y(70) ?>" stroke="#B9BCC6" stroke-dasharray="6 7"/>
          <text class="lbl cx" x="<?= $x1 ?>" y="<?= $Y(70) + 28 ?>" fill="#8D909B" font-size="15" text-anchor="end">Average</text>
          <path class="lbl" d="<?= $area ?>" fill="url(#af)"/>
          <path class="line" d="<?= $line ?>" fill="none" stroke="url(#lg)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
          <?php foreach ($pts as $i => $v): $last = $i === $n - 1; ?>
            <g class="pt" style="transition-delay:<?= .4 + $i * .35 ?>s">
              <?php if ($last): ?><circle cx="<?= $X($i) ?>" cy="<?= $Y($v) ?>" r="18" fill="#F5B841" opacity=".2"/><?php endif; ?>
              <circle cx="<?= $X($i) ?>" cy="<?= $Y($v) ?>" r="<?= $last ? 9 : 6.5 ?>" fill="<?= $last ? '#F5B841' : '#fff' ?>" stroke="<?= $last ? '#C98A12' : '#3B5BFF' ?>" stroke-width="3.5"/>
              <text class="cv" x="<?= $X($i) ?>" y="<?= $Y($v) - 24 ?>" fill="#08091A" font-size="21" font-weight="800" text-anchor="middle" font-family="Plus Jakarta Sans, Inter, sans-serif"><?= $v ?></text>
            </g>
            <text class="cx" x="<?= $X($i) ?>" y="302" fill="#6E7280" font-size="16" text-anchor="middle"><?= $labels[$i] ?></text>
          <?php endforeach; ?>
        </svg>
      </div>
      <div class="panel rv d2" id="wBox">
        <h3>What builds your score</h3>
        <div class="sub">Every term, out of 100</div>
        <div class="weights">
          <?php foreach ($w as $k => $v): ?><div class="wt"><div><?= $k ?><span><?= $v ?>%</span></div><i style="--v:<?= $v ?>%"></i></div><?php endforeach; ?>
        </div>
        <div class="note">Your latest results <b>count the most</b>, so it is never too late to rise.</div>
      </div>
    </div>
  </div>
</section>

<!-- AUDIENCES -->
<section class="path">
  <div class="wrap">
    <div class="center">
      <span class="eyebrow rv">Made for everyone who cares</span>
      <h2 class="h2 rv d1">Students. Parents. Teachers.</h2>
    </div>
    <div class="aud">
      <div class="ap s rv" id="students">
        <span class="tg">For students</span>
        <h3>Your future starts in 10th class</h3>
        <ul>
          <li><?= $check ?>Show talent beyond marks through tasks and challenges</li>
          <li><?= $check ?>Mentors who guide you every year</li>
          <li><?= $check ?>Be chosen among the best</li>
          <li><?= $check ?>Reach opportunities in India and abroad</li>
        </ul>
        <?php if ($open): ?><a class="btn btn-gold" href="register.php">Join as a student <?= $arrow ?></a><?php endif; ?>
      </div>
      <div class="ap rv d1" id="parents">
        <span class="tg">For parents</span>
        <h3>A clear path for your child</h3>
        <ul>
          <li><?= $check ?>Your child's growth followed for <?= $years ?> years</li>
          <li><?= $check ?>Guidance at every important turning point</li>
          <li><?= $check ?>Access to a network across 4 countries</li>
          <li><?= $check ?>Nothing kept without your consent</li>
        </ul>
        <?php if ($open): ?><a class="btn btn-dark" href="register.php">Register your child <?= $arrow ?></a><?php endif; ?>
      </div>
      <div class="ap rv d2" id="teachers">
        <span class="tg">For teachers</span>
        <h3>Give your best students wings</h3>
        <ul>
          <li><?= $check ?>Help talented students get noticed</li>
          <li><?= $check ?>Recommend students who deserve more</li>
          <li><?= $check ?>See them grow long after they leave your class</li>
          <li><?= $check ?>Be part of their journey to success</li>
        </ul>
        <?php if ($open): ?><a class="btn btn-dark" href="register.php">Recommend a student <?= $arrow ?></a><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- TRUST -->
<section class="trust" style="padding-top:80px;background:var(--white)">
  <div class="wrap">
    <div class="tgrid">
      <div class="ti rv"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg></i><div><b>Parent consent first</b><span>Students under 18 join only with a parent or guardian's consent.</span></div></div>
      <div class="ti rv d1"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></i><div><b>Private and protected</b><span>Only our team sees student records. They are never sold.</span></div></div>
      <div class="ti rv d2"><i><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg></i><div><b>Your right to delete</b><span>Ask us anytime to correct or remove your details.</span></div></div>
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
      <details><summary>Who can join <?= $org ?>?</summary><p>Students from 10th class onwards who want to build a bright future. Students under 18 join with a parent or guardian's consent.</p></details>
      <details><summary>What will I do after joining?</summary><p>You will answer questionnaires, complete tasks and take part in challenges set by our team, and upload your work online. We also follow your marks and activities every term.</p></details>
      <details><summary>How are the best students chosen?</summary><p>Not by one exam. We look at your marks, activities, tasks and challenges over the years. Students who keep growing and stay consistent rise to the top.</p></details>
      <details><summary>What does "the path to your destiny" mean?</summary><p>In <?= $years ?> years we will know your strengths well. We use that to guide you toward the right career, higher studies or job profile, in India or abroad through our network in the USA, UK and Australia.</p></details>
      <details><summary>Is a job abroad guaranteed?</summary><p>No. Opportunities are offered to selected students based on their performance and the eligibility rules of each opportunity and country. What we promise is guidance, a strong record of your talent, and access to our network.</p></details>
      <details><summary>Who can see my details?</summary><p>Only the <?= $org ?> team. Your details are not public, never sold, and you can ask us to correct or delete them at any time.</p></details>
    </div>
  </div>
</section>

<!-- FINAL -->
<section class="final">
  <div class="wrap">
    <div class="finalbox rv">
      <div class="fg"><?= $gate ?></div>
      <h2>The gate is open.<br><span class="hl">Walk in today.</span></h2>
      <p>Join today, and in <?= $years ?> years we will show you the path to your destiny.</p>
      <?php if ($open): ?><a class="btn btn-gold" href="register.php">Join <?= $org ?> now <?= $arrow ?></a><?php else: ?><p style="margin:0">Admissions open soon.</p><?php endif; ?>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <a class="logo" href="#top"><i><?= $gate ?></i><span><?= $org ?></span></a>
    <nav><a href="#promise">Our promise</a><a href="#path">Your path</a><a href="#global">Global network</a><a href="#faq">FAQ</a><?php if ($open): ?><a href="register.php">Join</a><?php endif; ?></nav>
    <span>© <?= date('Y') ?> <?= $org ?></span>
  </div>
</footer>

<script>
const nav = document.getElementById('nav');
const onScroll = () => nav.classList.toggle('solid', scrollY > 30);
addEventListener('scroll', onScroll, {passive: true}); onScroll();
const io = 'IntersectionObserver' in window ? new IntersectionObserver(es => es.forEach(e => {
  if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
}), {threshold: .15, rootMargin: '0px 0px -40px 0px'}) : null;
document.querySelectorAll('.rv').forEach(el => io ? io.observe(el) : el.classList.add('in'));
// The gate doors open once the page has loaded.
addEventListener('load', () => document.getElementById('gate').classList.add('loaded'));
</script>
</body>
</html>
