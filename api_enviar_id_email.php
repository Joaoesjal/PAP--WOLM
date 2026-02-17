<?php
session_start();

$conn = new mysqli("localhost", "root", "nova_password", "pap");
if ($conn->connect_error) {
    die("Falha na conexão");
}

// email do cuidador logado
$email = $_SESSION['email'] ?? null;

if (!$email) {
    die("Utilizador não autenticado.");
}

// MESMA QUERY que você já usa
$sql = "SELECT p.id, p.id_unico_esp32 
        FROM pulseiras p 
        LEFT JOIN utentes u ON u.id_pulseira = p.id 
        WHERE u.id IS NULL 
        ORDER BY p.id ASC 
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $pulseira = $result->fetch_assoc();
    $id_unico = $pulseira['id_unico_esp32'];

    // enviar email
    $assunto = "ID da sua pulseira";
    $mensagem = "Olá,\n\nO ID da pulseira disponível é:\n\n$id_unico\n\n"
              . "Insira este ID no utente correto no painel.";
    $headers = "From: sistema@pap.com";

    if (mail($email, $assunto, $mensagem, $headers)) {
        echo "ID enviado para o seu email com sucesso.";
    } else {
        echo "Erro ao enviar email.";
    }

} else {
    echo "Nenhuma pulseira disponível.";
}

$conn->close();
?>
