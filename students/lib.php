<?php
// Student Tracker: shared helpers (data folder, login, students, performance records, scoring).

// Saved data lives next to the website folder (students-data/), outside what a deploy replaces.
// Falls back to data/ inside this folder, which is blocked from the web and not in git.
function pick_data_dir(): string {
    $old = __DIR__ . '/data';
    $root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($root !== '' && strpos(__DIR__, $root) === 0) {
        $new = dirname($root) . '/students-data';
        if ((@is_dir($new) || @mkdir($new, 0700, true)) && @is_writable($new)) return $new;
    }
    return $old;
}
define('DATA_DIR', pick_data_dir());
date_default_timezone_set('Asia/Kolkata');

const DEFAULT_SETTINGS = [
    'org_name' => 'Student Tracker',
    'track_years' => 5,               // how long a student is followed from the day they join (3 to 5)
    'w_marks' => 60,                  // weights for a record's score, in percent
    'w_attendance' => 20,
    'w_skills' => 20,
    'public_form' => true,            // the public registration page accepts new students
    'motto' => 'Track today. Shape tomorrow.',   // shown big on the welcome page
    'goal' => 'We walk with every student for up to five years, following their studies, marks, skills and achievements, so that when the right opportunity comes, we know exactly who is ready for it.',
];

// ---------- JSON files ----------
function read_json(string $name): array {
    $f = DATA_DIR . '/' . $name;
    if (!is_file($f)) return [];
    $d = json_decode((string)file_get_contents($f), true);
    return is_array($d) ? $d : [];
}

function write_json(string $name, array $data): void {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0700, true);
    $f = DATA_DIR . '/' . $name;
    $tmp = $f . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    rename($tmp, $f);
}

// Runs $fn on students.json while holding a lock, so two saves at once never lose one another.
function with_students(callable $fn) {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0700, true);
    $lock = fopen(DATA_DIR . '/students.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        $all = read_json('students.json');
        $result = $fn($all);
        write_json('students.json', $all);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function students_all(): array { return read_json('students.json'); }

function settings(): array { return array_merge(DEFAULT_SETTINGS, read_json('settings.json')); }
function is_setup(): bool { return !empty(settings()['password_hash']); }
function new_id(): string { return bin2hex(random_bytes(6)); }

// ---------- login ----------
function start_session(): void {
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    session_name('stutrack');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $dir === '' ? '/' : $dir . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function logged_in(): bool { return !empty($_SESSION['stu_admin']); }

function login_locked(): int {
    $left = (int)(read_json('login.json')['until'] ?? 0) - time();
    return $left > 0 ? $left : 0;
}

function login_failed(): void {
    $fails = (int)(read_json('login.json')['fails'] ?? 0) + 1;
    write_json('login.json', ['fails' => $fails >= 8 ? 0 : $fails, 'until' => $fails >= 8 ? time() + 600 : 0]);
}

// ---------- cleaning input ----------
function clean(string $s, int $max = 200): string {
    $s = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '');
    return mb_substr($s, 0, $max);
}
function clean_date(string $s): string { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : ''; }
// A number in [0, $max], or null when left empty.
function clean_num($v, float $max = 100): ?float {
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) return null;
    return max(0, min($max, round((float)$v, 2)));
}

const STUDENT_FIELDS = [
    'name' => 120, 'gender' => 20, 'phone' => 20, 'email' => 120, 'address' => 300, 'city' => 80,
    'guardian_name' => 120, 'guardian_phone' => 20, 'guardian_relation' => 40,
    'institution' => 160, 'course' => 120, 'stream' => 120, 'current_year' => 40, 'roll_no' => 40,
    'interests' => 300, 'goal' => 300, 'notes' => 1000, 'consent_by' => 120,
];
const STUDENT_DATES = ['dob', 'joined', 'consent_date'];

