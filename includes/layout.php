<?php
function getMenuPorGrupo(int $grupo): array
{
  $menus = [
    1 => [
      ['href' => 'gestor_dashboard.php', 'icon' => 'fa-house', 'label' => 'Home'],
      ['href' => 'cursos.php', 'icon' => 'fa-graduation-cap', 'label' => 'Cursos'],
      ['href' => 'disciplinas.php', 'icon' => 'fa-book', 'label' => 'Disciplinas'],
      ['href' => 'planos_estudos.php', 'icon' => 'fa-list-check', 'label' => 'Plano de Estudos'],
      ['href' => 'fichas_validar.php', 'icon' => 'fa-id-card', 'label' => 'Fichas de Aluno'],
      ['href' => 'gerir_utilizadores.php', 'icon' => 'fa-users-gear', 'label' => 'Utilizadores'],
    ],
    2 => [
      ['href' => 'funcionario_dashboard.php', 'icon' => 'fa-house', 'label' => 'Home'],
      ['href' => 'matriculas_gerir.php', 'icon' => 'fa-file-signature', 'label' => 'Matrículas'],
      ['href' => 'pautas.php', 'icon' => 'fa-table-list', 'label' => 'Pautas'],
    ],
    3 => [
      ['href' => 'aluno_dashboard.php', 'icon' => 'fa-house', 'label' => 'Home'],
      ['href' => 'ficha_aluno.php', 'icon' => 'fa-id-card', 'label' => 'Minha Ficha'],
      ['href' => 'matricula_pedido.php', 'icon' => 'fa-file-signature', 'label' => 'Matrícula'],
      ['href' => 'aluno_estado.php', 'icon' => 'fa-magnifying-glass', 'label' => 'Estado dos Pedidos'],
    ],
  ];
  return $menus[$grupo] ?? [];
}

