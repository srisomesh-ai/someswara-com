<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ========== CONFIG ==========
$data_dir = sys_get_temp_dir() . '/renewal_campaign';
@mkdir($data_dir, 0777, true);
$devices_file = $data_dir . '/devices.json';
$calls_file = $data_dir . '/calls.json';

// ========== MOCK DEVICE DATA ==========
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

// ========== HELPER FUNCTIONS ==========

function readDevices() {
    global $devices_file;
    if (file_exists($devices_file)) {
        $json = file_get_contents($devices_file);
        return json_decode($json, true) ?? [];
    }
    return [];
}

function writeDevices($devices) {
    global $devices_file;
    $json = json_encode($devices, JSON_PRETTY_PRINT);
    if (file_put_contents($devices_file, $json) === false) {
        throw new Exception("Cannot write devices file");
    }
}

function readCalls() {
    global $calls_file;
    if (file_exists($calls_file)) {
        $json = file_get_contents($calls_file);
        return json_decode($json, true) ?? [];
    }
    return [];
}

function writeCalls($calls) {
    global $calls_file;
    $json = json_encode($calls, JSON_PRETTY_PRINT);
    if (file_put_contents($calls_file, $json) === false) {
        throw new Exception("Cannot write calls file");
    }
}

// ========== ENDPOINT HANDLERS ==========

function listDevices() {
    $filter_status = $_GET['status'] ?? null;
    $filter_days = intval($_GET['days'] ?? 7);
    
    $devices = readDevices();
    
    // Filter by expiry date
    $filtered = [];
    $now = time();
    $expiry_limit = $now + ($filter_days * 86400);
    
    foreach ($devices as $device) {
        $expiry_time = strtotime($device['expiry_date']);
        if ($expiry_time < $expiry_limit && $expiry_time > $now) {
            // Check status filter
            if ($filter_status && $device['device_status'] !== $filter_status) {
                continue;
            }
            
            // Add derived fields
            $device['days_left'] = intval(($expiry_time - $now) / 86400);
            $device['callable'] = ($device['device_status'] === 'online') ? 1 : 0;
            
            $filtered[] = $device;
        }
    }
    
    // Sort by expiry date
    usort($filtered, function($a, $b) {
        return strtotime($a['expiry_date']) - strtotime($b['expiry_date']);
    });
    
    echo json_encode([
        'devices' => $filtered,
        'total' => count($filtered),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function triggerCall($sim) {
    if (!$sim) {
        http_response_code(400);
        echo json_encode(['error' => 'SIM number required']);
        return;
    }
    
    $devices = readDevices();
    $device = null;
    
    foreach ($devices as $d) {
        if ($d['sim_number'] === $sim) {
            $device = $d;
            break;
        }
    }
    
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
    
    // Log the call
    $call_log = [
        'sim_number' => $sim,
        'customer_phone' => $device['customer_phone'],
        'call_status' => 'initiated',
        'call_time' => date('Y-m-d H:i:s'),
        'response' => json_encode([
            'message' => "Outgoing call initiated to {$device['customer_phone']}",
            'customer' => $device['customer_name'],
            'vehicle' => $device['vehicle_name'],
            'expiry' => $device['expiry_date']
        ])
    ];
    
    $calls = readCalls();
    $calls[] = $call_log;
    writeCalls($calls);
    
    echo json_encode([
        'success' => true,
        'message' => "Call triggered for {$device['customer_name']}",
        'device' => $device,
        'call_log' => $call_log
    ]);
}

function getCallLogs($sim = null) {
    $calls = readCalls();
    
    if ($sim) {
        $calls = array_filter($calls, function($log) use ($sim) {
            return $log['sim_number'] === $sim;
        });
    }
    
    // Sort by date descending
    usort($calls, function($a, $b) {
        return strtotime($b['call_time']) - strtotime($a['call_time']);
    });
    
    // Limit to 50
    $calls = array_slice($calls, 0, 50);
    
    echo json_encode(['logs' => $calls]);
}

function syncDevices() {
    try {
        $mock_devices = getMockDevices();
        
        $devices = [];
        foreach ($mock_devices as $device) {
            $devices[] = [
                'sim_number' => $device['sim'],
                'vehicle_name' => $device['vehicle'],
                'customer_name' => $device['customer'],
                'customer_phone' => $device['phone'],
                'expiry_date' => $device['expiry'],
                'server_name' => $device['server'],
                'device_status' => $device['status'],
                'last_online' => $device['last_online'],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        writeDevices($devices);
        
        echo json_encode([
            'success' => true,
            'synced' => count($devices),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function showDiagnostics() {
    global $data_dir;
    
    echo json_encode([
        'php_version' => phpversion(),
        'temp_dir' => sys_get_temp_dir(),
        'data_dir' => $data_dir,
        'data_dir_exists' => is_dir($data_dir),
        'data_dir_writable' => is_writable($data_dir),
        'json_support' => extension_loaded('json') ? 'YES' : 'NO',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// ========== MAIN ROUTER ==========

try {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'diagnostics':
            showDiagnostics();
            break;
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
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>