// Builds a student from posted fields. $old keeps what is not posted (id, records, created time).
function student_from_post(array $p, array $old = []): array {
    $s = $old;
    foreach (STUDENT_FIELDS as $k => $max) if (array_key_exists($k, $p)) $s[$k] = clean((string)$p[$k], $max);
    foreach (STUDENT_DATES as $k) if (array_key_exists($k, $p)) $s[$k] = clean_date((string)$p[$k]);
    if (array_key_exists('status', $p)) $s['status'] = in_array($p['status'], ['active', 'pending', 'completed', 'withdrawn'], true) ? $p['status'] : 'active';
    if (array_key_exists('shortlisted', $p)) $s['shortlisted'] = !empty($p['shortlisted']) && $p['shortlisted'] !== '0';
    $s['id'] = $s['id'] ?? new_id();
    $s['created'] = $s['created'] ?? time();
    $s['updated'] = time();
    $s['records'] = $s['records'] ?? [];
    $s['status'] = $s['status'] ?? 'active';
    if (empty($s['joined'])) $s['joined'] = date('Y-m-d');
    return $s;
}

function record_from_post(array $p): array {
    return [
        'id' => new_id(),
        'period' => clean((string)($p['period'] ?? ''), 60),          // e.g. "2026 Term 1", "Year 2 Sem 1"
        'date' => clean_date((string)($p['date'] ?? '')) ?: date('Y-m-d'),
        'class' => clean((string)($p['class'] ?? ''), 80),             // class / semester at that time
        'marks' => clean_num($p['marks'] ?? null),                    // percent
        'attendance' => clean_num($p['attendance'] ?? null),          // percent
        'skills' => clean_num($p['skills'] ?? null, 10),              // 0 to 10, rated by a mentor
        'rank' => clean((string)($p['rank'] ?? ''), 20),
        'achievements' => clean((string)($p['achievements'] ?? ''), 500),
        'remarks' => clean((string)($p['remarks'] ?? ''), 500),
        'added' => time(),
    ];
}

// ---------- scoring ----------
// A record's score out of 100 from marks, attendance and skills, using the weights in Settings.
// A part that was left empty does not count, and the other parts share its weight.
function record_score(array $r, array $set): ?float {
    $parts = [
        [$r['marks'] ?? null, (float)$set['w_marks']],
        [$r['attendance'] ?? null, (float)$set['w_attendance']],
        [isset($r['skills']) && $r['skills'] !== null ? $r['skills'] * 10 : null, (float)$set['w_skills']],
    ];
    $sum = 0; $w = 0;
    foreach ($parts as [$v, $wt]) if ($v !== null && $wt > 0) { $sum += $v * $wt; $w += $wt; }
    return $w > 0 ? round($sum / $w, 1) : null;
}

// Student summary: overall score (latest three records, newest counted most), trend, and tracking window.
function student_summary(array $s, array $set): array {
    $recs = $s['records'] ?? [];
    usort($recs, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
    $scores = [];
    foreach ($recs as $r) { $sc = record_score($r, $set); if ($sc !== null) $scores[] = $sc; }
    $score = null; $trend = null;
    if ($scores) {
        $last = array_slice($scores, -3);
        $weights = array_slice([1, 2, 3], -count($last));
        $score = round(array_sum(array_map(fn($v, $w) => $v * $w, $last, $weights)) / array_sum($weights), 1);
        if (count($scores) > 1) $trend = round(end($scores) - $scores[0], 1);
    }
    $years = max(3, min(5, (int)$set['track_years']));
    $joined = $s['joined'] ?? date('Y-m-d', $s['created'] ?? time());
    $ends = date('Y-m-d', strtotime("$joined +$years years"));
    $age = null;
    if (!empty($s['dob'])) $age = (int)(new DateTime($s['dob']))->diff(new DateTime('today'))->y;
    return ['score' => $score, 'trend' => $trend, 'record_count' => count($recs), 'track_ends' => $ends,
        'track_over' => $ends < date('Y-m-d'), 'age' => $age, 'minor' => $age !== null && $age < 18];
}

// A light version of a student for the list.
function student_row(array $s, array $set): array {
    $keep = ['id', 'name', 'gender', 'phone', 'institution', 'course', 'stream', 'current_year', 'city', 'status', 'shortlisted', 'joined', 'consent_date'];
    $row = array_intersect_key($s, array_flip($keep));
    return $row + student_summary($s, $set);
}
