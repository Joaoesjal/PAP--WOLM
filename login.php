<?php
session_start();
require_once 'db_connection.php';

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===== LOGIN ===== */
    if (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $erro = "Preencha todos os campos!";
        } else {
            $stmt = $conn->prepare("SELECT id, nome, password FROM cuidadores WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $hashGuardado = $row['password'];

                /* Password já encriptada */
                if (password_verify($password, $hashGuardado)) {

                    session_regenerate_id(true);
                    $_SESSION['cuidador_id'] = $row['id'];
                    $_SESSION['cuidador_nome'] = $row['nome'];

                    header("Location: painel_cuidador.php");
                    exit;
                }

                /* Password antiga (texto simples) */
                if ($password === $hashGuardado) {

                    // Encriptar automaticamente
                    $novoHash = password_hash($password, PASSWORD_DEFAULT);

                    $update = $conn->prepare(
                        "UPDATE cuidadores SET password = ? WHERE id = ?"
                    );
                    $update->bind_param("si", $novoHash, $row['id']);
                    $update->execute();

                    session_regenerate_id(true);
                    $_SESSION['cuidador_id'] = $row['id'];
                    $_SESSION['cuidador_nome'] = $row['nome'];

                    header("Location: painel_cuidador.php");
                    exit;
                }

                $erro = "Palavra-passe incorreta!";

            } else {
                $erro = "Email não encontrado!";
            }
            $stmt->close();
        }
    }

    /* ===== REGISTO ===== */
    if (isset($_POST['registar'])) {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $password_confirm = trim($_POST['password_confirm']);

        if (empty($nome) || empty($email) || empty($password) || empty($password_confirm)) {
            $erro = "Preencha todos os campos!";
        } elseif ($password !== $password_confirm) {
            $erro = "As passwords não coincidem!";
        } elseif (strlen($password) < 6) {
            $erro = "A password deve ter pelo menos 6 caracteres!";
        } else {
            // Verificar se email já existe
            $stmt = $conn->prepare("SELECT id FROM cuidadores WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $erro = "Este email já está registado!";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO cuidadores (nome, email, password) VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sss", $nome, $email, $hash);

                if ($stmt->execute()) {
                    $sucesso = "Conta criada com sucesso! Já pode entrar.";
                } else {
                    $erro = "Erro ao criar conta.";
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - WOLM</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    /* Ajuste para a página de login */
    .login-main {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        padding: 2rem;
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
    
    /* Label dentro do password-wrapper */
    .password-wrapper label {
        position: absolute;
        top: 50%;
        left: 1.2rem;
        transform: translateY(-50%);
        color: #757575;
        pointer-events: none;
        transition: all 0.3s ease;
        background: transparent;
        font-weight: 500;
    }
    
    /* Label flutuante quando input tem foco ou está preenchido */
    .password-wrapper input:focus + label,
    .password-wrapper input:not(:placeholder-shown) + label,
    .password-wrapper input:valid + label {
        top: -12px;
        left: 12px;
        font-size: 0.85rem;
        color: #66bb6a;
        background: #ffffff;
        padding: 0 8px;
        font-weight: 600;
        transform: translateY(0);
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
        z-index: 10;
    }
    
    .toggle-password:hover {
        color: #66bb6a;
    }
    
    .toggle-password svg {
        pointer-events: none;
    }
    
    /* Info de password */
    .password-info {
        display: block;
        color: #757575;
        font-size: 0.85rem;
        margin-top: 0.3rem;
    }
    
    /* Mensagem de coincidência */
    .password-match {
        display: block;
        margin-top: 0.3rem;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .password-match.success {
        color: #66bb6a;
    }
    
    .password-match.error {
        color: #c62828;
    }
    
    /* Mensagens de erro/sucesso */
    .alert-message {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 600;
    }
    
    .alert-error {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }
    
    .alert-success {
        background: #e8f5e9;
        color: #1b5e20;
        border-left: 4px solid #66bb6a;
    }
  </style>
</head>
<body>
  <header>
    <h1>WOLM</h1>
    <nav>
      <ul>
        <li><a href="index.html">Início</a></li>
        <li><a href="sobre.html">Sobre Nós</a></li>
        <li><a href="contactos.html">Contacte-nos</a></li>
        <li><a href="wolm1.html">Wolm1</a></li>
        <li><a href="login.php" class="ativo">Entrar</a></li>
      </ul>
    </nav>
  </header>

<main class="login-main">

  <section class="login-container">
    
    <!-- FORMULÁRIO DE LOGIN -->
    <div class="form-wrapper active" id="login-form">
      <h2>Bem-vindo à WOLM</h2>

      <?php if (!empty($erro) && !isset($_POST['registar'])): ?>
        <div class="alert-message alert-error">
          ✗ <?php echo htmlspecialchars($erro); ?>
        </div>
      <?php endif; ?>

      <form class="form-login" method="POST">
        <div class="input-box">
          <input type="email" name="email" required placeholder=" " />
          <label>Email</label>
        </div>

        <div class="input-box">
          <div class="password-wrapper">
            <input type="password" name="password" id="login-password" minlength="6" required placeholder=" " />
            <label>Palavra-passe</label>
            <span class="toggle-password" onclick="togglePassword('login-password', this)">
              <!-- OLHO ABERTO (Visível) -->
              <svg class="eye-icon" width="22" height="22" viewBox="0 0 576 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-1.5 0-3 .1-4.5 .2c5.3 10.5 8.5 22.4 8.5 35.1c0 44.2-35.8 80-80 80c-12.7 0-24.6-3.2-35.1-8.5c-.1 1.5-.2 3-.2 4.5c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"/>
              </svg>
              <!-- OLHO CORTADO (Escondida) - PADRÃO -->
              <svg class="eye-slash-icon" width="22" height="22" viewBox="0 0 640 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zm151 118.3C226 97.7 269.5 80 320 80c65.2 0 118.8 29.6 159.9 67.7C518.4 183.5 545 226 558.6 256c-12.6 28-36.6 66.8-70.9 100.9l-53.8-42.2c9.1-17.6 14.2-37.5 14.2-58.7c0-70.7-57.3-128-128-128c-32.2 0-61.7 11.9-84.2 31.5l-46.1-36.1zM394.9 284.2l-81.5-63.9c4.2-8.5 6.6-18.2 6.6-28.3c0-5.5-.7-10.9-2-16c.7 0 1.3 0 2 0c44.2 0 80 35.8 80 80c0 9.9-1.8 19.4-5.1 28.2zm51.3 163.3l-41.9-33C378.8 425.4 350.7 432 320 432c-65.2 0-118.8-29.6-159.9-67.7C121.6 328.5 95 286 81.4 256c8.3-18.4 21.5-41.5 39.4-64.8L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5zm-88-69.3L302 334c-23.5-5.4-43.1-21.2-53.7-42.3l-56.1-44.2c-.2 2.8-.3 5.6-.3 8.5c0 70.7 57.3 128 128 128c13.3 0 26.1-2 38.2-5.8z"/>
              </svg>
            </span>
          </div>
        </div>

        <button type="submit" name="login">Entrar</button>
      </form>

      <p style="text-align:center; margin-top:1.5rem;">
        Ainda não tem conta?
        <a href="#" onclick="mostrarRegisto(); return false;" style="color: #66bb6a; font-weight: 600;">Criar conta</a>
      </p>
    </div>

    <!-- FORMULÁRIO DE REGISTO -->
    <div class="form-wrapper" id="register-form">
      <h2>Criar Conta</h2>

      <?php if (!empty($erro) && isset($_POST['registar'])): ?>
        <div class="alert-message alert-error">
          ✗ <?php echo htmlspecialchars($erro); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($sucesso)): ?>
        <div class="alert-message alert-success">
          ✓ <?php echo htmlspecialchars($sucesso); ?>
        </div>
      <?php endif; ?>

      <form class="form-login" method="POST" id="registerForm">
        <div class="input-box">
          <input type="text" name="nome" required placeholder=" " />
          <label>Nome</label>
        </div>

        <div class="input-box">
          <input type="email" name="email" required placeholder=" " />
          <label>Email</label>
        </div>

        <div class="input-box">
          <div class="password-wrapper">
            <input type="password" name="password" id="register-password" minlength="6" required placeholder=" " />
            <label>Palavra-passe</label>
            <span class="toggle-password" onclick="togglePassword('register-password', this)">
              <!-- OLHO ABERTO (Visível) -->
              <svg class="eye-icon" width="22" height="22" viewBox="0 0 576 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-1.5 0-3 .1-4.5 .2c5.3 10.5 8.5 22.4 8.5 35.1c0 44.2-35.8 80-80 80c-12.7 0-24.6-3.2-35.1-8.5c-.1 1.5-.2 3-.2 4.5c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"/>
              </svg>
              <!-- OLHO CORTADO (Escondida) - PADRÃO -->
              <svg class="eye-slash-icon" width="22" height="22" viewBox="0 0 640 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zm151 118.3C226 97.7 269.5 80 320 80c65.2 0 118.8 29.6 159.9 67.7C518.4 183.5 545 226 558.6 256c-12.6 28-36.6 66.8-70.9 100.9l-53.8-42.2c9.1-17.6 14.2-37.5 14.2-58.7c0-70.7-57.3-128-128-128c-32.2 0-61.7 11.9-84.2 31.5l-46.1-36.1zM394.9 284.2l-81.5-63.9c4.2-8.5 6.6-18.2 6.6-28.3c0-5.5-.7-10.9-2-16c.7 0 1.3 0 2 0c44.2 0 80 35.8 80 80c0 9.9-1.8 19.4-5.1 28.2zm51.3 163.3l-41.9-33C378.8 425.4 350.7 432 320 432c-65.2 0-118.8-29.6-159.9-67.7C121.6 328.5 95 286 81.4 256c8.3-18.4 21.5-41.5 39.4-64.8L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5zm-88-69.3L302 334c-23.5-5.4-43.1-21.2-53.7-42.3l-56.1-44.2c-.2 2.8-.3 5.6-.3 8.5c0 70.7 57.3 128 128 128c13.3 0 26.1-2 38.2-5.8z"/>
              </svg>
            </span>
          </div>
          <small class="password-info">Mínimo de 6 caracteres</small>
        </div>

        <div class="input-box">
          <div class="password-wrapper">
            <input type="password" name="password_confirm" id="register-password-confirm" minlength="6" required placeholder=" " />
            <label>Confirmar Palavra-passe</label>
            <span class="toggle-password" onclick="togglePassword('register-password-confirm', this)">
              <!-- OLHO ABERTO (Visível) -->
              <svg class="eye-icon" width="22" height="22" viewBox="0 0 576 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-1.5 0-3 .1-4.5 .2c5.3 10.5 8.5 22.4 8.5 35.1c0 44.2-35.8 80-80 80c-12.7 0-24.6-3.2-35.1-8.5c-.1 1.5-.2 3-.2 4.5c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"/>
              </svg>
              <!-- OLHO CORTADO (Escondida) - PADRÃO -->
              <svg class="eye-slash-icon" width="22" height="22" viewBox="0 0 640 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zm151 118.3C226 97.7 269.5 80 320 80c65.2 0 118.8 29.6 159.9 67.7C518.4 183.5 545 226 558.6 256c-12.6 28-36.6 66.8-70.9 100.9l-53.8-42.2c9.1-17.6 14.2-37.5 14.2-58.7c0-70.7-57.3-128-128-128c-32.2 0-61.7 11.9-84.2 31.5l-46.1-36.1zM394.9 284.2l-81.5-63.9c4.2-8.5 6.6-18.2 6.6-28.3c0-5.5-.7-10.9-2-16c.7 0 1.3 0 2 0c44.2 0 80 35.8 80 80c0 9.9-1.8 19.4-5.1 28.2zm51.3 163.3l-41.9-33C378.8 425.4 350.7 432 320 432c-65.2 0-118.8-29.6-159.9-67.7C121.6 328.5 95 286 81.4 256c8.3-18.4 21.5-41.5 39.4-64.8L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5zm-88-69.3L302 334c-23.5-5.4-43.1-21.2-53.7-42.3l-56.1-44.2c-.2 2.8-.3 5.6-.3 8.5c0 70.7 57.3 128 128 128c13.3 0 26.1-2 38.2-5.8z"/>
              </svg>
            </span>
          </div>
          <small class="password-info">Mínimo de 6 caracteres</small>
          <small class="password-match" id="passwordMatch"></small>
        </div>

        <button type="submit" name="registar">Registar</button>
      </form>

      <p style="text-align:center; margin-top:1.5rem;">
        Já tem conta?
        <a href="#" onclick="mostrarLogin(); return false;" style="color: #66bb6a; font-weight: 600;">Entrar</a>
      </p>
    </div>
    
  </section>
</main>

<footer>
  <div class="footer-container">
    <!-- Marca e Descrição -->
    <div class="footer-brand">
      <h3>WOLM</h3>
      <p>A sua parceira em inovação e tecnologia para cuidados de saúde inteligentes. Cuidando de quem você ama com tecnologia de ponta.</p>
    </div>

    <!-- Links Rápidos -->
    <div class="footer-links">
      <h4>Links Rápidos</h4>
      <div class="links-column">
        <a href="index.html">Início</a>
        <a href="sobre.html">Sobre Nós</a>
        <a href="wolm1.html">WOLM1</a>
        <a href="contactos.html">Contactos</a>
        <a href="login.php">Entrar</a>
      </div>
    </div>

    <!-- Informações de Contato -->
    <div class="footer-contact">
      <h4>Contacto</h4>
      <p>info@wolm.pt</p>
      <p>+351 123 456 789</p>
    </div>
  </div>

  <!-- Copyright -->
  <div class="footer-copyright">
    <p>&copy; 2025 WOLM. Todos os direitos reservados.</p>
  </div>
</footer>

<script>
// Alternar entre Login e Registo
function mostrarRegisto() {
  document.getElementById("login-form").classList.remove("active");
  document.getElementById("register-form").classList.add("active");
}

function mostrarLogin() {
  document.getElementById("register-form").classList.remove("active");
  document.getElementById("login-form").classList.add("active");
}

// Função para mostrar/ocultar password
function togglePassword(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    const eyeIcon = toggleBtn.querySelector('.eye-icon');
    const eyeSlashIcon = toggleBtn.querySelector('.eye-slash-icon');
    
    if (input.type === 'password') {
        // Mudar para VISÍVEL
        input.type = 'text';
        eyeIcon.style.display = 'block';
        eyeSlashIcon.style.display = 'none';
    } else {
        // Mudar para ESCONDIDA
        input.type = 'password';
        eyeIcon.style.display = 'none';
        eyeSlashIcon.style.display = 'block';
    }
}

// Verificar se as passwords coincidem no registo
document.getElementById('register-password-confirm').addEventListener('input', function() {
    const password = document.getElementById('register-password').value;
    const confirm = this.value;
    const matchText = document.getElementById('passwordMatch');
    
    if (confirm.length === 0) {
        matchText.textContent = '';
        matchText.className = 'password-match';
        return;
    }
    
    if (password === confirm) {
        matchText.textContent = '✓ As passwords coincidem';
        matchText.className = 'password-match success';
    } else {
        matchText.textContent = '✗ As passwords não coincidem';
        matchText.className = 'password-match error';
    }
});

// Validação do formulário de registo
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('register-password').value;
    const confirm = document.getElementById('register-password-confirm').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('A password deve ter pelo menos 6 caracteres!');
        return false;
    }
    
    if (password !== confirm) {
        e.preventDefault();
        alert('As passwords não coincidem!');
        return false;
    }
});

// Se houve sucesso no registo, mostrar formulário de login após 2 segundos
<?php if (!empty($sucesso)): ?>
setTimeout(function() {
    mostrarLogin();
}, 2000);
<?php endif; ?>
</script>

</body>
</html>