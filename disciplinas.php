<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(1);

// APAGAR
if (isset($_GET['del'])) {
  $id = (int) $_GET['del'];
  $use = $pdo->prepare('SELECT COUNT(*) FROM plano_estudos WHERE DISCIPLINA=?');
  $use->execute([$id]);

  if ($use->fetchColumn() > 0) {
    header('Location: disciplinas.php?err=Não+pode+apagar:+disciplina+em+uso+no+plano+de+estudos.');
  } else {
    $pdo->prepare('DELETE FROM disciplinas WHERE ID=?')->execute([$id]);
    header('Location: disciplinas.php?ok=Disciplina+removida.');
  }
  exit;
}

// ADICIONAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $nome = trim($_POST['nome'] ?? '');
  if ($nome !== '') {
    $pdo->prepare('INSERT INTO disciplinas (Nome_disc) VALUES (?)')->execute([$nome]);
    header('Location: disciplinas.php?ok=Disciplina+adicionada.');
    exit;
  }
}

// EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
  $id = (int) $_POST['id'];
  $nome = trim($_POST['nome'] ?? '');
  if ($nome !== '') {
    $pdo->prepare('UPDATE disciplinas SET Nome_disc=? WHERE ID=?')->execute([$nome, $id]);
    header('Location: disciplinas.php?ok=Disciplina+atualizada.');
    exit;
  }
}

$disciplinas = $pdo->query('SELECT * FROM disciplinas ORDER BY Nome_disc')->fetchAll();

renderHeader('Gestão de Disciplinas', 1, $_SESSION['user']);
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
  <i class="fa-solid fa-book"></i>
  Gestão de Disciplinas (UC)
</h1>

<?php if (isset($_GET['ok'])):
  echo msg('success', $_GET['ok']);
endif; ?>
<?php if (isset($_GET['err'])):
  echo msg('danger', $_GET['err']);
endif; ?>

<div class="card">
  <div class="card-title">Adicionar Unidade Curricular</div>

  <form method="POST" class="form-flex">
    <input class="form-control input-flex" type="text" name="nome" placeholder="Nome da disciplina/UC" required>

    <button class="btn btn-primary" type="submit" name="add">
      <i class="fa-solid fa-plus"></i> Adicionar
    </button>
  </form>
</div>

<div class="card">
  <div class="card-title">Lista de Disciplinas</div>

  <div class="table-wrap">
    <table>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Ações</th>
      </tr>

      <?php foreach ($disciplinas as $row): ?>
        <tr>
          <td><?= $row['ID'] ?></td>
          <td><?= htmlspecialchars($row['Nome_disc']) ?></td>
          <td>
            <div class="btn-group">
              <a class="btn btn-warning btn-sm" href="?edit=<?= $row['ID'] ?>">
                <i class="fa-solid fa-pen"></i>
              </a>

              <a class="btn btn-danger btn-sm" href="?del=<?= $row['ID'] ?>"
                onclick="return confirm('Remover disciplina?')">
                <i class="fa-solid fa-trash"></i>
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
  $row = $pdo->prepare('SELECT * FROM disciplinas WHERE ID=?');
  $row->execute([$id]);
  $disc = $row->fetch();
  if ($disc):
    ?>

    <div class="card">
      <div class="card-title">Editar Disciplina</div>

      <form method="POST" class="form-flex">
        <input type="hidden" name="id" value="<?= $disc['ID'] ?>">

        <input class="form-control input-flex" type="text" name="nome" value="<?= htmlspecialchars($disc['Nome_disc']) ?>"
          required>

        <button class="btn btn-success" type="submit" name="edit">
          <i class="fa-solid fa-floppy-disk"></i> Guardar
        </button>

        <a class="btn btn-secondary" href="disciplinas.php">Cancelar</a>
      </form>
    </div>

  <?php endif; endif; ?>

<?php renderFooter(); ?>