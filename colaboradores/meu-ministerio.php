<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirLogin();

$pdo = conectarBanco();
$meuId = membroAtualId();
$erro = '';
$sucesso = '';

// -------- Ações (POST) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'confirmar_presenca') {
            $escalaId = (int) ($_POST['escala_id'] ?? 0);
            $pdo->prepare('UPDATE escala_membros SET confirmado = 1 WHERE escala_id = ? AND membro_id = ?')
                ->execute([$escalaId, $meuId]);
            $sucesso = 'Presença confirmada!';

        } elseif ($acao === 'add_pessoa_ministerio') {
            $ministerioId = (int) ($_POST['ministerio_id'] ?? 0);
            $membroId = (int) ($_POST['membro_id'] ?? 0);
            $funcao = trim($_POST['funcao'] ?? '') ?: 'Voluntário(a)';
            if (ehLiderDoMinisterio($pdo, $ministerioId) && $membroId) {
                $stmt = $pdo->prepare('INSERT INTO membros_ministerios (membro_id, ministerio_id, funcao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE funcao = VALUES(funcao), ativo = 1');
                $stmt->execute([$membroId, $ministerioId, $funcao]);
                $sucesso = 'Pessoa adicionada ao ministério.';
            }

        } elseif ($acao === 'remover_pessoa_ministerio') {
            $ministerioId = (int) ($_POST['ministerio_id'] ?? 0);
            $membroId = (int) ($_POST['membro_id'] ?? 0);
            if (ehLiderDoMinisterio($pdo, $ministerioId)) {
                $pdo->prepare('DELETE FROM membros_ministerios WHERE membro_id = ? AND ministerio_id = ?')->execute([$membroId, $ministerioId]);
                $sucesso = 'Pessoa removida do ministério.';
            }

        } elseif ($acao === 'criar_escala') {
            $ministerioId = (int) ($_POST['ministerio_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $data = trim($_POST['data_escala'] ?? '');
            if (ehLiderDoMinisterio($pdo, $ministerioId) && $titulo !== '' && $data !== '') {
                $stmt = $pdo->prepare('INSERT INTO escalas (ministerio_id, titulo, data_escala, hora_escala, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $ministerioId, $titulo, $data,
                    trim($_POST['hora_escala'] ?? '') ?: null,
                    trim($_POST['observacoes'] ?? '') ?: null,
                    $meuId,
                ]);
                $sucesso = 'Escala criada.';
            } elseif ($titulo === '' || $data === '') {
                $erro = 'Informe ao menos o título e a data da escala.';
            }

        } elseif ($acao === 'add_pessoa_escala') {
            $escalaId = (int) ($_POST['escala_id'] ?? 0);
            $membroId = (int) ($_POST['membro_id'] ?? 0);
            $funcaoEscala = trim($_POST['funcao_escala'] ?? '') ?: null;
            $stmt = $pdo->prepare('SELECT ministerio_id FROM escalas WHERE id = ?');
            $stmt->execute([$escalaId]);
            $ministerioDaEscala = (int) $stmt->fetchColumn();
            if ($ministerioDaEscala && ehLiderDoMinisterio($pdo, $ministerioDaEscala) && $membroId) {
                $stmt = $pdo->prepare('INSERT INTO escala_membros (escala_id, membro_id, funcao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE funcao = VALUES(funcao)');
                $stmt->execute([$escalaId, $membroId, $funcaoEscala]);
                $sucesso = 'Pessoa escalada.';
            }

        } elseif ($acao === 'remover_pessoa_escala') {
            $escalaId = (int) ($_POST['escala_id'] ?? 0);
            $membroId = (int) ($_POST['membro_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT ministerio_id FROM escalas WHERE id = ?');
            $stmt->execute([$escalaId]);
            $ministerioDaEscala = (int) $stmt->fetchColumn();
            if ($ministerioDaEscala && ehLiderDoMinisterio($pdo, $ministerioDaEscala)) {
                $pdo->prepare('DELETE FROM escala_membros WHERE escala_id = ? AND membro_id = ?')->execute([$escalaId, $membroId]);
                $sucesso = 'Pessoa removida da escala.';
            }

        } elseif ($acao === 'excluir_escala') {
            $escalaId = (int) ($_POST['escala_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT ministerio_id FROM escalas WHERE id = ?');
            $stmt->execute([$escalaId]);
            $ministerioDaEscala = (int) $stmt->fetchColumn();
            if ($ministerioDaEscala && ehLiderDoMinisterio($pdo, $ministerioDaEscala)) {
                $pdo->prepare('DELETE FROM escalas WHERE id = ?')->execute([$escalaId]);
                $sucesso = 'Escala excluída.';
            }

        } elseif ($acao === 'criar_evento_ministerio') {
            $ministerioId = (int) ($_POST['ministerio_id'] ?? 0);
            $titulo = trim($_POST['titulo_evento'] ?? '');
            $data = trim($_POST['data_evento'] ?? '');
            if (ehLiderDoMinisterio($pdo, $ministerioId) && $titulo !== '' && $data !== '') {
                $stmt = $pdo->prepare('SELECT nome FROM ministerios WHERE id = ?');
                $stmt->execute([$ministerioId]);
                $nomeMin = $stmt->fetchColumn();
                $stmt = $pdo->prepare('INSERT INTO eventos (titulo, descricao, data_evento, hora_evento, local, categoria, ministerio_id, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $titulo,
                    trim($_POST['descricao_evento'] ?? '') ?: null,
                    $data,
                    trim($_POST['hora_evento'] ?? '') ?: null,
                    trim($_POST['local_evento'] ?? '') ?: null,
                    $nomeMin,
                    $ministerioId,
                    $meuId,
                ]);
                $sucesso = 'Evento adicionado à agenda do ministério.';
            } elseif ($titulo === '' || $data === '') {
                $erro = 'Informe ao menos o título e a data do evento.';
            }
        }
    }
}

