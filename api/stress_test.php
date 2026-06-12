<?php

header('Content-Type: application/json');

$config = require __DIR__ . '/mqtt_config.php';
require_once __DIR__ . '/phpMQTT.php';

$total = isset($_GET['total']) ? intval($_GET['total']) : 100;
$device = isset($_GET['device']) ? $_GET['device'] : 'device1';
$gpio = isset($_GET['gpio']) ? intval($_GET['gpio']) : 14;

$success = 0;
$failed = 0;

$client = new phpMQTT(
    $config['host'],
    $config['port'],
    $config['client_id']
);

$client->setCredentials(
    $config['username'],
    $config['password']
);

if (!$client->connect())
{
    http_response_code(500);

    echo json_encode([
        'error' => 'MQTT connect failed',
        'reason' => $client->getLastError()
    ]);

    exit;
}

$topic =
    $config['topic_prefix'] .
    $device .
    '/gpio/' .
    $gpio .
    '/set';

$start = microtime(true);

for ($i = 1; $i <= $total; $i++)
{
    $command = ($i % 2);

    $payload = json_encode([
        'command' => (string)$command,
        'message_id' => (string)$i
    ]);

    $ok = $client->publish(
        $topic,
        $payload,
        $config['qos'],
        $config['retain']
    );

    if ($ok)
    {
        $success++;
    }
    else
    {
        $failed++;
    }
}

$client->close();

$elapsed = round(
    microtime(true) - $start,
    3
);

echo json_encode([
    'status' => 'finished',
    'device' => $device,
    'gpio' => $gpio,
    'requested' => $total,
    'success' => $success,
    'failed' => $failed,
    'time_seconds' => $elapsed
], JSON_PRETTY_PRINT);