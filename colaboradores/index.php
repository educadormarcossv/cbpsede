<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();

$totalMembros = (int) $pdo->query("SELECT COUNT(*) FROM membros WHERE ativo = 1")->fetchColumn();
$totalFamilias = (int) $pdo->query('SELECT COUNT(*) FROM familias')->fetchColumn();
$totalCriancas = (int) $pdo->query('SELECT COUNT(*) FROM criancas')->fetchColumn();
$totalMinisterios = (int) $pdo->query("SELECT COUNT(*) FROM ministerios WHERE ativo = 1")->fetchColumn();
$totalDocumentos = (int) $pdo->query("SELECT COUNT(*) FROM documentos")->fetchColumn();

$aniversariantes = buscarAniversariantes($pdo, 30);

$proximosEventos = $pdo->query("
    SELECT * FROM eventos WHERE data_evento >= CURDATE() ORDER BY data_evento, hora_evento LIMIT 5
")->fetchAll();
$mesesAbrev = [1=>'JAN',2=>'FEV',3=>'MAR',4=>'ABR',5=>'MAI',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SET',10=>'OUT',11=>'NOV',12=>'DEZ'];

$porMinisterio = $pdo->query("
    SELECT m.id, m.nome, COUNT(mm.id) AS total
    FROM ministerios m
    LEFT JOIN membros_ministerios mm ON mm.ministerio_id = m.id AND mm.ativo = 1
    WHERE m.ativo = 1
    GROUP BY m.id, m.nome
    ORDER BY m.nome
")->fetchAll();

$recentes = $pdo->query("
    SELECT id, nome, criado_em FROM membros ORDER BY criado_em DESC LIMIT 8
")->fetchAll();

$tituloPagina = 'Início';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Olá, <?= escaparHtml(membroAtualNome()) ?></h1>

<div class="cartoes-resumo">
  <div class="cartao-resumo"><strong><?= $totalMembros ?></strong><span>membros ativos</span></div>
  <div class="cartao-resumo"><strong><?= $totalFamilias ?></strong><span>famílias cadastradas</span></div>
  <div class="cartao-resumo"><strong><?= $totalCriancas ?></strong><span>crianças cadastradas</span></div>
  <div class="cartao-resumo"><strong><?= $totalMinisterios ?></strong><span>ministérios ativos</span></div>
  <div class="cartao-resumo"><strong><?= $totalDocumentos ?></strong><span>arquivos no painel</span></div>
</div>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">📅 Próximos eventos</span>
    <div class="lista-agenda" style="margin-top:14px;">
      <?php if (!$proximosEventos): ?>
      <p style="color:var(--text-muted);font-size:0.9rem;">Nenhum evento futuro na agenda ainda.</p>
      <?php endif; ?>
      <?php foreach ($proximosEventos as $e): $d = new DateTime($e['data_evento']); ?>
      <div class="item-agenda">
        <div class="quando"><span class="dia"><?= $d->format('d') ?></span><span class="mes"><?= $mesesAbrev[(int)$d->format('n')] ?></span></div>
        <div class="conteudo">
          <h4><a href="evento.php?id=<?= (int)$e['id'] ?>"><?= escaparHtml($e['titulo']) ?></a></h4>
          <?php $meta = array_filter([$e['hora_evento'] ? substr($e['hora_evento'],0,5).'h' : null, $e['local']]); ?>
          <?php if ($meta): ?><div class="meta"><?= escaparHtml(implode(' · ', $meta)) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="agenda.php" class="botao-fantasma" style="margin-top:10px;display:inline-block;">Ver agenda completa →</a>

    <span class="rotulo" style="margin-top:28px;display:block;">🎂 Aniversariantes (próximos 30 dias)</span>
    <div class="lista-aniversario" style="margin-top:14px;">
      <?php if (!$aniversariantes): ?>
      <p style="color:var(--text-muted);font-size:0.9rem;">Ninguém fazendo aniversário nos próximos 30 dias.</p>
      <?php endif; ?>
      <?php foreach ($aniversariantes as $a): ?>
      <div class="item-aniversario">
        <span class="data"><?= $a['proxima_data']->format('d/m') ?></span>
        <div style="flex:1;">
          <strong style="font-size:0.9rem;"><?= escaparHtml($a['nome']) ?></strong>
          <div style="font-size:0.78rem;color:var(--text-muted);">
            <?= $a['tipo'] === 'crianca' ? 'Criança' : 'Membro' ?> · completa <?= $a['idade_completar'] ?> anos
            <?= $a['dias_faltando'] === 0 ? ' · 🎉 hoje!' : '' ?>
          </div>
        </div>
        <a class="botao-mini" href="<?= $a['tipo'] === 'crianca' ? 'crianca.php?id=' . (int)$a['id'] : 'membro.php?id=' . (int)$a['id'] ?>">Ver</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <span class="rotulo">Membros por ministério</span>
    <table class="tabela-admin" style="margin-top:14px;">
      <thead><tr><th>Ministério</th><th>Pessoas</th></tr></thead>
      <tbody>
        <?php foreach ($porMinisterio as $m): ?>
        <tr>
          <td><a href="ministerio.php?id=<?= (int)$m['id'] ?>"><?= escaparHtml($m['nome']) ?></a></td>
          <td><?= (int)$m['total'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <span class="rotulo" style="margin-top:28px;display:block;">Últimos membros cadastrados</span>
    <table class="tabela-admin" style="margin-top:14px;">
      <thead><tr><th>Nome</th><th>Cadastro</th></tr></thead>
      <tbody>
        <?php if (!$recentes): ?>
        <tr><td colspan="2" style="color:var(--text-muted);">Nenhum membro cadastrado ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($recentes as $r): ?>
        <tr>
          <td><a href="membro.php?id=<?= (int)$r['id'] ?>"><?= escaparHtml($r['nome']) ?></a></td>
          <td><?= formatarData($r['criado_em']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
