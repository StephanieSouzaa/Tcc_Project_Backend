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

// === CREATE TABLE IF NOT EXISTS ===
$tableQuery = "CREATE TABLE IF NOT EXISTS gpio_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME,
    device_id VARCHAR(255),
    gpio_id INT,
    state INT
)";
$conn->query($tableQuery);

// === POST → INSERT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['timestamp']) || !isset($_POST['device_id']) || !isset($_POST['gpio_id']) || !isset($_POST['state'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required parameters: timestamp, device_id, gpio_id, state"]);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO gpio_states (timestamp, device_id, gpio_id, state) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssii", $_POST['timestamp'], $_POST['device_id'], $_POST['gpio_id'], $_POST['state']);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "ok",
            "message" => "GPIO state inserted",
            "id" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Insert failed"]);
    }

    $stmt->close();
}

// === METHOD NOT ALLOWED ===
else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}

$conn->close();
?>