<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$erro = '';
$sucesso = ($_GET['criado'] ?? '') === '1' ? 'Evento cadastrado na agenda.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'novo_evento') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } else {
        $titulo = trim($_POST['titulo'] ?? '');
        $dataEvento = trim($_POST['data_evento'] ?? '');
        if ($titulo === '' || $dataEvento === '') {
            $erro = 'Informe ao menos o título e a data do evento.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO eventos (titulo, descricao, data_evento, hora_evento, local, categoria, criado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $titulo,
                trim($_POST['descricao'] ?? '') ?: null,
                $dataEvento,
                trim($_POST['hora_evento'] ?? '') ?: null,
                trim($_POST['local'] ?? '') ?: null,
                trim($_POST['categoria'] ?? '') ?: null,
                membroAtualId(),
            ]);
            header('Location: agenda.php?criado=1');
            exit;
        }
    }
}

$proximos = $pdo->query("
    SELECT * FROM eventos WHERE data_evento >= CURDATE() ORDER BY data_evento, hora_evento
")->fetchAll();

$passados = $pdo->query("
    SELECT * FROM eventos WHERE data_evento < CURDATE() ORDER BY data_evento DESC LIMIT 15
")->fetchAll();

$mesesAbrev = [1=>'JAN',2=>'FEV',3=>'MAR',4=>'ABR',5=>'MAI',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SET',10=>'OUT',11=>'NOV',12=>'DEZ'];

function renderizarItemAgenda(array $e, array $mesesAbrev): void {
    $data = new DateTime($e['data_evento']);
    echo '<div class="item-agenda">';
    echo '<div class="quando"><span class="dia">' . $data->format('d') . '</span><span class="mes">' . $mesesAbrev[(int)$data->format('n')] . '</span></div>';
    echo '<div class="conteudo">';
    echo '<h4><a href="evento.php?id=' . (int)$e['id'] . '">' . escaparHtml($e['titulo']) . '</a></h4>';
    if ($e['descricao']) echo '<p>' . escaparHtml(mb_strimwidth($e['descricao'], 0, 140, '...')) . '</p>';
    $meta = [];
    if ($e['hora_evento']) $meta[] = substr($e['hora_evento'], 0, 5) . 'h';
    if ($e['local']) $meta[] = $e['local'];
    if ($e['categoria']) $meta[] = $e['categoria'];
    if ($meta) echo '<div class="meta">' . escaparHtml(implode(' &middot; ', $meta)) . '</div>';
    echo '</div></div>';
}

$tituloPagina = 'Agenda';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Agenda dos líderes</h1>
<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">Próximos eventos</span>
    <div class="lista-agenda" style="margin-top:14px;">
      <?php if (!$proximos): ?>
      <p style="color:var(--a-muted);">Nenhum evento futuro cadastrado ainda.</p>
      <?php endif; ?>
      <?php foreach ($proximos as $e): renderizarItemAgenda($e, $mesesAbrev); endforeach; ?>
    </div>

    <?php if ($passados): ?>
    <span class="rotulo" style="margin-top:32px;display:block;">Eventos passados (últimos 15)</span>
    <div class="lista-agenda" style="margin-top:14px;opacity:.7;">
      <?php foreach ($passados as $e): renderizarItemAgenda($e, $mesesAbrev); endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <span class="rotulo">Novo evento</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="novo_evento">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ex.: Reunião de líderes">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="data_evento">Data *</label>
          <input type="date" id="data_evento" name="data_evento" required>
        </div>
        <div class="campo">
          <label for="hora_evento">Horário</label>
          <input type="time" id="hora_evento" name="hora_evento">
        </div>
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="local">Local</label>
          <input type="text" id="local" name="local" placeholder="Ex.: Salão principal">
        </div>
        <div class="campo">
          <label for="categoria">Categoria</label>
          <input type="text" id="categoria" name="categoria" placeholder="Ex.: Líderes, Infantil, Jovens...">
        </div>
      </div>
      <div class="campo">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao"></textarea>
      </div>
      <button type="submit" class="botao-primario">Adicionar à agenda</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
