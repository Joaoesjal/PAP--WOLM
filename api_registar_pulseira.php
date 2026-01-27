<?php
$ligacao = new mysqli("localhost", "root", "", "wolm");

if ($ligacao->connect_error) {
    die("Erro na ligação");
}

$id_pulseira = $_POST['id_pulseira'];

$sql = "INSERT INTO pulseiras (id_pulseira) VALUES ('$id_pulseira')";

if ($ligacao->query($sql) === TRUE) {
    echo "Pulseira registada com sucesso";
} else {
    echo "Erro ao registar";
}
?>
