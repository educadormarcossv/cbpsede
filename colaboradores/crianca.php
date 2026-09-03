<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirLogin();

$pdo = conectarBanco();
$id = (int) ($_GET['id'] ?? 0);
$erro = '';
$sucesso = '';

$stmt = $pdo->prepare('SELECT c.*, f.nome_familia FROM criancas c JOIN familias f ON f.id = c.familia_id WHERE c.id = ?');
$stmt->execute([$id]);
$crianca = $stmt->fetch();
if (!$crianca) {
    header('Location: criancas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_crianca') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome.';
        } else {
            $erroFoto = null;
            $novaFoto = salvarFotoUpload($_FILES['foto'] ?? [], __DIR__ . '/uploads/fotos', 'crianca' . $id, $erroFoto);
            if ($erroFoto) {
                $erro = $erroFoto;
            } else {
                $sql = '
                    UPDATE criancas SET nome=?, data_nascimento=?, mae_nome=?, mae_telefone=?, pai_nome=?, pai_telefone=?,
                    tem_alergia=?, alergia_qual=?, usa_medicamento=?, medicamento_qual=?,
                    contato_emergencia_nome=?, contato_emergencia_telefone=?, pessoas_autorizadas_retirar=?,
                    batizado=?, autorizacao_imagem_em=?, observacoes=?' . ($novaFoto ? ', foto_caminho=?' : '') . ' WHERE id=?';
                $params = [
                    $nome,
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
                    isset($_POST['batizado']) ? 1 : 0,
                    trim($_POST['autorizacao_imagem_em'] ?? '') ?: null,
                    trim($_POST['observacoes'] ?? '') ?: null,
                ];
                if ($novaFoto) $params[] = $novaFoto;
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
                $sucesso = 'Dados atualizados.';
            }
        }
    }
    $stmt = $pdo->prepare('SELECT c.*, f.nome_familia FROM criancas c JOIN familias f ON f.id = c.familia_id WHERE c.id = ?');
    $stmt->execute([$id]);
    $crianca = $stmt->fetch();
}

$tituloPagina = $crianca['nome'];
require __DIR__ . '/includes/cabecalho.php';
?>

<div class="trilha" style="margin-bottom:14px;"><a href="criancas.php">Crianças</a> / <?= escaparHtml($crianca['nome']) ?></div>

<div style="display:flex;align-items:center;gap:18px;margin-bottom:20px;flex-wrap:wrap;">
  <?php if (fotoExisteNoServidor($crianca['foto_caminho'] ? 'uploads/fotos/' . $crianca['foto_caminho'] : null)): ?>
  <img class="avatar-grande" src="uploads/fotos/<?= escaparHtml($crianca['foto_caminho']) ?>" alt="">
  <?php else: ?>
  <span class="avatar-grande-vazio"><i class="fa-solid fa-child"></i></span>
  <?php endif; ?>
  <div>
    <h1 class="admin-titulo" style="margin-bottom:4px;"><?= escaparHtml($crianca['nome']) ?></h1>
    <p style="color:var(--text-muted);font-size:0.9rem;">Família <a href="familia.php?id=<?= (int)$crianca['familia_id'] ?>"><?= escaparHtml($crianca['nome_familia']) ?></a> · <?= calcularIdade($crianca['data_nascimento']) ?? '?' ?> anos</p>
    <?php if ($crianca['tem_alergia']): ?><span class="badge-status" style="background:rgba(230,44,50,.12);color:#B32226;">⚠️ Alergia: <?= escaparHtml($crianca['alergia_qual'] ?: 'ver observações') ?></span><?php endif; ?>
    <?php if ($crianca['usa_medicamento']): ?><span class="badge-status" style="background:rgba(245,195,30,.18);color:#a37c0f;">💊 Medicamento contínuo</span><?php endif; ?>
  </div>
</div>

