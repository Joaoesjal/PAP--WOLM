<?php

session_start();

$foto = $_SESSION['cuidador_foto'] ?? '';

// Se não houver foto, pegar a primeira letra do nome
$inicial = '';
if (empty($foto) && !empty($_SESSION['cuidador_nome'])) {
    $inicial = strtoupper(substr($_SESSION['cuidador_nome'], 0, 1));
}


if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connection.php';

$cuidador_id = $_SESSION['cuidador_id'];
$cuidador_nome = $_SESSION['cuidador_nome'];

// FUNÇÃO PARA OBTER UTENTES
function obterUtentes($conn, $cuidador_id) {
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
    return $stmt->get_result();
}

// CHAMADA DA FUNÇÃO
$utentes = obterUtentes($conn, $cuidador_id);

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
    <ul class="menu-principal">
        <li><a href="painel_cuidador.php" class="ativo">Painel cuidador</a></li>

        <!-- Avatar com submenu -->
       <li class="perfil-menu">
    <div class="avatar-wrapper" tabindex="0">
        <?php if (!empty($foto)): ?>
            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto de Perfil" class="foto-perfil">
        <?php else: ?>
            <div class="avatar-inicial"><?php echo $inicial; ?></div>
        <?php endif; ?>
    </div>

    <ul class="submenu">
        <li><a href="perfil_cuidador.php">Editar Perfil</a></li>
        <li><a href="logout.php">Sair</a></li>
    </ul>
</li>

    </ul>
</nav>

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
                <th>Ações</th>
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
                <td>
                    <!-- Botão de editar -->
                    <a href="editar_utente.php?id=<?php echo $utente['id']; ?>" class="btn-editar">Editar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<!-- Botão de imprimir -->
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