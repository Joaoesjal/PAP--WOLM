<?php
session_start();
header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['cuidador_id'])) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Sessão expirada. Por favor, faça login novamente.'
    ]);
    exit;
}

require_once 'db_connection.php';

$cuidador_id = $_SESSION['cuidador_id'];

// Ler dados JSON
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

$utente_id = $dados['utente_id'] ?? null;
$id_pulseira = $dados['id_pulseira'] ?? null;

// Validações
if (!$utente_id || !$id_pulseira) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Dados incompletos.'
    ]);
    exit;
}

// Limpar e converter para maiúsculas
$id_pulseira = strtoupper(trim($id_pulseira));

// Validar formato MAC address
if (!preg_match('/^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/', $id_pulseira)) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Formato de ID inválido. Use: AA:BB:CC:DD:EE:FF'
    ]);
    exit;
}

// Verificar se o utente pertence ao cuidador
$stmt = $conn->prepare("SELECT nome FROM utentes WHERE id = ? AND id_cuidador = ?");
$stmt->bind_param("ii", $utente_id, $cuidador_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Utente não encontrado.'
    ]);
    exit;
}

$utente = $result->fetch_assoc();
$nome_utente = $utente['nome'];

// Verificar se a pulseira já existe na base de dados
$stmt = $conn->prepare("SELECT id FROM pulseiras WHERE id_unico_esp32 = ?");
$stmt->bind_param("s", $id_pulseira);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Pulseira já existe - pegar o ID
    $pulseira = $result->fetch_assoc();
    $pulseira_id = $pulseira['id'];
    
    // Verificar se já está associada a outro utente
    $stmt = $conn->prepare("SELECT nome FROM utentes WHERE id_pulseira = ? AND id != ?");
    $stmt->bind_param("ii", $pulseira_id, $utente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $outro_utente = $result->fetch_assoc();
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Esta pulseira já está associada a: ' . $outro_utente['nome']
        ]);
        exit;
    }
    
} else {
    // Pulseira não existe - criar novo registo
    $stmt = $conn->prepare("INSERT INTO pulseiras (id_unico_esp32) VALUES (?)");
    $stmt->bind_param("s", $id_pulseira);
    
    if (!$stmt->execute()) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Erro ao registar pulseira.'
        ]);
        exit;
    }
    
    $pulseira_id = $conn->insert_id;
}

// Associar pulseira ao utente
$stmt = $conn->prepare("UPDATE utentes SET id_pulseira = ? WHERE id = ?");
$stmt->bind_param("ii", $pulseira_id, $utente_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => "Pulseira $id_pulseira associada a $nome_utente!"
    ]);
} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao associar pulseira.'
    ]);
}

$conn->close();
?>