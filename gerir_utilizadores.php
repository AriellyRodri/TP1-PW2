<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(1);

// ================================================================
// FUNÇÃO: calcular próximo login de aluno (aluno1, aluno2, ...)
// ================================================================
function proximoLoginAluno(PDO $pdo): string
{
  $stmt = $pdo->query("SELECT login FROM users WHERE login REGEXP '^aluno[0-9]+$'");
  $numeros = [];
  foreach ($stmt->fetchAll() as $row) {
    $n = (int) substr($row['login'], 5);
    $numeros[] = $n;
  }
  if (empty($numeros))
    return 'aluno1';
  sort($numeros);
  $proximo = 1;
  foreach ($numeros as $n) {
    if ($n === $proximo)
      $proximo++;
  }
  return 'aluno' . $proximo;
}

// ================================================================
// FUNÇÃO: calcular próximo número de aluno (500, 501, ...)
// ================================================================
function proximoNumeroAluno(PDO $pdo): string
{
  $stmt = $pdo->query(
    'SELECT MAX(CAST(numero_aluno AS UNSIGNED)) FROM users WHERE grupo = 3 AND numero_aluno IS NOT NULL'
  );
  $max = (int) $stmt->fetchColumn();
  return (string) max(500, $max + 1);
}

// ================================================================
// APAGAR UTILIZADOR
// ================================================================
if (isset($_GET['del'])) {
  $id = (int) $_GET['del'];
  if ($id === (int) $_SESSION['user_id']) {
    header('Location: gerir_utilizadores.php?err=' . urlencode('Não pode apagar o seu próprio utilizador.'));
    exit;
  }
  $chk = $pdo->prepare('SELECT COUNT(*) FROM fichas_aluno WHERE user_id = ?');
  $chk->execute([$id]);
  if ((int) $chk->fetchColumn() > 0) {
    header('Location: gerir_utilizadores.php?err=' . urlencode('Não pode apagar: o utilizador tem ficha associada.'));
    exit;
  }
  $pdo->prepare('DELETE FROM users WHERE ID = ?')->execute([$id]);
  header('Location: gerir_utilizadores.php?ok=' . urlencode('Utilizador removido com sucesso.'));
  exit;
}

// ================================================================
// CRIAR NOVO UTILIZADOR
// ================================================================
$erros_criar = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {

  $grupo = (int) ($_POST['grupo'] ?? 0);
  $pass = $_POST['pwd'] ?? '';
  $pass2 = $_POST['pwd2'] ?? '';

  if ($grupo === 3) {
    $login = proximoLoginAluno($pdo);
  } else {
    $login = trim($_POST['login'] ?? '');
    if ($login === '')
      $erros_criar[] = 'O login é obrigatório.';
    if (strlen($login) < 3)
      $erros_criar[] = 'O login deve ter pelo menos 3 caracteres.';
    if (!preg_match('/^[a-zA-Z0-9._@-]+$/', $login))
      $erros_criar[] = 'O login só pode ter letras, números e . _ @ -';
  }

  if (strlen($pass) < 4)
    $erros_criar[] = 'A password deve ter pelo menos 4 caracteres.';
  if ($pass !== $pass2)
    $erros_criar[] = 'As passwords não coincidem.';
  if (!in_array($grupo, [1, 2, 3]))
    $erros_criar[] = 'Selecione um perfil válido.';

  if (empty($erros_criar) && $grupo !== 3) {
    $dup = $pdo->prepare('SELECT ID FROM users WHERE login = ?');
    $dup->execute([$login]);
    if ($dup->fetch())
      $erros_criar[] = "O login \"$login\" já existe.";
  }

  if (empty($erros_criar)) {
    $hash = md5($pass);
    $numAluno = ($grupo === 3) ? proximoNumeroAluno($pdo) : null;

    $pdo->prepare('INSERT INTO users (login, pwd, grupo, numero_aluno) VALUES (?, ?, ?, ?)')
      ->execute([$login, $hash, $grupo, $numAluno]);

    $ok = "Utilizador criado com sucesso.";
    if ($grupo === 3) {
      $ok = "Aluno criado: login <strong>$login</strong> · Nº de aluno <strong>$numAluno</strong>.";
    }
    header('Location: gerir_utilizadores.php?ok=' . urlencode($ok));
    exit;
  }
}

