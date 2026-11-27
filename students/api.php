<?php
require __DIR__ . '/lib.php';
start_session();

$action = $_GET['action'] ?? '';

function out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $msg, int $code = 400): void { out(['error' => $msg], $code); }

// Anything that changes data must be a POST sent by our own pages (the custom header blocks cross-site forms).
$writes = ['setup', 'login', 'logout', 'save_student', 'delete_student', 'add_record', 'delete_record', 'save_settings', 'register', 'change_password'];
if (in_array($action, $writes, true)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_SERVER['HTTP_X_STU'] ?? '') !== '1') fail('Bad request.', 400);
}

// ---------- public ----------
if ($action === 'state') out(['setup' => is_setup(), 'logged_in' => logged_in(), 'org_name' => settings()['org_name']]);

if ($action === 'setup') {
    if (is_setup()) fail('Already set up.', 403);
    $pw = (string)($_POST['password'] ?? '');
    if (strlen($pw) < 8) fail('Use a password of at least 8 characters.');
    $s = DEFAULT_SETTINGS;
    $s['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    $s['org_name'] = clean((string)($_POST['org_name'] ?? ''), 80) ?: DEFAULT_SETTINGS['org_name'];
    write_json('settings.json', $s);
    session_regenerate_id(true);
    $_SESSION['stu_admin'] = true;
    out(['ok' => true]);
}

if ($action === 'login') {
    if ($left = login_locked()) fail('Too many wrong tries. Try again in ' . ceil($left / 60) . ' minutes.', 429);
    $s = settings();
    if (!empty($s['password_hash']) && password_verify((string)($_POST['password'] ?? ''), $s['password_hash'])) {
        write_json('login.json', ['fails' => 0, 'until' => 0]);
        session_regenerate_id(true);
        $_SESSION['stu_admin'] = true;
        out(['ok' => true]);
    }
    login_failed();
    fail('Wrong password.', 401);
}

// Public registration form (register.php). Goes in as "pending" until the admin approves it.
if ($action === 'register') {
    $set = settings();
    if (!is_setup() || empty($set['public_form'])) fail('Registration is closed right now.', 403);
    if (!empty($_POST['website'])) out(['ok' => true]);   // hidden field only bots fill in
    $p = $_POST;
    if (clean((string)($p['name'] ?? '')) === '' || clean((string)($p['phone'] ?? '')) === '') fail('Please fill in the name and phone number.');
    if (empty($p['consent'])) fail('Please tick the consent box to continue.');
    $dob = clean_date((string)($p['dob'] ?? ''));
    if ($dob === '') fail('Please fill in the date of birth.');
    $minor = (new DateTime($dob))->diff(new DateTime('today'))->y < 18;
    if ($minor && (clean((string)($p['guardian_name'] ?? '')) === '' || empty($p['guardian_consent'])))
        fail('The student is under 18, so a parent or guardian must fill in their name and give consent.');
    // Simple limit: at most 20 sign-ups an hour from one address.
    $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'stu');
    $rl = read_json('register_rate.json');
    $rl = array_filter($rl, fn($t) => $t > time() - 3600);
    $mine = array_filter($rl, fn($t, $k) => strpos($k, $ip) === 0, ARRAY_FILTER_USE_BOTH);
    if (count($mine) >= 20) fail('Too many sign-ups from this connection. Please try later.', 429);
    $rl[$ip . '-' . new_id()] = time();
    write_json('register_rate.json', $rl);

    $allowed = array_flip(['name', 'gender', 'dob', 'phone', 'email', 'address', 'city', 'guardian_name', 'guardian_phone', 'guardian_relation',
        'institution', 'course', 'stream', 'current_year', 'roll_no', 'interests', 'goal']);
    $st = student_from_post(array_intersect_key($p, $allowed));
    $st['status'] = 'pending';
    $st['source'] = 'self';
    $st['consent_date'] = date('Y-m-d');
    $st['consent_by'] = $minor ? 'Guardian: ' . clean((string)$p['guardian_name'], 100) : 'Self: ' . $st['name'];
    with_students(function (&$all) use ($st) { $all[] = $st; });
    out(['ok' => true]);
}

// ---------- admin only from here ----------
if (!logged_in()) fail('Please log in.', 401);

if ($action === 'logout') { $_SESSION = []; session_destroy(); out(['ok' => true]); }

if ($action === 'list') {
    $set = settings();
    out(['students' => array_map(fn($s) => student_row($s, $set), students_all()), 'settings' => array_diff_key($set, ['password_hash' => 1])]);
}

