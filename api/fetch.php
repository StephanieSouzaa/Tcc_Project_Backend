<?php
header("Content-Type: application/json");

// === DB CONFIG ===
$conn = new mysqli("localhost", "steph999_2026", "Euroino2026", "steph999_Tcc_Project");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$conn->set_charset("utf8mb4");

// === GET → FETCH ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $result = $conn->query(
        "SELECT id, timestamp, device_id, gpio_id, state
         FROM gpio_states
         ORDER BY timestamp DESC"
    );

    if (!$result) {
        http_response_code(500);
        echo json_encode(["error" => "Query failed"]);
        exit;
    }

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}

// === METHOD NOT ALLOWED ===
else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}

$conn->close();
?>