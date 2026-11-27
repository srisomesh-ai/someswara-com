<?php
require __DIR__ . '/lib.php';
$set = settings();
$open = is_setup() && !empty($set['public_form']);
$org = htmlspecialchars($set['org_name'], ENT_QUOTES);
header('X-Frame-Options: DENY');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student registration | <?= $org ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
:root{--accent:#1F4FD1;--bg:#F6F6F3;--line:#E4E4DF;--line2:#C9C9C3;--text:#1A1A1A;--muted:#5A5F66;--bad:#A01225;--good:#127A3E}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 Inter,system-ui,sans-serif}
.wrap{max-width:720px;margin:0 auto;padding:32px 16px 60px;display:flex;flex-direction:column;gap:18px}
h1{margin:0;font-size:26px} h2{margin:0;font-size:17px}
.card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:12px}
.grid{display:flex;flex-wrap:wrap;gap:12px}
.field{display:flex;flex-direction:column;gap:5px;flex:1 1 200px;min-width:0}
.field label{font-size:13px;font-weight:600;color:var(--muted)}
input,select,textarea{font:inherit;color:var(--text);min-height:44px;padding:8px 11px;border-radius:8px;border:1px solid var(--line2);background:#fff;width:100%}
textarea{min-height:70px;resize:vertical}
.check{display:flex;gap:10px;align-items:flex-start;font-size:14px}
.check input{width:20px;min-height:20px;height:20px;margin-top:2px;flex:none}
.card>.field{flex:none}
.hint{font-size:13px;color:var(--muted)}
.err{color:var(--bad);font-weight:600;min-height:20px}
button{font:inherit;width:100%;min-height:52px;border-radius:10px;border:none;background:var(--accent);color:#fff;font-weight:700;font-size:16px;cursor:pointer}
.hide{display:none!important}
.hp{position:absolute;left:-9999px}
</style>
</head>
<body>
<div class="wrap">
  <div><h1><?= $org ?></h1><p class="hint">Student registration · <a href="welcome.php" style="color:var(--accent)">About us</a></p></div>
<?php if (!$open): ?>
  <div class="card"><h2>Registration is closed right now</h2><p class="hint">Please check back later.</p></div>
<?php else: ?>
  <div id="done" class="card hide"><h2>Thank you, you are registered.</h2><p>Our team will check your details and contact you.</p></div>
  <form id="f" autocomplete="on">
    <div class="card"><h2>About the student</h2>
      <div class="grid">
        <div class="field"><label>Full name *</label><input name="name" required></div>
        <div class="field"><label>Date of birth *</label><input name="dob" type="date" required id="dob"></div>
        <div class="field"><label>Gender</label><select name="gender"><option></option><option>Female</option><option>Male</option><option>Other</option></select></div>
      </div>
      <div class="grid">
        <div class="field"><label>Mobile number *</label><input name="phone" type="tel" required></div>
        <div class="field"><label>Email</label><input name="email" type="email"></div>
        <div class="field"><label>City / town</label><input name="city"></div>
      </div>
      <div class="field"><label>Address</label><textarea name="address"></textarea></div>
    </div>
    <div class="card"><h2>Education</h2>
      <div class="grid">
        <div class="field"><label>School / college</label><input name="institution"></div>
        <div class="field"><label>Course / class</label><input name="course" placeholder="e.g. 10th, Inter MPC, B.Tech CSE"></div>
      </div>
      <div class="grid">
        <div class="field"><label>Stream / main subjects</label><input name="stream"></div>
        <div class="field"><label>Current year / semester</label><input name="current_year"></div>
        <div class="field"><label>Roll no.</label><input name="roll_no"></div>
      </div>
      <div class="field"><label>Interests and skills</label><textarea name="interests"></textarea></div>
      <div class="field"><label>What would you like to become?</label><textarea name="goal"></textarea></div>
    </div>
    <div class="card"><h2>Parent / guardian <span id="gReq" class="hint">(required for students under 18)</span></h2>
      <div class="grid">
        <div class="field"><label>Name</label><input name="guardian_name"></div>
        <div class="field"><label>Relation</label><input name="guardian_relation" placeholder="Father, Mother, …"></div>
        <div class="field"><label>Phone</label><input name="guardian_phone" type="tel"></div>
      </div>
    </div>
    <div class="card"><h2>Consent</h2>
      <p class="hint"><?= $org ?> will keep the details above and the student's marks, attendance and achievements for up to <?= (int)$set['track_years'] ?> years,
        to follow their progress and to suggest suitable courses, scholarships and jobs. The details are seen only by our team and are not sold.
        You can ask us at any time to correct or delete them.</p>
      <label class="check"><input type="checkbox" name="consent" value="1"> I agree to my details being kept and used as described above.</label>
      <label class="check hide" id="gc"><input type="checkbox" name="guardian_consent" value="1"> I am the parent / guardian named above and I give consent for this student, who is under 18.</label>
      <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    </div>
    <div class="err" id="err"></div>
    <button type="submit" id="go">Register</button>
  </form>
<script>
const f = document.getElementById('f');
const dob = document.getElementById('dob');
dob.max = new Date().toISOString().slice(0, 10);
dob.oninput = () => {
  const d = new Date(dob.value + 'T00:00'), t = new Date();
  let age = t.getFullYear() - d.getFullYear(); if (t < new Date(t.getFullYear(), d.getMonth(), d.getDate())) age--;
  document.getElementById('gc').classList.toggle('hide', !(dob.value && age < 18));
};
f.onsubmit = async e => {
  e.preventDefault();
  const err = document.getElementById('err'), btn = document.getElementById('go');
  err.textContent = ''; btn.disabled = true;
  try {
    const r = await fetch('api.php?action=register', {method: 'POST', headers: {'X-Stu': '1'}, body: new FormData(f)});
    const j = await r.json();
    if (j.error) throw new Error(j.error);
    f.classList.add('hide'); document.getElementById('done').classList.remove('hide'); window.scrollTo(0, 0);
  } catch (x) { err.textContent = x.message || 'Could not send. Please try again.'; }
  btn.disabled = false;
};
</script>
<?php endif; ?>
</div>
</body>
</html>
