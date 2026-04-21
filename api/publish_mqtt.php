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
    http_response_code(500);
    echo json_encode(['error' => 'MQTT connect failed']);
    exit;
}

$topic = $config['topic_prefix'] . $device . '/gpio/' . $gpio . '/set';

// payload simples (melhor pro ESP)
$payload = $command;

$ok = $client->publish($topic, $payload, $config['qos'], $config['retain']);

$client->close();

if ($ok) {
    echo json_encode([
        'status' => 'ok',
        'topic' => $topic,
        'payload' => $payload
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Publish failed']);
}