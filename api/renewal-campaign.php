<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========== CONFIG ==========
$exotel_sid = 'srisomesh';
$exotel_token = 'your_exotel_token_here';
$exotel_from = '+919440XXX';

// Use temporary data directory (more compatible)
$data_dir = '/tmp/renewal_campaign_' . md5(__DIR__);
@mkdir($data_dir, 0777, true);
$sqlite_db = $data_dir . '/renewal_campaign.db';

// ========== SQLITE DB INIT ==========
try {
    $pdo = new PDO('sqlite:' . $sqlite_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_THROW);
    
    // Create tables if not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devices (
            id INTEGER PRIMARY KEY,
            sim_number TEXT UNIQUE,
            vehicle_name TEXT,
            customer_name TEXT,
            customer_phone TEXT,
            expiry_date TEXT,
            server_name TEXT,
            device_status TEXT DEFAULT 'unknown',
            last_online TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS call_logs (
            id INTEGER PRIMARY KEY,
            sim_number TEXT,
            customer_phone TEXT,
            call_status TEXT,
            call_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            response TEXT
        )
    ");
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database init error', 'details' => $e->getMessage()]));
}

// ========== MOCK DEVICE DATA (For Testing) ==========
function getMockDevices() {
    return [
        [
            'sim' => '9191000000001',
            'vehicle' => 'Maruti Alto - AP47B2005',
            'customer' => 'Ramesh Logistics',
            'phone' => '9876543210',
            'expiry' => '2026-09-05',
            'server' => 'S1',
            'status' => 'online',
            'last_online' => '2026-08-29 14:32'
        ],
        [
            'sim' => '9191000000002',
            'vehicle' => 'Hyundai i10 - AP42C1234',
            'customer' => 'Krishna Transport',
            'phone' => '9765432109',
            'expiry' => '2026-09-12',
            'server' => 'S2',
            'status' => 'online',
            'last_online' => '2026-08-29 13:45'
        ],
        [
            'sim' => '9191000000003',
            'vehicle' => 'Tata Truck - TS28K3456',
            'customer' => 'Venkatesh Cargo',
            'phone' => '8765432109',
            'expiry' => '2026-09-08',
            'server' => 'S1',
            'status' => 'offline',
            'last_online' => '2026-08-28 09:15'
        ],
        [
            'sim' => '9191000000004',
            'vehicle' => 'Mahindra Bolero - AP14G5678',
            'customer' => 'Sai Exports Ltd',
            'phone' => '7654321098',
            'expiry' => '2026-09-10',
            'server' => 'S3',
            'status' => 'online',
            'last_online' => '2026-08-29 15:20'
        ],
        [
            'sim' => '9191000000005',
            'vehicle' => 'TATA ACE - TS09M7890',
            'customer' => 'Mohan Logistics',
            'phone' => '6543210987',
            'expiry' => '2026-09-15',
            'server' => 'S2',
            'status' => 'offline',
            'last_online' => '2026-08-25 11:30'
        ]
    ];
}

// ========== ENDPOINTS ==========

$action = $_GET['action'] ?? 'list';

