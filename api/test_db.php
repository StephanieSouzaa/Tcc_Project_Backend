<?php
$conn = new mysqli(
    "localhost",
    "steph999_2026",
    "Euroino2026",
    "steph999_Tcc_Project"
);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

echo "✅ Conectado ao banco com sucesso!";
$conn->close();
?>
