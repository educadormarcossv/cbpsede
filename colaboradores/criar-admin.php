<?php
// Página de configuração inicial: cria o primeiro administrador do painel.
// Só funciona enquanto não existir NENHUM admin cadastrado - depois disso, ela se desativa sozinha.
// Depois de usar uma vez, pode apagar este arquivo do servidor (não é obrigatório, mas é mais limpo).
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo = conectarBanco();
$jaExisteAdmin = (int) $pdo->query("SELECT COUNT(*) FROM membros WHERE papel = 'admin' AND senha_hash IS NOT NULL")->fetchColumn() > 0;

$erro = '';
$sucesso = '';

if (!$jaExisteAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '' || strlen($senha) < 6) {
        $erro = 'Preencha nome, e-mail e uma senha com pelo menos 6 caracteres.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM membros WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Já existe um cadastro com este e-mail.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO membros (nome, email, senha_hash, papel, ativo) VALUES (?, ?, ?, 'admin', 1)");
            $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            $sucesso = 'Administrador criado! Já pode entrar no painel.';
            $jaExisteAdmin = true;
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
<title>Configuração inicial | Painel CBP Sede</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="pagina-admin">
<div class="caixa-login">
  <h1 style="font-family:var(--font-heading);font-size:1.3rem;margin-bottom:20px;">Configuração inicial</h1>

  <?php if ($jaExisteAdmin): ?>
    <?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
    <p style="color:var(--text-muted);font-size:0.9rem;">Já existe um administrador cadastrado. Por segurança, esta página está desativada.</p>
    <a href="login.php" class="botao-primario" style="margin-top:16px;display:inline-block;">Ir para o login →</a>
  <?php else: ?>
    <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:20px;">Crie a primeira conta de administrador do painel. Essa página só funciona uma vez.</p>
    <?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>
    <form method="post" class="formulario" style="max-width:none;">
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required>
      </div>
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="campo">
        <label for="senha">Senha (mínimo 6 caracteres)</label>
        <input type="password" id="senha" name="senha" required minlength="6">
      </div>
      <button type="submit" class="botao-primario" style="width:100%;justify-content:center;">Criar administrador</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
