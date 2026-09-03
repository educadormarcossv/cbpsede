<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirLogin();

$pdo = conectarBanco();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'nova_familia') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } else {
        $nome = trim($_POST['nome_familia'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome da família.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO familias (nome_familia, telefone, endereco, bairro, cidade, estado, cep, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $nome,
                trim($_POST['telefone'] ?? '') ?: null,
                trim($_POST['endereco'] ?? '') ?: null,
                trim($_POST['bairro'] ?? '') ?: null,
                trim($_POST['cidade'] ?? 'São Vicente') ?: null,
                trim($_POST['estado'] ?? 'SP') ?: null,
                trim($_POST['cep'] ?? '') ?: null,
                trim($_POST['observacoes'] ?? '') ?: null,
                membroAtualId(),
            ]);
            header('Location: familia.php?id=' . $pdo->lastInsertId() . '&criada=1');
            exit;
        }
    }
}

$familias = $pdo->query("
    SELECT f.id, f.nome_familia, f.telefone, f.bairro,
        (SELECT COUNT(*) FROM membros m WHERE m.familia_id = f.id) AS total_membros,
        (SELECT COUNT(*) FROM criancas c WHERE c.familia_id = f.id) AS total_criancas
    FROM familias f ORDER BY f.nome_familia
")->fetchAll();

$tituloPagina = 'Famílias';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Famílias</h1>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <table class="tabela-admin">
      <thead><tr><th>Família</th><th>Membros</th><th>Crianças</th><th></th></tr></thead>
      <tbody>
        <?php if (!$familias): ?>
        <tr><td colspan="4" style="color:var(--text-muted);">Nenhuma família cadastrada ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($familias as $f): ?>
        <tr>
          <td><a href="familia.php?id=<?= (int)$f['id'] ?>"><?= escaparHtml($f['nome_familia']) ?></a></td>
          <td><?= (int)$f['total_membros'] ?></td>
          <td><?= (int)$f['total_criancas'] ?></td>
          <td><a class="botao-mini" href="familia.php?id=<?= (int)$f['id'] ?>">Ver →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div>
    <span class="rotulo">Cadastrar nova família</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="nova_familia">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome_familia">Nome da família *</label>
        <input type="text" id="nome_familia" name="nome_familia" placeholder="Ex.: Família Souza" required>
      </div>
      <div class="campo">
        <label for="telefone">Telefone / WhatsApp</label>
        <input type="text" id="telefone" name="telefone">
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
          <label for="cep">CEP</label>
          <input type="text" id="cep" name="cep">
        </div>
      </div>
      <div class="campo">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes"></textarea>
      </div>
      <button type="submit" class="botao-primario">Cadastrar família</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
