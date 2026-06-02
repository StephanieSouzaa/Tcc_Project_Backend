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

// garantir coluna message_id
$checkMsgCol = $conn->query("SHOW COLUMNS FROM gpio_states LIKE 'message_id'");
if ($checkMsgCol->num_rows == 0) {
    $conn->query("ALTER TABLE gpio_states ADD COLUMN message_id VARCHAR(255) DEFAULT NULL");
}

// === CHECK AND ADD SERVER_TIMESTAMP COLUMN ===
$checkColumn = $conn->query("SHOW COLUMNS FROM gpio_states LIKE 'server_timestamp'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE gpio_states ADD COLUMN server_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP");
}

// === POST → INSERT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['timestamp']) || !isset($_POST['device_id']) || !isset($_POST['gpio_id']) || !isset($_POST['state'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required parameters: timestamp, device_id, gpio_id, state"]);
        exit;
    }

    $message_id = isset($_POST['message_id']) ? $_POST['message_id'] : null;

    $stmt = $conn->prepare(
        "INSERT INTO gpio_states (timestamp, device_id, gpio_id, state, message_id) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssiis", $_POST['timestamp'], $_POST['device_id'], $_POST['gpio_id'], $_POST['state'], $message_id);

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

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['device_id']) || !isset($_GET['gpio_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing parameters: device_id, gpio_id"]);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT * FROM gpio_states
         WHERE device_id = ? AND gpio_id = ?
         ORDER BY server_timestamp DESC
         LIMIT 1"
    );

    $stmt->bind_param("si", $_GET['device_id'], $_GET['gpio_id']);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["message" => "No data found"]);
    }

    $stmt->close();
}

$conn->close();
?>