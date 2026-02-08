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

        if (empty($nome) || empty($email) || empty($password)) {
            $erro = "Preencha todos os campos!";
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
    <div class="form-wrapper active" id="login-form">
  <h2>Bem-vindo à WOLM</h2>

  <form class="form-login" method="POST">
    <div class="input-box">
      <input type="email" name="email" required />

      <label>Email</label>
    </div>

    <div class="input-box">
      <input type="password" name="password" minlength="6" required />
      <label>Palavra-passe</label>
    </div>

    <!-- Entrar botão -->
    <button type="submit" name="login">Entrar</button>
  </form>

  <p style="text-align:center; margin-top:1.5rem;">
    Ainda não tem conta?

    <!-- Criar Conta botão -->
    <a href="#" onclick="mostrarRegisto()">Criar conta</a>
  </p>
</div>

<div class="form-wrapper" id="register-form">
  <h2>Criar Conta</h2>

  <form class="form-login" method="POST">
    <div class="input-box">
      <input type="text" name="nome" required />
      <label>Nome</label>
    </div>

    <div class="input-box">
      <input type="email" name="email" required />
      <label>Email</label>
    </div>

    <div class="input-box">
      <input type="password" name="password" required />
      <label>Palavra-passe</label>
      <small class="password-info">Mínimo de 6 caracteres</small>
    </div>

    <button type="submit" name="registar">Registar</button>
  </form>

  <p style="text-align:center; margin-top:1.5rem;">
    Já tem conta?
    <a href="#" onclick="mostrarLogin()">Entrar</a>
  </p>
    </div>
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
function mostrarRegisto() {
  document.getElementById("login-form").classList.remove("active");
  document.getElementById("register-form").classList.add("active");
}

function mostrarLogin() {
  document.getElementById("register-form").classList.remove("active");
  document.getElementById("login-form").classList.add("active");
}
</script>
</body>
</html>
