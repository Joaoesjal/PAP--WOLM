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
        <h2>Editar Perfil</h2>

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
                <h3 class="titulo-secao">Foto de Perfil</h3>
                
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
                        Escolher Nova Foto
                    </label>
                    <input type="file" name="foto" id="foto" accept="image/*">
                    <small style="display: block; text-align: center; margin-top: 0.5rem; color: #757575;">
                        Qualquer formato de imagem (máx. 5MB)
                    </small>
                </div>
            </div>

            <!-- SEÇÃO: Informações Pessoais -->
            <div class="secao-form">
                <h3 class="titulo-secao">Informações Pessoais</h3>
                
                <div>
                    <label for="nome">Nome Completo </label>
                    <input type="text" 
                           name="nome" 
                           id="nome" 
                           value="<?php echo htmlspecialchars($cuidador['nome']); ?>" 
                           required
                           minlength="3"
                           placeholder="Digite seu nome completo">
                </div>

                <div>
                    <label for="email">Email </label>
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
                <h3 class="titulo-secao">Alterar Password</h3>
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
                            <!-- OLHO ABERTO (Visível) - Font Awesome Style -->
                            <svg class="eye-icon" width="22" height="22" viewBox="0 0 576 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-1.5 0-3 .1-4.5 .2c5.3 10.5 8.5 22.4 8.5 35.1c0 44.2-35.8 80-80 80c-12.7 0-24.6-3.2-35.1-8.5c-.1 1.5-.2 3-.2 4.5c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"/>
                            </svg>
                            <!-- OLHO CORTADO (Escondida) - Font Awesome Style - PADRÃO -->
                            <svg class="eye-slash-icon" width="22" height="22" viewBox="0 0 640 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zm151 118.3C226 97.7 269.5 80 320 80c65.2 0 118.8 29.6 159.9 67.7C518.4 183.5 545 226 558.6 256c-12.6 28-36.6 66.8-70.9 100.9l-53.8-42.2c9.1-17.6 14.2-37.5 14.2-58.7c0-70.7-57.3-128-128-128c-32.2 0-61.7 11.9-84.2 31.5l-46.1-36.1zM394.9 284.2l-81.5-63.9c4.2-8.5 6.6-18.2 6.6-28.3c0-5.5-.7-10.9-2-16c.7 0 1.3 0 2 0c44.2 0 80 35.8 80 80c0 9.9-1.8 19.4-5.1 28.2zm51.3 163.3l-41.9-33C378.8 425.4 350.7 432 320 432c-65.2 0-118.8-29.6-159.9-67.7C121.6 328.5 95 286 81.4 256c8.3-18.4 21.5-41.5 39.4-64.8L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5zm-88-69.3L302 334c-23.5-5.4-43.1-21.2-53.7-42.3l-56.1-44.2c-.2 2.8-.3 5.6-.3 8.5c0 70.7 57.3 128 128 128c13.3 0 26.1-2 38.2-5.8z"/>
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
                            <!-- OLHO ABERTO (Visível) - Font Awesome Style -->
                            <svg class="eye-icon" width="22" height="22" viewBox="0 0 576 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-1.5 0-3 .1-4.5 .2c5.3 10.5 8.5 22.4 8.5 35.1c0 44.2-35.8 80-80 80c-12.7 0-24.6-3.2-35.1-8.5c-.1 1.5-.2 3-.2 4.5c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"/>
                            </svg>
                            <!-- OLHO CORTADO (Escondida) - Font Awesome Style - PADRÃO -->
                            <svg class="eye-slash-icon" width="22" height="22" viewBox="0 0 640 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zm151 118.3C226 97.7 269.5 80 320 80c65.2 0 118.8 29.6 159.9 67.7C518.4 183.5 545 226 558.6 256c-12.6 28-36.6 66.8-70.9 100.9l-53.8-42.2c9.1-17.6 14.2-37.5 14.2-58.7c0-70.7-57.3-128-128-128c-32.2 0-61.7 11.9-84.2 31.5l-46.1-36.1zM394.9 284.2l-81.5-63.9c4.2-8.5 6.6-18.2 6.6-28.3c0-5.5-.7-10.9-2-16c.7 0 1.3 0 2 0c44.2 0 80 35.8 80 80c0 9.9-1.8 19.4-5.1 28.2zm51.3 163.3l-41.9-33C378.8 425.4 350.7 432 320 432c-65.2 0-118.8-29.6-159.9-67.7C121.6 328.5 95 286 81.4 256c8.3-18.4 21.5-41.5 39.4-64.8L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5zm-88-69.3L302 334c-23.5-5.4-43.1-21.2-53.7-42.3l-56.1-44.2c-.2 2.8-.3 5.6-.3 8.5c0 70.7 57.3 128 128 128c13.3 0 26.1-2 38.2-5.8z"/>
                            </svg>
                        </span>
                    </div>
                    <small id="passwordMatch" style="display: none; margin-top: 0.3rem;"></small>
                </div>
            </div>

            <button type="submit" class="botao btn-large">
                Guardar Alterações
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
        // Mudar para VISÍVEL
        input.type = 'text';
        eyeIcon.style.display = 'block';        // Mostrar olho ABERTO
        eyeSlashIcon.style.display = 'none';    // Esconder olho CORTADO
    } else {
        // Mudar para ESCONDIDA
        input.type = 'password';
        eyeIcon.style.display = 'none';         // Esconder olho ABERTO
        eyeSlashIcon.style.display = 'block';   // Mostrar olho CORTADO
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