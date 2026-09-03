<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'novo_ministerio') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome do ministério.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO ministerios (nome, descricao) VALUES (?, ?)');
                $stmt->execute([$nome, trim($_POST['descricao'] ?? '') ?: null]);
                header('Location: ministerio.php?id=' . $pdo->lastInsertId());
                exit;
            } catch (PDOException $e) {
                $erro = 'Já existe um ministério com esse nome.';
            }
        }
    }
}

$ministerios = $pdo->query("
    SELECT m.id, m.nome, m.ativo, l.nome AS lider_nome,
        (SELECT COUNT(*) FROM membros_ministerios mm WHERE mm.ministerio_id = m.id AND mm.ativo = 1) AS total
    FROM ministerios m LEFT JOIN membros l ON l.id = m.lider_id
    ORDER BY m.nome
")->fetchAll();

$tituloPagina = 'Ministérios';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Ministérios</h1>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <table class="tabela-admin">
      <thead><tr><th>Ministério</th><th>Líder</th><th>Pessoas</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($ministerios as $m): ?>
        <tr<?= !$m['ativo'] ? ' style="opacity:.5;"' : '' ?>>
          <td><a href="ministerio.php?id=<?= (int)$m['id'] ?>"><?= escaparHtml($m['nome']) ?></a></td>
          <td><?= escaparHtml($m['lider_nome']) ?: '-' ?></td>
          <td><?= (int)$m['total'] ?></td>
          <td><a class="botao-mini" href="ministerio.php?id=<?= (int)$m['id'] ?>">Ver →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div>
    <span class="rotulo">Criar novo ministério</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="novo_ministerio">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome" required>
      </div>
      <div class="campo">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao"></textarea>
      </div>
      <button type="submit" class="botao-primario">Criar ministério</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
