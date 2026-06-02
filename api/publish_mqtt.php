<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Use POST']);
    exit;
}

if (!isset($_POST['device_id'], $_POST['gpio_id'], $_POST['command'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing params']);
    exit;
}

$config = require __DIR__ . '/mqtt_config.php';
require_once __DIR__ . '/phpMQTT.php';

$device = $_POST['device_id'];
$gpio = $_POST['gpio_id'];
$command = $_POST['command'];

$client = new phpMQTT($config['host'], $config['port'], $config['client_id']);
$client->setCredentials($config['username'], $config['password']);

if (!$client->connect()) {
    $err = method_exists($client, 'getLastError') ? $client->getLastError() : null;
    http_response_code(500);
    echo json_encode(['error' => 'MQTT connect failed', 'reason' => $err]);
    exit;
}

$topic = $config['topic_prefix'] . $device . '/gpio/' . $gpio . '/set';

// Registrar log da mensagem no banco criando um registro "pending" para obter um ID sequencial
$db = new mysqli("localhost", "steph999_2026", "Euroino2026", "steph999_Tcc_Project");
if (!$db->connect_error) {
    $db->set_charset("utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS message_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id VARCHAR(255),
        direction VARCHAR(16),
        topic VARCHAR(512),
        payload TEXT,
        device_id VARCHAR(255),
        gpio_id INT,
        status VARCHAR(64),
        server_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // inserir registro pendente para obter id sequencial
    $stmt = $db->prepare("INSERT INTO message_log (direction, topic, payload, device_id, gpio_id, status) VALUES ('outgoing', ?, '', ?, ?, 'pending')");
    if ($stmt) {
        $stmt->bind_param('ssi', $topic, $device, $gpio);
        $stmt->execute();
        $log_id = $db->insert_id;
        $stmt->close();
    } else {
        $log_id = null;
    }
} else {
    $log_id = null;
}

// usar o id do log como message_id sequencial quando disponível
$message_id = $log_id ? (string)$log_id : uniqid('', true);

// payload: enviar como JSON com command e message_id para rastreabilidade
$payloadObj = [
    'command' => (string)$command,
    'message_id' => $message_id
];
$payload = json_encode($payloadObj);

$ok = $client->publish($topic, $payload, $config['qos'], $config['retain']);

$client->close();

// Atualizar log com payload e status
if (isset($db) && !$db->connect_error && isset($log_id) && $log_id) {
    $status = $ok ? 'sent' : 'failed';
    $upd = $db->prepare("UPDATE message_log SET message_id = ?, payload = ?, status = ? WHERE id = ?");
    if ($upd) {
        $upd->bind_param('sssi', $message_id, $payload, $status, $log_id);
        $upd->execute();
        $upd->close();
    }
    $db->close();
}

if ($ok) {
    echo json_encode([
        'status' => 'ok',
        'topic' => $topic,
        'payload' => $payload,
        'message_id' => $message_id
    ]);
} else {
    $err = method_exists($client, 'getLastError') ? $client->getLastError() : null;
    http_response_code(500);
    echo json_encode(['error' => 'Publish failed', 'reason' => $err, 'message_id' => $message_id]);
}