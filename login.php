<?php
session_start();
require_once 'acessoBD.php';
require_once 'includes/auth.php';

// Se já logado, redirecionar
if (isset($_SESSION['user_id'])) {
  $dest = [1 => 'gestor_dashboard.php', 2 => 'funcionario_dashboard.php', 3 => 'aluno_dashboard.php'];
  header('Location: ' . ($dest[$_SESSION['grupo']] ?? 'login.php'));
  exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $input = trim($_POST['user'] ?? '');
  $pass = $_POST['pass'] ?? '';

  if ($input === '' || $pass === '') {
    $erro = 'Preencha utilizador e password.';
  } else {
    // A validação da password é feita diretamente pela BD com MD5()
    // O PHP não toca na password — é a BD que compara
    $stmt = $pdo->prepare('SELECT * FROM users WHERE login = ? AND pwd = MD5(?)');
    $stmt->execute([$input, $pass]);
    $dados = $stmt->fetch();

    if ($dados) {
      session_regenerate_id(true);
      $_SESSION['user_id'] = $dados['ID'];
      $_SESSION['user'] = $dados['login'];
      $_SESSION['grupo'] = (int) $dados['grupo'];
      $_SESSION['last_activity'] = time();

      // Tentar buscar nome real da ficha do aluno (se existir)
      $_SESSION['nome_display'] = $dados['login'];
      if ((int) $dados['grupo'] === 3) {
        $fichaStmt = $pdo->prepare(
          "SELECT nome FROM fichas_aluno WHERE user_id = ? AND estado = 'aprovada' LIMIT 1"
        );
        $fichaStmt->execute([$dados['ID']]);
        $fichaRow = $fichaStmt->fetch();
        if ($fichaRow) {
          $_SESSION['nome_display'] = $fichaRow['nome'];
        }
      }

      $dest = [1 => 'gestor_dashboard.php', 2 => 'funcionario_dashboard.php', 3 => 'aluno_dashboard.php'];
      header('Location: ' . ($dest[$_SESSION['grupo']] ?? 'login.php'));
      exit;
    } else {
      $erro = 'Utilizador ou password inválidos.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — IPCA Académico</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Arial, sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #1a56db 0%, #4facfe 100%);
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
      padding: 16px;
    }

    .login-box {
      background: white;
      border-radius: 12px;
      padding: 40px 36px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, .2);
    }

    .login-logo {
      text-align: center;
      margin-bottom: 28px;
    }

    .login-logo i {
      font-size: 44px;
      color: #1a56db;
    }

    .login-logo h2 {
      margin-top: 10px;
      font-size: 22px;
      color: #111827;
      font-weight: 700;
    }

    .login-logo p {
      color: #6b7280;
      font-size: 13px;
      margin-top: 4px;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 6px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: 14px;
    }

    .form-control {
      width: 100%;
      padding: 10px 12px 10px 38px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      font-size: 14px;
      transition: .2s;
      color: #111827;
    }

    .form-control:focus {
      border-color: #1a56db;
      outline: none;
      box-shadow: 0 0 0 3px rgba(26, 86, 219, .15);
    }

    .btn-login {
      width: 100%;
      padding: 11px;
      background: #1a56db;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 6px;
      transition: .2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-login:hover {
      background: #1540a8;
    }

    .alert-err {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .alert-info {
      background: #dbeafe;
      color: #1e40af;
      border: 1px solid #bfdbfe;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .credentials {
      margin-top: 20px;
      padding: 14px;
      background: #f9fafb;
      border-radius: 8px;
      font-size: 12px;
      color: #374151;
      border: 1px solid #e5e7eb;
    }

    .credentials strong {
      display: block;
      margin-bottom: 8px;
      color: #111827;
      font-size: 13px;
    }

    .credentials table {
      width: 100%;
      border-collapse: collapse;
    }

    .credentials td {
      padding: 3px 6px;
      vertical-align: top;
    }

    .credentials td:first-child {
      font-weight: 700;
      color: #1a56db;
      white-space: nowrap;
    }

    .credentials td:last-child {
      color: #6b7280;
      font-style: italic;
    }

    .login-footer {
      text-align: center;
      margin-top: 18px;
      font-size: 11px;
      color: #9ca3af;
    }
  </style>
</head>

<body>
  <div class="login-wrap">
    <div class="login-box">
      <div class="login-logo">
        <i class="fa-solid fa-university"></i>
        <h2>Coliseum Académico</h2>
        <p>Sistema de Gestão Académica</p>
      </div>

      <?php if (isset($_GET['exp'])): ?>
        <div class="alert-info"><i class="fa-solid fa-clock"></i> Sessão expirada. Entre novamente.</div>
      <?php endif; ?>
      <?php if (isset($_GET['denied'])): ?>
        <div class="alert-err"><i class="fa-solid fa-ban"></i> Sem permissão para aceder a essa área.</div>
      <?php endif; ?>
      <?php if ($erro !== ''): ?>
        <div class="alert-err"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label for="user">Utilizador</label>
          <div class="input-wrap">
            <i class="fa-solid fa-user"></i>
            <input class="form-control" type="text" id="user" name="user"
              value="<?= htmlspecialchars($_POST['user'] ?? '') ?>" placeholder="Nome de utilizador" required autofocus>
          </div>
        </div>
        <div class="form-group">
          <label for="pass">Password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input class="form-control" type="password" id="pass" name="pass" placeholder="Password" required>
          </div>
        </div>
        <button type="submit" name="login" class="btn-login">
          <i class="fa-solid fa-right-to-bracket"></i> Entrar
        </button>
      </form>

      <div class="credentials">
        <strong>Credenciais de teste:</strong>
        <table>
          <tr>
            <td>gestor</td>
            <td>gestot123</td>
          </tr>
          <tr>
            <td>funcionario</td>
            <td>func123</td>
          </tr>
          <tr>
            <td>aluno1</td>
            <td>aluno123</td>
          </tr>
          <tr>
            <td>aluno2</td>
            <td>aluno123</td>
          </tr>
          <tr>
            <td>aluno3</td>
            <td>aluno123</td>
          </tr>
        </table>
      </div>

      <div class="login-footer">Sistema de Gestão Coliseum Académico</div>
    </div>
  </div>
</body>

</html>