<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= escaparHtml($tituloPagina ?? 'Painel de Colaboradores') ?> | CBP Sede</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="icon" href="../assets/images/favicon.png">
</head>
<body class="pagina-admin">

<div class="admin-topo">
  <div class="admin-topo-wrap">
    <a href="index.php" class="brand">
      <img class="logo-icon" src="../assets/images/logo.jpg" alt="Logo CBP Sede">
      <span class="name">CBP SEDE<small>Painel de Colaboradores</small></span>
    </a>
    <nav class="admin-nav">
      <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'ativo' : '' ?>">Início</a>
      <a href="membros.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['membros.php','membro.php']) ? 'ativo' : '' ?>">Membros</a>
      <a href="familias.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['familias.php','familia.php']) ? 'ativo' : '' ?>">Famílias</a>
      <a href="criancas.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['criancas.php','crianca.php']) ? 'ativo' : '' ?>">Crianças</a>
      <a href="ministerios.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['ministerios.php','ministerio.php']) ? 'ativo' : '' ?>">Ministérios</a>
      <a href="meu-perfil.php" class="<?= basename($_SERVER['PHP_SELF']) === 'meu-perfil.php' ? 'ativo' : '' ?>">
        <?php if (fotoExisteNoServidor(membroAtualFoto())): ?>
        <img src="<?= escaparHtml(membroAtualFoto()) ?>" alt="" class="avatar-mini">
        <?php else: ?>
        <span class="avatar-mini avatar-vazio"></span>
        <?php endif; ?>
        <?= escaparHtml(membroAtualNome()) ?>
      </a>
      <a href="logout.php">Sair</a>
    </nav>
    <button class="nav-toggle" id="admin-nav-toggle" aria-label="Abrir menu"><i class="fa-solid fa-bars"></i></button>
  </div>
</div>

<div class="admin-conteudo">
  <div class="admin-conteudo-wrap">
