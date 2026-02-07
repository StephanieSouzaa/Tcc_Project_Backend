<?php
// Database credentials
$servername = "localhost";
$username   = "steph999_2026";
$password   = "Euroino2026";
$database   = "steph999_Tcc_Project";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Create table
$sql = "CREATE TABLE IF NOT EXISTS datapoints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    username VARCHAR(255) NOT NULL
)";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

// Insert data if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user'])) {

    $stmt = $conn->prepare(
        "INSERT INTO datapoints (username) VALUES (?)"
    );

    $stmt->bind_param("s", $_POST['user']);

    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error inserting data";
    }

    $stmt->close();
}

$conn->close();
?>
