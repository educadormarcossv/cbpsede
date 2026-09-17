<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo = conectarBanco();
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$erro = '';
$sucesso = false;

$stmt = $pdo->prepare('SELECT id, nome, token_acesso_expira FROM membros WHERE token_acesso = ?');
$stmt->execute([$token]);
$membro = $stmt->fetch();

$valido = $membro && strtotime($membro['token_acesso_expira']) > time();

if ($valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    if (strlen($senha) < 6) {
        $erro = 'A senha precisa ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo->prepare('UPDATE membros SET senha_hash = ?, ativo = 1, token_acesso = NULL, token_acesso_expira = NULL WHERE id = ?')
            ->execute([password_hash($senha, PASSWORD_DEFAULT), $membro['id']]);
        $sucesso = true;
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Definir senha | Painel de Líderes CBP Sede</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="icon" href="../assets/images/favicon.png">
</head>
<body class="pagina-admin">
<div class="caixa-login">
  <div style="text-align:center;margin-bottom:24px;">
    <img src="../assets/images/logo.jpg" alt="CBP Sede" style="width:64px;height:64px;border-radius:50%;box-shadow:var(--shadow-sm);margin:0 auto 14px;">
    <h1 style="font-family:var(--font-heading);font-size:1.3rem;">Bem-vindo(a) ao Painel</h1>
    <p style="color:var(--text-muted);font-size:0.9rem;">Comunidade Batista da Paz</p>
  </div>

  <?php if (!$valido): ?>
    <div class="mensagem-erro">Este link é inválido ou já expirou. Peça um novo convite a um administrador.</div>
    <a href="login.php" class="botao-primario" style="margin-top:16px;display:inline-block;">Ir para o login →</a>

  <?php elseif ($sucesso): ?>
    <div class="mensagem-flash">Senha criada com sucesso, <?= escaparHtml($membro['nome']) ?>! Já pode entrar.</div>
    <a href="login.php" class="botao-primario" style="margin-top:16px;display:inline-block;">Ir para o login →</a>

  <?php else: ?>
    <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:16px;">Olá, <?= escaparHtml($membro['nome']) ?>! Crie sua senha de acesso ao painel.</p>
    <?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>
    <form method="post" class="formulario" style="max-width:none;">
      <input type="hidden" name="token" value="<?= escaparHtml($token) ?>">
      <div class="campo">
        <label for="senha">Nova senha (mínimo 6 caracteres)</label>
        <input type="password" id="senha" name="senha" required minlength="6">
      </div>
      <div class="campo">
        <label for="confirmar">Confirmar senha</label>
        <input type="password" id="confirmar" name="confirmar" required minlength="6">
      </div>
      <button type="submit" class="botao-primario" style="width:100%;justify-content:center;">Criar senha e continuar</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
