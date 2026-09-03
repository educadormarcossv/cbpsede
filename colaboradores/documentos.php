<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'enviar_documento') {
        $titulo = trim($_POST['titulo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '') ?: null;
        $descricao = trim($_POST['descricao'] ?? '') ?: null;
        if ($titulo === '') {
            $erro = 'Informe um título para o arquivo.';
        } else {
            $erroUpload = null;
            $resultado = salvarDocumentoUpload($_FILES['arquivo'] ?? [], __DIR__ . '/uploads/documentos', $erroUpload);
            if ($erroUpload) {
                $erro = $erroUpload;
            } elseif (!$resultado) {
                $erro = 'Escolha um arquivo para enviar.';
            } else {
                [$nomeSalvo, $nomeOriginal, $tamanho] = $resultado;
                $stmt = $pdo->prepare('
                    INSERT INTO documentos (titulo, categoria, descricao, arquivo_caminho, arquivo_nome_original, tamanho_bytes, enviado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $titulo, $categoria, $descricao,
                    'uploads/documentos/' . $nomeSalvo, $nomeOriginal, $tamanho,
                    membroAtualId(),
                ]);
                $sucesso = 'Arquivo enviado.';
            }
        }
    } elseif (($_POST['acao'] ?? '') === 'excluir_documento') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT arquivo_caminho FROM documentos WHERE id = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if ($doc) {
            $caminhoAbsoluto = __DIR__ . '/' . $doc['arquivo_caminho'];
            if (is_file($caminhoAbsoluto)) {
                @unlink($caminhoAbsoluto);
            }
            $pdo->prepare('DELETE FROM documentos WHERE id = ?')->execute([$id]);
            $sucesso = 'Arquivo excluído.';
        }
    }
}

$categoriaFiltro = trim($_GET['categoria'] ?? '');
if ($categoriaFiltro !== '') {
    $stmt = $pdo->prepare('
        SELECT d.*, m.nome AS enviado_por_nome FROM documentos d
        LEFT JOIN membros m ON m.id = d.enviado_por
        WHERE d.categoria = ? ORDER BY d.criado_em DESC
    ');
    $stmt->execute([$categoriaFiltro]);
} else {
    $stmt = $pdo->query('
        SELECT d.*, m.nome AS enviado_por_nome FROM documentos d
        LEFT JOIN membros m ON m.id = d.enviado_por
        ORDER BY d.criado_em DESC
    ');
}
$documentos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT DISTINCT categoria FROM documentos WHERE categoria IS NOT NULL ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);

$tituloPagina = 'Arquivos';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Arquivos dos líderes</h1>
<p style="color:var(--a-muted);margin-top:-12px;margin-bottom:20px;font-size:0.9rem;">Atas, escalas, planilhas e
formulários compartilhados entre a liderança. Só quem tem acesso ao painel consegue ver e baixar.</p>

<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <?php if ($categorias): ?>
    <div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;">
      <a href="documentos.php" class="botao-mini<?= $categoriaFiltro === '' ? ' perigo' : '' ?>">Todas</a>
      <?php foreach ($categorias as $c): ?>
      <a href="documentos.php?categoria=<?= urlencode($c) ?>" class="botao-mini<?= $categoriaFiltro === $c ? ' perigo' : '' ?>"><?= escaparHtml($c) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="lista-documentos">
      <?php if (!$documentos): ?>
      <p style="color:var(--a-muted);">Nenhum arquivo enviado ainda.</p>
      <?php endif; ?>
      <?php foreach ($documentos as $d): ?>
      <div class="item-documento">
        <div class="icone"><i class="fa-solid <?= iconeParaExtensao($d['arquivo_nome_original']) ?>"></i></div>
        <div class="info">
          <strong><?= escaparHtml($d['titulo']) ?></strong>
          <span>
            <?php if ($d['categoria']): ?><?= escaparHtml($d['categoria']) ?> &middot; <?php endif; ?>
            <?= formatarTamanhoArquivo($d['tamanho_bytes']) ?> &middot;
            enviado por <?= escaparHtml($d['enviado_por_nome'] ?? 'alguém do painel') ?> em <?= formatarData($d['criado_em']) ?>
          </span>
          <?php if ($d['descricao']): ?><p style="margin:6px 0 0;font-size:0.85rem;"><?= nl2br(escaparHtml($d['descricao'])) ?></p><?php endif; ?>
        </div>
        <a class="botao-mini" href="<?= escaparHtml($d['arquivo_caminho']) ?>" download="<?= escaparHtml($d['arquivo_nome_original']) ?>">Baixar</a>
        <?php if (ehAdmin()): ?>
        <form method="post" onsubmit="return confirm('Excluir este arquivo? Essa ação não pode ser desfeita.');">
          <input type="hidden" name="acao" value="excluir_documento">
          <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
          <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
          <button type="submit" class="botao-mini perigo">Excluir</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <span class="rotulo">Enviar novo arquivo</span>
    <form method="post" enctype="multipart/form-data" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="enviar_documento">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ex.: Ata da reunião de líderes - agosto">
      </div>
      <div class="campo">
        <label for="categoria">Categoria</label>
        <input type="text" id="categoria" name="categoria" placeholder="Ex.: Atas, Escalas, Ministério Infantil...">
      </div>
      <div class="campo">
        <label for="arquivo">Arquivo *</label>
        <input type="file" id="arquivo" name="arquivo" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.txt,.csv">
      </div>
      <div class="campo">
        <label for="descricao">Observações</label>
        <textarea id="descricao" name="descricao"></textarea>
      </div>
      <button type="submit" class="botao-primario">Enviar arquivo</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