switch ($action) {
    
    case 'list':
        listDevices();
        break;
        
    case 'trigger-call':
        triggerCall($_POST['sim'] ?? null);
        break;
        
    case 'get-call-logs':
        getCallLogs($_GET['sim'] ?? null);
        break;
        
    case 'sync-devices':
        syncDevices();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

// ========== FUNCTIONS ==========

function listDevices() {
    global $pdo;
    
    $filter_status = $_GET['status'] ?? null;  // 'online' or 'offline'
    $filter_days = $_GET['days'] ?? 7;  // Expires within X days
    
    $query = "SELECT * FROM devices WHERE 1=1";
    $params = [];
    
    if ($filter_status) {
        $query .= " AND device_status = ?";
        $params[] = $filter_status;
    }
    
    // Calculate expiry window (e.g., expires in next 10 days)
    $query .= " AND DATE(expiry_date) BETWEEN DATE('now') AND DATE('now', '+' || ? || ' days')";
    $params[] = $filter_days;
    
    $query .= " ORDER BY DATE(expiry_date) ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add derived fields
    foreach ($devices as &$device) {
        $device['days_left'] = intval((strtotime($device['expiry_date']) - time()) / 86400);
        $device['callable'] = ($device['device_status'] === 'online') ? 1 : 0;  // Only call online devices
    }
    
    echo json_encode(['devices' => $devices, 'total' => count($devices)]);
}

function triggerCall($sim) {
    global $pdo, $exotel_sid, $exotel_token, $exotel_from;
    
    if (!$sim) {
        http_response_code(400);
        echo json_encode(['error' => 'SIM number required']);
        return;
    }
    
    // Get device info
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE sim_number = ?");
    $stmt->execute([$sim]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found']);
        return;
    }
    
    if ($device['device_status'] !== 'online') {
        http_response_code(400);
        echo json_encode(['error' => 'Device is offline. Cannot call for offline devices.']);
        return;
    }
    
    // Prepare call message
    $customer_name = $device['customer_name'];
    $expiry_date = $device['expiry_date'];
    $vehicle = $device['vehicle_name'];
    
    // In production, use Sarvam AI TTS or Exotel's native TTS
    // For now, we'll prepare the call and log it
    $call_message = "Hello $customer_name, your GPS device for $vehicle expires on $expiry_date. Press 1 to renew now, press 2 to speak with an agent.";
    
    $customer_phone = $device['customer_phone'];
    
    // ===== EXOTEL CALL INTEGRATION =====
    // Exotel API: POST https://api.exotel.com/v1/Accounts/{SID}/Calls/connect.json
    $exotel_url = "https://api.exotel.com/v1/Accounts/$exotel_sid/Calls/connect.json";
    
    $call_data = [
        'From' => $exotel_from,
        'To' => $customer_phone,
        'CallerId' => $exotel_from,
        'Url' => 'http://somewhere.com/ivr_callback.php',  // IVR webhook (optional for now)
        'CallType' => 'trans'  // trans = transactional
    ];
    
    // For TEST: Log without actual API call
    $call_result = simulateExotelCall($call_data, $device);
    
    // Log the call attempt
    $stmt = $pdo->prepare("
        INSERT INTO call_logs (sim_number, customer_phone, call_status, response)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $sim,
        $customer_phone,
        $call_result['status'],
        json_encode($call_result['details'])
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Call triggered for {$device['customer_name']}",
        'device' => $device,
        'call_result' => $call_result
    ]);
}

function simulateExotelCall($call_data, $device) {
    // In test mode, simulate the call
    return [
        'status' => 'initiated',
        'details' => [
            'message' => "Outgoing call initiated to {$device['customer_phone']}",
            'customer' => $device['customer_name'],
            'vehicle' => $device['vehicle_name'],
            'expiry' => $device['expiry_date'],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
}

function getCallLogs($sim = null) {
    global $pdo;
    
    $query = "SELECT * FROM call_logs WHERE 1=1";
    $params = [];
    
    if ($sim) {
        $query .= " AND sim_number = ?";
        $params[] = $sim;
    }
    
    $query .= " ORDER BY call_time DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['logs' => $logs]);
}

function syncDevices() {
    global $pdo;
    
    try {
        // Sync from mock data (in production, fetch from BharatGPS servers)
        $mock_devices = getMockDevices();
        
        $count = 0;
        foreach ($mock_devices as $device) {
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO devices 
                (sim_number, vehicle_name, customer_name, customer_phone, expiry_date, server_name, device_status, last_online)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $device['sim'],
                $device['vehicle'],
                $device['customer'],
                $device['phone'],
                $device['expiry'],
                $device['server'],
                $device['status'],
                $device['last_online']
            ]);
            if ($result) $count++;
        }
        
        echo json_encode(['success' => true, 'synced' => $count, 'total' => count($mock_devices)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Sync failed', 'details' => $e->getMessage()]);
    }
}
