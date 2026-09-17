<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$tipo = $_GET['tipo'] ?? '';

$ministerios = $pdo->query('SELECT id, nome FROM ministerios WHERE ativo = 1 ORDER BY nome')->fetchAll();

$titulo = '';
$colunas = [];
$linhas = [];

if ($tipo === 'sexo') {
    $sexo = ($_GET['sexo'] ?? 'F') === 'M' ? 'M' : 'F';
    $titulo = $sexo === 'F' ? 'Mulheres da igreja' : 'Homens da igreja';
    $stmt = $pdo->prepare("
        SELECT nome, data_nascimento, telefone, bairro FROM membros
        WHERE ativo = 1 AND sexo = ? ORDER BY nome
    ");
    $stmt->execute([$sexo]);
    $linhas = $stmt->fetchAll();
    $colunas = ['Nome', 'Nascimento', 'Telefone', 'Bairro'];

} elseif ($tipo === 'idade') {
    $min = (int) ($_GET['min'] ?? 0);
    $max = (int) ($_GET['max'] ?? 120);
    $titulo = "Membros de {$min} a {$max} anos";
    $stmt = $pdo->prepare("
        SELECT nome, data_nascimento, telefone,
               TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) AS idade
        FROM membros
        WHERE ativo = 1 AND data_nascimento IS NOT NULL
          AND TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN ? AND ?
        ORDER BY idade, nome
    ");
    $stmt->execute([$min, $max]);
    $linhas = $stmt->fetchAll();
    $colunas = ['Nome', 'Nascimento', 'Telefone', 'Idade'];

} elseif ($tipo === 'criancas') {
    $titulo = 'Crianças cadastradas';
    $linhas = $pdo->query("
        SELECT c.nome, c.data_nascimento, f.nome_familia,
               TIMESTAMPDIFF(YEAR, c.data_nascimento, CURDATE()) AS idade
        FROM criancas c
        LEFT JOIN familias f ON f.id = c.familia_id
        ORDER BY c.nome
    ")->fetchAll();
    $colunas = ['Nome', 'Nascimento', 'Idade', 'Família'];

} elseif ($tipo === 'ministerio') {
    $ministerioId = (int) ($_GET['ministerio_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT nome FROM ministerios WHERE id = ?');
    $stmt->execute([$ministerioId]);
    $nomeMin = $stmt->fetchColumn();
    $titulo = $nomeMin ? 'Ministério: ' . $nomeMin : 'Ministério';
    $stmt = $pdo->prepare("
        SELECT m.nome, m.telefone, mm.funcao
        FROM membros_ministerios mm JOIN membros m ON m.id = mm.membro_id
        WHERE mm.ministerio_id = ? AND mm.ativo = 1 ORDER BY m.nome
    ");
    $stmt->execute([$ministerioId]);
    $linhas = $stmt->fetchAll();
    $colunas = ['Nome', 'Telefone', 'Função'];

} elseif ($tipo === 'todos') {
    $titulo = 'Todos os membros ativos';
    $linhas = $pdo->query("
        SELECT nome, data_nascimento, telefone, bairro FROM membros
        WHERE ativo = 1 ORDER BY nome
    ")->fetchAll();
    $colunas = ['Nome', 'Nascimento', 'Telefone', 'Bairro'];
}

function celulaRelatorio(array $linha, string $coluna) {
    switch ($coluna) {
        case 'Nascimento':
            return isset($linha['data_nascimento']) ? formatarData($linha['data_nascimento']) : '-';
        case 'Idade':
            return isset($linha['idade']) ? $linha['idade'] . ' anos' : '-';
        case 'Família':
            return $linha['nome_familia'] ?? '-';
        case 'Função':
            return $linha['funcao'] ?? '-';
        case 'Nome':
            return $linha['nome'] ?? '-';
        case 'Telefone':
            return $linha['telefone'] ?? '-';
        case 'Bairro':
            return $linha['bairro'] ?? '-';
        default:
            return '-';
    }
}

$tituloPagina = 'Relatórios';
require __DIR__ . '/includes/cabecalho.php';
?>

<style>
  @media print {
    .admin-topo, .nao-imprimir { display: none !important; }
    .admin-conteudo-wrap { padding: 0 !important; max-width: 100% !important; }
    body { background: #fff !important; }
  }
  .relatorio-cabecalho {
    display: flex; align-items: center; gap: 14px; border-bottom: 2px solid var(--a-wine);
    padding-bottom: 14px; margin-bottom: 20px;
  }
  .relatorio-cabecalho img { width: 46px; height: 46px; border-radius: 50%; }
  .relatorio-cabecalho h1 { margin: 0; font-size: 1.3rem; color: var(--a-wine-dark); }
  .relatorio-cabecalho span { display: block; font-size: 0.82rem; color: var(--a-muted); }
  .cartoes-relatorio { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
  .cartao-relatorio {
    background: var(--a-card); border: 1px solid var(--a-line); border-top: 3px solid var(--a-wine);
    border-radius: var(--a-radius); padding: 18px 20px; text-decoration: none; color: var(--a-ink); display: block;
  }
  .cartao-relatorio:hover { border-top-color: var(--a-gold); }
  .cartao-relatorio strong { display: block; font-family: var(--font-heading); color: var(--a-wine-dark); font-size: 0.98rem; margin-bottom: 4px; }
  .cartao-relatorio span { font-size: 0.82rem; color: var(--a-muted); }
</style>

<h1 class="admin-titulo">Relatórios</h1>

<?php if ($tipo === ''): ?>
<p style="color:var(--a-muted);margin-top:-12px;margin-bottom:24px;">Escolha um relatório pra ver e imprimir.</p>
<div class="cartoes-relatorio">
  <a class="cartao-relatorio" href="aniversariantes.php"><strong>🎂 Aniversariantes do mês</strong><span>Membros e crianças, por mês escolhido</span></a>
  <a class="cartao-relatorio" href="relatorios.php?tipo=sexo&sexo=F"><strong>👩 Mulheres</strong><span>Todas as mulheres ativas</span></a>
  <a class="cartao-relatorio" href="relatorios.php?tipo=sexo&sexo=M"><strong>👨 Homens</strong><span>Todos os homens ativos</span></a>
  <a class="cartao-relatorio" href="relatorios.php?tipo=criancas"><strong>🧒 Crianças</strong><span>Todas as crianças cadastradas</span></a>
  <a class="cartao-relatorio" href="relatorios.php?tipo=idade&min=0&max=17"><strong>📏 Por faixa etária</strong><span>Escolha um intervalo de idade</span></a>
  <a class="cartao-relatorio" href="#ministerio-form"><strong>⛪ Por ministério</strong><span>Todos os membros de um ministério</span></a>
  <a class="cartao-relatorio" href="relatorios.php?tipo=todos"><strong>📋 Todos os membros</strong><span>Lista completa e ativa</span></a>
</div>

<div id="ministerio-form" style="margin-top:30px;max-width:420px;">
  <span class="rotulo">Relatório por ministério</span>
  <form method="get" style="margin-top:10px;display:flex;gap:10px;">
    <input type="hidden" name="tipo" value="ministerio">
    <select name="ministerio_id" style="flex:1;padding:9px 12px;border:1px solid var(--a-line);border-radius:8px;">
      <?php foreach ($ministerios as $m): ?>
      <option value="<?= (int) $m['id'] ?>"><?= escaparHtml($m['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="botao-mini">Ver</button>
  </form>
</div>

<div style="margin-top:30px;max-width:420px;">
  <span class="rotulo">Relatório por faixa etária (personalizada)</span>
  <form method="get" style="margin-top:10px;display:flex;gap:10px;align-items:flex-end;">
    <input type="hidden" name="tipo" value="idade">
    <div class="campo" style="margin:0;">
      <label>De</label>
      <input type="number" name="min" value="0" min="0" max="120" style="width:80px;">
    </div>
    <div class="campo" style="margin:0;">
      <label>Até</label>
      <input type="number" name="max" value="120" min="0" max="120" style="width:80px;">
    </div>
    <button type="submit" class="botao-mini">Ver</button>
  </form>
</div>

<?php else: ?>

<div class="nao-imprimir" style="display:flex;gap:14px;margin-bottom:22px;flex-wrap:wrap;">
  <button onclick="window.print()" class="botao-primario" type="button">🖨️ Imprimir</button>
  <a href="relatorios.php" class="botao-mini">← Voltar aos relatórios</a>
</div>

<div class="relatorio-cabecalho">
  <img src="../assets/images/logo.jpg" alt="CBP Sede">
  <div>
    <h1><?= escaparHtml($titulo) ?></h1>
    <span>Comunidade Batista da Paz &middot; CBP Sede &middot; <?= count($linhas) ?> registro(s)</span>
  </div>
</div>

<table class="tabela-admin">
  <thead><tr><?php foreach ($colunas as $c): ?><th><?= escaparHtml($c) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
    <?php if (!$linhas): ?>
    <tr><td colspan="<?= count($colunas) ?>" style="color:var(--a-muted);">Nenhum registro encontrado.</td></tr>
    <?php endif; ?>
    <?php foreach ($linhas as $l): ?>
    <tr>
      <?php foreach ($colunas as $c): ?><td><?= escaparHtml((string) celulaRelatorio($l, $c)) ?></td><?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

<?php require __DIR__ . '/includes/rodape.php'; ?>
