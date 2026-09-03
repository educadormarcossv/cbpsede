<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$erro = '';
$sucesso = ($_GET['criado'] ?? '') === '1' ? 'Membro cadastrado com sucesso.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'novo_membro') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome do membro.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO membros (nome, data_nascimento, telefone, endereco, bairro, cidade, estado, cep, membro_desde, batizado, observacoes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $nome,
                trim($_POST['data_nascimento'] ?? '') ?: null,
                trim($_POST['telefone'] ?? '') ?: null,
                trim($_POST['endereco'] ?? '') ?: null,
                trim($_POST['bairro'] ?? '') ?: null,
                trim($_POST['cidade'] ?? 'São Vicente') ?: null,
                trim($_POST['estado'] ?? 'SP') ?: null,
                trim($_POST['cep'] ?? '') ?: null,
                trim($_POST['membro_desde'] ?? '') ?: null,
                isset($_POST['batizado']) ? 1 : 0,
                trim($_POST['observacoes'] ?? '') ?: null,
            ]);
            header('Location: membro.php?id=' . $pdo->lastInsertId() . '&criado=1');
            exit;
        }
    }
}

$busca = trim($_GET['busca'] ?? '');
if ($busca !== '') {
    $stmt = $pdo->prepare("SELECT id, nome, telefone, bairro, papel, ativo FROM membros WHERE nome LIKE ? ORDER BY nome");
    $stmt->execute(['%' . $busca . '%']);
} else {
    $stmt = $pdo->query("SELECT id, nome, telefone, bairro, papel, ativo FROM membros ORDER BY nome");
}
$membros = $stmt->fetchAll();

$tituloPagina = 'Membros';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Membros</h1>
<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <form method="get" style="margin-bottom:16px;display:flex;gap:10px;">
      <input type="text" name="busca" placeholder="Buscar por nome..." value="<?= escaparHtml($busca) ?>" style="flex:1;padding:11px 14px;border:1.5px solid var(--border-color);border-radius:10px;">
      <button type="submit" class="botao-mini">Buscar</button>
    </form>
    <table class="tabela-admin">
      <thead><tr><th>Nome</th><th>Bairro</th><th>Papel</th><th></th></tr></thead>
      <tbody>
        <?php if (!$membros): ?>
        <tr><td colspan="4" style="color:var(--text-muted);">Nenhum membro encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($membros as $m): ?>
        <tr<?= !$m['ativo'] ? ' style="opacity:.5;"' : '' ?>>
          <td><a href="membro.php?id=<?= (int)$m['id'] ?>"><?= escaparHtml($m['nome']) ?></a></td>
          <td><?= escaparHtml($m['bairro']) ?: '-' ?></td>
          <td><?= escaparHtml(ucfirst($m['papel'])) ?></td>
          <td><a class="botao-mini" href="membro.php?id=<?= (int)$m['id'] ?>">Ver →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div>
    <span class="rotulo">Cadastrar novo membro</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="novo_membro">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome">Nome completo *</label>
        <input type="text" id="nome" name="nome" required>
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="data_nascimento">Data de nascimento</label>
          <input type="date" id="data_nascimento" name="data_nascimento">
        </div>
        <div class="campo">
          <label for="telefone">Telefone / WhatsApp</label>
          <input type="text" id="telefone" name="telefone" placeholder="Ex.: 13991234567">
        </div>
      </div>
      <div class="campo">
        <label for="endereco">Endereço</label>
        <input type="text" id="endereco" name="endereco">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="bairro">Bairro</label>
          <input type="text" id="bairro" name="bairro">
        </div>
        <div class="campo">
          <label for="membro_desde">Membro desde</label>
          <input type="date" id="membro_desde" name="membro_desde">
        </div>
      </div>
      <div class="campo">
        <label><input type="checkbox" name="batizado" value="1" style="width:auto;display:inline-block;margin-right:8px;">Batizado(a)</label>
      </div>
      <div class="campo">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes"></textarea>
      </div>
      <button type="submit" class="botao-primario">Cadastrar membro</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
