<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "nova_password";
$dbname = "pap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "erro", "mensagem" => "Falha na conexão"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if(isset($data['id_unico_esp32'])) {

    $id_unico = $data['id_unico_esp32'];

    // Verifica se já existe
    $check = $conn->prepare("SELECT id FROM pulseiras WHERE id_unico_esp32 = ?");
    $check->bind_param("s", $id_unico);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        echo json_encode([
            "status" => "existe",
            "mensagem" => "Pulseira já registada"
        ]);
    } else {

        $stmt = $conn->prepare("INSERT INTO pulseiras (id_unico_esp32) VALUES (?)");
        $stmt->bind_param("s", $id_unico);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "sucesso",
                "id_unico_esp32" => $id_unico
            ]);
        } else {
            echo json_encode([
                "status" => "erro",
                "mensagem" => "Erro ao inserir"
            ]);
        }

        $stmt->close();
    }

    $check->close();

} else {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "ID não enviado"
    ]);
}

$conn->close();
?>
