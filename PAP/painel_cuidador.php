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
        u.peso,
        u.altura,
        p.id_unico_esp32,
        GROUP_CONCAT(
            CONCAT(m.nome, ' (', m.data_hora, ')')
            SEPARATOR ' | '
        ) AS medicamentos
    FROM utentes u
    LEFT JOIN pulseiras p ON u.id_pulseira = p.id
    LEFT JOIN medicamentos m ON m.id_utente = u.id
    WHERE u.id_cuidador = ?
    GROUP BY 
        u.id, 
        u.nome, 
        u.data_nascimento,
        u.peso,
        u.altura,
        p.id_unico_esp32
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
</header>

<main class="painel-container">
    <h2>Bem-vindo, <?php echo htmlspecialchars($cuidador_nome); ?></h2>

<?php if ($utentes->num_rows > 0): ?>
    <div class="tabela-container">
    <table class="tabela-utentes">
        <thead>
            <tr>
                <th>Nome do Utente</th>
                <th>Data de Nascimento</th>
                <th>Peso (kg)</th>
                <th>Altura (cm)</th>
                <th>Medicamentos</th>
                <th>Pulseira</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($utente = $utentes->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($utente['nome']); ?></td>
                <td><?php echo htmlspecialchars($utente['data_nascimento']); ?></td>
                <td><?php echo htmlspecialchars($utente['peso']); ?></td>
                <td><?php echo htmlspecialchars($utente['altura']); ?></td>
                <td>
                    <?php
                    if (!empty($utente['medicamentos'])) {
                        $lista = explode(' | ', $utente['medicamentos']);
                        echo '<ul>';
                        foreach ($lista as $med) {
                            echo '<li>' . htmlspecialchars($med) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo 'Nenhum medicamento registado';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ($utente['id_unico_esp32']) {
                        echo 'Associada (' . htmlspecialchars($utente['id_unico_esp32']) . ')';
                    } else {
                        echo 'Não associada';
                    }
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
// secção do código onde está o botão de imprimir PDF
?>
<button onclick="window.print()" class="btn-imprimir">Imprimir Relatório</button>

<?php else: ?>
    <p>Não existem utentes associados.</p>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 WOLM</p>
</footer>

</body>
</html>
