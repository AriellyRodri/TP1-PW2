<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(3);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT fa.*, c.Nome AS curso FROM fichas_aluno fa JOIN cursos c ON fa.curso_id=c.ID WHERE fa.user_id=?");
$stmt->execute([$user_id]);
$ficha = $stmt->fetch();

if ($ficha && $ficha['estado'] === 'aprovada') {
  $_SESSION['nome_display'] = $ficha['nome'];
} else {
  $_SESSION['nome_display'] = $_SESSION['nome_display'] ?? $_SESSION['user'];
}

$totalMatriculas = 0;
$matriculaEstado = null;
if ($ficha) {
  $m = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE ficha_id=?");
  $m->execute([$ficha['ID']]);
  $totalMatriculas = $m->fetchColumn();

  $last = $pdo->prepare("SELECT estado FROM matriculas WHERE ficha_id=? ORDER BY criado_em DESC LIMIT 1");
  $last->execute([$ficha['ID']]);
  $matriculaEstado = $last->fetchColumn();
}

renderHeader('Dashboard Aluno', 3, $_SESSION['nome_display']);
?>

<!-- CSS no HEAD (ou coloca isto dentro do layout.php no <head>) -->
<style>
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .card-center {
    text-align: center;
    padding: 20px;
  }

  .icon-large {
    font-size: 32px;
  }

  .icon-ficha-ok {
    color: #059669;
  }

  .icon-ficha-off {
    color: #9ca3af;
  }

  .icon-matricula {
    color: #1a56db;
  }

  .icon-estado {
    color: #7c3aed;
  }

  .card-title-text {
    margin: 8px 0;
    font-weight: 700;
    font-size: 15px;
  }

  .big-number {
    font-size: 28px;
    font-weight: 700;
  }

  .btn-block {
    margin-top: 12px;
    display: block;
  }

  .alert-link {
    color: inherit;
    font-weight: 600;
  }

  .text-muted {
    color: #9ca3af;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-house"></i>
  Bem-vindo, <?= htmlspecialchars($_SESSION['nome_display']) ?>!
</h1>

<div class="dashboard-grid">

  <div class="card card-center">
    <i class="fa-solid fa-id-card icon-large <?= $ficha ? 'icon-ficha-ok' : 'icon-ficha-off' ?>"></i>
    <div class="card-title-text">Ficha de Aluno</div>
    <?php if ($ficha): ?>
      <span class="badge badge-<?= $ficha['estado'] ?>"><?= ucfirst($ficha['estado']) ?></span>
    <?php else: ?>
      <span class="badge badge-rascunho">Não criada</span>
    <?php endif; ?>
    <a href="ficha_aluno.php" class="btn btn-primary btn-sm btn-block">
      <?= $ficha ? 'Ver / Editar' : 'Criar Ficha' ?>
    </a>
  </div>

  <div class="card card-center">
    <i class="fa-solid fa-file-signature icon-large icon-matricula"></i>
    <div class="card-title-text">Matrículas</div>
    <div class="big-number"><?= $totalMatriculas ?></div>
    <?php if ($matriculaEstado): ?>
      <span class="badge badge-<?= $matriculaEstado ?>"><?= ucfirst($matriculaEstado) ?></span>
    <?php endif; ?>
    <a href="matricula_pedido.php" class="btn btn-primary btn-sm btn-block">Gerir Matrícula</a>
  </div>

  <div class="card card-center">
    <i class="fa-solid fa-magnifying-glass icon-large icon-estado"></i>
    <div class="card-title-text">Estado dos Pedidos</div>
    <a href="aluno_estado.php" class="btn btn-primary btn-sm btn-block">Ver Estado</a>
  </div>

</div>

<?php if (!$ficha): ?>
  <div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    Para começar, <a href="ficha_aluno.php" class="alert-link">crie a sua ficha de aluno</a>
    e submeta-a para validação.
  </div>
<?php elseif ($ficha['estado'] === 'rejeitada'): ?>
  <div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation"></i>
    A sua ficha foi <strong>rejeitada</strong>.
    Motivo: <?= htmlspecialchars($ficha['observacoes'] ?? 'sem indicação') ?>.
    <a href="ficha_aluno.php" class="alert-link"> Corrigir ficha →</a>
  </div>
<?php endif; ?>

<?php if ($ficha && $ficha['estado'] === 'aprovada'): ?>
  <div class="card">
    <div class="card-title">
      <i class="fa-solid fa-book" style="color:#059669;"></i> Plano de Estudos do Meu Curso
    </div>

    <?php
    $pe = $pdo->prepare("
      SELECT d.Nome_disc, pe.ano, pe.semestre
      FROM plano_estudos pe
      JOIN disciplinas d ON pe.DISCIPLINA=d.ID
      WHERE pe.CURSOS=?
      ORDER BY pe.ano, pe.semestre, d.Nome_disc
    ");
    $pe->execute([$ficha['curso_id']]);
    $ucs = $pe->fetchAll();
    ?>

    <?php if (empty($ucs)): ?>
      <p class="text-muted">Sem UC associadas ao curso.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <tr>
            <th>Unidade Curricular</th>
            <th>Ano</th>
            <th>Semestre</th>
          </tr>
          <?php foreach ($ucs as $uc): ?>
            <tr>
              <td><?= htmlspecialchars($uc['Nome_disc']) ?></td>
              <td><?= $uc['ano'] ?>º</td>
              <td><?= $uc['semestre'] ?>º</td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php renderFooter(); ?>