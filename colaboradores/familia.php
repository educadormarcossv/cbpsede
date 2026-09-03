<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$id = (int) ($_GET['id'] ?? 0);
$erro = '';
$sucesso = ($_GET['criada'] ?? '') === '1' ? 'Família cadastrada com sucesso.' : '';

$stmt = $pdo->prepare('SELECT * FROM familias WHERE id = ?');
$stmt->execute([$id]);
$familia = $stmt->fetch();
if (!$familia) {
    header('Location: familias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'atualizar_familia') {
        $nome = trim($_POST['nome_familia'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome da família.';
        } else {
            $stmt = $pdo->prepare('UPDATE familias SET nome_familia=?, telefone=?, endereco=?, bairro=?, cidade=?, estado=?, cep=?, observacoes=? WHERE id=?');
            $stmt->execute([
                $nome,
                trim($_POST['telefone'] ?? '') ?: null,
                trim($_POST['endereco'] ?? '') ?: null,
                trim($_POST['bairro'] ?? '') ?: null,
                trim($_POST['cidade'] ?? '') ?: null,
                trim($_POST['estado'] ?? '') ?: null,
                trim($_POST['cep'] ?? '') ?: null,
                trim($_POST['observacoes'] ?? '') ?: null,
                $id,
            ]);
            $sucesso = 'Dados da família atualizados.';
        }
    } elseif (($_POST['acao'] ?? '') === 'nova_crianca') {
        $nomeCrianca = trim($_POST['nome_crianca'] ?? '');
        if ($nomeCrianca === '') {
            $erro = 'Informe o nome da criança.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO criancas (
                    familia_id, nome, data_nascimento, mae_nome, mae_telefone, pai_nome, pai_telefone,
                    tem_alergia, alergia_qual, usa_medicamento, medicamento_qual,
                    contato_emergencia_nome, contato_emergencia_telefone, pessoas_autorizadas_retirar,
                    observacoes, criado_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $id,
                $nomeCrianca,
                trim($_POST['data_nascimento'] ?? '') ?: null,
                trim($_POST['mae_nome'] ?? '') ?: null,
                trim($_POST['mae_telefone'] ?? '') ?: null,
                trim($_POST['pai_nome'] ?? '') ?: null,
                trim($_POST['pai_telefone'] ?? '') ?: null,
                isset($_POST['tem_alergia']) ? 1 : 0,
                trim($_POST['alergia_qual'] ?? '') ?: null,
                isset($_POST['usa_medicamento']) ? 1 : 0,
                trim($_POST['medicamento_qual'] ?? '') ?: null,
                trim($_POST['contato_emergencia_nome'] ?? '') ?: null,
                trim($_POST['contato_emergencia_telefone'] ?? '') ?: null,
                trim($_POST['pessoas_autorizadas_retirar'] ?? '') ?: null,
                trim($_POST['observacoes_crianca'] ?? '') ?: null,
                membroAtualId(),
            ]);
            header('Location: familia.php?id=' . $id . '&crianca_criada=1');
            exit;
        }
    }
    $stmt = $pdo->prepare('SELECT * FROM familias WHERE id = ?');
    $stmt->execute([$id]);
    $familia = $stmt->fetch();
}

if (($_GET['crianca_criada'] ?? '') === '1') {
    $sucesso = 'Criança cadastrada com sucesso.';
}

$stmt = $pdo->prepare("SELECT id, nome, parentesco_familia FROM membros WHERE familia_id = ? ORDER BY nome");
$stmt->execute([$id]);
$membrosDaFamilia = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, nome, data_nascimento FROM criancas WHERE familia_id = ? ORDER BY nome');
$stmt->execute([$id]);
$criancas = $stmt->fetchAll();

$mensagemPadrao = "Olá, família {$familia['nome_familia']}! Aqui é da CBP Sede. ";
$linkZap = linkWhatsApp($familia['telefone'], $mensagemPadrao);

$tituloPagina = $familia['nome_familia'];
require __DIR__ . '/includes/cabecalho.php';
?>

<div class="trilha" style="margin-bottom:14px;"><a href="familias.php">Famílias</a> / <?= escaparHtml($familia['nome_familia']) ?></div>
<h1 class="admin-titulo"><?= escaparHtml($familia['nome_familia']) ?></h1>

<?php if ($linkZap): ?>
<div style="margin-bottom:20px;">
  <a href="<?= escaparHtml($linkZap) ?>" target="_blank" rel="noopener" class="botao-mini" style="background:#25D366;color:#fff;border-color:#25D366;">💬 Chamar no WhatsApp</a>
</div>
<?php endif; ?>

