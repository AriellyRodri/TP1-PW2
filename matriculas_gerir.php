<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(2);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
  $id = (int) $_POST['matricula_id'];
  $acao = $_POST['acao'];
  $obs = trim($_POST['observacoes'] ?? '');

  if (in_array($acao, ['aprovada', 'rejeitada'])) {
    $pdo->prepare("UPDATE matriculas SET estado=?, observacoes=?, aprovado_por=?, data_decisao=NOW()
                       WHERE ID=? AND estado='pendente'")
      ->execute([$acao, $obs, $_SESSION['user_id'], $id]);
    header('Location: matriculas_gerir.php?ok=Matrícula+' . ($acao === 'aprovada' ? 'aprovada' : 'rejeitada') . '.');
    exit;
  }
}

$filtro = $_GET['estado'] ?? 'pendente';
$estados = [
  'pendente' => 'Pendentes',
  'aprovada' => 'Aprovadas',
  'rejeitada' => 'Rejeitadas',
  'todas' => 'Todas'
];

$where = $filtro !== 'todas' ? "WHERE m.estado = :estado" : "";

$stmt = $pdo->prepare("
    SELECT m.*,
           fa.nome  AS aluno_nome,
           fa.email AS aluno_email,
           c.Nome   AS curso,
           ud.login AS decisao_login
    FROM matriculas m
    JOIN fichas_aluno fa ON m.ficha_id = fa.ID
    JOIN cursos c        ON m.curso_id = c.ID
    LEFT JOIN users ud   ON m.aprovado_por = ud.ID
    $where
    ORDER BY m.criado_em DESC
");
if ($filtro !== 'todas')
  $stmt->bindValue(':estado', $filtro);
$stmt->execute();
$matriculas = $stmt->fetchAll();

$detalhe = null;
if (isset($_GET['ver'])) {
  $s = $pdo->prepare("
        SELECT m.*,
               fa.nome        AS aluno_nome,
               fa.email       AS aluno_email,
               fa.telefone,
               fa.morada,
               u.numero_aluno,
               c.Nome         AS curso
        FROM matriculas m
        JOIN fichas_aluno fa ON m.ficha_id = fa.ID
        JOIN users u         ON fa.user_id = u.ID
        JOIN cursos c        ON m.curso_id = c.ID
        WHERE m.ID = ?
    ");
  $s->execute([(int) $_GET['ver']]);
  $detalhe = $s->fetch();
  if ($detalhe && $detalhe['estado'] !== 'pendente') {
    $detalhe = null; // só mostrar se ainda pendente
  }
}

renderHeader('Gestão de Matrículas', 2, $_SESSION['nome_display'] ?? $_SESSION['user']);
?>

<style>
  .filtros-card {
    padding: 16px;
  }

  .filtros-wrap {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .empty-msg {
    color: #9ca3af;
    font-size: 14px;
  }

  .decisao-info {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
  }

  .card-detalhe {
    border: 2px solid #1a56db;
  }

  .detalhe-dados {
    font-size: 14px;
    margin-bottom: 16px;
  }

  .detalhe-dados p {
    margin-bottom: 6px;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-file-signature"></i> Gestão de Matrículas
</h1>

<?php if (isset($_GET['ok'])):
  echo msg('success', $_GET['ok']); endif; ?>

<!-- Filtros -->
<div class="card filtros-card">
  <div class="filtros-wrap">
    <?php foreach ($estados as $k => $v): ?>
      <a href="?estado=<?= $k ?>" class="btn btn-sm <?= $filtro === $k ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $v ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Tabela de matrículas -->
<div class="card">
  <div class="card-title">
    Pedidos — <?= $estados[$filtro] ?> (<?= count($matriculas) ?>)
  </div>

  <?php if (empty($matriculas)): ?>
    <p class="empty-msg">Nenhum pedido encontrado.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Aluno</th>
            <th>Email</th>
            <th>Curso</th>
            <th>Ano Letivo</th>
            <th>Estado</th>
            <th>Submetido em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($matriculas as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['aluno_nome']) ?></td>
              <td><?= htmlspecialchars($m['aluno_email']) ?></td>
              <td><?= htmlspecialchars($m['curso']) ?></td>
              <td><?= htmlspecialchars($m['ano_letivo']) ?></td>
              <td>
                <span class="badge badge-<?= $m['estado'] ?>">
                  <?= ucfirst($m['estado']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($m['criado_em']) ?></td>
              <td>
                <?php if ($m['estado'] === 'pendente'): ?>
                  <a class="btn btn-sm btn-secondary" href="?ver=<?= $m['ID'] ?>&estado=<?= $filtro ?>">
                    <i class="fa-solid fa-eye"></i> Decidir
                  </a>
                <?php else: ?>
                  <span class="decisao-info">
                    <?= htmlspecialchars($m['decisao_login'] ?? '') ?><br>
                    <?= htmlspecialchars($m['data_decisao'] ?? '') ?>
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Detalhe / formulário de decisão -->
<?php if ($detalhe): ?>
  <div class="card card-detalhe">
    <div class="card-title">Decidir Pedido #<?= $detalhe['ID'] ?></div>

    <div class="detalhe-dados">
      <p><strong>Aluno:</strong> <?= htmlspecialchars($detalhe['aluno_nome']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($detalhe['aluno_email']) ?></p>
      <p><strong>Nº Aluno:</strong> <?= htmlspecialchars($detalhe['numero_aluno'] ?? '—') ?></p>
      <p><strong>Curso:</strong> <?= htmlspecialchars($detalhe['curso']) ?></p>
      <p><strong>Ano Letivo:</strong> <?= htmlspecialchars($detalhe['ano_letivo']) ?></p>
      <p><strong>Submetido em:</strong> <?= htmlspecialchars($detalhe['criado_em']) ?></p>
    </div>

    <form method="POST">
      <input type="hidden" name="matricula_id" value="<?= $detalhe['ID'] ?>">
      <div class="form-group">
        <label>Observações</label>
        <textarea class="form-control" name="observacoes" rows="3" placeholder="Opcional..."></textarea>
      </div>
      <div class="btn-group">
        <button class="btn btn-success" type="submit" name="acao" value="aprovada"
          onclick="return confirm('Aprovar esta matrícula?')">
          <i class="fa-solid fa-circle-check"></i> Aprovar
        </button>
        <button class="btn btn-danger" type="submit" name="acao" value="rejeitada"
          onclick="return confirm('Rejeitar esta matrícula?')">
          <i class="fa-solid fa-circle-xmark"></i> Rejeitar
        </button>
        <a class="btn btn-secondary" href="matriculas_gerir.php">
          <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php renderFooter(); ?>