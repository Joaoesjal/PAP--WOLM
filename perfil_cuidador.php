<?php
session_start();

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connection.php';

$cuidador_id = $_SESSION['cuidador_id'];
$mensagem = "";
$erro = "";

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

    // Validações básicas
    if (empty($nome) || empty($email)) {
        $erro = "Nome e email são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } else {
        // Atualizar nome e email
        $stmt = $conn->prepare("UPDATE cuidadores SET nome = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nome, $email, $cuidador_id);
        
        if ($stmt->execute()) {
            $_SESSION['cuidador_nome'] = $nome;
            $mensagem = "Perfil atualizado com sucesso!";
        } else {
            $erro = "Erro ao atualizar perfil.";
        }

        /* Password */
        if (!empty($_POST['password'])) {
            if ($_POST['password'] !== $_POST['password_confirm']) {
                $erro = "As passwords não coincidem.";
                $mensagem = "";
            } elseif (strlen($_POST['password']) < 6) {
                $erro = "A password deve ter pelo menos 6 caracteres.";
                $mensagem = "";
            } else {
                $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE cuidadores SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hash, $cuidador_id);
                
                if ($stmt->execute()) {
                    $mensagem = "Perfil e password atualizados com sucesso!";
                } else {
                    $erro = "Erro ao atualizar password.";
                    $mensagem = "";
                }
            }
        }

        /* Upload da foto */
        if (!empty($_FILES['foto']['name'])) {
            $pasta = "uploads/";
            
            // Criar pasta se não existir
            if (!is_dir($pasta)) {
                mkdir($pasta, 0777, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            
            // Validar se é uma imagem pelo MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
            finfo_close($finfo);
            
            if (strpos($mime, 'image/') !== 0) {
                $erro = "Por favor, selecione um ficheiro de imagem válido.";
                $mensagem = "";
            } elseif ($_FILES['foto']['size'] > 5000000) { // 5MB
                $erro = "A imagem deve ter no máximo 5MB.";
                $mensagem = "";
            } else {
                $nome_foto = "cuidador_" . $cuidador_id . "_" . time() . "." . $ext;
                $caminho = $pasta . $nome_foto;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
                    // Apagar foto antiga
                    if (!empty($cuidador['foto']) && file_exists($cuidador['foto'])) {
                        unlink($cuidador['foto']);
                    }
                    
                    $stmt = $conn->prepare("UPDATE cuidadores SET foto = ? WHERE id = ?");
                    $stmt->bind_param("si", $caminho, $cuidador_id);
                    $stmt->execute();

                    $_SESSION['cuidador_foto'] = $caminho;
                    $mensagem = "Perfil atualizado com sucesso!";
                } else {
                    $erro = "Erro ao fazer upload da foto.";
                    $mensagem = "";
                }
            }
        }

        // Recarregar dados atualizados
        $stmt = $conn->prepare("SELECT nome, email, foto FROM cuidadores WHERE id = ?");
        $stmt->bind_param("i", $cuidador_id);
        $stmt->execute();
        $cuidador = $stmt->get_result()->fetch_assoc();
        $inicial = strtoupper(substr($cuidador['nome'], 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - WOLM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .preview-container {
            display: flex;
            justify-content: center;
            margin: 1rem 0;
        }
        
        .preview-foto {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #66bb6a;
            display: none;
        }
        
        .preview-foto.active {
            display: block;
        }
        
        /* Estilos para o toggle de password */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-wrapper input {
            flex: 1;
            padding-right: 45px !important;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #757575;
            user-select: none;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }
        
        .toggle-password:hover {
            color: #66bb6a;
        }
        
        .toggle-password svg {
            pointer-events: none;
        }
    </style>
</head>
<body>

<header>    
    <h1>WOLM</h1>
    <a href="painel_cuidador.php" class="voltar-btn">← Voltar ao Painel</a>
</header>

<main>
    <div class="perfil-container">
        <h2>✏️ Editar Perfil</h2>

        <?php if ($mensagem): ?>
            <div class="perfil-sucesso">
                ✓ <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="perfil-erro">
                ✗ <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="perfil-form" id="formPerfil">

            <!-- SEÇÃO: Foto de Perfil -->
            <div class="secao-form">
                <h3 class="titulo-secao">📷 Foto de Perfil</h3>
                
                <div class="perfil-avatar-section">
                    <div class="perfil-avatar">
                        <?php if (!empty($cuidador['foto'])): ?>
                            <img src="<?php echo htmlspecialchars($cuidador['foto']); ?>" alt="Foto de perfil" id="fotoAtual">
                        <?php else: ?>
                            <div class="avatar-inicial" id="avatarInicial"><?php echo $inicial; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Preview da nova foto -->
                    <div class="preview-container">
                        <img id="previewFoto" class="preview-foto" alt="Preview">
                    </div>
                    
                    <label for="foto" class="custom-file-upload">
                        📁 Escolher Nova Foto
                    </label>
                    <input type="file" name="foto" id="foto" accept="image/*">
                    <small style="display: block; text-align: center; margin-top: 0.5rem; color: #757575;">
                        Qualquer formato de imagem (máx. 5MB)
                    </small>
                </div>
            </div>

            <!-- SEÇÃO: Informações Pessoais -->
            <div class="secao-form">
                <h3 class="titulo-secao">👤 Informações Pessoais</h3>
                
                <div>
                    <label for="nome">Nome Completo *</label>
                    <input type="text" 
                           name="nome" 
                           id="nome" 
                           value="<?php echo htmlspecialchars($cuidador['nome']); ?>" 
                           required
                           minlength="3"
                           placeholder="Digite seu nome completo">
                </div>

                <div>
                    <label for="email">Email *</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="<?php echo htmlspecialchars($cuidador['email']); ?>" 
                           required
                           placeholder="seu@email.com">
                </div>
            </div>

            <!-- SEÇÃO: Segurança -->
            <div class="secao-form">
                <h3 class="titulo-secao">🔒 Alterar Password</h3>
                <p style="color: #757575; font-size: 0.9rem; margin-bottom: 1rem;">
                    Deixe em branco se não quiser alterar a password
                </p>
                
                <div>
                    <label for="password">Nova Password</label>
                    <div class="password-wrapper">
                        <input type="password" 
                               name="password" 
                               id="password"
                               minlength="6"
                               placeholder="Mínimo 6 caracteres">
                        <span class="toggle-password" onclick="togglePassword('password', this)">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12.5" r="3.5" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                <path d="M3 3L21 21M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5M12 9.5C13.933 9.5 15.5 11.067 15.5 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div>
                    <label for="password_confirm">Confirmar Nova Password</label>
                    <div class="password-wrapper">
                        <input type="password" 
                               name="password_confirm" 
                               id="password_confirm"
                               placeholder="Digite a password novamente">
                        <span class="toggle-password" onclick="togglePassword('password_confirm', this)">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12.5" r="3.5" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                <path d="M3 3L21 21M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5M12 9.5C13.933 9.5 15.5 11.067 15.5 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                    <small id="passwordMatch" style="display: none; margin-top: 0.3rem;"></small>
                </div>
            </div>

            <button type="submit" class="botao btn-large">
                💾 Guardar Alterações
            </button>
        </form>
    </div>
</main>

<script>
// Preview da foto
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('previewFoto');
    const fotoAtual = document.getElementById('fotoAtual');
    const avatarInicial = document.getElementById('avatarInicial');
    
    if (file) {
        // Validar tamanho
        if (file.size > 5000000) {
            alert('A imagem deve ter no máximo 5MB');
            this.value = '';
            return;
        }
        
        // Validar se é imagem
        if (!file.type.startsWith('image/')) {
            alert('Por favor, selecione um ficheiro de imagem válido');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.add('active');
            
            // Esconder foto/avatar atual
            if (fotoAtual) fotoAtual.style.display = 'none';
            if (avatarInicial) avatarInicial.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});

// Função para mostrar/ocultar password
function togglePassword(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    const eyeIcon = toggleBtn.querySelector('.eye-icon');
    const eyeSlashIcon = toggleBtn.querySelector('.eye-slash-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.style.display = 'none';
        eyeSlashIcon.style.display = 'block';
    } else {
        input.type = 'password';
        eyeIcon.style.display = 'block';
        eyeSlashIcon.style.display = 'none';
    }
}

// Verificar se as passwords coincidem
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    const matchText = document.getElementById('passwordMatch');
    
    if (confirm.length === 0) {
        matchText.style.display = 'none';
        return;
    }
    
    matchText.style.display = 'block';
    
    if (password === confirm) {
        matchText.textContent = '✓ As passwords coincidem';
        matchText.style.color = '#66bb6a';
    } else {
        matchText.textContent = '✗ As passwords não coincidem';
        matchText.style.color = '#c62828';
    }
});

// Validação do formulário
document.getElementById('formPerfil').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;
    
    // Se estiver a alterar password
    if (password.length > 0) {
        if (password.length < 6) {
            e.preventDefault();
            alert('A password deve ter pelo menos 6 caracteres');
            return false;
        }
        
        if (password !== confirm) {
            e.preventDefault();
            alert('As passwords não coincidem');
            return false;
        }
    }
    
    // Confirmação antes de submeter
    return confirm('Tem a certeza que deseja guardar as alterações?');
});
</script>

</body>
</html>