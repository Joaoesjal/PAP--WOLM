<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

$cuidador_id = $_SESSION['cuidador_id'];
$utente_id = $_GET['id'] ?? null;

if (!$utente_id) {
    exit("Utente não encontrado.");
}

// Buscar utente
$stmt = $conn->prepare(
    "SELECT * FROM utentes WHERE id = ? AND id_cuidador = ?"
);
$stmt->bind_param("ii", $utente_id, $cuidador_id);
$stmt->execute();
$result = $stmt->get_result();
$utente = $result->fetch_assoc();

if (!$utente) {
    exit("Utente não encontrado ou não pertence a este cuidador.");
}

// BUSCAR MEDICAMENTOS (AGORA NO SÍTIO CERTO)
$stmt = $conn->prepare(
    "SELECT nome, data_hora 
     FROM medicamentos 
     WHERE id_utente = ? 
     ORDER BY data_hora"
);
$stmt->bind_param("i", $utente_id);
$stmt->execute();
$res = $stmt->get_result();

$texto_medicamentos = '';
while ($row = $res->fetch_assoc()) {
    $texto_medicamentos .=
        $row['nome'] . ' ' .
        date('H:i', strtotime($row['data_hora'])) . "\n";
}

// POST (guardar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $data_nascimento = $_POST['data_nascimento'];
    $peso = $_POST['peso'];
    $altura = $_POST['altura'];

    // GUARDAR MEDICAMENTOS
if (isset($_POST['medicamentos'])) {

    // Apagar os medicamentos antigos
    $stmt = $conn->prepare("DELETE FROM medicamentos WHERE id_utente = ?");
    $stmt->bind_param("i", $utente_id);
    $stmt->execute();

    // Pega cada linha do textarea
    $linhas = explode("\n", trim($_POST['medicamentos']));

    foreach ($linhas as $linha) {
        if (trim($linha) === '') continue;

        // separa nome e hora (ex: Paracetamol 08:00)
        preg_match('/(.+)\s+(\d{2}:\d{2})/', $linha, $matches);
        if (!$matches) continue;

        $nome_med = trim($matches[1]);
        $hora = $matches[2];
        $data_hora = date('Y-m-d') . ' ' . $hora . ':00';

        $stmt = $conn->prepare(
            "INSERT INTO medicamentos (id_utente, nome, data_hora)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $utente_id, $nome_med, $data_hora);
        $stmt->execute();
    }
}

    // Atualizar utente
    $stmt = $conn->prepare(
        "UPDATE utentes 
         SET nome=?, data_nascimento=?, peso=?, altura=? 
         WHERE id=? AND id_cuidador=?"
    );
    $stmt->bind_param(
        "ssddii",
        $nome,
        $data_nascimento,
        $peso,
        $altura,
        $utente_id,
        $cuidador_id
    );
    $stmt->execute();

    header("Location: painel_cuidador.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Utente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WOLM</h1>
</header>

<main class="conteudo">
    <h2>Editar Utente</h2>

    <form method="POST" class="form-login">
        <div class="input-box">
            <input type="text" name="nome" value="<?php echo htmlspecialchars($utente['nome']); ?>" required>
            <label>Nome</label>
        </div>

        <div class="input-box">
            <input type="date" name="data_nascimento" value="<?php echo htmlspecialchars($utente['data_nascimento']); ?>" required>
            <label>Data de nascimento</label>
        </div>

        <div class="input-box">
            <input type="number" step="0.1" name="peso" value="<?php echo htmlspecialchars($utente['peso']); ?>" required>
            <label>Peso (kg)</label>
        </div>

        <div class="input-box">
            <input type="number" step="0.01" name="altura" value="<?php echo htmlspecialchars($utente['altura']); ?>" required>
            <label>Altura (cm)</label>
        </div>

        <div class="input-box">
            <input type="text" name="nome_medicamento">
            <label>Nome do medicamento</label>
        </div>

        <div class="input-box">
            <input type="date" name="data_medicamento">
            <label>Data</label>
        </div>

        <div class="input-box">
            <input type="time" name="hora_medicamento">
            <label>Hora</label>
        </div>
        
        <!-- Botão para Guardar as alterações -->
        <button type="submit" class="botao">Guardar Alterações</button>
    </form>

    <p style="margin-top:1rem;"><a href="painel_cuidador.php" class="voltar-btn">Voltar ao Painel</a></p>
</main>

</body>
</html>
