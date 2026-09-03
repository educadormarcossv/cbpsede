<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';
exigirGestor();

$pdo = conectarBanco();
$id = (int) ($_GET['id'] ?? 0);
$erro = '';
$sucesso = ($_GET['criado'] ?? '') === '1' ? 'Membro cadastrado com sucesso.' : '';

$stmt = $pdo->prepare('SELECT * FROM membros WHERE id = ?');
$stmt->execute([$id]);
$membro = $stmt->fetch();
if (!$membro) {
    header('Location: membros.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada, tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'atualizar_membro') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome.';
        } else {
            $erroFoto = null;
            $novaFoto = salvarFotoUpload($_FILES['foto'] ?? [], __DIR__ . '/uploads/perfil', 'membro' . $id, $erroFoto);
            if ($erroFoto) {
                $erro = $erroFoto;
            } else {
                $stmt = $pdo->prepare('
                    UPDATE membros SET nome=?, data_nascimento=?, telefone=?, endereco=?, bairro=?, cidade=?, estado=?, cep=?,
                    estado_civil=?, membro_desde=?, modo_recepcao=?, batizado=?, data_batismo=?, observacoes=?, ativo=?' .
                    ($novaFoto ? ', foto_caminho=?' : '') . ' WHERE id=?'
                );
                $params = [
                    $nome,
                    trim($_POST['data_nascimento'] ?? '') ?: null,
                    trim($_POST['telefone'] ?? '') ?: null,
                    trim($_POST['endereco'] ?? '') ?: null,
                    trim($_POST['bairro'] ?? '') ?: null,
                    trim($_POST['cidade'] ?? '') ?: null,
                    trim($_POST['estado'] ?? '') ?: null,
                    trim($_POST['cep'] ?? '') ?: null,
                    trim($_POST['estado_civil'] ?? '') ?: null,
                    trim($_POST['membro_desde'] ?? '') ?: null,
                    trim($_POST['modo_recepcao'] ?? '') ?: null,
                    isset($_POST['batizado']) ? 1 : 0,
                    trim($_POST['data_batismo'] ?? '') ?: null,
                    trim($_POST['observacoes'] ?? '') ?: null,
                    isset($_POST['ativo']) ? 1 : 0,
                ];
                if ($novaFoto) $params[] = $novaFoto;
                $params[] = $id;
                $stmt->execute($params);
                $sucesso = 'Dados atualizados.';
            }
        }
    } elseif (($_POST['acao'] ?? '') === 'acesso_painel' && ehAdmin()) {
        $email = trim($_POST['email'] ?? '');
        $papel = $_POST['papel'] ?? 'membro';
        $novaSenha = $_POST['nova_senha'] ?? '';
        if ($email === '') {
            $erro = 'Informe o e-mail para dar acesso ao painel.';
        } else {
            if ($novaSenha !== '') {
                $stmt = $pdo->prepare('UPDATE membros SET email=?, papel=?, senha_hash=? WHERE id=?');
                $stmt->execute([$email, $papel, password_hash($novaSenha, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE membros SET email=?, papel=? WHERE id=?');
                $stmt->execute([$email, $papel, $id]);
            }
            $sucesso = 'Acesso ao painel atualizado.';
        }
    } elseif (($_POST['acao'] ?? '') === 'ligar_familia') {
        $familiaId = (int) ($_POST['familia_id'] ?? 0);
        $parentesco = trim($_POST['parentesco_familia'] ?? '');
        $stmt = $pdo->prepare('UPDATE membros SET familia_id = ?, parentesco_familia = ? WHERE id = ?');
        $stmt->execute([$familiaId ?: null, $parentesco ?: null, $id]);
        $sucesso = 'Família vinculada.';
    } elseif (($_POST['acao'] ?? '') === 'add_ministerio') {
        $ministerioId = (int) ($_POST['ministerio_id'] ?? 0);
        $funcao = trim($_POST['funcao'] ?? '') ?: 'Voluntário(a)';
        if ($ministerioId) {
            $stmt = $pdo->prepare('INSERT INTO membros_ministerios (membro_id, ministerio_id, funcao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE funcao = VALUES(funcao), ativo = 1');
            $stmt->execute([$id, $ministerioId, $funcao]);
            $sucesso = 'Ministério vinculado.';
        }
    } elseif (($_POST['acao'] ?? '') === 'remover_ministerio') {
        $ministerioId = (int) ($_POST['ministerio_id'] ?? 0);
        $pdo->prepare('DELETE FROM membros_ministerios WHERE membro_id = ? AND ministerio_id = ?')->execute([$id, $ministerioId]);
        $sucesso = 'Ministério removido.';
    }

    $stmt = $pdo->prepare('SELECT * FROM membros WHERE id = ?');
    $stmt->execute([$id]);
    $membro = $stmt->fetch();
}

$familias = $pdo->query('SELECT id, nome_familia FROM familias ORDER BY nome_familia')->fetchAll();
$ministerios = $pdo->query('SELECT id, nome FROM ministerios WHERE ativo = 1 ORDER BY nome')->fetchAll();

$stmt = $pdo->prepare('
    SELECT mm.ministerio_id, mm.funcao, m.nome
    FROM membros_ministerios mm JOIN ministerios m ON m.id = mm.ministerio_id
    WHERE mm.membro_id = ? AND mm.ativo = 1 ORDER BY m.nome
');
$stmt->execute([$id]);
$ministeriosDoMembro = $stmt->fetchAll();

$familiaAtual = null;
if ($membro['familia_id']) {
    $stmt = $pdo->prepare('SELECT * FROM familias WHERE id = ?');
    $stmt->execute([$membro['familia_id']]);
    $familiaAtual = $stmt->fetch();
}

$mensagemPadrao = "Olá, {$membro['nome']}! Aqui é da CBP Sede. ";
$linkZap = linkWhatsApp($membro['telefone'], $mensagemPadrao);

$tituloPagina = $membro['nome'];
require __DIR__ . '/includes/cabecalho.php';
?>

<div class="trilha" style="margin-bottom:14px;"><a href="membros.php">Membros</a> / <?= escaparHtml($membro['nome']) ?></div>

<div style="display:flex;align-items:center;gap:18px;margin-bottom:20px;flex-wrap:wrap;">
  <?php if (fotoExisteNoServidor($membro['foto_caminho'] ? 'uploads/perfil/' . $membro['foto_caminho'] : null)): ?>
  <img class="avatar-grande" src="uploads/perfil/<?= escaparHtml($membro['foto_caminho']) ?>" alt="">
  <?php else: ?>
  <span class="avatar-grande-vazio"><i class="fa-solid fa-user"></i></span>
  <?php endif; ?>
  <div>
    <h1 class="admin-titulo" style="margin-bottom:4px;"><?= escaparHtml($membro['nome']) ?></h1>
    <?php if (!$membro['ativo']): ?><span class="badge-status" style="background:#eee;color:#888;">Inativo</span><?php endif; ?>
    <?php if ($membro['batizado']): ?><span class="badge-status" style="background:rgba(0,175,245,.15);color:#0e83cd;">Batizado(a)</span><?php endif; ?>
    <?php if ($linkZap): ?><a href="<?= escaparHtml($linkZap) ?>" target="_blank" rel="noopener" class="botao-mini" style="background:#25D366;color:#fff;border-color:#25D366;">💬 WhatsApp</a><?php endif; ?>
  </div>
</div>

<?php if ($sucesso): ?><div class="mensagem-flash"><?= escaparHtml($sucesso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem-erro"><?= escaparHtml($erro) ?></div><?php endif; ?>

<div class="grid grid-2" style="align-items:start;gap:32px;">
  <div>
    <span class="rotulo">Dados pessoais</span>
    <form method="post" enctype="multipart/form-data" class="formulario" style="margin-top:14px;">
      <input type="hidden" name="acao" value="atualizar_membro">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="campo">
        <label for="nome">Nome completo *</label>
        <input type="text" id="nome" name="nome" value="<?= escaparHtml($membro['nome']) ?>" required>
      </div>
      <div class="campo">
        <label for="foto">Foto de perfil</label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="data_nascimento">Data de nascimento</label>
          <input type="date" id="data_nascimento" name="data_nascimento" value="<?= escaparHtml($membro['data_nascimento']) ?>">
        </div>
        <div class="campo">
          <label for="telefone">Telefone / WhatsApp</label>
          <input type="text" id="telefone" name="telefone" value="<?= escaparHtml($membro['telefone']) ?>">
        </div>
      </div>
      <div class="campo">
        <label for="endereco">Endereço</label>
        <input type="text" id="endereco" name="endereco" value="<?= escaparHtml($membro['endereco']) ?>">
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="bairro">Bairro</label>
          <input type="text" id="bairro" name="bairro" value="<?= escaparHtml($membro['bairro']) ?>">
        </div>
        <div class="campo">
          <label for="cidade">Cidade</label>
          <input type="text" id="cidade" name="cidade" value="<?= escaparHtml($membro['cidade'] ?: 'São Vicente') ?>">
        </div>
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="estado_civil">Estado civil</label>
          <select id="estado_civil" name="estado_civil">
            <option value="">-</option>
            <?php foreach (['solteiro'=>'Solteiro(a)','casado'=>'Casado(a)','viuvo'=>'Viúvo(a)','divorciado'=>'Divorciado(a)'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $membro['estado_civil']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="membro_desde">Membro desde</label>
          <input type="date" id="membro_desde" name="membro_desde" value="<?= escaparHtml($membro['membro_desde']) ?>">
        </div>
      </div>
      <div class="grade-campos">
        <div class="campo">
          <label for="modo_recepcao">Modo de recepção</label>
          <select id="modo_recepcao" name="modo_recepcao">
            <option value="">-</option>
            <?php foreach (['batismo'=>'Batismo','carta'=>'Carta','aclamacao'=>'Aclamação','reconciliacao'=>'Reconciliação'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $membro['modo_recepcao']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="data_batismo">Data de batismo</label>
          <input type="date" id="data_batismo" name="data_batismo" value="<?= escaparHtml($membro['data_batismo']) ?>">
        </div>
      </div>
      <div class="campo">
        <label><input type="checkbox" name="batizado" value="1" <?= $membro['batizado']?'checked':'' ?> style="width:auto;display:inline-block;margin-right:8px;">Batizado(a)</label>
      </div>
      <div class="campo">
        <label><input type="checkbox" name="ativo" value="1" <?= $membro['ativo']?'checked':'' ?> style="width:auto;display:inline-block;margin-right:8px;">Membro ativo</label>
      </div>
      <div class="campo">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes"><?= escaparHtml($membro['observacoes']) ?></textarea>
      </div>
      <button type="submit" class="botao-primario">Salvar alterações</button>
    </form>
  </div>

  <div>
    <span class="rotulo">Família</span>
    <?php if ($familiaAtual): ?>
    <p style="margin-top:10px;">Vinculado à família <a href="familia.php?id=<?= (int)$familiaAtual['id'] ?>"><strong><?= escaparHtml($familiaAtual['nome_familia']) ?></strong></a> (<?= escaparHtml($membro['parentesco_familia'] ?: 'parentesco não informado') ?>)</p>
    <?php else: ?>
    <p style="margin-top:10px;color:var(--text-muted);">Ainda não vinculado a nenhuma família cadastrada.</p>
    <?php endif; ?>
    <form method="post" class="formulario" style="margin-top:12px;max-width:none;">
      <input type="hidden" name="acao" value="ligar_familia">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="grade-campos">
        <div class="campo">
          <label for="familia_id">Família</label>
          <select id="familia_id" name="familia_id">
            <option value="">Nenhuma</option>
            <?php foreach ($familias as $f): ?>
            <option value="<?= (int)$f['id'] ?>" <?= $membro['familia_id']==$f['id']?'selected':'' ?>><?= escaparHtml($f['nome_familia']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="parentesco_familia">Parentesco</label>
          <input type="text" id="parentesco_familia" name="parentesco_familia" value="<?= escaparHtml($membro['parentesco_familia']) ?>" placeholder="Pai, mãe, filho(a)...">
        </div>
      </div>
      <button type="submit" class="botao-mini">Salvar vínculo</button>
    </form>

    <span class="rotulo" style="margin-top:28px;display:block;">Ministérios</span>
    <table class="tabela-admin" style="margin-top:12px;">
      <thead><tr><th>Ministério</th><th>Função</th><th></th></tr></thead>
      <tbody>
        <?php if (!$ministeriosDoMembro): ?>
        <tr><td colspan="3" style="color:var(--text-muted);">Não serve em nenhum ministério ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($ministeriosDoMembro as $mm): ?>
        <tr>
          <td><?= escaparHtml($mm['nome']) ?></td>
          <td><?= escaparHtml($mm['funcao']) ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Remover deste ministério?');">
              <input type="hidden" name="acao" value="remover_ministerio">
              <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
              <input type="hidden" name="ministerio_id" value="<?= (int)$mm['ministerio_id'] ?>">
              <button type="submit" class="botao-mini perigo">Remover</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" class="formulario" style="margin-top:12px;max-width:none;">
      <input type="hidden" name="acao" value="add_ministerio">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="grade-campos">
        <div class="campo">
          <label for="ministerio_id">Adicionar a um ministério</label>
          <select id="ministerio_id" name="ministerio_id">
            <?php foreach ($ministerios as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= escaparHtml($m['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="funcao">Função</label>
          <input type="text" id="funcao" name="funcao" placeholder="Líder, voluntário(a)...">
        </div>
      </div>
      <button type="submit" class="botao-mini">Vincular</button>
    </form>

    <?php if (ehAdmin()): ?>
    <span class="rotulo" style="margin-top:28px;display:block;">🔒 Acesso ao painel</span>
    <p style="color:var(--text-muted);font-size:13px;margin-top:6px;">Só quem tem e-mail e senha aqui consegue entrar no painel de colaboradores.</p>
    <form method="post" class="formulario" style="margin-top:12px;max-width:none;">
      <input type="hidden" name="acao" value="acesso_painel">
      <input type="hidden" name="csrf" value="<?= gerarTokenCsrf() ?>">
      <div class="grade-campos">
        <div class="campo">
          <label for="email">E-mail de acesso</label>
          <input type="email" id="email" name="email" value="<?= escaparHtml($membro['email']) ?>">
        </div>
        <div class="campo">
          <label for="papel">Papel no painel</label>
          <select id="papel" name="papel">
            <?php foreach (['membro'=>'Membro (sem acesso)','lider'=>'Líder','admin'=>'Administrador'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $membro['papel']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="campo">
        <label for="nova_senha">Definir/redefinir senha (deixe em branco pra não alterar)</label>
        <input type="password" id="nova_senha" name="nova_senha" placeholder="Nova senha">
      </div>
      <button type="submit" class="botao-mini">Salvar acesso</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
