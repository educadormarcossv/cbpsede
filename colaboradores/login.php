<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $pdo = conectarBanco();
        $stmt = $pdo->prepare('SELECT id, nome, senha_hash, papel, foto_caminho, ativo FROM membros WHERE email = ?');
        $stmt->execute([$email]);
        $membro = $stmt->fetch();

        if (!$membro || !$membro['senha_hash'] || !password_verify($senha, $membro['senha_hash'])) {
            $erro = 'E-mail ou senha incorretos.';
        } elseif (!$membro['ativo']) {
            $erro = 'Este acesso está desativado. Fale com a administração.';
        } else {
            session_regenerate_id(true);
            $_SESSION['membro_id'] = (int) $membro['id'];
            $_SESSION['membro_nome'] = $membro['nome'];
            $_SESSION['membro_papel'] = $membro['papel'];
            $_SESSION['membro_foto'] = $membro['foto_caminho'] ? 'uploads/perfil/' . $membro['foto_caminho'] : null;
            $_SESSION['ultimo_acesso'] = time();
            $pdo->prepare('UPDATE membros SET ultimo_acesso = NOW() WHERE id = ?')->execute([$membro['id']]);
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Entrar | Painel CBP Sede</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="icon" href="../assets/images/favicon.png">
</head>
<body class="pagina-admin">
<div class="caixa-login">
  <div style="text-align:center;margin-bottom:24px;">
    <img src="../assets/images/logo.jpg" alt="CBP Sede" style="width:64px;height:64px;border-radius:50%;box-shadow:var(--shadow-sm);margin:0 auto 14px;">
    <h1 style="font-family:var(--font-heading);font-size:1.3rem;">Painel de Colaboradores</h1>
    <p style="color:var(--text-muted);font-size:0.9rem;">Comunidade Batista da Paz</p>
  </div>
  <?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>
  <form method="post" class="formulario" style="max-width:none;">
    <div class="campo">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required autofocus>
    </div>
    <div class="campo">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" required>
    </div>
    <button type="submit" class="botao-primario" style="width:100%;justify-content:center;">Entrar</button>
  </form>
</div>
</body>
</html>
