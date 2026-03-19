<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(1);

// Estatísticas rápidas
$totalCursos = $pdo->query('SELECT COUNT(*) FROM cursos WHERE ativo=1')->fetchColumn();
$totalDisc = $pdo->query('SELECT COUNT(*) FROM disciplinas')->fetchColumn();
$fichasPendentes = $pdo->query("SELECT COUNT(*) FROM fichas_aluno WHERE estado='submetida'")->fetchColumn();
$totalAlunos = $pdo->query("SELECT COUNT(*) FROM fichas_aluno WHERE estado='aprovada'")->fetchColumn();

$fichasRecentes = $pdo->query("
    SELECT fa.nome, fa.estado, fa.criado_em, c.Nome AS curso
    FROM fichas_aluno fa
    JOIN cursos c ON fa.curso_id = c.ID
    ORDER BY fa.criado_em DESC
    LIMIT 5
")->fetchAll();

renderHeader('Dashboard Gestor', 1, $_SESSION['user']);
?>

<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    text-align: center;
    padding: 20px;
  }

  .stat-card .stat-icon {
    font-size: 32px;
    margin-bottom: 8px;
  }

  .stat-card .stat-icon.blue {
    color: #1a56db;
  }

  .stat-card .stat-icon.purple {
    color: #7c3aed;
  }

  .stat-card .stat-icon.orange {
    color: #d97706;
  }

  .stat-card .stat-icon.green {
    color: #059669;
  }

  .stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .stat-card .stat-label {
    color: #6b7280;
    font-size: 13px;
  }

  .stat-card .stat-action {
    margin-top: 10px;
  }

  .panels-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .actions-group {
    flex-direction: column;
  }

  .empty-msg {
    color: #9ca3af;
    font-size: 14px;
  }

  @media (max-width: 768px) {
    .panels-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-house"></i> Home — Gestor Pedagógico
</h1>

<div class="stats-grid">

  <div class="card stat-card">
    <i class="fa-solid fa-graduation-cap stat-icon blue"></i>
    <div class="stat-value"><?= $totalCursos ?></div>
    <div class="stat-label">Cursos Ativos</div>
  </div>

  <div class="card stat-card">
    <i class="fa-solid fa-book stat-icon purple"></i>
    <div class="stat-value"><?= $totalDisc ?></div>
    <div class="stat-label">Disciplinas</div>
  </div>

  <div class="card stat-card">
    <i class="fa-solid fa-id-card stat-icon orange"></i>
    <div class="stat-value"><?= $fichasPendentes ?></div>
    <div class="stat-label">Fichas p/ Validar</div>
    <?php if ($fichasPendentes > 0): ?>
      <div class="stat-action">
        <a href="fichas_validar.php" class="btn btn-warning btn-sm">Ver fichas</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="card stat-card">
    <i class="fa-solid fa-user-graduate stat-icon green"></i>
    <div class="stat-value"><?= $totalAlunos ?></div>
    <div class="stat-label">Alunos Aprovados</div>
  </div>

</div>

<div class="panels-grid">

  <div class="card">
    <div class="card-title">
      <i class="fa-solid fa-bolt" style="color:#1a56db;"></i> Ações Rápidas
    </div>
    <div class="btn-group actions-group">
      <a href="cursos.php" class="btn btn-primary"><i class="fa-solid fa-graduation-cap"></i> Gerir Cursos</a>
      <a href="disciplinas.php" class="btn btn-primary"><i class="fa-solid fa-book"></i> Gerir Disciplinas</a>
      <a href="planos_estudos.php" class="btn btn-primary"><i class="fa-solid fa-list-check"></i> Plano de Estudos</a>
      <a href="fichas_validar.php" class="btn btn-warning"><i class="fa-solid fa-id-card"></i> Validar Fichas</a>
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <i class="fa-solid fa-clock-rotate-left" style="color:#7c3aed;"></i> Fichas Recentes
    </div>

    <?php if (empty($fichasRecentes)): ?>
      <p class="empty-msg">Nenhuma ficha submetida.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Aluno</th>
              <th>Curso</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fichasRecentes as $f): ?>
              <tr>
                <td><?= htmlspecialchars($f['nome']) ?></td>
                <td><?= htmlspecialchars($f['curso']) ?></td>
                <td>
                  <span class="badge badge-<?= $f['estado'] ?>">
                    <?= ucfirst($f['estado']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php renderFooter(); ?>