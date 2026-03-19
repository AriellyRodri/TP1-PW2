<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireGrupo(2);

//  CRIAR PAUTA 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_pauta'])) {
  $disc_id = (int) $_POST['disciplina_id'];
  $ano_letivo = trim($_POST['ano_letivo'] ?? '');
  $epoca = $_POST['epoca'] ?? 'Normal';

  if ($disc_id > 0 && $ano_letivo !== '' && in_array($epoca, ['Normal', 'Recurso', 'Especial'])) {
    $chk = $pdo->prepare("SELECT ID FROM pautas WHERE disciplina_id=? AND ano_letivo=? AND epoca=?");
    $chk->execute([$disc_id, $ano_letivo, $epoca]);

    if ($chk->fetch()) {
      header('Location: pautas.php?err=Já+existe+pauta+para+esta+UC%2Fano%2Fépoca.');
    } else {
      $pdo->prepare("INSERT INTO pautas (disciplina_id, ano_letivo, epoca, criado_por) VALUES (?,?,?,?)")
        ->execute([$disc_id, $ano_letivo, $epoca, $_SESSION['user_id']]);
      $pauta_id = $pdo->lastInsertId();

      // Inserir automaticamente alunos elegíveis
      $alunos_stmt = $pdo->prepare("
                SELECT DISTINCT fa.ID AS ficha_id
                FROM fichas_aluno fa
                JOIN matriculas m    ON m.ficha_id = fa.ID AND m.estado = 'aprovada'
                JOIN plano_estudos pe ON pe.CURSOS = m.curso_id AND pe.DISCIPLINA = ?
                WHERE fa.estado = 'aprovada'
            ");
      $alunos_stmt->execute([$disc_id]);
      $alunos_elegiv = $alunos_stmt->fetchAll();

      foreach ($alunos_elegiv as $al) {
        $pdo->prepare("INSERT IGNORE INTO notas (pauta_id, ficha_id) VALUES (?,?)")
          ->execute([$pauta_id, $al['ficha_id']]);
      }

      header("Location: pautas.php?ver=$pauta_id&ok=Pauta+criada+com+" . count($alunos_elegiv) . "+alunos.");
    }
  } else {
    header('Location: pautas.php?err=Preencha+todos+os+campos.');
  }
  exit;
}

//  GUARDAR NOTAS 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_notas'])) {
  $pauta_id = (int) $_POST['pauta_id'];
  $notas = $_POST['nota'] ?? [];
  $obs_arr = $_POST['obs_nota'] ?? [];

  foreach ($notas as $nota_id => $valor) {
    $valor_float = ($valor === '' || $valor === null) ? null : (float) $valor;
    if ($valor_float !== null && ($valor_float < 0 || $valor_float > 20))
      continue;
    $obs_val = trim($obs_arr[$nota_id] ?? '');
    $pdo->prepare("UPDATE notas SET nota=?, observacoes=? WHERE ID=? AND pauta_id=?")
      ->execute([$valor_float, $obs_val, (int) $nota_id, $pauta_id]);
  }

  // Adicionar aluno manualmente
  if (!empty($_POST['add_ficha_id'])) {
    $pdo->prepare("INSERT IGNORE INTO notas (pauta_id, ficha_id) VALUES (?,?)")
      ->execute([$pauta_id, (int) $_POST['add_ficha_id']]);
  }

  header("Location: pautas.php?ver=$pauta_id&ok=Notas+guardadas.");
  exit;
}

//  APAGAR PAUTA 
if (isset($_GET['del_pauta'])) {
  $id = (int) $_GET['del_pauta'];
  $pdo->prepare('DELETE FROM notas  WHERE pauta_id=?')->execute([$id]);
  $pdo->prepare('DELETE FROM pautas WHERE ID=?')->execute([$id]);
  header('Location: pautas.php?ok=Pauta+removida.');
  exit;
}

//  DADOS PARA A PÁGINA 
$disciplinas = $pdo->query('SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc')->fetchAll();

