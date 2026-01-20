<?php
$url = $_GET['url'] ?? null;
$data = json_decode(file_get_contents($url), true);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalhe</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <a href="index.html" class="btn-voltar">←</a>
  <h1>WOLM</h1>
  <nav>
    <ul>
      <li><a href="index.html">Início</a></li>
      <li><a href="sobre.html">Sobre Nós</a></li>
      <li><a href="contactos.html">Contacte-nos</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="conteudo">
    <h2><?= ucfirst($data['name']) ?></h2>

    <img src="<?= $data['sprites']['front_default'] ?>" width="150" class="img-preview">

    <p><strong>Peso:</strong> <?= $data['weight'] ?></p>
    <p><strong>Altura:</strong> <?= $data['height'] ?></p>

    <h3>Tipos:</h3>
    <ul>
      <?php foreach($data['types'] as $t){ ?>
        <li><?= $t['type']['name'] ?></li>
      <?php } ?>
    </ul>
  </section>
</main>

<footer>
  <p>&copy; 2025 WOLM. Todos os direitos reservados.</p>
</footer>

</body>
</html>
