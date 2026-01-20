<?php
session_start();

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connection.php';

$cuidador_id = $_SESSION['cuidador_id'];
$cuidador_nome = $_SESSION['cuidador_nome'];

$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.nome,
        u.data_nascimento,
        p.id_unico_esp32
    FROM utentes u
    LEFT JOIN pulseiras p ON u.id_pulseira = p.id
    WHERE u.id_cuidador = ?
");
$stmt->bind_param("i", $cuidador_id);
$stmt->execute();
$utentes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel do Cuidador - WOLM</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WOLM</h1>
    <nav>
        <ul>
            <li><a href="painel.php" class="ativo">Painel</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main class="painel-container">
    <h2>Bem-vindo, <?php echo htmlspecialchars($cuidador_nome); ?></h2>

    <?php if ($utentes->num_rows > 0): ?>
        <?php while ($utente = $utentes->fetch_assoc()): ?>
            <div class="utente-card">
                <p><strong>Nome Utente:</strong><?php echo htmlspecialchars($utente['nome']); ?></p>
                <p><strong>Data de nascimento:</strong> <?php echo $utente['data_nascimento']; ?></p>

                <p><strong>Pulseira:</strong>
                    <?php
                    if ($utente['id_unico_esp32']) {
                        echo "Associada (" . htmlspecialchars($utente['id_unico_esp32']) . ")";
                    } else {
                        echo "Não associada";
                    }
                    ?>
                </p>

                <a href="medicamentos.php?id=<?php echo $utente['id']; ?>" class="botao">
                    Ver Medicamentos
                </a>

                <a href="localizacao.php?id=<?php echo $utente['id']; ?>" class="botao">
                    Ver Localização
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Não existem utentes associados.</p>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 WOLM</p>
</footer>

</body>
</html>