// ================================================================
// EDITAR UTILIZADOR
// ================================================================
$erros_editar = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {

  $id = (int) ($_POST['id'] ?? 0);
  $grupo = (int) ($_POST['grupo'] ?? 0);
  $pass = $_POST['pwd'] ?? '';
  $pass2 = $_POST['pwd2'] ?? '';

  $atual = $pdo->prepare('SELECT grupo, login, numero_aluno FROM users WHERE ID = ?');
  $atual->execute([$id]);
  $dadosAtuais = $atual->fetch();

  if ($dadosAtuais['grupo'] === 3 || $grupo === 3) {
    $novoLogin = $dadosAtuais['login'];
  } else {
    $novoLogin = trim($_POST['login'] ?? '');
    if ($novoLogin === '')
      $erros_editar[] = 'O login é obrigatório.';
    if (strlen($novoLogin) < 3)
      $erros_editar[] = 'O login deve ter pelo menos 3 caracteres.';
    if (!preg_match('/^[a-zA-Z0-9._@-]+$/', $novoLogin))
      $erros_editar[] = 'O login só pode ter letras, números e . _ @ -';
  }

  if (!in_array($grupo, [1, 2, 3]))
    $erros_editar[] = 'Selecione um perfil válido.';
  if ($pass !== '' && strlen($pass) < 4)
    $erros_editar[] = 'A nova password deve ter pelo menos 4 caracteres.';
  if ($pass !== '' && $pass !== $pass2)
    $erros_editar[] = 'As passwords não coincidem.';

  if (empty($erros_editar) && !($dadosAtuais['grupo'] === 3 || $grupo === 3)) {
    $dup = $pdo->prepare('SELECT ID FROM users WHERE login = ? AND ID != ?');
    $dup->execute([$novoLogin, $id]);
    if ($dup->fetch())
      $erros_editar[] = "O login \"$novoLogin\" já está em uso.";
  }

  if (empty($erros_editar)) {
    $numAluno = $dadosAtuais['numero_aluno'];
    if ($grupo === 3 && $dadosAtuais['grupo'] != 3) {
      $novoLogin = proximoLoginAluno($pdo);
      $numAluno = proximoNumeroAluno($pdo);
    } elseif ($grupo !== 3) {
      $numAluno = null;
    }

    if ($pass !== '') {
      $hash = md5($pass);
      $pdo->prepare('UPDATE users SET login=?, pwd=?, grupo=?, numero_aluno=? WHERE ID=?')
        ->execute([$novoLogin, $hash, $grupo, $numAluno, $id]);
    } else {
      $pdo->prepare('UPDATE users SET login=?, grupo=?, numero_aluno=? WHERE ID=?')
        ->execute([$novoLogin, $grupo, $numAluno, $id]);
    }

    if ($id === (int) $_SESSION['user_id']) {
      $_SESSION['user'] = $novoLogin;
    }

    header('Location: gerir_utilizadores.php?ok=' . urlencode('Utilizador atualizado com sucesso.'));
    exit;
  }
}

// ================================================================
// LISTAR
// ================================================================
$utilizadores = $pdo->query("
    SELECT u.ID, u.login, u.grupo, u.numero_aluno, g.GRUPO AS grupo_nome
    FROM users u JOIN grupos g ON u.grupo = g.ID
    ORDER BY u.grupo ASC, u.numero_aluno ASC, u.login ASC
")->fetchAll();

$grupos = $pdo->query('SELECT * FROM grupos ORDER BY ID')->fetchAll();

$proximoLogin = proximoLoginAluno($pdo);
$proximoNum = proximoNumeroAluno($pdo);

$editando = null;
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare('SELECT * FROM users WHERE ID = ?');
  $stmt->execute([(int) $_GET['editar']]);
  $editando = $stmt->fetch();
}

renderHeader('Gestão de Utilizadores', 1, $_SESSION['user']);
?>