<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">Dados da família</span>
    <form method="post" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="atualizar_familia">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome_familia">Nome da família *</label>
        <input type="text" id="nome_familia" name="nome_familia" value="<?= escaparHtml($familia['nome_familia']) ?>" required>
      </div>
      <div class="campo">
        <label for="telefone">Telefone / WhatsApp</label>
        <input type="text" id="telefone" name="telefone" value="<?= escaparHtml($familia['telefone']) ?>">
      </div>
      <div class="campo">
        <label for="endereco">Endereço</label>
        <input type="text" id="endereco" name="endereco" value="<?= escaparHtml($familia['endereco']) ?>">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="bairro">Bairro</label>
          <input type="text" id="bairro" name="bairro" value="<?= escaparHtml($familia['bairro']) ?>">
        </div>
        <div class="campo">
          <label for="cep">CEP</label>
          <input type="text" id="cep" name="cep" value="<?= escaparHtml($familia['cep']) ?>">
        </div>
      </div>
      <div class="campo">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes"><?= escaparHtml($familia['observacoes']) ?></textarea>
      </div>
      <button type="submit" class="botao-primario">Salvar alterações</button>
    </form>

    <span class="rotulo" style="margin-top:32px;display:block;">Membros desta família (<?= count($membrosDaFamilia) ?>)</span>
    <table class="tabela-admin" style="margin-top:14px;">
      <thead><tr><th>Nome</th><th>Parentesco</th><th></th></tr></thead>
      <tbody>
        <?php if (!$membrosDaFamilia): ?>
        <tr><td colspan="3" style="color:var(--text-muted);">Nenhum membro vinculado ainda. Vincule pela página do membro.</td></tr>
        <?php endif; ?>
        <?php foreach ($membrosDaFamilia as $m): ?>
        <tr>
          <td><a href="membro.php?id=<?= (int)$m['id'] ?>"><?= escaparHtml($m['nome']) ?></a></td>
          <td><?= escaparHtml($m['parentesco_familia']) ?: '-' ?></td>
          <td><a class="botao-mini" href="membro.php?id=<?= (int)$m['id'] ?>">Ver →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div>
    <span class="rotulo">Crianças desta família (<?= count($criancas) ?>)</span>
    <table class="tabela-admin" style="margin-top:14px;">
      <thead><tr><th>Nome</th><th>Idade</th><th></th></tr></thead>
      <tbody>
        <?php if (!$criancas): ?>
        <tr><td colspan="3" style="color:var(--text-muted);">Nenhuma criança cadastrada ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($criancas as $c): ?>
        <tr>
          <td><?= escaparHtml($c['nome']) ?></td>
          <td><?= calcularIdade($c['data_nascimento']) ?? '-' ?></td>
          <td><a class="botao-mini" href="crianca.php?id=<?= (int)$c['id'] ?>">Ver →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <span class="rotulo" style="margin-top:32px;display:block;">Adicionar criança a esta família</span>
    <form method="post" class="formulario" style="margin-top:14px;max-width:none;">
      <input type="hidden" name="acao" value="nova_crianca">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome_crianca">Nome da criança *</label>
        <input type="text" id="nome_crianca" name="nome_crianca" required>
      </div>
      <div class="campo">
        <label for="data_nascimento">Data de nascimento</label>
        <input type="date" id="data_nascimento" name="data_nascimento">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="mae_nome">Nome da mãe</label>
          <input type="text" id="mae_nome" name="mae_nome">
        </div>
        <div class="campo">
          <label for="mae_telefone">Telefone da mãe</label>
          <input type="text" id="mae_telefone" name="mae_telefone">
        </div>
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="pai_nome">Nome do pai</label>
          <input type="text" id="pai_nome" name="pai_nome">
        </div>
        <div class="campo">
          <label for="pai_telefone">Telefone do pai</label>
          <input type="text" id="pai_telefone" name="pai_telefone">
        </div>
      </div>
      <div class="campo">
        <label><input type="checkbox" name="tem_alergia" value="1" style="width:auto;display:inline-block;margin-right:8px;">Tem alergia ou doença crônica</label>
      </div>
      <div class="campo">
        <label for="alergia_qual">Qual (se marcado acima)</label>
        <input type="text" id="alergia_qual" name="alergia_qual">
      </div>
      <div class="campo">
        <label><input type="checkbox" name="usa_medicamento" value="1" style="width:auto;display:inline-block;margin-right:8px;">Faz uso contínuo de medicamento</label>
      </div>
      <div class="campo">
        <label for="medicamento_qual">Qual (se marcado acima)</label>
        <input type="text" id="medicamento_qual" name="medicamento_qual">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="contato_emergencia_nome">Em caso de emergência, avisar</label>
          <input type="text" id="contato_emergencia_nome" name="contato_emergencia_nome">
        </div>
        <div class="campo">
          <label for="contato_emergencia_telefone">Telefone de emergência</label>
          <input type="text" id="contato_emergencia_telefone" name="contato_emergencia_telefone">
        </div>
      </div>
      <div class="campo">
        <label for="pessoas_autorizadas_retirar">Pessoas autorizadas a retirar a criança</label>
        <textarea id="pessoas_autorizadas_retirar" name="pessoas_autorizadas_retirar"></textarea>
      </div>
      <div class="campo">
        <label for="observacoes_crianca">Observações</label>
        <textarea id="observacoes_crianca" name="observacoes_crianca"></textarea>
      </div>
      <button type="submit" class="botao-primario">Cadastrar criança</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
