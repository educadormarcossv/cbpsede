<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT c.id, c.nome, c.data_nascimento, c.tem_alergia, c.familia_id, f.nome_familia
    FROM criancas c JOIN familias f ON f.id = c.familia_id
";
if ($busca !== '') {
    $sql .= ' WHERE c.nome LIKE ? ';
    $stmt = $pdo->prepare($sql . ' ORDER BY c.nome');
    $stmt->execute(['%' . $busca . '%']);
} else {
    $stmt = $pdo->query($sql . ' ORDER BY c.nome');
}
$criancas = $stmt->fetchAll();

$tituloPagina = 'Crianças';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Crianças</h1>
<p style="color:var(--text-muted);margin-bottom:20px;max-width:640px;">Cadastro pra recepção segura no Ministério Infantil: alergias, medicamentos, contato de emergência e quem está autorizado a retirar. Pra cadastrar uma criança nova, vá até a família dela.</p>

<form method="get" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px;">
  <input type="text" name="busca" placeholder="Buscar por nome..." value="<?= escaparHtml($busca) ?>" style="flex:1;padding:11px 14px;border:1.5px solid var(--border-color);border-radius:10px;">
  <button type="submit" class="botao-mini">Buscar</button>
</form>

<table class="tabela-admin">
  <thead><tr><th>Nome</th><th>Idade</th><th>Família</th><th>Alergia</th><th></th></tr></thead>
  <tbody>
    <?php if (!$criancas): ?>
    <tr><td colspan="5" style="color:var(--text-muted);">Nenhuma criança encontrada.</td></tr>
    <?php endif; ?>
    <?php foreach ($criancas as $c): ?>
    <tr>
      <td><a href="crianca.php?id=<?= (int)$c['id'] ?>"><?= escaparHtml($c['nome']) ?></a></td>
      <td><?= calcularIdade($c['data_nascimento']) ?? '-' ?></td>
      <td><a href="familia.php?id=<?= (int)$c['familia_id'] ?>"><?= escaparHtml($c['nome_familia']) ?></a></td>
      <td><?= $c['tem_alergia'] ? '<span class="badge-status" style="background:rgba(230,44,50,.12);color:#B32226;">⚠️ Sim</span>' : '-' ?></td>
      <td><a class="botao-mini" href="crianca.php?id=<?= (int)$c['id'] ?>">Ver →</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/rodape.php'; ?>