<style>
  .preview-aluno {
    display: none;
    margin-bottom: 16px;
  }

  .preview-aluno .preview-boxes {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
  }

  .preview-box {
    padding: 12px 20px;
    border-radius: 8px;
    min-width: 160px;
  }

  .preview-box.green {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
  }

  .preview-box.blue {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
  }

  .preview-box .box-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
  }

  .preview-box.green .box-label {
    color: #16a34a;
  }

  .preview-box.blue .box-label {
    color: #0891b2;
  }

  .preview-box .box-value {
    font-size: 20px;
    font-weight: 700;
    margin-top: 4px;
  }

  .preview-box.green .box-value {
    color: #166534;
  }

  .preview-box.blue .box-value {
    color: #0c4a6e;
  }

  .preview-aluno .preview-hint {
    color: #6b7280;
    font-size: 12px;
    margin-top: 6px;
    display: block;
  }

  .input-disabled {
    background: #f9fafb;
    color: #6b7280;
  }

  .field-hint {
    color: #6b7280;
    font-size: 12px;
    margin-top: 4px;
    display: block;
  }

  .badge-perfil {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    color: white;
  }

  .badge-perfil.gestor {
    background: #7c3aed;
  }

  .badge-perfil.funcionario {
    background: #0891b2;
  }

  .badge-perfil.aluno {
    background: #059669;
  }

  .num-aluno {
    font-weight: 700;
    color: #0891b2;
  }

  .num-aluno-vazio {
    color: #d1d5db;
  }

  .input-num-aluno {
    background: #f0f9ff;
    font-weight: 700;
    color: #0891b2;
    max-width: 140px;
  }
</style>

<h1 class="page-title"><i class="fa-solid fa-users-gear"></i> Gestão de Utilizadores</h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success">
    <i class="fa-solid fa-circle-check"></i> <?= $_GET['ok'] ?>
  </div>
<?php endif; ?>
<?php if (isset($_GET['err'])):
  echo msg('danger', $_GET['err']); endif; ?>

<!-- ============================================================
     FORMULÁRIO CRIAR / EDITAR
     ============================================================ -->
<div class="card">

  <?php if ($editando): ?>
    <!-- ── EDITAR ── -->
    <div class="card-title">
      <i class="fa-solid fa-pen" style="color:#d97706;"></i>
      Editar Utilizador — <code><?= htmlspecialchars($editando['login']) ?></code>
    </div>

    <?php if (!empty($erros_editar)): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-xmark"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $erros_editar)) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" value="<?= $editando['ID'] ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Login</label>
          <?php if ($editando['grupo'] == 3): ?>
            <input class="form-control input-disabled" type="text" value="<?= htmlspecialchars($editando['login']) ?>"
              disabled>
            <span class="field-hint">Login automático — não editável em alunos.</span>
          <?php else: ?>
            <input class="form-control" type="text" name="login" required maxlength="100"
              value="<?= htmlspecialchars($_POST['login'] ?? $editando['login']) ?>">
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label>Perfil *</label>
          <select class="form-control" name="grupo" required>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= $g['ID'] ?>" <?= (($_POST['grupo'] ?? $editando['grupo']) == $g['ID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['GRUPO']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <?php if ($editando['numero_aluno']): ?>
        <div class="form-group">
          <label>Nº de Aluno</label>
          <input class="form-control input-num-aluno" type="text" value="<?= htmlspecialchars($editando['numero_aluno']) ?>"
            disabled>
          <span class="field-hint">Atribuído automaticamente — não editável.</span>
        </div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>Nova Password
            <span style="font-weight:400;color:#9ca3af;">(em branco = não alterar)</span>
          </label>
          <input class="form-control" type="password" name="pwd" placeholder="Nova password (mín. 4 caracteres)">
        </div>
        <div class="form-group">
          <label>Confirmar Nova Password</label>
          <input class="form-control" type="password" name="pwd2" placeholder="Repetir nova password">
        </div>
      </div>

      <div class="btn-group">
        <button class="btn btn-warning" type="submit">
          <i class="fa-solid fa-floppy-disk"></i> Guardar Alterações
        </button>
        <a class="btn btn-secondary" href="gerir_utilizadores.php">
          <i class="fa-solid fa-xmark"></i> Cancelar
        </a>
      </div>
    </form>

  <?php else: ?>
    <!-- ── CRIAR ── -->
    <div class="card-title">
      <i class="fa-solid fa-user-plus" style="color:#1a56db;"></i>
      Criar Novo Utilizador
    </div>

    <?php if (!empty($erros_criar)): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-xmark"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $erros_criar)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="formCriar">
      <input type="hidden" name="acao" value="criar">

      <div class="form-row">
        <div class="form-group" id="campoLogin">
          <label>Login *</label>
          <input class="form-control" type="text" name="login" id="inputLogin" maxlength="100"
            value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" placeholder="ex: gestor2">
        </div>

        <div class="form-group">
          <label>Perfil *</label>
          <select class="form-control" name="grupo" id="selectGrupo" required onchange="atualizarFormulario(this.value)">
            <option value="">Selecionar...</option>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= $g['ID'] ?>" <?= (($_POST['grupo'] ?? '') == $g['ID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['GRUPO']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Preview automático para alunos -->
      <div class="preview-aluno" id="previewAluno">
        <div class="preview-boxes">
          <div class="preview-box green">
            <div class="box-label">Login atribuído</div>
            <div class="box-value"><?= htmlspecialchars($proximoLogin) ?></div>
          </div>
          <div class="preview-box blue">
            <div class="box-label">Nº de aluno</div>
            <div class="box-value"><?= htmlspecialchars($proximoNum) ?></div>
          </div>
        </div>
        <span class="preview-hint">
          <i class="fa-solid fa-circle-info"></i>
          Gerados automaticamente — o login e o número de aluno são atribuídos em sequência.
        </span>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Password *</label>
          <input class="form-control" type="password" name="pwd" required placeholder="Mínimo 4 caracteres">
        </div>
        <div class="form-group">
          <label>Confirmar Password *</label>
          <input class="form-control" type="password" name="pwd2" required placeholder="Repetir password">
        </div>
      </div>

      <button class="btn btn-primary" type="submit">
        <i class="fa-solid fa-user-plus"></i> Criar Utilizador
      </button>
    </form>

  <?php endif; ?>
