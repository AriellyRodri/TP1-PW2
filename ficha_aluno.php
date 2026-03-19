<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(3);

$user_id = $_SESSION['user_id'];
$msgType = '';
$msgText = '';

// Buscar ficha existente
$stmt = $pdo->prepare('SELECT * FROM fichas_aluno WHERE user_id=?');
$stmt->execute([$user_id]);
$ficha = $stmt->fetch();

$podeEditar = !$ficha || in_array($ficha['estado'], ['rascunho', 'rejeitada']);

$cursos = $pdo->query('SELECT ID,Nome FROM cursos WHERE ativo=1 ORDER BY Nome')->fetchAll();

// GUARDAR / SUBMETER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $podeEditar) {
  $action = $_POST['action'] ?? 'guardar';
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefone = trim($_POST['telefone'] ?? '');
  $data_nasc = $_POST['data_nascimento'] ?? '';
  $morada = trim($_POST['morada'] ?? '');
  $curso_id = (int) ($_POST['curso_id'] ?? 0);

  $erros = [];
  if ($nome === '')
    $erros[] = 'Nome obrigatório.';
  if ($email === '')
    $erros[] = 'Email obrigatório.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $erros[] = 'Email inválido.';
  if ($curso_id <= 0)
    $erros[] = 'Selecione um curso.';

  $fotoNova = $ficha['foto'] ?? null;

  if (!empty($_FILES['foto']['name'])) {
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];
    $maxSize = 2 * 1024 * 1024;

    if (!in_array($ext, $allowed)) {
      $erros[] = 'Foto: apenas JPG ou PNG.';
    } elseif ($_FILES['foto']['size'] > $maxSize) {
      $erros[] = 'Foto: máximo 2MB.';
    } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
      $erros[] = 'Erro no upload.';
    } else {
      $dir = 'uploads/fotos/';
      if (!is_dir($dir))
        mkdir($dir, 0755, true);

      $fotoNome = 'aluno_' . $user_id . '_' . time() . '.' . $ext;

      if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $fotoNome)) {
        if ($fotoNova && file_exists($dir . $fotoNova))
          unlink($dir . $fotoNova);
        $fotoNova = $fotoNome;
      } else {
        $erros[] = 'Falha ao guardar foto.';
      }
    }
  }

  if (empty($erros)) {
    $estado = ($action === 'submeter') ? 'submetida' : 'rascunho';

    if (!$ficha) {
      $pdo->prepare("INSERT INTO fichas_aluno
        (user_id,nome,email,telefone,data_nascimento,morada,curso_id,foto,estado)
        VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$user_id, $nome, $email, $telefone, $data_nasc, $morada, $curso_id, $fotoNova, $estado]);
    } else {
      $pdo->prepare("UPDATE fichas_aluno SET
        nome=?,email=?,telefone=?,data_nascimento=?,morada=?,curso_id=?,foto=?,estado=?
        WHERE user_id=?")
        ->execute([$nome, $email, $telefone, $data_nasc, $morada, $curso_id, $fotoNova, $estado, $user_id]);
    }

    $_SESSION['nome_display'] = $nome;
    $msgType = 'success';
    $msgText = ($action === 'submeter') ? 'Ficha submetida!' : 'Rascunho guardado.';

    $stmt->execute([$user_id]);
    $ficha = $stmt->fetch();
    $podeEditar = !$ficha || in_array($ficha['estado'], ['rascunho', 'rejeitada']);
  } else {
    $msgType = 'danger';
    $msgText = implode(' | ', $erros);
  }
}

if ($ficha && !empty($ficha['nome'])) {
  $_SESSION['nome_display'] = $ficha['nome'];
}

renderHeader('Minha Ficha', 3, $_SESSION['nome_display'] ?? $_SESSION['user']);
?>

<style>
  .status-box {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }

  .status-text {
    font-size: 14px;
    color: #6b7280;
  }

  .foto-preview {
    margin-bottom: 16px;
  }

  .foto-preview img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #e5e7eb;
  }

  .btn-group-spacing {
    margin-top: 8px;
  }

  .text-muted-small {
    color: #6b7280;
    font-size: 13px;
    margin-top: 8px;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-id-card"></i> Ficha de Aluno
</h1>

<?php if ($msgText !== '')
  echo msg($msgType, $msgText); ?>

<div class="card">

  <?php if ($ficha): ?>
    <div class="status-box">
      <span class="status-text">Estado atual:</span>
      <span class="badge badge-<?= $ficha['estado'] ?>">
        <?= ucfirst($ficha['estado']) ?>
      </span>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <?php if ($ficha && $ficha['foto']): ?>
      <div class="foto-preview">
        <img src="uploads/fotos/<?= htmlspecialchars($ficha['foto']) ?>">
      </div>
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label>Nome *</label>
        <input class="form-control" type="text" name="nome" value="<?= htmlspecialchars($ficha['nome'] ?? '') ?>"
          <?= !$podeEditar ? 'disabled' : '' ?>>
      </div>

      <div class="form-group">
        <label>Email *</label>
        <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($ficha['email'] ?? '') ?>"
          <?= !$podeEditar ? 'disabled' : '' ?>>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Telefone</label>
        <input class="form-control" type="text" name="telefone"
          value="<?= htmlspecialchars($ficha['telefone'] ?? '') ?>" <?= !$podeEditar ? 'disabled' : '' ?>>
      </div>

      <div class="form-group">
        <label>Data Nascimento</label>
        <input class="form-control" type="date" name="data_nascimento"
          value="<?= htmlspecialchars($ficha['data_nascimento'] ?? '') ?>" <?= !$podeEditar ? 'disabled' : '' ?>>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Morada</label>
        <input class="form-control" type="text" name="morada" value="<?= htmlspecialchars($ficha['morada'] ?? '') ?>"
          <?= !$podeEditar ? 'disabled' : '' ?>>
      </div>

      <div class="form-group">
        <label>Curso *</label>
        <select class="form-control" name="curso_id" <?= !$podeEditar ? 'disabled' : '' ?>>
          <option value="">Selecionar...</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= $c['ID'] ?>" <?= ($ficha['curso_id'] ?? 0) == $c['ID'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['Nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if ($podeEditar): ?>

      <div class="form-group">
        <label>Fotografia</label>
        <input class="form-control" type="file" name="foto">
      </div>

      <div class="btn-group btn-group-spacing">
        <button class="btn btn-secondary" name="action" value="guardar">
          Guardar
        </button>

        <button class="btn btn-primary" name="action" value="submeter">
          Submeter
        </button>
      </div>

    <?php else: ?>
      <p class="text-muted-small">
        A ficha não pode ser editada.
      </p>
    <?php endif; ?>

  </form>
</div>

<?php renderFooter(); ?>