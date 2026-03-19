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

if ($ficha && !empty($ficha['nome'])) {
  $_SESSION['nome_display'] = $ficha['nome'];
}

$matriculas = [];
$notas = [];
if ($ficha) {
  $m = $pdo->prepare("
        SELECT m.*, c.Nome AS curso, u.login AS decisao_login
        FROM matriculas m
        JOIN cursos c ON m.curso_id=c.ID
        LEFT JOIN users u ON m.aprovado_por=u.ID
        WHERE m.ficha_id=?
        ORDER BY m.criado_em DESC
    ");
  $m->execute([$ficha['ID']]);
  $matriculas = $m->fetchAll();

  $n = $pdo->prepare("
        SELECT n.nota, n.observacoes, p.ano_letivo, p.epoca, d.Nome_disc AS disciplina
        FROM notas n
        JOIN pautas p ON n.pauta_id=p.ID
        JOIN disciplinas d ON p.disciplina_id=d.ID
        WHERE n.ficha_id=?
        ORDER BY p.ano_letivo DESC, d.Nome_disc
    ");
  $n->execute([$ficha['ID']]);
  $notas = $n->fetchAll();
}

renderHeader('Estado dos Pedidos', 3, $_SESSION['nome_display'] ?? $_SESSION['user']);
?>

<style>
  .text-muted {
    color: #9ca3af;
  }

  .table-small {
    font-size: 14px;
    max-width: 500px;
  }

  .td-label {
    font-weight: 600;
    padding: 4px 16px 4px 0;
  }

  .obs-warning {
    color: #92400e;
  }

  .nota {
    font-weight: 700;
  }

  .nota-aprovada {
    color: #16a34a;
  }

  .nota-reprovada {
    color: #dc2626;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-magnifying-glass"></i>
  Estado dos Meus Pedidos
</h1>

<!-- FICHA -->
<div class="card">
  <div class="card-title">
    <i class="fa-solid fa-id-card"></i> Ficha de Aluno
  </div>

  <?php if (!$ficha): ?>
    <p class="text-muted">
      Ainda não criou a sua ficha.
      <a href="ficha_aluno.php">Criar agora →</a>
    </p>
  <?php else: ?>
    <table class="table-small">
      <tr>
        <td class="td-label">Estado</td>
        <td><span class="badge badge-<?= $ficha['estado'] ?>"><?= ucfirst($ficha['estado']) ?></span></td>
      </tr>
      <tr>
        <td class="td-label">Curso</td>
        <td><?= htmlspecialchars($ficha['curso']) ?></td>
      </tr>
      <tr>
        <td class="td-label">Submetida em</td>
        <td><?= htmlspecialchars($ficha['criado_em']) ?></td>
      </tr>

      <?php if ($ficha['data_validacao']): ?>
        <tr>
          <td class="td-label">Validada em</td>
          <td><?= htmlspecialchars($ficha['data_validacao']) ?></td>
        </tr>
      <?php endif; ?>

      <?php if ($ficha['observacoes']): ?>
        <tr>
          <td class="td-label">Obs. Gestor</td>
          <td class="obs-warning"><?= htmlspecialchars($ficha['observacoes']) ?></td>
        </tr>
      <?php endif; ?>
    </table>
  <?php endif; ?>
</div>

<!-- MATRÍCULAS -->
<div class="card">
  <div class="card-title">
    <i class="fa-solid fa-file-signature"></i> Pedidos de Matrícula
  </div>

  <?php if (empty($matriculas)): ?>
    <p class="text-muted">
      Nenhum pedido submetido.
      <a href="matricula_pedido.php">Submeter →</a>
    </p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <tr>
          <th>Curso</th>
          <th>Ano Letivo</th>
          <th>Estado</th>
          <th>Decisão por</th>
          <th>Data Decisão</th>
          <th>Obs.</th>
        </tr>

        <?php foreach ($matriculas as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['curso']) ?></td>
            <td><?= htmlspecialchars($m['ano_letivo']) ?></td>
            <td>
              <span class="badge badge-<?= $m['estado'] ?>">
                <?= ucfirst($m['estado']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($m['decisao_login'] ?? '—') ?></td>
            <td><?= htmlspecialchars($m['data_decisao'] ?? '—') ?></td>
            <td><?= htmlspecialchars($m['observacoes'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- NOTAS -->
<div class="card">
  <div class="card-title">
    <i class="fa-solid fa-star"></i> As Minhas Notas
  </div>

  <?php if (empty($notas)): ?>
    <p class="text-muted">Ainda não há notas lançadas.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <tr>
          <th>Disciplina</th>
          <th>Ano Letivo</th>
          <th>Época</th>
          <th>Nota</th>
          <th>Obs.</th>
        </tr>

        <?php foreach ($notas as $n): ?>
          <?php
          $notaClass = '';
          if ($n['nota'] !== null) {
            $notaClass = $n['nota'] >= 10 ? 'nota-aprovada' : 'nota-reprovada';
          }
          ?>
          <tr>
            <td><?= htmlspecialchars($n['disciplina']) ?></td>
            <td><?= htmlspecialchars($n['ano_letivo']) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($n['epoca']) ?>">
                <?= $n['epoca'] ?>
              </span>
            </td>
            <td class="nota <?= $notaClass ?>">
              <?= $n['nota'] !== null ? number_format($n['nota'], 1) : '—' ?>
            </td>
            <td><?= htmlspecialchars($n['observacoes'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>