<?php

// Verifica se veio por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    // Destinatário (onde vais receber)
    $para = "info@wolm.pt"; // muda se quiseres
    $assunto = "Novo contacto do site";

    $conteudo = "Nome: $nome\nEmail: $email\nMensagem:\n$mensagem";

    // Cabeçalhos
    $headers = "From: $email";

    // Tenta enviar
    if (mail($para, $assunto, $conteudo, $headers)) {
        echo "<h2>Email enviado com sucesso ✅</h2>";
        echo "<a href='index.html'>Voltar</a>";
    } else {
        echo "<h2>Erro ao enviar 😥</h2>";
        echo "<a href='contactos.html'>Tentar novamente</a>";
    }

} else {
    echo "Acesso inválido!";
}
