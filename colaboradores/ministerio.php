<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$id = (int) ($_GET['id'] ?? 0);
$erro = '';
$sucesso = '';

$stmt = $pdo->prepare('SELECT * FROM ministerios WHERE id = ?');
$stmt->execute([$id]);
$ministerio = $stmt->fetch();
if (!$ministerio) {
    header('Location: ministerios.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'atualizar_ministerio') {
        $nome = trim($_POST['nome'] ?? '');
        $liderId = (int) ($_POST['lider_id'] ?? 0);
        if ($nome === '') {
            $erro = 'Informe o nome.';
        } else {
            $stmt = $pdo->prepare('UPDATE ministerios SET nome=?, descricao=?, lider_id=?, ativo=? WHERE id=?');
            $stmt->execute([$nome, trim($_POST['descricao'] ?? '') ?: null, $liderId ?: null, isset($_POST['ativo']) ? 1 : 0, $id]);
            $sucesso = 'Ministério atualizado.';
        }
    } elseif (($_POST['acao'] ?? '') === 'add_membro') {
        $membroId = (int) ($_POST['membro_id'] ?? 0);
        $funcao = trim($_POST['funcao'] ?? '') ?: 'Voluntário(a)';
        if ($membroId) {
            $stmt = $pdo->prepare('INSERT INTO membros_ministerios (membro_id, ministerio_id, funcao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE funcao = VALUES(funcao), ativo = 1');
            $stmt->execute([$membroId, $id, $funcao]);
            $sucesso = 'Pessoa adicionada.';
        }
    } elseif (($_POST['acao'] ?? '') === 'remover_membro') {
        $membroId = (int) ($_POST['membro_id'] ?? 0);
        $pdo->prepare('DELETE FROM membros_ministerios WHERE membro_id = ? AND ministerio_id = ?')->execute([$membroId, $id]);
        $sucesso = 'Pessoa removida.';
    }
    $stmt = $pdo->prepare('SELECT * FROM ministerios WHERE id = ?');
    $stmt->execute([$id]);
    $ministerio = $stmt->fetch();
}

$todosMembros = $pdo->query('SELECT id, nome FROM membros WHERE ativo = 1 ORDER BY nome')->fetchAll();

$stmt = $pdo->prepare('
    SELECT mm.membro_id, mm.funcao, m.nome
    FROM membros_ministerios mm JOIN membros m ON m.id = mm.membro_id
    WHERE mm.ministerio_id = ? AND mm.ativo = 1 ORDER BY m.nome
');
$stmt->execute([$id]);
$pessoas = $stmt->fetchAll();

$tituloPagina = $ministerio['nome'];
require __DIR__ . '/includes/cabecalho.php';
?>

<div class="trilha" style="margin-bottom:14px;"><a href="ministerios.php">Ministérios</a> / <?= escaparHtml($ministerio['nome']) ?></div>
<h1 class="admin-titulo"><?= escaparHtml($ministerio['nome']) ?></h1>

<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">Dados do ministério</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="atualizar_ministerio">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome" value="<?= escaparHtml($ministerio['nome']) ?>" required>
      </div>
      <div class="campo">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao"><?= escaparHtml($ministerio['descricao']) ?></textarea>
      </div>
      <div class="campo">
        <label for="lider_id">Líder</label>
        <select id="lider_id" name="lider_id">
          <option value="">Nenhum definido</option>
          <?php foreach ($todosMembros as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= $ministerio['lider_id']==$m['id']?'selected':'' ?>><?= escaparHtml($m['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label><input type="checkbox" name="ativo" value="1" <?= $ministerio['ativo']?'checked':'' ?> style="width:auto;display:inline-block;margin-right:8px;">Ministério ativo</label>
      </div>
      <button type="submit" class="botao-primario">Salvar alterações</button>
    </form>
  </div>

  <div>
    <span class="rotulo">Pessoas neste ministério (<?= count($pessoas) ?>)</span>
    <table class="tabela-admin" style="margin-top:14px;">
      <thead><tr><th>Nome</th><th>Função</th><th></th></tr></thead>
      <tbody>
        <?php if (!$pessoas): ?>
        <tr><td colspan="3" style="color:var(--text-muted);">Ninguém vinculado ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($pessoas as $p): ?>
        <tr>
          <td><a href="membro.php?id=<?= (int)$p['membro_id'] ?>"><?= escaparHtml($p['nome']) ?></a></td>
          <td><?= escaparHtml($p['funcao']) ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Remover deste ministério?');">
              <input type="hidden" name="acao" value="remover_membro">
              <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
              <input type="hidden" name="membro_id" value="<?= (int)$p['membro_id'] ?>">
              <button type="submit" class="botao-mini perigo">Remover</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <span class="rotulo" style="margin-top:24px;display:block;">Adicionar pessoa</span>
    <form method="post" class="formulario" style="margin-top:12px;max-width:none;">
      <input type="hidden" name="acao" value="add_membro">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="grade-campos">
        <div class="campo">
          <label for="membro_id">Membro</label>
          <select id="membro_id" name="membro_id">
            <?php foreach ($todosMembros as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= escaparHtml($m['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="funcao">Função</label>
          <input type="text" id="funcao" name="funcao" placeholder="Líder, voluntário(a)...">
        </div>
      </div>
      <button type="submit" class="botao-mini">Adicionar</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