if ($action === 'get') {
    $id = (string)($_GET['id'] ?? '');
    $set = settings();
    foreach (students_all() as $s) if (($s['id'] ?? '') === $id) {
        foreach ($s['records'] as &$r) $r['score'] = record_score($r, $set);
        unset($r);
        usort($s['records'], fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
        out(['student' => $s, 'summary' => student_summary($s, $set)]);
    }
    fail('Student not found.', 404);
}

if ($action === 'save_student') {
    $id = (string)($_POST['id'] ?? '');
    if ($id === '' && clean((string)($_POST['name'] ?? '')) === '') fail('Please fill in the name.');
    $saved = with_students(function (&$all) use ($id) {
        if ($id !== '') {
            foreach ($all as &$s) if (($s['id'] ?? '') === $id) { $s = student_from_post($_POST, $s); return $s; }
            return null;
        }
        $s = student_from_post($_POST);
        $s['source'] = 'admin';
        $all[] = $s;
        return $s;
    });
    if (!$saved) fail('Student not found.', 404);
    out(['ok' => true, 'id' => $saved['id']]);
}

// Deleting removes every saved detail of the student (right to erasure).
if ($action === 'delete_student') {
    $id = (string)($_POST['id'] ?? '');
    with_students(function (&$all) use ($id) { $all = array_values(array_filter($all, fn($s) => ($s['id'] ?? '') !== $id)); });
    out(['ok' => true]);
}

if ($action === 'add_record') {
    $id = (string)($_POST['id'] ?? '');
    $rec = record_from_post($_POST);
    if ($rec['marks'] === null && $rec['attendance'] === null && $rec['skills'] === null && $rec['remarks'] === '' && $rec['achievements'] === '')
        fail('Fill in at least marks, attendance, skills or a remark.');
    $ok = with_students(function (&$all) use ($id, $rec) {
        foreach ($all as &$s) if (($s['id'] ?? '') === $id) { $s['records'][] = $rec; $s['updated'] = time(); return true; }
        return false;
    });
    if (!$ok) fail('Student not found.', 404);
    out(['ok' => true]);
}

if ($action === 'delete_record') {
    $id = (string)($_POST['id'] ?? ''); $rid = (string)($_POST['record_id'] ?? '');
    with_students(function (&$all) use ($id, $rid) {
        foreach ($all as &$s) if (($s['id'] ?? '') === $id)
            $s['records'] = array_values(array_filter($s['records'], fn($r) => ($r['id'] ?? '') !== $rid));
    });
    out(['ok' => true]);
}

if ($action === 'save_settings') {
    $s = settings();
    $s['org_name'] = clean((string)($_POST['org_name'] ?? $s['org_name']), 80) ?: DEFAULT_SETTINGS['org_name'];
    $s['track_years'] = max(3, min(5, (int)($_POST['track_years'] ?? $s['track_years'])));
    foreach (['w_marks', 'w_attendance', 'w_skills'] as $k) $s[$k] = (int)clean_num($_POST[$k] ?? $s[$k]);
    if ($s['w_marks'] + $s['w_attendance'] + $s['w_skills'] <= 0) fail('At least one weight must be above 0.');
    $s['public_form'] = !empty($_POST['public_form']) && $_POST['public_form'] !== '0';
    write_json('settings.json', $s);
    out(['ok' => true]);
}

if ($action === 'change_password') {
    $s = settings();
    if (!password_verify((string)($_POST['old'] ?? ''), $s['password_hash'])) fail('The current password is wrong.');
    $pw = (string)($_POST['new'] ?? '');
    if (strlen($pw) < 8) fail('Use a password of at least 8 characters.');
    $s['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    write_json('settings.json', $s);
    out(['ok' => true]);
}

// Spreadsheet download: one line per student, with their latest record and overall score.
if ($action === 'export') {
    $set = settings();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students-' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output', 'w');
    fwrite($f, "\xEF\xBB\xBF");
    $cols = ['name', 'gender', 'dob', 'phone', 'email', 'city', 'guardian_name', 'guardian_phone', 'institution', 'course', 'stream', 'current_year', 'roll_no', 'status', 'joined', 'consent_date', 'consent_by'];
    fputcsv($f, array_merge($cols, ['shortlisted', 'records', 'score', 'trend', 'tracking_ends', 'latest_period', 'latest_marks', 'latest_attendance', 'latest_skills']));
    foreach (students_all() as $s) {
        $sum = student_summary($s, $set);
        $recs = $s['records'] ?? [];
        usort($recs, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
        $last = end($recs) ?: [];
        $line = array_map(fn($c) => (string)($s[$c] ?? ''), $cols);
        // Stop spreadsheet programs from reading a cell as a formula.
        $line = array_map(fn($v) => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $line);
        fputcsv($f, array_merge($line, [!empty($s['shortlisted']) ? 'yes' : '', $sum['record_count'], $sum['score'], $sum['trend'], $sum['track_ends'],
            $last['period'] ?? '', $last['marks'] ?? '', $last['attendance'] ?? '', $last['skills'] ?? '']));
    }
    exit;
}

fail('Unknown action.', 404);
