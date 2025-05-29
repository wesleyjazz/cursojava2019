<?php
session_start();
// Verificar role do usuário se estiver logado
$is_technician = false;
$is_admin = false;

if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $is_technician = ($user['role'] ?? '') === 'technician';
    $is_admin = ($user['role'] ?? '') === 'admin';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Manutenção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-tools"></i> Manutenção Filiperson
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="machines.php"><i class="bi bi-pc-display"></i> Máquinas</a></li>
                    
                    <?php if ($is_admin): ?>
                        <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people-fill"></i> Usuários</a></li>
                    <?php endif; ?>
                    
                    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-clipboard2-check"></i> Ordens</a></li>
		    
		    <li class="nav-item"><a class="nav-link" href="chamados.php"><i class="bi bi-clipboard2-check"></i> Chamados</a></li>
                    
                    <li class="nav-item"><a class="nav-link" href="chamado_create.php"><i class="bi bi-megaphone-fill"></i> Abrir Chamado</a></li>

                    
                    <li class="nav-item"><a class="nav-link" href="schedules.php"><i class="bi bi-calendar-event"></i> Agendamentos</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-file-earmark-bar-graph"></i> Relatórios</a></li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['username'])): ?>
                        <span class="text-white me-3 d-flex align-items-center">
                            <i class="bi bi-person-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
