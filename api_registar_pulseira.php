<?php
// Conexão à base de dados
$servername = "localhost";
$username = "root";
$password = "nova_password";
$dbname = "pap";

// Cria conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Checa conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Pega os dados do POST (JSON)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if(isset($data['bracelet_id'])) {
    $bracelet_id = $data['bracelet_id'];

    // Guarda na tabela "pulseiras"
    $sql = "INSERT INTO pulseiras (bracelet_id) VALUES ('$bracelet_id')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "sucesso", "id" => $bracelet_id]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "ID não enviado"]);
}

$conn->close();
?>
