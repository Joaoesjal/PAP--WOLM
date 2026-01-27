<?php
session_start();

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connection.php';

$cuidador_id = $_SESSION['cuidador_id'];
$mensagem = "";

/* Buscar dados */
$stmt = $conn->prepare("SELECT nome, email, foto FROM cuidadores WHERE id = ?");
$stmt->bind_param("i", $cuidador_id);
$stmt->execute();
$cuidador = $stmt->get_result()->fetch_assoc();

/* Inicial */
$inicial = strtoupper(substr($cuidador['nome'], 0, 1));

/* Processar formulário */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE cuidadores SET nome = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nome, $email, $cuidador_id);
    $stmt->execute();

    $_SESSION['cuidador_nome'] = $nome;

    /* Password */
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE cuidadores SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $cuidador_id);
        $stmt->execute();
    }

    /* Upload da foto */
    if (!empty($_FILES['foto']['name'])) {
        $pasta = "uploads/";
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $nome_foto = "cuidador_" . $cuidador_id . "." . $ext;
        $caminho = $pasta . $nome_foto;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
            $stmt = $conn->prepare("UPDATE cuidadores SET foto = ? WHERE id = ?");
            $stmt->bind_param("si", $caminho, $cuidador_id);
            $stmt->execute();

            $_SESSION['cuidador_foto'] = $caminho;
        }
    }

    $mensagem = "Perfil atualizado com sucesso!";

    $stmt = $conn->prepare("SELECT nome, email, foto FROM cuidadores WHERE id = ?");
    $stmt->bind_param("i", $cuidador_id);
    $stmt->execute();
    $cuidador = $stmt->get_result()->fetch_assoc();

}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WOLM</h1>
    <a href="painel_cuidador.php" class="voltar-btn">← Voltar</a>
</header>

<main class="perfil-container">
    <h2>Editar Perfil</h2>

    <?php if ($mensagem): ?>
        <p class="perfil-sucesso"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="perfil-form">

        <div class="perfil-avatar">
            <?php if (!empty($cuidador['foto'])): ?>
                <img src="<?php echo htmlspecialchars($cuidador['foto']); ?>">
            <?php else: ?>
                <div class="avatar-inicial"><?php echo $inicial; ?></div>
            <?php endif; ?>
        </div>

        <label for="foto" class="custom-file-upload">Escolher foto</label>
        <input type="file" name="foto" id="foto" accept="image/*">

        <label for="nome">Nome</label>
        <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($cuidador['nome']); ?>" required>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($cuidador['email']); ?>" required>

        <label for="password">Nova Password</label>
        <input type="password" name="password" id="password">

        <button type="submit" class="botao">Guardar</button>
    </form>
</main>
</body>
</html>
