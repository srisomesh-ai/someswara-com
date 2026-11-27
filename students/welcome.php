<?php
require __DIR__ . '/lib.php';
$set = settings();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$org = $h($set['org_name']);
$years = (int)$set['track_years'];
$open = is_setup() && !empty($set['public_form']);
header('X-Frame-Options: DENY');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $org ?> | <?= $h($set['motto']) ?></title>
<meta name="description" content="<?= $h($set['goal']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
:root{--accent:#1F4FD1;--accent-dark:#163A9C;--accent-soft:#E6ECFB;--ink:#0F1A33;--bg:#F6F6F3;--line:#E4E4DF;--text:#1A1A1A;--muted:#5A5F66;--gold:#F2B33D}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--text);font:16px/1.6 Inter,system-ui,sans-serif}
a{color:inherit}
.wrap{max-width:1080px;margin:0 auto;padding:0 20px}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 26px;border-radius:999px;font-weight:700;text-decoration:none;font-size:16px}
.btn-p{background:var(--gold);color:var(--ink)}
.btn-o{border:1.5px solid rgba(255,255,255,.5);color:#fff}

/* hero */
.hero{background:radial-gradient(1200px 500px at 80% -10%,#3A6BF0 0%,transparent 60%),linear-gradient(160deg,#163A9C,#0F1A33);color:#fff;padding:22px 0 90px;overflow:hidden}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:70px}
.top b{font-size:18px;letter-spacing:-.01em}
.top a{font-size:14px;font-weight:600;opacity:.85;text-decoration:none}
.eyebrow{display:inline-block;font-size:13px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold);margin-bottom:18px}
.hero h1{margin:0 0 20px;font-size:clamp(38px,7vw,68px);line-height:1.05;font-weight:800;letter-spacing:-.03em;max-width:820px}
.hero p.lead{margin:0 0 34px;font-size:clamp(17px,2.2vw,20px);max-width:640px;opacity:.88}
.cta{display:flex;flex-wrap:wrap;gap:12px}

/* sections */
section{padding:80px 0}
.label{font-size:13px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent)}
h2{margin:10px 0 16px;font-size:clamp(28px,4.4vw,42px);line-height:1.12;letter-spacing:-.02em;font-weight:800;color:var(--ink)}
.intro{max-width:680px;color:var(--muted);font-size:18px;margin:0}

.goal{background:#fff}
.goalgrid{display:flex;flex-wrap:wrap;gap:40px;align-items:center}
.goalgrid>div{flex:1 1 340px}
.quote{background:var(--accent-soft);border-radius:20px;padding:34px;font-size:clamp(22px,3vw,28px);font-weight:700;line-height:1.3;color:var(--accent-dark);position:relative}
.quote:before{content:"“";position:absolute;top:-6px;left:22px;font-size:80px;color:var(--accent);opacity:.25;line-height:1}
.quote small{display:block;margin-top:14px;font-size:14px;font-weight:600;color:var(--muted)}

/* journey */
.journey{display:flex;flex-direction:column;gap:0;margin-top:48px;position:relative}
.step{display:flex;gap:22px;position:relative;padding-bottom:36px}
.step:last-child{padding-bottom:0}
.step:not(:last-child):before{content:"";position:absolute;left:27px;top:56px;bottom:0;width:2px;background:linear-gradient(var(--accent),#C9D6F6)}
.dot{flex:none;width:56px;height:56px;border-radius:50%;background:var(--accent);color:#fff;font-weight:800;font-size:15px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 6px var(--accent-soft)}
.step:last-child .dot{background:var(--gold);color:var(--ink);box-shadow:0 0 0 6px #FCEBC7}
.step h3{margin:6px 0 6px;font-size:21px;color:var(--ink)}
.step p{margin:0;color:var(--muted);max-width:620px}
.step .when{font-size:13px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.08em}

/* growth chart */
.future{background:var(--ink);color:#fff}
.future h2{color:#fff}
.future .intro{color:rgba(255,255,255,.75)}
.future .label{color:var(--gold)}
.chartcard{margin-top:40px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:24px}
.chartcard svg{width:100%;max-width:760px;margin:0 auto;height:auto;display:block}
.legend{display:flex;flex-wrap:wrap;gap:18px;margin-top:12px;font-size:14px;color:rgba(255,255,255,.75)}
.legend i{display:inline-block;width:14px;height:3px;border-radius:2px;vertical-align:middle;margin-right:6px}

/* tracked */
.cards{display:flex;flex-wrap:wrap;gap:16px;margin-top:40px}
.card{flex:1 1 230px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:26px}
.card .ic{width:46px;height:46px;border-radius:12px;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;margin-bottom:16px}
.card h3{margin:0 0 6px;font-size:18px;color:var(--ink)}
.card p{margin:0;color:var(--muted);font-size:15px}

.opps{display:flex;flex-wrap:wrap;gap:10px;margin-top:28px}
.opps span{background:#fff;border:1px solid var(--line);border-radius:999px;padding:9px 16px;font-weight:600;font-size:15px}

.trust{background:#fff}
.trust ul{list-style:none;padding:0;margin:30px 0 0;display:flex;flex-wrap:wrap;gap:14px}
.trust li{flex:1 1 260px;display:flex;gap:12px;align-items:flex-start;font-size:16px}
.trust li:before{content:"✓";flex:none;width:26px;height:26px;border-radius:50%;background:#E2F3E8;color:#127A3E;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:14px}

.final{text-align:center;background:linear-gradient(160deg,#163A9C,#0F1A33);color:#fff}
.final h2{color:#fff;margin-left:auto;margin-right:auto;max-width:700px}
.final p{opacity:.85;margin:0 auto 30px;max-width:560px;font-size:18px}
footer{padding:26px 0;color:var(--muted);font-size:14px;text-align:center}
@media (max-width:600px){section{padding:60px 0}.top{margin-bottom:48px}.hero{padding-bottom:70px}.quote{padding:28px 24px}}
</style>
</head>
<body>

<header class="hero">
  <div class="wrap">
    <div class="top"><b><?= $org ?></b><?php if ($open): ?><a href="register.php">Register →</a><?php endif; ?></div>
    <span class="eyebrow">Welcome</span>
    <h1><?= $h($set['motto']) ?></h1>
    <p class="lead"><?= $h($set['goal']) ?></p>
    <div class="cta">
      <?php if ($open): ?><a class="btn btn-p" href="register.php">Register as a student</a><?php endif; ?>
      <a class="btn btn-o" href="#journey">See how it works</a>
    </div>
  </div>
</header>

<section class="goal">
  <div class="wrap goalgrid">
    <div>
      <span class="label">Our goal</span>
      <h2>Every talent seen. Every student guided.</h2>
      <p class="intro">Marks on one exam never tell the whole story. A student who improves term after term, attends every class, and wins at sports or competitions deserves to be noticed. We follow each student for <?= $years ?> years so their real strength becomes clear, and we bring them the opportunities they have earned.</p>
    </div>
    <div>
      <div class="quote"><?= $h($set['motto']) ?><small>Our promise to every student who joins <?= $org ?></small></div>
    </div>
  </div>
</section>

<section id="journey">
  <div class="wrap">
    <span class="label">The journey</span>
    <h2>How we shape your future</h2>
    <p class="intro">One simple path, from the day you join to the day the right opportunity reaches you.</p>
    <div class="journey">
      <div class="step"><div class="dot">1</div><div><span class="when">Day one</span><h3>Join</h3><p>Register in two minutes with your details, your school or college, your interests and what you want to become. Students under 18 join with a parent's consent.</p></div></div>
      <div class="step"><div class="dot">2</div><div><span class="when">Every term</span><h3>Track</h3><p>We record your marks, attendance, skills and achievements every term. Over the years this becomes your complete growth story, not just one report card.</p></div></div>
      <div class="step"><div class="dot">3</div><div><span class="when">Every year</span><h3>Grow</h3><p>We watch your trend. When you rise, we notice it. When you slip, we notice early and guide you back on track.</p></div></div>
      <div class="step"><div class="dot">4</div><div><span class="when">Year 2 onwards</span><h3>Stand out</h3><p>Your progress builds a score that shows consistency and improvement. Students who keep growing rise to the top of our list.</p></div></div>
      <div class="step"><div class="dot">★</div><div><span class="when">When you are ready</span><h3>Your future</h3><p>When a scholarship, course, internship or job comes up, we already know who is ready. The best candidates are picked first, and that can be you.</p></div></div>
    </div>
  </div>
</section>

<section class="future">
  <div class="wrap">
    <span class="label">How we show the future</span>
    <h2>Your growth, year by year</h2>
    <p class="intro">Every record adds to a clear picture of where you are heading. This is how a student's path can look over <?= $years ?> years with us.</p>
    <div class="chartcard">
      <svg viewBox="0 0 600 330" role="img" aria-label="Example: a student's score rising from 62 in year 1 to 88 in year <?= $years ?>">
        <?php
        $pts = [62, 68, 75, 81, 88];
        $pts = array_slice($pts, 0, $years);
        $n = count($pts); $x0 = 52; $x1 = 560; $yb = 270; $yt = 40;
        $X = fn($i) => $x0 + ($x1 - $x0) * $i / max(1, $n - 1);
        $Y = fn($v) => $yb - ($v - 40) / 60 * ($yb - $yt);
        foreach ([40, 60, 80, 100] as $g) echo '<line x1="' . $x0 . '" x2="' . $x1 . '" y1="' . $Y($g) . '" y2="' . $Y($g) . '" stroke="rgba(255,255,255,.1)"/><text x="' . ($x0 - 12) . '" y="' . ($Y($g) + 4) . '" fill="rgba(255,255,255,.5)" font-size="16" text-anchor="end">' . $g . '</text>';
        $line = ''; $area = 'M' . $X(0) . ' ' . $yb;
        foreach ($pts as $i => $v) { $line .= ($i ? 'L' : 'M') . $X($i) . ' ' . $Y($v); $area .= ' L' . $X($i) . ' ' . $Y($v); }
        $area .= ' L' . $X($n - 1) . ' ' . $yb . ' Z';
        ?>
        <defs><linearGradient id="fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#F2B33D" stop-opacity=".35"/><stop offset="1" stop-color="#F2B33D" stop-opacity="0"/></linearGradient></defs>
        <line x1="<?= $x0 ?>" x2="<?= $x1 ?>" y1="<?= $Y(70) ?>" y2="<?= $Y(70) ?>" stroke="rgba(255,255,255,.35)" stroke-dasharray="6 6"/>
        <text x="<?= $x1 ?>" y="<?= $Y(70) + 22 ?>" fill="rgba(255,255,255,.6)" font-size="16" text-anchor="end">Average student</text>
        <path d="<?= $area ?>" fill="url(#fill)"/>
        <path d="<?= $line ?>" fill="none" stroke="#F2B33D" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        <?php foreach ($pts as $i => $v): ?>
          <circle cx="<?= $X($i) ?>" cy="<?= $Y($v) ?>" r="<?= $i === $n - 1 ? 9 : 6 ?>" fill="<?= $i === $n - 1 ? '#F2B33D' : '#0F1A33' ?>" stroke="#F2B33D" stroke-width="3"/>
          <text x="<?= $X($i) ?>" y="<?= $Y($v) - 20 ?>" fill="#fff" font-size="21" font-weight="700" text-anchor="middle"><?= $v ?></text>
          <text x="<?= $X($i) ?>" y="312" fill="rgba(255,255,255,.65)" font-size="17" text-anchor="middle">Year <?= $i + 1 ?></text>
        <?php endforeach; ?>
        <text x="<?= $X($n - 1) ?>" y="<?= $Y($pts[$n - 1]) + 54 ?>" fill="#F2B33D" font-size="17" font-weight="700" text-anchor="end">Ready for opportunity</text>
      </svg>
      <div class="legend"><span><i style="background:#F2B33D"></i>A student's score over the years (example)</span><span><i style="background:rgba(255,255,255,.4)"></i>Average</span></div>
    </div>
  </div>
</section>

<section>
  <div class="wrap">
    <span class="label">What we follow</span>
    <h2>The whole student, not just marks</h2>
    <div class="cards">
      <div class="card"><div class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1F4FD1" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg></div><h3>Education</h3><p>School, college, course and subjects as you move from class to class.</p></div>
      <div class="card"><div class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1F4FD1" stroke-width="2"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></div><h3>Performance</h3><p>Marks and rank every term, so improvement is easy to see.</p></div>
      <div class="card"><div class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1F4FD1" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg></div><h3>Attendance</h3><p>Being there, every day, shows commitment that employers value.</p></div>
      <div class="card"><div class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1F4FD1" stroke-width="2"><circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 8 5-3 5 3-1.5-8"/></svg></div><h3>Skills &amp; achievements</h3><p>Sports, competitions, certificates and talents beyond the classroom.</p></div>
    </div>
    <div class="opps"><span>🎓 Scholarships</span><span>📚 Higher studies</span><span>💼 Internships</span><span>🚀 Jobs</span><span>🧭 Career guidance</span></div>
  </div>
</section>

<section class="trust">
  <div class="wrap">
    <span class="label">Your data, safe</span>
    <h2>Trust comes first</h2>
    <ul>
      <li>We keep your details only with your consent, and a parent's consent for students under 18.</li>
      <li>Only our team can see your records. They are never sold.</li>
      <li>You can ask us to correct or delete your details at any time.</li>
    </ul>
  </div>
</section>

<?php if ($open): ?>
<section class="final">
  <div class="wrap">
    <h2>Your future starts with one step</h2>
    <p>Join <?= $org ?> today and let your progress speak for you.</p>
    <a class="btn btn-p" href="register.php">Register as a student</a>
  </div>
</section>
<?php endif; ?>

<footer>© <?= date('Y') ?> <?= $org ?></footer>
</body>
</html>
