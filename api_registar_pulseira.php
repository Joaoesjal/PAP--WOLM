<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "nova_password", "pap");
if ($conn->connect_error) {
    echo json_encode(["status" => "erro", "mensagem" => "Falha na conexão"]);
    exit;
}

// Busca pulseiras que NÃO estão associadas a nenhum utente
$sql = "SELECT p.id, p.id_unico_esp32 
        FROM pulseiras p 
        LEFT JOIN utentes u ON u.id_pulseira = p.id 
        WHERE u.id IS NULL 
        ORDER BY p.id ASC 
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $pulseira = $result->fetch_assoc();
    
    echo json_encode([
        "status" => "sucesso",
        "pulseira_id" => $pulseira['id'],
        "id_unico_esp32" => $pulseira['id_unico_esp32']
    ]);
} else {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Nenhuma pulseira disponível. Registe uma nova pulseira primeiro."
    ]);
}

$conn->close();
?>