<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(3);

$user_id = $_SESSION['user_id'];

// Verificar se tem ficha aprovada
$stmt = $pdo->prepare("SELECT * FROM fichas_aluno WHERE user_id=? AND estado='aprovada'");
$stmt->execute([$user_id]);
$ficha = $stmt->fetch();

// Atualizar nome na sessão se tiver ficha
if ($ficha && !empty($ficha['nome'])) {
  $_SESSION['nome_display'] = $ficha['nome'];
}

// Buscar nome do curso da ficha
$nomeCurso = '—';
if ($ficha && $ficha['curso_id'] > 0) {
  $sc = $pdo->prepare('SELECT Nome FROM cursos WHERE ID=?');
  $sc->execute([$ficha['curso_id']]);
  $nomeCurso = $sc->fetchColumn() ?: '—';
}

// Submeter pedido
$msgType = '';
$msgText = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submeter']) && $ficha) {
  $ano_letivo = trim($_POST['ano_letivo'] ?? '');

  if ($ano_letivo === '') {
    $msgType = 'danger';
    $msgText = 'Indique o ano letivo.';
  } else {
    $chk = $pdo->prepare("SELECT ID FROM matriculas
                               WHERE ficha_id=? AND curso_id=? AND ano_letivo=?
                               AND estado IN ('pendente','aprovada')");
    $chk->execute([$ficha['ID'], $ficha['curso_id'], $ano_letivo]);

    if ($chk->fetch()) {
      $msgType = 'warning';
      $msgText = 'Já existe um pedido de matrícula para este curso e ano letivo.';
    } else {
      $pdo->prepare("INSERT INTO matriculas (ficha_id, curso_id, ano_letivo) VALUES (?, ?, ?)")
        ->execute([$ficha['ID'], $ficha['curso_id'], $ano_letivo]);
      $msgType = 'success';
      $msgText = 'Pedido de matrícula submetido com sucesso!';
    }
  }
}

// Listar pedidos do aluno
$pedidos = [];
if ($ficha) {
  $stmt2 = $pdo->prepare("
        SELECT m.*, c.Nome AS curso, u.login AS aprovado_login
        FROM matriculas m
        JOIN cursos c ON m.curso_id = c.ID
        LEFT JOIN users u ON m.aprovado_por = u.ID
        WHERE m.ficha_id = ?
        ORDER BY m.criado_em DESC
    ");
  $stmt2->execute([$ficha['ID']]);
  $pedidos = $stmt2->fetchAll();
}

renderHeader('Pedido de Matrícula', 3, $_SESSION['nome_display'] ?? $_SESSION['user']);
?>

<style>
  /* ── Aviso sem ficha aprovada ─────────────────────────────────── */
  .alert-link {
    color: inherit;
    font-weight: 600;
  }

  /* ── Mensagem sem pedidos ─────────────────────────────────────── */
  .empty-msg {
    color: #9ca3af;
    font-size: 14px;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-file-signature"></i> Pedido de Matrícula
</h1>

<?php if ($msgText):
  echo msg($msgType, $msgText); endif; ?>

<?php if (!$ficha): ?>

  <!-- Aviso: ficha ainda não aprovada -->
  <div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation"></i>
    Para submeter uma matrícula, a sua ficha de aluno tem de estar
    <strong>aprovada</strong> pelo Gestor Pedagógico.
    <a href="ficha_aluno.php" class="alert-link"> → Ir para a minha ficha</a>
  </div>

<?php else: ?>

  <!-- Formulário de novo pedido -->
  <div class="card">
    <div class="card-title">Novo Pedido de Matrícula</div>
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label>Curso</label>
          <input class="form-control" type="text" value="<?= htmlspecialchars($nomeCurso) ?>" disabled>
        </div>
        <div class="form-group">
          <label>Ano Letivo *</label>
          <input class="form-control" type="text" name="ano_letivo" placeholder="ex: 2025/2026" maxlength="10" required
            pattern="\d{4}\/\d{4}" title="Formato: AAAA/AAAA">
        </div>
      </div>
      <button class="btn btn-primary" type="submit" name="submeter"
        onclick="return confirm('Submeter pedido de matrícula?')">
        <i class="fa-solid fa-paper-plane"></i> Submeter Pedido
      </button>
    </form>
  </div>

  <!-- Lista de pedidos do aluno -->
  <div class="card">
    <div class="card-title">Os Meus Pedidos</div>

    <?php if (empty($pedidos)): ?>
      <p class="empty-msg">Ainda não efetuou nenhum pedido.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Curso</th>
              <th>Ano Letivo</th>
              <th>Estado</th>
              <th>Decisão por</th>
              <th>Data Decisão</th>
              <th>Obs.</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pedidos as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['curso']) ?></td>
                <td><?= htmlspecialchars($p['ano_letivo']) ?></td>
                <td>
                  <span class="badge badge-<?= $p['estado'] ?>">
                    <?= ucfirst($p['estado']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($p['aprovado_login'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['data_decisao'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['observacoes'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php renderFooter(); ?>