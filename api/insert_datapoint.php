<?php
// Database credentials
$servername = 'localhost';
$username = 'cpses_stl5ctav02';
$password = 'Euroino2026';
$database = 'steph999_Tcc_Project';

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Create table if it does not exist
$sql = "CREATE TABLE IF NOT EXISTS datapoints (
    ID INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo 'Table datapoints created successfully';
} else {
    echo 'Error creating table: ' . $conn->error;
}

// Insert data if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'];
    $insertSql = "INSERT INTO datapoints (user) VALUES ('$user')";
    if ($conn->query($insertSql) === TRUE) {
        echo 'New record created successfully';
    } else {
        echo 'Error: ' . $insertSql . '\n' . $conn->error;
    }
}

$conn->close();
?>