<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();

$meses = [
    1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho',
    7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro',
];

$mes = (int) ($_GET['mes'] ?? date('n'));
if ($mes < 1 || $mes > 12) $mes = (int) date('n');

$stmt = $pdo->prepare("
    SELECT nome, data_nascimento, telefone, 'Membro' AS tipo
    FROM membros
    WHERE data_nascimento IS NOT NULL AND ativo = 1 AND MONTH(data_nascimento) = ?
    UNION ALL
    SELECT c.nome, c.data_nascimento, COALESCE(c.mae_telefone, c.pai_telefone) AS telefone, 'Criança' AS tipo
    FROM criancas c
    WHERE c.data_nascimento IS NOT NULL AND MONTH(c.data_nascimento) = ?
    ORDER BY DAY(data_nascimento)
");
$stmt->execute([$mes, $mes]);
$pessoas = $stmt->fetchAll();

$tituloPagina = 'Aniversariantes de ' . $meses[$mes];
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
</style>

<div class="nao-imprimir" style="display:flex;gap:14px;align-items:flex-end;margin-bottom:22px;flex-wrap:wrap;">
  <form method="get" style="display:flex;gap:10px;align-items:flex-end;">
    <div>
      <label style="display:block;font-size:0.82rem;font-weight:600;margin-bottom:5px;">Mês</label>
      <select name="mes" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid var(--a-line);border-radius:8px;">
        <?php foreach ($meses as $num => $nome): ?>
        <option value="<?= $num ?>" <?= $num === $mes ? 'selected' : '' ?>><?= $nome ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
  <button onclick="window.print()" class="botao-primario" type="button">🖨️ Imprimir</button>
  <a href="index.php" class="botao-mini">← Voltar ao início</a>
</div>

<div class="relatorio-cabecalho">
  <img src="../assets/images/logo.jpg" alt="CBP Sede">
  <div>
    <h1>Aniversariantes de <?= $meses[$mes] ?></h1>
    <span>Comunidade Batista da Paz &middot; CBP Sede</span>
  </div>
</div>

<table class="tabela-admin">
  <thead><tr><th>Dia</th><th>Nome</th><th>Tipo</th><th>Contato</th></tr></thead>
  <tbody>
    <?php if (!$pessoas): ?>
    <tr><td colspan="4" style="color:var(--a-muted);">Ninguém faz aniversário em <?= $meses[$mes] ?>.</td></tr>
    <?php endif; ?>
    <?php foreach ($pessoas as $p): $d = new DateTime($p['data_nascimento']); ?>
    <tr>
      <td><?= $d->format('d') ?></td>
      <td><?= escaparHtml($p['nome']) ?></td>
      <td><?= escaparHtml($p['tipo']) ?></td>
      <td><?= escaparHtml($p['telefone'] ?: '-') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/rodape.php'; ?>