$pautas = $pdo->query("
    SELECT p.*, d.Nome_disc AS disciplina, u.login AS criado_login,
           COUNT(n.ID)              AS total_alunos,
           SUM(n.nota IS NOT NULL)  AS com_nota
    FROM pautas p
    JOIN disciplinas d ON p.disciplina_id = d.ID
    JOIN users u       ON p.criado_por    = u.ID
    LEFT JOIN notas n  ON n.pauta_id      = p.ID
    GROUP BY p.ID
    ORDER BY p.criado_em DESC
")->fetchAll();

// Detalhe de pauta 
$pauta_detalhe = null;
$alunos_pauta = [];
$nao_inscritos = [];

if (isset($_GET['ver'])) {
  $pid = (int) $_GET['ver'];

  $stmtP = $pdo->prepare("
        SELECT p.*, d.Nome_disc AS disciplina
        FROM pautas p
        JOIN disciplinas d ON p.disciplina_id = d.ID
        WHERE p.ID = ?
    ");
  $stmtP->execute([$pid]);
  $pauta_detalhe = $stmtP->fetch();

  if ($pauta_detalhe) {
    $stmtL = $pdo->prepare("
            SELECT n.ID AS nota_id, n.nota, n.observacoes AS obs_nota,
                   fa.nome, u.numero_aluno, fa.email
            FROM notas n
            JOIN fichas_aluno fa ON n.ficha_id = fa.ID
            JOIN users u         ON fa.user_id  = u.ID
            WHERE n.pauta_id = ?
            ORDER BY fa.nome
        ");
    $stmtL->execute([$pid]);
    $alunos_pauta = $stmtL->fetchAll();

    $stmtE = $pdo->prepare("
            SELECT fa.ID AS ficha_id, fa.nome, u.numero_aluno
            FROM fichas_aluno fa
            JOIN users u         ON fa.user_id   = u.ID
            JOIN matriculas m    ON m.ficha_id   = fa.ID AND m.estado = 'aprovada'
            JOIN plano_estudos pe ON pe.CURSOS   = m.curso_id AND pe.DISCIPLINA = ?
            WHERE fa.estado = 'aprovada'
              AND fa.ID NOT IN (SELECT ficha_id FROM notas WHERE pauta_id = ?)
        ");
    $stmtE->execute([$pauta_detalhe['disciplina_id'], $pid]);
    $nao_inscritos = $stmtE->fetchAll();
  }
}

renderHeader('Pautas de Avaliação', 2, $_SESSION['user']);
?>

<style>
  .empty-msg {
    color: #9ca3af;
    font-size: 14px;
  }

  .card-pauta {
    border: 2px solid #1a56db;
  }

  .input-nota {
    width: 80px;
  }

  .add-manual {
    margin-top: 16px;
    padding: 14px;
    background: #f9fafb;
    border-radius: 8px;
  }

  .add-manual label {
    font-weight: 600;
    font-size: 13px;
    color: #374151;
    display: block;
    margin-bottom: 8px;
  }

  .add-manual .add-manual-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
  }

  .add-manual .select-aluno {
    max-width: 280px;
  }

  .notas-actions {
    margin-top: 16px;
  }

  .cell-vazio {
    color: #9ca3af;
    text-align: center;
  }
</style>

<h1 class="page-title">
  <i class="fa-solid fa-table-list"></i> Pautas de Avaliação
</h1>

<?php if (isset($_GET['ok'])):
  echo msg('success', $_GET['ok']); endif; ?>
<?php if (isset($_GET['err'])):
  echo msg('danger', $_GET['err']); endif; ?>

<div class="card">
  <div class="card-title">Criar Nova Pauta</div>
  <form method="POST">
    <div class="form-row-3">
      <div class="form-group">
        <label>Disciplina / UC</label>
        <select class="form-control" name="disciplina_id" required>
          <option value="">Selecionar...</option>
          <?php foreach ($disciplinas as $d): ?>
            <option value="<?= $d['ID'] ?>">
              <?= htmlspecialchars($d['Nome_disc']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Ano Letivo</label>
        <input class="form-control" type="text" name="ano_letivo" placeholder="ex: 2025/2026" pattern="\d{4}\/\d{4}"
          required maxlength="10">
      </div>
      <div class="form-group">
        <label>Época</label>
        <select class="form-control" name="epoca" required>
          <option value="Normal">Normal</option>
          <option value="Recurso">Recurso</option>
          <option value="Especial">Especial</option>
        </select>
      </div>
    </div>
    <button class="btn btn-primary" type="submit" name="criar_pauta">
      <i class="fa-solid fa-plus"></i> Criar Pauta
    </button>
  </form>
</div>

<div class="card">
  <div class="card-title">Pautas Existentes</div>

  <?php if (empty($pautas)): ?>
    <p class="empty-msg">Nenhuma pauta criada.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Disciplina</th>
            <th>Ano Letivo</th>
            <th>Época</th>
            <th>Alunos</th>
            <th>Notas Lançadas</th>
            <th>Criada por</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pautas as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['disciplina']) ?></td>
              <td><?= htmlspecialchars($p['ano_letivo']) ?></td>
              <td>
                <span class="badge badge-<?= strtolower($p['epoca']) ?>">
                  <?= $p['epoca'] ?>
                </span>
              </td>
              <td><?= $p['total_alunos'] ?></td>
              <td><?= $p['com_nota'] ?> / <?= $p['total_alunos'] ?></td>
              <td><?= htmlspecialchars($p['criado_login']) ?></td>
              <td>
                <div class="btn-group">
                  <a class="btn btn-sm btn-primary" href="?ver=<?= $p['ID'] ?>">
                    <i class="fa-solid fa-pen-to-square"></i> Editar
                  </a>
                  <a class="btn btn-sm btn-danger" href="?del_pauta=<?= $p['ID'] ?>"
                    onclick="return confirm('Apagar esta pauta e todas as notas?')">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($pauta_detalhe): ?>
  <div class="card card-pauta">
    <div class="card-title">
      Pauta: <?= htmlspecialchars($pauta_detalhe['disciplina']) ?> —
      <?= htmlspecialchars($pauta_detalhe['ano_letivo']) ?> —
      <span class="badge badge-<?= strtolower($pauta_detalhe['epoca']) ?>">
        <?= $pauta_detalhe['epoca'] ?>
      </span>
    </div>

    <form method="POST">
      <input type="hidden" name="pauta_id" value="<?= $pid ?>">

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Aluno</th>
              <th>Nº Aluno</th>
              <th>Email</th>
              <th>Nota (0–20)</th>
              <th>Observações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($alunos_pauta)): ?>
              <tr>
                <td colspan="5" class="cell-vazio">Nenhum aluno nesta pauta.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($alunos_pauta as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['nome']) ?></td>
                <td><?= htmlspecialchars($a['numero_aluno'] ?? '—') ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td>
                  <input type="number" name="nota[<?= $a['nota_id'] ?>]" class="form-control input-nota" min="0" max="20"
                    step="0.1" value="<?= htmlspecialchars($a['nota'] ?? '') ?>" placeholder="—">
                </td>
                <td>
                  <input type="text" name="obs_nota[<?= $a['nota_id'] ?>]" class="form-control" maxlength="255"
                    value="<?= htmlspecialchars($a['obs_nota'] ?? '') ?>" placeholder="Obs.">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Adicionar aluno manualmente -->
      <?php if (!empty($nao_inscritos)): ?>
        <div class="add-manual">
          <label>Adicionar aluno manualmente:</label>
          <div class="add-manual-row">
            <select class="form-control select-aluno" name="add_ficha_id">
              <option value="">Selecionar aluno...</option>
              <?php foreach ($nao_inscritos as $ni): ?>
                <option value="<?= $ni['ficha_id'] ?>">
                  <?= htmlspecialchars($ni['nome']) ?>
                  (<?= htmlspecialchars($ni['numero_aluno'] ?? 'sem nº') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-secondary btn-sm" type="submit" name="guardar_notas">
              <i class="fa-solid fa-plus"></i> Adicionar
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Ações -->
      <div class="btn-group notas-actions">
        <button class="btn btn-success" type="submit" name="guardar_notas">
          <i class="fa-solid fa-floppy-disk"></i> Guardar Notas
        </button>
        <a class="btn btn-secondary" href="pautas.php">
          <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php renderFooter(); ?>