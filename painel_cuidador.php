<?php

session_start();

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connection.php';

$cuidador_id = $_SESSION['cuidador_id'];
$cuidador_nome = $_SESSION['cuidador_nome'];

// Buscar foto atualizada do cuidador
$stmt_foto = $conn->prepare("SELECT foto FROM cuidadores WHERE id = ?");
$stmt_foto->bind_param("i", $cuidador_id);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$cuidador_data = $result_foto->fetch_assoc();

// Atualizar foto na sessão
$foto = $cuidador_data['foto'] ?? '';
$_SESSION['cuidador_foto'] = $foto;

// Se não houver foto, pegar a primeira letra do nome
$inicial = '';
if (empty($foto) && !empty($_SESSION['cuidador_nome'])) {
    $inicial = strtoupper(substr($_SESSION['cuidador_nome'], 0, 1));
}

// FUNÇÃO PARA OBTER UTENTES (SEM DUPLICADOS)
function obterUtentes($conn, $cuidador_id) {
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.nome,
            u.data_nascimento,
            u.peso,
            u.altura,
            p.id_unico_esp32,
            (SELECT GROUP_CONCAT(
                CONCAT(m2.nome, ' (', DATE_FORMAT(m2.data_hora, '%d/%m/%Y %H:%i'), ')')
                ORDER BY m2.data_hora
                SEPARATOR ' | '
            )
            FROM medicamentos m2
            WHERE m2.id_utente = u.id) AS medicamentos
        FROM utentes u
        LEFT JOIN pulseiras p ON u.id_pulseira = p.id
        WHERE u.id_cuidador = ?
        ORDER BY u.nome ASC
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cuidador - WOLM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== ESTILOS ESPECÍFICOS DO PAINEL ===== */
        .painel-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .painel-container h2 {
            color: #1b5e20;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            font-weight: 700;
        }

        /* Container de ações do painel */
        .acoes-painel {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        /* Container de ações na tabela */
        .acoes-utente {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* Botão Ver Localização - Dentro da Tabela */
        .btn-localizacao {
            display: inline-block;
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(33, 150, 243, 0.25);
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-localizacao:hover {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.35);
        }

        /* Botão Remover Utente */
        .btn-remover-utente {
            display: inline-block;
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(244, 67, 54, 0.25);
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-remover-utente:hover {
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.35);
        }

        /* Botão Adicionar Utente - AZUL */
        .btn-adicionar-utente {
            background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
            color: #ffffff;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 165, 245, 0.25);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            display: inline-block;
        }

        .btn-adicionar-utente:hover {
            background: linear-gradient(135deg, #1e88e5 0%, #1976d2 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 165, 245, 0.35);
        }

        /* Botão Imprimir - CINZENTO */
        .btn-imprimir {
            background: linear-gradient(135deg, #78909c 0%, #607d8b 100%);
            color: #ffffff;
            padding: 1rem 2rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(96, 125, 139, 0.25);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-imprimir:hover {
            background: linear-gradient(135deg, #607d8b 0%, #546e7a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(96, 125, 139, 0.35);
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .acoes-painel {
                flex-direction: column;
            }

            .btn-adicionar-utente,
            .btn-imprimir {
                width: 100%;
                text-align: center;
            }

            .acoes-utente {
                flex-direction: column;
            }

            .btn-localizacao,
            .btn-editar,
            .btn-remover-utente {
                width: 100%;
                text-align: center;
            }
        }

        /* Impressão */
        @media print {
            header, footer, .acoes-painel, .btn-localizacao, .btn-editar, .btn-remover-utente {
                display: none;
            }

            .tabela-container {
                box-shadow: none;
            }
        }
    </style>
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

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="perfil-sucesso">
            ✓ <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
        <div class="perfil-erro">
            ✗ <?php echo htmlspecialchars($_SESSION['erro']); ?>
        </div>
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

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
                    <?php if ($utente['id_unico_esp32']): ?>
                        <span class="pulseira-associada">
                            <?php echo htmlspecialchars($utente['id_unico_esp32']); ?>
                        </span>
                    <?php else: ?>
                        <span class="pulseira-nao-associada"
                              style="cursor:pointer; color:#d32f2f; font-weight:bold;"
                              onclick="associarPulseira(<?php echo $utente['id']; ?>)">
                            Associar
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="acoes-utente">
                        <!-- Botão de editar -->
                        <a href="editar_utente.php?id=<?php echo $utente['id']; ?>" class="btn-editar">
                            Editar
                        </a>
                        
                        <!-- Botão de remover -->
                        <a href="remover_utente.php?id=<?php echo $utente['id']; ?>" 
                           class="btn-remover-utente"
                           onclick="return confirm('Tem a certeza que deseja remover o utente <?php echo htmlspecialchars($utente['nome']); ?>? Esta ação não pode ser revertida.');">
                            Remover
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Botões de ação -->
<div class="acoes-painel">
    <!-- Botão Adicionar Novo Utente - AZUL -->
    <a href="criar_utente.php" class="btn-adicionar-utente">
        ➕ Adicionar Utente
    </a>
    
    <!-- Botão Imprimir - CINZENTO -->
    <button onclick="window.print()" class="btn-imprimir">
        🖨️ Imprimir Relatório
    </button>
</div>

<?php else: ?>
    
    <div class="sem-utentes-container">
        
        <div class="sem-utentes-icon">
            👥
        </div>
        
        <p class="sem-utentes-texto">
            Ainda não existem utentes associados à sua conta.
        </p>
        
        <a href="criar_utente.php" class="btn-criar-utente">
            ➕ Criar Primeiro Utente
        </a>
        
    </div>
    
<?php endif; ?>

</main>

<footer>
    <div class="footer-copyright">
        <p>&copy; 2025 WOLM - Todos os direitos reservados</p>
    </div>
</footer>

<script>
// Função para associar pulseira MANUALMENTE
async function associarPulseira(idUtente) {
    
    // Pedir ao cuidador para escrever o ID da pulseira
    const idPulseira = prompt(
        "📝 Digite o ID da pulseira\n\n" +
        "O ID está escrito num papel na pulseira.\n" +
        "Exemplo: AA:BB:CC:DD:EE:FF"
    );
    
    // Se cancelou ou não escreveu nada
    if (!idPulseira || idPulseira.trim() === '') {
        return;
    }
    
    // Limpar espaços e converter para maiúsculas
    const idLimpo = idPulseira.trim().toUpperCase();
    
    // Validar formato básico do MAC address
    const formatoMAC = /^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/;
    
    if (!formatoMAC.test(idLimpo)) {
        alert(
            "❌ Formato inválido!\n\n" +
            "O ID deve estar no formato:\n" +
            "AA:BB:CC:DD:EE:FF\n\n" +
            "Exemplo: 48:3F:DA:12:34:56"
        );
        return;
    }
    
    // Confirmar associação
    if (!confirm('Confirma associar a pulseira ' + idLimpo + '?')) {
        return;
    }
    
    try {
        // Enviar para o servidor
        const resposta = await fetch("associar_pulseira_manual.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                utente_id: idUtente,
                id_pulseira: idLimpo
            })
        });
        
        const dados = await resposta.json();
        
        // Mostrar resultado
        if (dados.status === "sucesso") {
            alert("✅ " + dados.mensagem);
            location.reload(); // Atualizar a página
        } else {
            alert("❌ " + dados.mensagem);
        }
        
    } catch (erro) {
        alert("❌ Erro ao associar pulseira. Tente novamente.");
        console.error(erro);
    }
}
</script>
</body>
</html>