<?php
session_start();

if (isset($_POST['confirmar'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$grupo = $_SESSION['grupo'] ?? 0;
$nome = $_SESSION['nome_display'] ?? $_SESSION['user'] ?? 'Utilizador';
$voltar = [1 => 'gestor_dashboard.php', 2 => 'funcionario_dashboard.php', 3 => 'aluno_dashboard.php'];
$url_voltar = $voltar[$grupo] ?? 'login.php';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminar Sessão — IPCA Académico</title>
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

        .card {
            background: white;
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .25);
            text-align: center;
            width: 100%;
            max-width: 420px;
            margin: 16px;
        }

        .icon-wrap {
            width: 72px;
            height: 72px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .icon-wrap i {
            font-size: 32px;
            color: #dc2626;
        }

        h2 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        p strong {
            color: #111827;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 20px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all .2s;
            width: 100%;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, .35);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #d1d5db;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="icon-wrap">
            <i class="fa-solid fa-right-from-bracket"></i>
        </div>

        <h2>Terminar Sessão</h2>
        <p>
            Olá, <strong><?= htmlspecialchars($nome) ?></strong>.<br>
            Tem a certeza que deseja sair?
        </p>

        <div class="btn-group">

            <form method="POST">
                <button class="btn btn-danger" type="submit" name="confirmar">
                    <i class="fa-solid fa-right-from-bracket"></i> Sim, quero sair
                </button>
            </form>

            <div class="divider">ou</div>

            <a class="btn btn-secondary" href="<?= htmlspecialchars($url_voltar) ?>">
                <i class="fa-solid fa-arrow-left"></i> Voltar à aplicação
            </a>

        </div>
    </div>

</body>

</html>