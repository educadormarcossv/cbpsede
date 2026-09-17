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
    } elseif (($_POST['acao'] ?? '') === 'novo_lancamento') {
        $tipo = ($_POST['tipo'] ?? '') === 'saida' ? 'saida' : 'entrada';
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));
        $data = trim($_POST['data_lancamento'] ?? '');

        if ($descricao === '' || !is_numeric($valor) || (float) $valor <= 0 || $data === '') {
            $erro = 'Informe descrição, um valor válido e a data do lançamento.';
        } else {
            $erroUpload = null;
            $comprovante = null;
            $resultado = salvarDocumentoUpload($_FILES['comprovante'] ?? [], __DIR__ . '/uploads/comprovantes', $erroUpload);
            if ($erroUpload) {
                $erro = $erroUpload;
            } else {
                if ($resultado) {
                    [$nomeSalvo] = $resultado;
                    $comprovante = 'uploads/comprovantes/' . $nomeSalvo;
                }
                $stmt = $pdo->prepare('
                    INSERT INTO lancamentos_financeiros (tipo, categoria, descricao, valor, data_lancamento, forma_pagamento, comprovante_caminho, observacoes, criado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $tipo,
                    trim($_POST['categoria'] ?? '') ?: null,
                    $descricao,
                    (float) $valor,
                    $data,
                    trim($_POST['forma_pagamento'] ?? '') ?: null,
                    $comprovante,
                    trim($_POST['observacoes'] ?? '') ?: null,
                    membroAtualId(),
                ]);
                $sucesso = 'Lançamento registrado.';
            }
        }
    } elseif (($_POST['acao'] ?? '') === 'excluir_lancamento' && ehAdmin()) {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT comprovante_caminho FROM lancamentos_financeiros WHERE id = ?');
        $stmt->execute([$id]);
        $caminho = $stmt->fetchColumn();
        if ($caminho && is_file(__DIR__ . '/' . $caminho)) {
            @unlink(__DIR__ . '/' . $caminho);
        }
        $pdo->prepare('DELETE FROM lancamentos_financeiros WHERE id = ?')->execute([$id]);
        $sucesso = 'Lançamento excluído.';
    }
}

$mesFiltro = trim($_GET['mes'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $mesFiltro)) {
    $mesFiltro = date('Y-m');
}

$stmt = $pdo->prepare("
    SELECT l.*, m.nome AS lancado_por FROM lancamentos_financeiros l
    LEFT JOIN membros m ON m.id = l.criado_por
    WHERE DATE_FORMAT(l.data_lancamento, '%Y-%m') = ?
    ORDER BY l.data_lancamento DESC, l.id DESC
");
$stmt->execute([$mesFiltro]);
$lancamentos = $stmt->fetchAll();

$totalEntradas = 0;
$totalSaidas = 0;
foreach ($lancamentos as $l) {
    if ($l['tipo'] === 'entrada') $totalEntradas += (float) $l['valor'];
    else $totalSaidas += (float) $l['valor'];
}
$saldo = $totalEntradas - $totalSaidas;

function formatarMoeda(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

$tituloPagina = 'Tesouraria';
require __DIR__ . '/includes/cabecalho.php';
?>

<h1 class="admin-titulo">Tesouraria</h1>
<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<form method="get" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;">
  <label for="mes" style="font-size:0.85rem;font-weight:600;">Mês da prestação de contas</label>
  <input type="month" id="mes" name="mes" value="<?= escaparHtml($mesFiltro) ?>" onchange="this.form.submit()"
         style="padding:8px 12px;border:1px solid var(--a-line);border-radius:8px;">
</form>

<div class="cartoes-resumo">
  <div class="cartao-resumo"><strong style="color:#1c7a44;"><?= formatarMoeda($totalEntradas) ?></strong><span>entradas no mês</span></div>
  <div class="cartao-resumo"><strong style="color:var(--a-red);"><?= formatarMoeda($totalSaidas) ?></strong><span>saídas no mês</span></div>
  <div class="cartao-resumo"><strong style="color:<?= $saldo >= 0 ? '#1c7a44' : 'var(--a-red)' ?>;"><?= formatarMoeda($saldo) ?></strong><span>saldo do mês</span></div>
</div>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">Lançamentos de <?= escaparHtml($mesFiltro) ?></span>
    <table class="tabela-admin" style="margin-top:12px;">
      <thead><tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Por</th><?php if (ehAdmin()): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
        <?php if (!$lancamentos): ?>
        <tr><td colspan="5" style="color:var(--a-muted);">Nenhum lançamento neste mês.</td></tr>
        <?php endif; ?>
        <?php foreach ($lancamentos as $l): ?>
        <tr>
          <td><?= formatarData($l['data_lancamento']) ?></td>
          <td>
            <?= escaparHtml($l['descricao']) ?>
            <?php if ($l['categoria']): ?><br><span style="font-size:0.75rem;color:var(--a-muted);"><?= escaparHtml($l['categoria']) ?></span><?php endif; ?>
            <?php if ($l['comprovante_caminho']): ?><br><a href="<?= escaparHtml($l['comprovante_caminho']) ?>" target="_blank" style="font-size:0.75rem;">Ver comprovante</a><?php endif; ?>
          </td>
          <td style="color:<?= $l['tipo']==='entrada' ? '#1c7a44' : 'var(--a-red)' ?>;font-weight:700;">
            <?= $l['tipo']==='entrada' ? '+' : '−' ?> <?= formatarMoeda((float) $l['valor']) ?>
          </td>
          <td style="font-size:0.82rem;"><?= escaparHtml($l['lancado_por'] ?? '-') ?></td>
          <?php if (ehAdmin()): ?>
          <td>
            <form method="post" onsubmit="return confirm('Excluir este lançamento? Essa ação não pode ser desfeita.');">
              <input type="hidden" name="acao" value="excluir_lancamento">
              <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
              <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
              <button type="submit" class="botao-mini perigo">Excluir</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div>
    <span class="rotulo">Novo lançamento</span>
    <form method="post" enctype="multipart/form-data" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="novo_lancamento">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="grade-campos">
        <div class="campo">
          <label for="tipo">Tipo *</label>
          <select id="tipo" name="tipo" required>
            <option value="entrada">Entrada (dízimo, oferta, doação...)</option>
            <option value="saida">Saída (despesa, pagamento...)</option>
          </select>
        </div>
        <div class="campo">
          <label for="valor">Valor (R$) *</label>
          <input type="text" id="valor" name="valor" required placeholder="Ex.: 350,00">
        </div>
      </div>
      <div class="campo">
        <label for="descricao">Descrição *</label>
        <input type="text" id="descricao" name="descricao" required placeholder="Ex.: Dízimos e ofertas do culto de domingo">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="data_lancamento">Data *</label>
          <input type="date" id="data_lancamento" name="data_lancamento" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="campo">
          <label for="categoria">Categoria</label>
          <input type="text" id="categoria" name="categoria" placeholder="Dízimos, Aluguel, Missões...">
        </div>
      </div>
      <div class="campo">
        <label for="forma_pagamento">Forma de pagamento</label>
        <input type="text" id="forma_pagamento" name="forma_pagamento" placeholder="Dinheiro, Pix, transferência...">
      </div>
      <div class="campo">
        <label for="comprovante">Comprovante (opcional)</label>
        <input type="file" id="comprovante" name="comprovante" accept=".pdf,.jpg,.jpeg,.png,.webp">
      </div>
      <div class="campo">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes"></textarea>
      </div>
      <button type="submit" class="botao-primario">Registrar lançamento</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
