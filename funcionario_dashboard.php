<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(2);

$pendentes = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE estado='pendente'")->fetchColumn();
$aprovadas = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE estado='aprovada'")->fetchColumn();
$totalPautas = $pdo->query("SELECT COUNT(*) FROM pautas")->fetchColumn();
$totalNotas = $pdo->query("SELECT COUNT(*) FROM notas WHERE nota IS NOT NULL")->fetchColumn();

renderHeader('Dashboard Funcionário', 2, $_SESSION['user']);
?>

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

  .icon-lg {
    font-size: 32px;
  }

  .text-big {
    font-size: 28px;
    font-weight: 700;
    margin: 8px 0;
  }

  .text-muted {
    color: #6b7280;
    font-size: 13px;
  }

  .mt-10 {
    margin-top: 10px;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .btn-column {
    display: flex;
    flex-direction: column;
  }

  .text-small-muted {
    color: #9ca3af;
    font-size: 14px;
  }

  .text-small {
    font-size: 12px;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-house"></i>
  Home — Serviços Académicos
</h1>

<div class="dashboard-grid">

  <div class="card card-center">
    <i class="fa-solid fa-hourglass-half icon-lg text-warning"></i>
    <div class="text-big"><?= $pendentes ?></div>
    <div class="text-muted">Matrículas Pendentes</div>

    <?php if ($pendentes > 0): ?>
      <a href="matriculas_gerir.php" class="btn btn-warning btn-sm mt-10">
        Tratar agora
      </a>
    <?php endif; ?>
  </div>

  <div class="card card-center">
    <i class="fa-solid fa-circle-check icon-lg text-success"></i>
    <div class="text-big"><?= $aprovadas ?></div>
    <div class="text-muted">Matrículas Aprovadas</div>
  </div>

  <div class="card card-center">
    <i class="fa-solid fa-table-list icon-lg text-primary"></i>
    <div class="text-big"><?= $totalPautas ?></div>
    <div class="text-muted">Pautas Criadas</div>
  </div>

  <div class="card card-center">
    <i class="fa-solid fa-star icon-lg text-purple"></i>
    <div class="text-big"><?= $totalNotas ?></div>
    <div class="text-muted">Notas Lançadas</div>
  </div>

</div>

<div class="grid-2">

  <div class="card">
    <div class="card-title">
      <i class="fa-solid fa-bolt text-info"></i> Ações Rápidas
    </div>

    <div class="btn-group btn-column">
      <a href="matriculas_gerir.php" class="btn btn-primary">
        <i class="fa-solid fa-file-signature"></i> Gerir Matrículas
      </a>

      <a href="pautas.php" class="btn btn-primary">
        <i class="fa-solid fa-table-list"></i> Gerir Pautas
      </a>
    </div>
  </div>

  <?php
  $recentes = $pdo->query("
  SELECT m.estado, m.criado_em, fa.nome AS aluno, c.Nome AS curso
  FROM matriculas m
  JOIN fichas_aluno fa ON m.ficha_id=fa.ID
  JOIN cursos c ON m.curso_id=c.ID
  ORDER BY m.criado_em DESC LIMIT 6
")->fetchAll();
  ?>

  <div class="card">
    <div class="card-title">
      <i class="fa-solid fa-clock-rotate-left text-info"></i> Matrículas Recentes
    </div>

    <?php if (empty($recentes)): ?>
      <p class="text-small-muted">Nenhuma matrícula registada.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <tr>
            <th>Aluno</th>
            <th>Curso</th>
            <th>Estado</th>
          </tr>

          <?php foreach ($recentes as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['aluno']) ?></td>
              <td class="text-small"><?= htmlspecialchars($r['curso']) ?></td>
              <td>
                <span class="badge badge-<?= $r['estado'] ?>">
                  <?= ucfirst($r['estado']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>

        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php renderFooter(); ?>