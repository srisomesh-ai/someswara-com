<?php
/**
 * AR Video Player — backend
 * Actions: login | create | list | delete | get
 * Storage: data/experiences.json + data/{id}/{target.jpg, video.mp4, targets.mind}
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ADMIN_PASS', 'arAdmin@2026');          // change this
define('DATA_DIR', __DIR__ . '/data');
define('DB', DATA_DIR . '/experiences.json');
define('MAX_VIDEO_MB', 200);

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!file_exists(DB)) file_put_contents(DB, '[]');

function out($ok, $data = []) { echo json_encode(['ok' => $ok] + $data); exit; }
function load() { return json_decode(file_get_contents(DB), true) ?: []; }
function save($list) { file_put_contents(DB, json_encode($list, JSON_PRETTY_PRINT), LOCK_EX); }
function auth() {
  $h = $_SERVER['HTTP_X_ADMIN_PASS'] ?? ($_POST['pass'] ?? '');
  if ($h !== ADMIN_PASS) out(false, ['error' => 'Wrong password']);
}
function baseUrl() {
  $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  return $s . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
}

$action = $_REQUEST['action'] ?? '';
// Empty POST with a body = server dropped it (over post_max_size)
if ($_SERVER['REQUEST_METHOD']==='POST' && !$_POST && !$_FILES && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0)
  out(false, ['error' => 'Upload too large for server (' . round($_SERVER['CONTENT_LENGTH']/1048576) . ' MB). Raise post_max_size / upload_max_filesize in hPanel PHP config, or use a smaller video.']);

switch ($action) {

  case 'login':
    auth();
    out(true);

  case 'list':
    auth();
    $list = load();
    foreach ($list as &$e) $e['viewUrl'] = baseUrl() . '/view.html?id=' . $e['id'];
    out(true, ['items' => array_values($list)]);

  case 'get':   // public — used by view.html
    $id = preg_replace('/[^a-z0-9]/', '', $_GET['id'] ?? '');
    foreach (load() as $e) {
      if ($e['id'] === $id) {
        out(true, ['item' => [
          'id' => $e['id'], 'title' => $e['title'],
          'ratio' => $e['ratio'],
          'mind' => "data/$id/targets.mind", 'video' => "data/$id/video.mp4",
          'target' => "data/$id/target.jpg",
        ]]);
      }
    }
    out(false, ['error' => 'Experience not found']);

  case 'create':
    auth();
    if (empty($_FILES['mind']) || empty($_FILES['video']) || empty($_FILES['target']))
      out(false, ['error' => 'Target image, video and compiled target are all required']);
    if ($_FILES['video']['size'] > MAX_VIDEO_MB * 1024 * 1024)
      out(false, ['error' => "Video must be under " . MAX_VIDEO_MB . " MB"]);

    $id  = substr(bin2hex(random_bytes(6)), 0, 10);
    $dir = DATA_DIR . "/$id";
    mkdir($dir, 0755, true);
    move_uploaded_file($_FILES['target']['tmp_name'], "$dir/target.jpg");
    move_uploaded_file($_FILES['video']['tmp_name'],  "$dir/video.mp4");
    move_uploaded_file($_FILES['mind']['tmp_name'],   "$dir/targets.mind");

    $list = load();
    $list[] = [
      'id' => $id,
      'title' => trim(strip_tags($_POST['title'] ?? 'Untitled')),
      'ratio' => (float)($_POST['ratio'] ?? 1),   // target height/width
      'created' => date('c'),
    ];
    save($list);
    out(true, ['id' => $id, 'viewUrl' => baseUrl() . "/view.html?id=$id"]);

  case 'delete':
    auth();
    $id = preg_replace('/[^a-z0-9]/', '', $_POST['id'] ?? '');
    $list = array_values(array_filter(load(), fn($e) => $e['id'] !== $id));
    save($list);
    $dir = DATA_DIR . "/$id";
    if ($id && is_dir($dir)) { array_map('unlink', glob("$dir/*")); rmdir($dir); }
    out(true);

  default:
    out(false, ['error' => 'Unknown action']);
}
