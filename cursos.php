<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(1);

$msg = '';

// DESATIVAR / REATIVAR
if (isset($_GET['toggle'])) {
  $id = (int) $_GET['toggle'];
  $stmt = $pdo->prepare('SELECT ativo FROM cursos WHERE ID=?');
  $stmt->execute([$id]);
  $cur = $stmt->fetch();
  if ($cur) {
    $novoEstado = $cur['ativo'] ? 0 : 1;
    $pdo->prepare('UPDATE cursos SET ativo=? WHERE ID=?')->execute([$novoEstado, $id]);
    $msg = $novoEstado ? 'Curso reativado.' : 'Curso desativado.';
  }
  header('Location: cursos.php?ok=' . urlencode($msg));
  exit;
}

// ADICIONAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $nome = trim($_POST['nome'] ?? '');
  if ($nome !== '') {
    $pdo->prepare('INSERT INTO cursos (Nome) VALUES (?)')->execute([$nome]);
    header('Location: cursos.php?ok=Curso+adicionado+com+sucesso.');
    exit;
  }
  $msg = 'O nome do curso não pode estar vazio.';
}

// EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
  $id = (int) $_POST['id'];
  $nome = trim($_POST['nome'] ?? '');
  if ($nome !== '') {
    $pdo->prepare('UPDATE cursos SET Nome=? WHERE ID=?')->execute([$nome, $id]);
    header('Location: cursos.php?ok=Curso+atualizado.');
    exit;
  }
  $msg = 'O nome não pode estar vazio.';
}

$cursos = $pdo->query('SELECT * FROM cursos ORDER BY ativo DESC, Nome')->fetchAll();

renderHeader('Gestão de Cursos', 1, $_SESSION['user']);
?>

<style>
  .form-flex {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .input-flex {
    flex: 1;
    min-width: 220px;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-graduation-cap"></i>
  Gestão de Cursos
</h1>

<?php if (isset($_GET['ok'])):
  echo msg('success', $_GET['ok']);
endif; ?>
<?php if ($msg !== ''):
  echo msg('danger', $msg);
endif; ?>

<div class="card">
  <div class="card-title">Adicionar Curso</div>
  <form method="POST" class="form-flex">
    <input class="form-control input-flex" type="text" name="nome" placeholder="Nome do curso" required>
    <button class="btn btn-primary" type="submit" name="add">
      <i class="fa-solid fa-plus"></i> Adicionar
    </button>
  </form>
</div>

<div class="card">
  <div class="card-title">Lista de Cursos</div>
  <div class="table-wrap">
    <table>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Estado</th>
        <th>Ações</th>
      </tr>

      <?php foreach ($cursos as $row): ?>
        <tr>
          <td><?= $row['ID'] ?></td>
          <td><?= htmlspecialchars($row['Nome']) ?></td>
          <td>
            <span class="badge <?= $row['ativo'] ? 'badge-aprovada' : 'badge-rejeitada' ?>">
              <?= $row['ativo'] ? 'Ativo' : 'Desativado' ?>
            </span>
          </td>
          <td>
            <div class="btn-group">
              <a class="btn btn-warning btn-sm" href="?edit=<?= $row['ID'] ?>">
                <i class="fa-solid fa-pen"></i> Editar
              </a>

              <a class="btn btn-sm <?= $row['ativo'] ? 'btn-danger' : 'btn-success' ?>" href="?toggle=<?= $row['ID'] ?>"
                onclick="return confirm('<?= $row['ativo'] ? 'Desativar' : 'Reativar' ?> este curso?')">
                <i class="fa-solid <?= $row['ativo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                <?= $row['ativo'] ? 'Desativar' : 'Reativar' ?>
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php if (isset($_GET['edit'])):
  $id = (int) $_GET['edit'];
  $cur = $pdo->prepare('SELECT * FROM cursos WHERE ID=?');
  $cur->execute([$id]);
  $curso = $cur->fetch();
  if ($curso):
    ?>

    <div class="card">
      <div class="card-title">Editar Curso</div>
      <form method="POST" class="form-flex">
        <input type="hidden" name="id" value="<?= $curso['ID'] ?>">

        <input class="form-control input-flex" type="text" name="nome" value="<?= htmlspecialchars($curso['Nome']) ?>"
          required>

        <button class="btn btn-success" type="submit" name="edit">
          <i class="fa-solid fa-floppy-disk"></i> Guardar
        </button>

        <a class="btn btn-secondary" href="cursos.php">Cancelar</a>
      </form>
    </div>

  <?php endif; endif; ?>

<?php renderFooter(); ?>