function renderHeader(string $titulo, int $grupo, string $user): void
{
  $menu = getMenuPorGrupo($grupo);
  $grupoNomes = [1 => 'Gestor Pedagógico', 2 => 'Serviços Académicos', 3 => 'Aluno'];
  $grupoNome = $grupoNomes[$grupo] ?? 'Utilizador';
  $grupoClass = ['', 'gestor', 'funcionario', 'aluno'][$grupo] ?? '';
  ?>
  <!DOCTYPE html>
  <html lang="pt">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> — Coliseum Académico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #1a56db;
        --primary-d: #1540a8;
        --gestor: #7c3aed;
        --funcionario: #0891b2;
        --aluno: #059669;
        --danger: #dc2626;
        --warning: #d97706;
        --success: #16a34a;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-500: #6b7280;
        --gray-700: #374151;
        --gray-900: #111827;
        --radius: 8px;
        --shadow: 0 1px 3px rgba(0, 0, 0, .12), 0 1px 2px rgba(0, 0, 0, .08);
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Arial, sans-serif;
      }

      body {
        background: var(--gray-100);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      .topbar {
        background: white;
        padding: 0 24px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 100;
      }

      .topbar-brand {
        font-weight: 700;
        font-size: 18px;
        color: var(--gray-900);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .topbar-brand i {
        color: var(--primary);
      }

      .topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
      }

      .badge-grupo {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: white;
      }

      .badge-grupo.gestor {
        background: var(--gestor);
      }

      .badge-grupo.funcionario {
        background: var(--funcionario);
      }

      .badge-grupo.aluno {
        background: var(--aluno);
      }

      .user-name {
        font-size: 14px;
        color: var(--gray-700);
        font-weight: 500;
      }

      .btn-logout {
        background: var(--danger);
        color: white;
        padding: 6px 14px;
        border-radius: var(--radius);
        text-decoration: none;
        font-size: 13px;
      }

      .btn-logout:hover {
        background: #b91c1c;
      }

      .layout {
        display: flex;
        flex: 1;
      }

      .sidebar {
        width: 220px;
        background: white;
        box-shadow: var(--shadow);
        padding: 16px 0;
        flex-shrink: 0;
      }

      .sidebar a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 20px;
        color: var(--gray-700);
        text-decoration: none;
        font-size: 14px;
        transition: .15s;
      }

      .sidebar a:hover {
        background: var(--gray-100);
        color: var(--primary);
      }

      .sidebar a.active {
        background: #eff6ff;
        color: var(--primary);
        font-weight: 600;
        border-right: 3px solid var(--primary);
      }

      .sidebar a i {
        width: 18px;
        text-align: center;
      }

      .sidebar-sep {
        border: none;
        border-top: 1px solid var(--gray-200);
        margin: 8px 0;
      }

      .main {
        flex: 1;
        padding: 28px;
        overflow-x: auto;
      }

      .page-title {
        font-size: 22px;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .page-title i {
        color: var(--primary);
      }

      .card {
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        box-shadow: var(--shadow);
        margin-bottom: 20px;
      }

      .card-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--gray-200);
      }

      .form-group {
        margin-bottom: 16px;
      }

      .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 6px;
      }

      .form-control {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        font-size: 14px;
        transition: .2s;
        background: white;
      }

      .form-control:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(26, 86, 219, .15);
      }

      .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
      }

      .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
      }

      .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--radius);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        border: none;
        transition: .2s;
      }

      .btn-primary {
        background: var(--primary);
        color: white;
      }

      .btn-primary:hover {
        background: var(--primary-d);
      }

      .btn-success {
        background: var(--success);
        color: white;
      }

      .btn-success:hover {
        background: #15803d;
      }

      .btn-danger {
        background: var(--danger);
        color: white;
      }

      .btn-danger:hover {
        background: #b91c1c;
      }

      .btn-warning {
        background: var(--warning);
        color: white;
      }

      .btn-warning:hover {
        background: #b45309;
      }

      .btn-secondary {
        background: var(--gray-500);
        color: white;
      }

      .btn-secondary:hover {
        background: var(--gray-700);
      }

      .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
      }

      .btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
      }

      .table-wrap {
        overflow-x: auto;
      }

      table {
        width: 100%;
        border-collapse: collapse;
      }

      th,
      td {
        padding: 11px 14px;
        text-align: left;
        font-size: 14px;
      }

      th {
        background: var(--gray-50);
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
      }

      td {
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
      }

      tr:last-child td {
        border-bottom: none;
      }

      tr:hover td {
        background: var(--gray-50);
      }

      .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }

      .badge-rascunho {
        background: #fef3c7;
        color: #92400e;
      }

      .badge-submetida {
        background: #dbeafe;
        color: #1e40af;
      }

      .badge-aprovada,
      .badge-aprovado {
        background: #dcfce7;
        color: #166534;
      }

      .badge-rejeitada,
      .badge-rejeitado {
        background: #fee2e2;
        color: #991b1b;
      }

      .badge-pendente {
        background: #fef9c3;
        color: #713f12;
      }

      .badge-normal {
        background: #e0f2fe;
        color: #075985;
      }

      .badge-recurso {
        background: #fce7f3;
        color: #831843;
      }

      .badge-especial {
        background: #f3e8ff;
        color: #6b21a8;
      }

      .alert {
        padding: 12px 16px;
        border-radius: var(--radius);
        font-size: 14px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
      }

      .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
      }

      .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
      }

      .alert-warning {
        background: #fef9c3;
        color: #713f12;
        border: 1px solid #fde68a;
      }

      @media(max-width:768px) {
        .sidebar {
          display: none;
        }

        .form-row,
        .form-row-3 {
          grid-template-columns: 1fr;
        }

        .main {
          padding: 16px;
        }
      }
    </style>
  </head>

  <body>
    <nav class="topbar">
      <a class="topbar-brand"
        href="<?= $grupo == 1 ? 'gestor_dashboard.php' : ($grupo == 2 ? 'funcionario_dashboard.php' : 'aluno_dashboard.php') ?>">
        <i class="fa-solid fa-university"></i> Coliseum Académico
      </a>
      <div class="topbar-right">
        <span class="badge-grupo <?= $grupoClass ?>"><?= $grupoNome ?></span>
        <span class="user-name"><i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars($user) ?></span>
        <a class="btn-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
      </div>
    </nav>
    <div class="layout">
      <aside class="sidebar">
        <?php
        $currentFile = basename($_SERVER['PHP_SELF']);
        foreach ($menu as $item):
          $isActive = ($currentFile === $item['href']) ? 'active' : '';
          ?>
          <a href="<?= $item['href'] ?>" class="<?= $isActive ?>">
            <i class="fa-solid <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
          </a>
        <?php endforeach; ?>
        <hr class="sidebar-sep">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </aside>
      <main class="main">
        <?php
}

function renderFooter(): void
{
  ?>
      </main>
    </div>
    <script>
      setTimeout(() => { document.querySelectorAll('.alert').forEach(a => { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }); }, 5000);
    </script>
  </body>

  </html>
  <?php
}

function msg(string $tipo, string $texto): string
{
  $icons = ['success' => 'fa-circle-check', 'danger' => 'fa-circle-xmark', 'info' => 'fa-circle-info', 'warning' => 'fa-triangle-exclamation'];
  $icon = $icons[$tipo] ?? 'fa-circle-info';
  return "<div class='alert alert-$tipo'><i class='fa-solid $icon'></i> " . htmlspecialchars($texto) . "</div>";
}