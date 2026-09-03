<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirLogin();

$pdo = conectarBanco();
$id = membroAtualId();
$erro = '';
$sucesso = '';

$stmt = $pdo->prepare('SELECT * FROM membros WHERE id = ?');
$stmt->execute([$id]);
$membro = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'atualizar_perfil') {
        $erroFoto = null;
        $novaFoto = salvarFotoUpload($_FILES['foto'] ?? [], __DIR__ . '/uploads/perfil', 'membro' . $id, $erroFoto);
        if ($erroFoto) {
            $erro = $erroFoto;
        } else {
            $stmt = $pdo->prepare('UPDATE membros SET telefone=?' . ($novaFoto ? ', foto_caminho=?' : '') . ' WHERE id=?');
            $params = [trim($_POST['telefone'] ?? '') ?: null];
            if ($novaFoto) {
                $params[] = $novaFoto;
                $_SESSION['membro_foto'] = 'uploads/perfil/' . $novaFoto;
            }
            $params[] = $id;
            $stmt->execute($params);
            $sucesso = 'Perfil atualizado.';
        }
    } elseif (($_POST['acao'] ?? '') === 'trocar_senha') {
        $atual = $_POST['senha_atual'] ?? '';
        $nova = $_POST['senha_nova'] ?? '';
        if (!$membro['senha_hash'] || !password_verify($atual, $membro['senha_hash'])) {
            $erro = 'Senha atual incorreta.';
        } elseif (strlen($nova) < 6) {
            $erro = 'A nova senha precisa ter pelo menos 6 caracteres.';
        } else {
            $pdo->prepare('UPDATE membros SET senha_hash = ? WHERE id = ?')->execute([password_hash($nova, PASSWORD_DEFAULT), $id]);
            $sucesso = 'Senha alterada com sucesso.';
        }
    }
    $stmt = $pdo->prepare('SELECT * FROM membros WHERE id = ?');
    $stmt->execute([$id]);
    $membro = $stmt->fetch();
}

$tituloPagina = 'Meu Perfil';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Meu Perfil</h1>
<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">Dados básicos</span>
    <form method="post" enctype="multipart/form-data" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="atualizar_perfil">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label>Nome</label>
        <p style="color:var(--text-muted);">Peça a um admin em <a href="membros.php">Membros</a> para alterar seu nome.</p>
      </div>
      <div class="campo">
        <label for="foto">Foto de perfil</label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="campo">
        <label for="telefone">Telefone / WhatsApp</label>
        <input type="text" id="telefone" name="telefone" value="<?= escaparHtml($membro['telefone']) ?>">
      </div>
      <button type="submit" class="botao-primario">Salvar</button>
    </form>
  </div>

  <div>
    <span class="rotulo">Alterar senha</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="trocar_senha">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="senha_atual">Senha atual</label>
        <input type="password" id="senha_atual" name="senha_atual" required>
      </div>
      <div class="campo">
        <label for="senha_nova">Nova senha</label>
        <input type="password" id="senha_nova" name="senha_nova" required minlength="6">
      </div>
      <button type="submit" class="botao-primario">Alterar senha</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
