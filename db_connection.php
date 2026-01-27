<?php
$servername = "localhost";
$username = "root";
$password = "nova_password";
$dbname = "pap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>
