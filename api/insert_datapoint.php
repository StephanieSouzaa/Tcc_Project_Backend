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

// === POST → INSERT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['user']) || empty($_POST['user'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'user' parameter"]);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO datapoints (username) VALUES (?)"
    );
    $stmt->bind_param("s", $_POST['user']);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "ok",
            "message" => "Datapoint inserted",
            "id" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Insert failed"]);
    }

    $stmt->close();
}

// === GET → LIST ===
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $result = $conn->query(
        "SELECT id, username, timestamp
         FROM datapoints
         ORDER BY timestamp DESC"
    );

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}

// === DELETE → DELETE BY ID ===
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // DELETE não tem $_POST, vem pela URL
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'id' parameter"]);
        exit;
    }

    $stmt = $conn->prepare(
        "DELETE FROM datapoints WHERE id = ?"
    );
    $stmt->bind_param("i", $_GET['id']);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "ok",
            "message" => "Datapoint deleted"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Delete failed"]);
    }

    $stmt->close();
}

// === METHOD NOT ALLOWED ===
else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}

$conn->close();
