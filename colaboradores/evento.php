<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$id = (int) ($_GET['id'] ?? 0);
$erro = '';
$sucesso = '';

$stmt = $pdo->prepare('SELECT e.*, m.nome AS criado_por_nome FROM eventos e LEFT JOIN membros m ON m.id = e.criado_por WHERE e.id = ?');
$stmt->execute([$id]);
$evento = $stmt->fetch();
if (!$evento) {
    header('Location: agenda.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'atualizar_evento') {
        $titulo = trim($_POST['titulo'] ?? '');
        $dataEvento = trim($_POST['data_evento'] ?? '');
        if ($titulo === '' || $dataEvento === '') {
            $erro = 'Informe ao menos o título e a data do evento.';
        } else {
            $stmt = $pdo->prepare('
                UPDATE eventos SET titulo=?, descricao=?, data_evento=?, hora_evento=?, local=?, categoria=? WHERE id=?
            ');
            $stmt->execute([
                $titulo,
                trim($_POST['descricao'] ?? '') ?: null,
                $dataEvento,
                trim($_POST['hora_evento'] ?? '') ?: null,
                trim($_POST['local'] ?? '') ?: null,
                trim($_POST['categoria'] ?? '') ?: null,
                $id,
            ]);
            $sucesso = 'Evento atualizado.';
        }
    } elseif (($_POST['acao'] ?? '') === 'excluir_evento' && ehAdmin()) {
        $pdo->prepare('DELETE FROM eventos WHERE id = ?')->execute([$id]);
        header('Location: agenda.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT e.*, m.nome AS criado_por_nome FROM eventos e LEFT JOIN membros m ON m.id = e.criado_por WHERE e.id = ?');
    $stmt->execute([$id]);
    $evento = $stmt->fetch();
}

$tituloPagina = $evento['titulo'];
require __DIR__ . '/includes/cabecalho.php';
?>

<div class="trilha" style="margin-bottom:14px;"><a href="agenda.php">Agenda</a> / <?= escaparHtml($evento['titulo']) ?></div>
<h1 class="admin-titulo"><?= escaparHtml($evento['titulo']) ?></h1>
<p style="color:var(--a-muted);margin-top:-12px;font-size:0.85rem;">Cadastrado por <?= escaparHtml($evento['criado_por_nome'] ?? 'alguém do painel') ?></p>

<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<form method="post" class="formulario" style="margin-top:20px;">
  <input type="hidden" name="acao" value="atualizar_evento">
  <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
  <div class="campo">
    <label for="titulo">Título *</label>
    <input type="text" id="titulo" name="titulo" value="<?= escaparHtml($evento['titulo']) ?>" required>
  </div>
  <div class="grade-campos">
    <div class="campo">
      <label for="data_evento">Data *</label>
      <input type="date" id="data_evento" name="data_evento" value="<?= escaparHtml($evento['data_evento']) ?>" required>
    </div>
    <div class="campo">
      <label for="hora_evento">Horário</label>
      <input type="time" id="hora_evento" name="hora_evento" value="<?= $evento['hora_evento'] ? substr($evento['hora_evento'],0,5) : '' ?>">
    </div>
  </div>
  <div class="grade-campos">
    <div class="campo">
      <label for="local">Local</label>
      <input type="text" id="local" name="local" value="<?= escaparHtml($evento['local']) ?>">
    </div>
    <div class="campo">
      <label for="categoria">Categoria</label>
      <input type="text" id="categoria" name="categoria" value="<?= escaparHtml($evento['categoria']) ?>">
    </div>
  </div>
  <div class="campo">
    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao"><?= escaparHtml($evento['descricao']) ?></textarea>
  </div>
  <button type="submit" class="botao-primario">Salvar alterações</button>
</form>

<?php if (ehAdmin()): ?>
<form method="post" style="margin-top:24px;" onsubmit="return confirm('Excluir este evento da agenda? Essa ação não pode ser desfeita.');">
  <input type="hidden" name="acao" value="excluir_evento">
  <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
  <button type="submit" class="botao-mini perigo">Excluir evento</button>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/rodape.php'; ?>