</div>

<!-- ============================================================
     TABELA DE UTILIZADORES
     ============================================================ -->
<div class="card">
  <div class="card-title">
    <i class="fa-solid fa-list" style="color:#1a56db;"></i>
    Utilizadores Registados (<?= count($utilizadores) ?>)
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Login</th>
          <th>Perfil</th>
          <th>Nº Aluno</th>
          <th style="width:160px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $classeGrupo = [1 => 'gestor', 2 => 'funcionario', 3 => 'aluno'];
        $labelGrupo = [1 => 'GESTOR', 2 => 'FUNCIONÁRIO', 3 => 'ALUNO'];
        foreach ($utilizadores as $u):
          $classe = $classeGrupo[$u['grupo']] ?? '';
          $label = $labelGrupo[$u['grupo']] ?? '?';
          ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($u['login']) ?></td>
            <td>
              <span class="badge-perfil <?= $classe ?>"><?= $label ?></span>
            </td>
            <td>
              <?php if ($u['numero_aluno']): ?>
                <span class="num-aluno"><?= htmlspecialchars($u['numero_aluno']) ?></span>
              <?php else: ?>
                <span class="num-aluno-vazio">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="btn-group">
                <a class="btn btn-warning btn-sm" href="gerir_utilizadores.php?editar=<?= $u['ID'] ?>">
                  <i class="fa-solid fa-pen"></i> Editar
                </a>
                <?php if ($u['ID'] !== (int) $_SESSION['user_id']): ?>
                  <a class="btn btn-danger btn-sm" href="gerir_utilizadores.php?del=<?= $u['ID'] ?>"
                    onclick="return confirm('Apagar o utilizador \'<?= htmlspecialchars(addslashes($u['login'])) ?>\'?')">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function atualizarFormulario(grupo) {
    const campoLogin = document.getElementById('campoLogin');
    const inputLogin = document.getElementById('inputLogin');
    const previewAluno = document.getElementById('previewAluno');

    if (grupo === '3') {
      campoLogin.style.display = 'none';
      previewAluno.style.display = 'block';
      inputLogin.required = false;
    } else {
      campoLogin.style.display = 'block';
      previewAluno.style.display = 'none';
      inputLogin.required = true;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('selectGrupo');
    if (sel) atualizarFormulario(sel.value);
  });
</script>

<?php renderFooter(); ?>