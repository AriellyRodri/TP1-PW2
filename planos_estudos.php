<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(1);

//  REMOVER 
if (isset($_GET['del'])) {
  $id = (int) $_GET['del'];
  $pdo->prepare('DELETE FROM plano_estudos WHERE ID=?')->execute([$id]);
  header('Location: planos_estudos.php?ok=Vínculo+removido.');
  exit;
}

//  ADICIONAR 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $curso = (int) $_POST['curso'];
  $disc = (int) $_POST['disciplina'];
  $ano = (int) $_POST['ano'];
  $sem = (int) $_POST['semestre'];

  if ($curso > 0 && $disc > 0 && $ano >= 1 && $sem >= 1) {
    $chk = $pdo->prepare('SELECT ID FROM plano_estudos WHERE CURSOS=? AND DISCIPLINA=? AND ano=? AND semestre=?');
    $chk->execute([$curso, $disc, $ano, $sem]);
    if ($chk->fetch()) {
      header('Location: planos_estudos.php?err=Esta+disciplina+já+existe+neste+curso%2Fano%2Fsemestre.');
      exit;
    }
    $pdo->prepare('INSERT INTO plano_estudos (CURSOS, DISCIPLINA, ano, semestre) VALUES (?,?,?,?)')
      ->execute([$curso, $disc, $ano, $sem]);
    header('Location: planos_estudos.php?ok=Disciplina+vinculada+com+sucesso.');
  } else {
    header('Location: planos_estudos.php?err=Preencha+todos+os+campos.');
  }
  exit;
}

//  DADOS 
$cursos = $pdo->query('SELECT ID, Nome FROM cursos WHERE ativo=1 ORDER BY Nome')->fetchAll();
$disciplinas = $pdo->query('SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc')->fetchAll();
$plano = $pdo->query("
    SELECT pe.ID, c.Nome AS curso, d.Nome_disc AS disciplina, pe.ano, pe.semestre
    FROM plano_estudos pe
    JOIN cursos c      ON pe.CURSOS     = c.ID
    JOIN disciplinas d ON pe.DISCIPLINA = d.ID
    ORDER BY c.Nome, pe.ano, pe.semestre, d.Nome_disc
")->fetchAll();

renderHeader('Plano de Estudos', 1, $_SESSION['user']);
?>

<style>
  .form-plano {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr auto;
    gap: 16px;
    align-items: flex-end;
  }

  .form-plano .form-group {
    margin-bottom: 0;
  }

  .btn-nowrap {
    white-space: nowrap;
  }

  @media (max-width: 900px) {
    .form-plano {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 600px) {
    .form-plano {
      grid-template-columns: 1fr;
    }
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-list-check"></i> Plano de Estudos
</h1>

<?php if (isset($_GET['ok'])):
  echo msg('success', $_GET['ok']); endif; ?>
<?php if (isset($_GET['err'])):
  echo msg('danger', $_GET['err']); endif; ?>

<div class="card">
  <div class="card-title">Associar UC ao Curso</div>
  <form method="POST">
    <div class="form-plano">

      <div class="form-group">
        <label>Curso</label>
        <select class="form-control" name="curso" required>
          <option value="">Selecionar...</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= $c['ID'] ?>">
              <?= htmlspecialchars($c['Nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Disciplina / UC</label>
        <select class="form-control" name="disciplina" required>
          <option value="">Selecionar...</option>
          <?php foreach ($disciplinas as $d): ?>
            <option value="<?= $d['ID'] ?>">
              <?= htmlspecialchars($d['Nome_disc']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Ano</label>
        <select class="form-control" name="ano" required>
          <option value="1">1.º Ano</option>
          <option value="2">2.º Ano</option>
          <option value="3">3.º Ano</option>
        </select>
      </div>

      <div class="form-group">
        <label>Semestre</label>
        <select class="form-control" name="semestre" required>
          <option value="1">1.º Sem.</option>
          <option value="2">2.º Sem.</option>
        </select>
      </div>

      <div class="form-group">
        <button class="btn btn-primary btn-nowrap" type="submit" name="add">
          <i class="fa-solid fa-link"></i> Associar
        </button>
      </div>

    </div>
  </form>
</div>

<div class="card">
  <div class="card-title">Associações Atuais</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Curso</th>
          <th>Disciplina / UC</th>
          <th>Ano</th>
          <th>Semestre</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($plano as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['curso']) ?></td>
            <td><?= htmlspecialchars($row['disciplina']) ?></td>
            <td><?= $row['ano'] ?>º</td>
            <td><?= $row['semestre'] ?>º</td>
            <td>
              <a class="btn btn-danger btn-sm" href="?del=<?= $row['ID'] ?>"
                onclick="return confirm('Remover esta associação?')">
                <i class="fa-solid fa-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php renderFooter(); ?>