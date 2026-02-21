<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "nova_password", "pap");

if ($conn->connect_error) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro na ligação"]);
    exit;
}

// Ler JSON enviado pelo ESP32
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_unico_esp32'])) {
    echo json_encode(["status" => "erro", "mensagem" => "ID não recebido"]);
    exit;
}

$id_unico = $conn->real_escape_string($data['id_unico_esp32']);

// Verificar se já existe
$verifica = $conn->query("SELECT id FROM pulseiras WHERE id_unico_esp32 = '$id_unico'");

if ($verifica->num_rows > 0) {
    echo json_encode(["status" => "sucesso", "mensagem" => "Pulseira já registada"]);
} else {

    $sql = "INSERT INTO pulseiras (id_unico_esp32) VALUES ('$id_unico')";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "sucesso", "mensagem" => "Pulseira registada com sucesso"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao inserir"]);
    }
}

$conn->close();
?>