// -------- Dados pra exibir --------
$stmt = $pdo->prepare('
    SELECT mn.id, mn.nome, mn.descricao, mn.lider_id
    FROM ministerios mn
    JOIN membros_ministerios mm ON mm.ministerio_id = mn.id
    WHERE mm.membro_id = ? AND mm.ativo = 1
    ORDER BY mn.nome
');
$stmt->execute([$meuId]);
$meusMinisterios = $stmt->fetchAll();

$minhasEscalas = $pdo->prepare("
    SELECT e.*, mn.nome AS ministerio_nome, em.funcao AS minha_funcao, em.confirmado
    FROM escala_membros em
    JOIN escalas e ON e.id = em.escala_id
    JOIN ministerios mn ON mn.id = e.ministerio_id
    WHERE em.membro_id = ? AND e.data_escala >= CURDATE()
    ORDER BY e.data_escala, e.hora_escala
");
$minhasEscalas->execute([$meuId]);
$minhasEscalas = $minhasEscalas->fetchAll();

$todosMembrosAtivos = $pdo->query('SELECT id, nome FROM membros WHERE ativo = 1 ORDER BY nome')->fetchAll();

$mesesAbrev = [1=>'JAN',2=>'FEV',3=>'MAR',4=>'ABR',5=>'MAI',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SET',10=>'OUT',11=>'NOV',12=>'DEZ'];

$tituloPagina = 'Meu Ministério';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Meu Ministério</h1>
<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<span class="rotulo">📋 Minhas próximas escalas</span>
<div class="lista-agenda" style="margin-top:12px;margin-bottom:34px;">
  <?php if (!$minhasEscalas): ?>
  <p style="color:var(--a-muted);">Você não está escalado(a) em nada nos próximos dias.</p>
  <?php endif; ?>
  <?php foreach ($minhasEscalas as $e): $d = new DateTime($e['data_escala']); ?>
  <div class="item-agenda">
    <div class="quando"><span class="dia"><?= $d->format('d') ?></span><span class="mes"><?= $mesesAbrev[(int)$d->format('n')] ?></span></div>
    <div class="conteudo" style="flex:1;">
      <h4><?= escaparHtml($e['titulo']) ?></h4>
      <div class="meta"><?= escaparHtml($e['ministerio_nome']) ?><?php if ($e['minha_funcao']): ?> &middot; <?= escaparHtml($e['minha_funcao']) ?><?php endif; ?></div>
    </div>
    <?php if ($e['confirmado']): ?>
    <span class="badge-status" style="background:rgba(42,197,108,.15);color:#1c7a44;">Confirmado</span>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="acao" value="confirmar_presenca">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <input type="hidden" name="escala_id" value="<?= (int) $e['id'] ?>">
      <button type="submit" class="botao-mini">Confirmar presença</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!$meusMinisterios): ?>
<p style="color:var(--a-muted);">Você ainda não está vinculado(a) a nenhum ministério. Fale com a liderança.</p>
<?php endif; ?>

<?php foreach ($meusMinisterios as $min):
    $souLider = ehLiderDoMinisterio($pdo, (int) $min['id']);
?>
<section style="margin-bottom:48px;padding-top:28px;border-top:1px solid var(--a-line);">
  <h2 style="color:var(--a-wine-dark);font-size:1.2rem;margin-bottom:4px;">
    <?= escaparHtml($min['nome']) ?>
    <?php if ($souLider): ?><span class="badge-status" style="background:rgba(201,162,39,.18);color:#8a6d10;">Você lidera</span><?php endif; ?>
  </h2>
  <?php if ($min['descricao']): ?><p style="color:var(--a-muted);margin-bottom:18px;"><?= escaparHtml($min['descricao']) ?></p><?php endif; ?>

  <?php
    $pessoas = $pdo->prepare('
        SELECT mm.membro_id, mm.funcao, m.nome
        FROM membros_ministerios mm JOIN membros m ON m.id = mm.membro_id
        WHERE mm.ministerio_id = ? AND mm.ativo = 1 ORDER BY m.nome
    ');
    $pessoas->execute([$min['id']]);
    $pessoas = $pessoas->fetchAll();

    $escalasDoMinisterio = $pdo->prepare("
        SELECT * FROM escalas WHERE ministerio_id = ? ORDER BY data_escala DESC LIMIT 10
    ");
    $escalasDoMinisterio->execute([$min['id']]);
    $escalasDoMinisterio = $escalasDoMinisterio->fetchAll();

    $eventosDoMinisterio = $pdo->prepare("
        SELECT * FROM eventos WHERE ministerio_id = ? AND data_evento >= CURDATE() ORDER BY data_evento LIMIT 10
    ");
    $eventosDoMinisterio->execute([$min['id']]);
    $eventosDoMinisterio = $eventosDoMinisterio->fetchAll();
  ?>

  <div class="grid grid-2" style="align-items:start;gap:32px;">
    <div>
      <span class="rotulo">Pessoas neste ministério (<?= count($pessoas) ?>)</span>
      <table class="tabela-admin" style="margin-top:12px;">
        <thead><tr><th>Nome</th><th>Função</th><?php if ($souLider): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach ($pessoas as $p): ?>
          <tr>
            <td><?= escaparHtml($p['nome']) ?></td>
            <td><?= escaparHtml($p['funcao']) ?></td>
            <?php if ($souLider): ?>
            <td>
              <form method="post" onsubmit="return confirm('Remover deste ministério?');">
                <input type="hidden" name="acao" value="remover_pessoa_ministerio">
                <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
                <input type="hidden" name="membro_id" value="<?= (int) $p['membro_id'] ?>">
                <button type="submit" class="botao-mini perigo">Remover</button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($souLider): ?>
      <span class="rotulo" style="margin-top:20px;display:block;">Adicionar pessoa</span>
      <form method="post" class="formulario" style="margin-top:10px;max-width:none;">
        <input type="hidden" name="acao" value="add_pessoa_ministerio">
        <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
        <div class="grade-campos">
          <div class="campo">
            <label for="membro_id_<?= (int)$min['id'] ?>">Pessoa</label>
            <select id="membro_id_<?= (int)$min['id'] ?>" name="membro_id" required>
              <?php foreach ($todosMembrosAtivos as $m): ?>
              <option value="<?= (int) $m['id'] ?>"><?= escaparHtml($m['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label for="funcao_<?= (int)$min['id'] ?>">Função</label>
            <input type="text" id="funcao_<?= (int)$min['id'] ?>" name="funcao" placeholder="Voluntário(a), vocal...">
          </div>
        </div>
        <button type="submit" class="botao-mini">Adicionar</button>
      </form>
      <?php endif; ?>
    </div>

    <div>
      <span class="rotulo">Agenda do ministério</span>
      <div class="lista-agenda" style="margin-top:12px;">
        <?php if (!$eventosDoMinisterio): ?>
        <p style="color:var(--a-muted);font-size:0.9rem;">Nenhum evento futuro cadastrado.</p>
        <?php endif; ?>
        <?php foreach ($eventosDoMinisterio as $ev): $d = new DateTime($ev['data_evento']); ?>
        <div class="item-agenda">
          <div class="quando"><span class="dia"><?= $d->format('d') ?></span><span class="mes"><?= $mesesAbrev[(int)$d->format('n')] ?></span></div>
          <div class="conteudo">
            <h4><?= escaparHtml($ev['titulo']) ?></h4>
            <?php $meta = array_filter([$ev['hora_evento'] ? substr($ev['hora_evento'],0,5).'h' : null, $ev['local']]); ?>
            <?php if ($meta): ?><div class="meta"><?= escaparHtml(implode(' · ', $meta)) ?></div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($souLider): ?>
      <span class="rotulo" style="margin-top:20px;display:block;">Novo evento do ministério</span>
      <form method="post" class="formulario" style="margin-top:10px;max-width:none;">
        <input type="hidden" name="acao" value="criar_evento_ministerio">
        <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
        <div class="campo">
          <label>Título *</label>
          <input type="text" name="titulo_evento" required>
        </div>
        <div class="grade-campos">
          <div class="campo">
            <label>Data *</label>
            <input type="date" name="data_evento" required>
          </div>
          <div class="campo">
            <label>Horário</label>
            <input type="time" name="hora_evento">
          </div>
        </div>
        <div class="campo">
          <label>Local</label>
          <input type="text" name="local_evento">
        </div>
        <div class="campo">
          <label>Descrição</label>
          <textarea name="descricao_evento"></textarea>
        </div>
        <button type="submit" class="botao-mini">Adicionar à agenda</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($souLider): ?>
  <div style="margin-top:28px;">
    <span class="rotulo">Escalas do ministério</span>

    <form method="post" class="formulario" style="margin-top:12px;max-width:none;">
      <input type="hidden" name="acao" value="criar_escala">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
      <div class="grade-campos">
        <div class="campo">
          <label>Título *</label>
          <input type="text" name="titulo" required placeholder="Ex.: Culto de domingo">
        </div>
        <div class="campo">
          <label>Data *</label>
          <input type="date" name="data_escala" required>
        </div>
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label>Horário</label>
          <input type="time" name="hora_escala">
        </div>
        <div class="campo">
          <label>Observações</label>
          <input type="text" name="observacoes">
        </div>
      </div>
      <button type="submit" class="botao-mini">Criar escala</button>
    </form>

    <?php foreach ($escalasDoMinisterio as $es):
        $pessoasEscala = $pdo->prepare('
            SELECT em.membro_id, em.funcao, em.confirmado, m.nome
            FROM escala_membros em JOIN membros m ON m.id = em.membro_id
            WHERE em.escala_id = ? ORDER BY m.nome
        ');
        $pessoasEscala->execute([$es['id']]);
        $pessoasEscala = $pessoasEscala->fetchAll();
        $dEs = new DateTime($es['data_escala']);
    ?>
    <div style="margin-top:18px;background:var(--a-card);border:1px solid var(--a-line);border-radius:var(--a-radius);padding:16px 18px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <strong><?= escaparHtml($es['titulo']) ?> &middot; <?= $dEs->format('d/m/Y') ?><?= $es['hora_escala'] ? ' às ' . substr($es['hora_escala'],0,5) : '' ?></strong>
        <form method="post" onsubmit="return confirm('Excluir esta escala?');">
          <input type="hidden" name="acao" value="excluir_escala">
          <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
          <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
          <input type="hidden" name="escala_id" value="<?= (int) $es['id'] ?>">
          <button type="submit" class="botao-mini perigo">Excluir escala</button>
        </form>
      </div>
      <table class="tabela-admin" style="margin-top:12px;">
        <thead><tr><th>Nome</th><th>Função</th><th>Confirmado</th><th></th></tr></thead>
        <tbody>
          <?php if (!$pessoasEscala): ?>
          <tr><td colspan="4" style="color:var(--a-muted);">Ninguém escalado ainda.</td></tr>
          <?php endif; ?>
          <?php foreach ($pessoasEscala as $pe): ?>
          <tr>
            <td><?= escaparHtml($pe['nome']) ?></td>
            <td><?= escaparHtml($pe['funcao']) ?></td>
            <td><?= $pe['confirmado'] ? '✓ Sim' : 'Aguardando' ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Remover desta escala?');">
                <input type="hidden" name="acao" value="remover_pessoa_escala">
                <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
                <input type="hidden" name="escala_id" value="<?= (int) $es['id'] ?>">
                <input type="hidden" name="membro_id" value="<?= (int) $pe['membro_id'] ?>">
                <button type="submit" class="botao-mini perigo">Remover</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="acao" value="add_pessoa_escala">
        <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="ministerio_id" value="<?= (int) $min['id'] ?>">
        <input type="hidden" name="escala_id" value="<?= (int) $es['id'] ?>">
        <div class="campo" style="margin:0;">
          <label>Pessoa</label>
          <select name="membro_id">
            <?php foreach ($pessoas as $p): ?>
            <option value="<?= (int) $p['membro_id'] ?>"><?= escaparHtml($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo" style="margin:0;">
          <label>Função na escala</label>
          <input type="text" name="funcao_escala" placeholder="Vocal, teclado, recepção...">
        </div>
        <button type="submit" class="botao-mini">Escalar</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/rodape.php'; ?>
