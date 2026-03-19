<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(1);

// VALIDAR / REJEITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
  $id = (int) $_POST['ficha_id'];
  $acao = $_POST['acao'];
  $obs = trim($_POST['observacoes'] ?? '');

  if (in_array($acao, ['aprovada', 'rejeitada'])) {
    $pdo->prepare("UPDATE fichas_aluno SET estado=?, observacoes=?, validado_por=?, data_validacao=NOW()
                   WHERE ID=? AND estado='submetida'")
      ->execute([$acao, $obs, $_SESSION['user_id'], $id]);

    header('Location: fichas_validar.php?ok=Ficha+' . ($acao === 'aprovada' ? 'aprovada' : 'rejeitada') . '+com+sucesso.');
    exit;
  }
}

$filtroEstado = $_GET['estado'] ?? 'submetida';
$estados = ['todas' => 'Todas', 'submetida' => 'Submetidas', 'aprovada' => 'Aprovadas', 'rejeitada' => 'Rejeitadas'];

$where = $filtroEstado !== 'todas' ? "WHERE fa.estado=:estado" : "";
$stmt = $pdo->prepare("
  SELECT fa.*, c.Nome AS curso, u.login AS login_aluno
  FROM fichas_aluno fa
  JOIN cursos c ON fa.curso_id=c.ID
  JOIN users u ON fa.user_id=u.ID
  $where
  ORDER BY fa.criado_em DESC
");

if ($filtroEstado !== 'todas') {
  $stmt->bindValue(':estado', $filtroEstado);
}

$stmt->execute();
$fichas = $stmt->fetchAll();

renderHeader('Fichas de Aluno', 1, $_SESSION['user']);
?>

<style>
  .filtros-box {
    padding: 16px;
  }

  .filtros-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .text-muted {
    color: #9ca3af;
  }

  .card-highlight {
    border: 2px solid #1a56db;
  }

  .foto-aluno {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    margin-bottom: 12px;
  }

  .foto-placeholder {
    width: 120px;
    height: 120px;
    background: #f3f4f6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    margin-bottom: 12px;
  }

  .table-details td:first-child {
    font-weight: 600;
    padding: 4px 12px 4px 0;
    color: #374151;
  }

  .hr-divider {
    margin: 16px 0;
    border-color: #e5e7eb;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-id-card"></i> Fichas de Aluno
</h1>

<?php if (isset($_GET['ok']))
  echo msg('success', $_GET['ok']); ?>

<!-- Filtros -->
<div class="card filtros-box">
  <div class="filtros-group">
    <?php foreach ($estados as $k => $v): ?>
      <a href="?estado=<?= $k ?>" class="btn btn-sm <?= $filtroEstado === $k ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $v ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-title">
    Fichas — <?= $estados[$filtroEstado] ?> (<?= count($fichas) ?>)
  </div>

  <?php if (empty($fichas)): ?>
    <p class="text-muted">Nenhuma ficha encontrada.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <tr>
          <th>Aluno</th>
          <th>Utilizador</th>
          <th>Curso</th>
          <th>Estado</th>
          <th>Submetida em</th>
          <th>Ações</th>
        </tr>

        <?php foreach ($fichas as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['nome']) ?></td>
            <td><?= htmlspecialchars($f['login_aluno']) ?></td>
            <td><?= htmlspecialchars($f['curso']) ?></td>
            <td>
              <span class="badge badge-<?= $f['estado'] ?>">
                <?= ucfirst($f['estado']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($f['criado_em']) ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="?ver=<?= $f['ID'] ?>&estado=<?= $filtroEstado ?>">
                <i class="fa-solid fa-eye"></i> Ver
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php
if (isset($_GET['ver'])) {
  $id = (int) $_GET['ver'];
  $s = $pdo->prepare("SELECT fa.*, c.Nome AS curso FROM fichas_aluno fa JOIN cursos c ON fa.curso_id=c.ID WHERE fa.ID=?");
  $s->execute([$id]);
  $f = $s->fetch();

  if ($f):
    ?>

    <div class="card card-highlight">
      <div class="card-title">Detalhe da Ficha #<?= $f['ID'] ?></div>

      <div class="form-row">
        <div>
          <?php if ($f['foto']): ?>
            <img src="uploads/fotos/<?= htmlspecialchars($f['foto']) ?>" class="foto-aluno">
          <?php else: ?>
            <div class="foto-placeholder">
              <i class="fa-solid fa-user fa-2x"></i>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <table class="table-details">
            <tr>
              <td>Nome</td>
              <td><?= htmlspecialchars($f['nome']) ?></td>
            </tr>
            <tr>
              <td>Email</td>
              <td><?= htmlspecialchars($f['email']) ?></td>
            </tr>
            <tr>
              <td>Telefone</td>
              <td><?= htmlspecialchars($f['telefone'] ?? '—') ?></td>
            </tr>
            <tr>
              <td>Data Nasc.</td>
              <td><?= htmlspecialchars($f['data_nascimento'] ?? '—') ?></td>
            </tr>
            <tr>
              <td>Morada</td>
              <td><?= htmlspecialchars($f['morada'] ?? '—') ?></td>
            </tr>
            <tr>
              <td>Nº Aluno</td>
              <td><?= htmlspecialchars($f['numero_aluno'] ?? '—') ?></td>
            </tr>
            <tr>
              <td>Curso</td>
              <td><?= htmlspecialchars($f['curso']) ?></td>
            </tr>
            <tr>
              <td>Estado</td>
              <td><span class="badge badge-<?= $f['estado'] ?>"><?= ucfirst($f['estado']) ?></span></td>
            </tr>

            <?php if ($f['observacoes']): ?>
              <tr>
                <td>Observações</td>
                <td><?= htmlspecialchars($f['observacoes']) ?></td>
              </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>

      <?php if ($f['estado'] === 'submetida'): ?>
        <hr class="hr-divider">

        <form method="POST">
          <input type="hidden" name="ficha_id" value="<?= $f['ID'] ?>">

          <div class="form-group">
            <label>Observações</label>
            <textarea class="form-control" name="observacoes" rows="3"></textarea>
          </div>

          <div class="btn-group">
            <button class="btn btn-success" name="acao" value="aprovada">
              <i class="fa-solid fa-circle-check"></i> Aprovar
            </button>

            <button class="btn btn-danger" name="acao" value="rejeitada">
              <i class="fa-solid fa-circle-xmark"></i> Rejeitar
            </button>

            <a class="btn btn-secondary" href="fichas_validar.php">
              <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
          </div>
        </form>

      <?php else: ?>
        <a class="btn btn-secondary" href="fichas_validar.php">
          <i class="fa-solid fa-arrow-left"></i> Voltar à lista
        </a>
      <?php endif; ?>

    </div>

  <?php endif;
} ?>

<?php renderFooter(); ?>