<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="formulario" style="max-width:640px;">
  <input type="hidden" name="acao" value="atualizar_crianca">
  <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
  <div class="campo">
    <label for="nome">Nome completo *</label>
    <input type="text" id="nome" name="nome" value="<?= escaparHtml($crianca['nome']) ?>" required>
  </div>
  <div class="campo">
    <label for="foto">Foto</label>
    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
  </div>
  <div class="campo">
    <label for="data_nascimento">Data de nascimento</label>
    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= escaparHtml($crianca['data_nascimento']) ?>">
  </div>
  <div class="grade-campos">
    <div class="campo">
      <label for="mae_nome">Nome da mãe</label>
      <input type="text" id="mae_nome" name="mae_nome" value="<?= escaparHtml($crianca['mae_nome']) ?>">
    </div>
    <div class="campo">
      <label for="mae_telefone">Telefone da mãe</label>
      <input type="text" id="mae_telefone" name="mae_telefone" value="<?= escaparHtml($crianca['mae_telefone']) ?>">
    </div>
  </div>
  <div class="grade-campos">
    <div class="campo">
      <label for="pai_nome">Nome do pai</label>
      <input type="text" id="pai_nome" name="pai_nome" value="<?= escaparHtml($crianca['pai_nome']) ?>">
    </div>
    <div class="campo">
      <label for="pai_telefone">Telefone do pai</label>
      <input type="text" id="pai_telefone" name="pai_telefone" value="<?= escaparHtml($crianca['pai_telefone']) ?>">
    </div>
  </div>
  <div class="campo">
    <label><input type="checkbox" name="tem_alergia" value="1" <?= $crianca['tem_alergia']?'checked':'' ?> style="width:auto;display:inline-block;margin-right:8px;">Tem alergia ou doença crônica</label>
  </div>
  <div class="campo">
    <label for="alergia_qual">Qual</label>
    <input type="text" id="alergia_qual" name="alergia_qual" value="<?= escaparHtml($crianca['alergia_qual']) ?>">
  </div>
  <div class="campo">
    <label><input type="checkbox" name="usa_medicamento" value="1" <?= $crianca['usa_medicamento']?'checked':'' ?> style="width:auto;display:inline-block;margin-right:8px;">Faz uso contínuo de medicamento</label>
  </div>
  <div class="campo">
    <label for="medicamento_qual">Qual</label>
    <input type="text" id="medicamento_qual" name="medicamento_qual" value="<?= escaparHtml($crianca['medicamento_qual']) ?>">
  </div>
  <div class="grade-campos">
    <div class="campo">
      <label for="contato_emergencia_nome">Em caso de emergência, avisar</label>
      <input type="text" id="contato_emergencia_nome" name="contato_emergencia_nome" value="<?= escaparHtml($crianca['contato_emergencia_nome']) ?>">
    </div>
    <div class="campo">
      <label for="contato_emergencia_telefone">Telefone de emergência</label>
      <input type="text" id="contato_emergencia_telefone" name="contato_emergencia_telefone" value="<?= escaparHtml($crianca['contato_emergencia_telefone']) ?>">
    </div>
  </div>
  <div class="campo">
    <label for="pessoas_autorizadas_retirar">Pessoas autorizadas a retirar</label>
    <textarea id="pessoas_autorizadas_retirar" name="pessoas_autorizadas_retirar"><?= escaparHtml($crianca['pessoas_autorizadas_retirar']) ?></textarea>
  </div>
  <div class="campo">
    <label><input type="checkbox" name="batizado" value="1" <?= $crianca['batizado']?'checked':'' ?> style="width:auto;display:inline-block;margin-right:8px;">Batizada(o)</label>
  </div>
  <div class="campo">
    <label for="autorizacao_imagem_em">Autorização de uso de imagem assinada em</label>
    <input type="date" id="autorizacao_imagem_em" name="autorizacao_imagem_em" value="<?= escaparHtml($crianca['autorizacao_imagem_em']) ?>">
  </div>
  <div class="campo">
    <label for="observacoes">Observações</label>
    <textarea id="observacoes" name="observacoes"><?= escaparHtml($crianca['observacoes']) ?></textarea>
  </div>
  <button type="submit" class="botao-primario">Salvar alterações</button>
</form>

<?php require __DIR__ . '/includes/rodape.php'; ?>
