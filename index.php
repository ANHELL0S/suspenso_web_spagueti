<?php
require 'config/Session.php';
require 'database.php';

Session::start();

if (!Session::isActive()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$remaining_time = Session::getRemainingTime();
$minutes = floor($remaining_time / 60);
$seconds = $remaining_time % 60;

// Obtener total de tareas del usuario
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
$stmt_total->execute([$_SESSION['user_id']]);
$total_tareas = (int) $stmt_total->fetchColumn();

// Obtener tareas completadas
$stmt_completadas = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND completed = TRUE");
$stmt_completadas->execute([$_SESSION['user_id']]);
$tareas_completadas = (int) $stmt_completadas->fetchColumn();

// Calcular pendientes
$tareas_pendientes = $total_tareas - $tareas_completadas;

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 600px;
            margin: 2rem auto;
            padding: 1rem;
            background: #fff;
            color: #333;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .btn {
            background: #000;
            color: #fff;
            padding: 0.4rem 0.8rem;
            text-decoration: none;
            border-radius: 4px;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .card {
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
            font-size: 0.95rem;
        }

        .stat {
            text-align: center;
            flex: 1;
        }

        .stat:not(:last-child) {
            border-right: 1px solid #eee;
        }

        .stat strong {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 0.2rem;
        }

        .link {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.95rem;
            text-decoration: underline;
            color: #000;
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <div>Sesión: <?= "$minutes:$seconds" ?> min</div>
        <a href="logout.php" class="btn">Cerrar sesión</a>
    </div>

    <h1>Hola, <?= htmlspecialchars($user['username']) ?></h1>

    <div class="card">
        <h2>Resumen de Tareas</h2>
        <div class="stats">
            <div class="stat">
                <strong><?= $total_tareas ?></strong>
                Total
            </div>
            <div class="stat">
                <strong><?= $tareas_completadas ?></strong>
                Completadas
            </div>
            <div class="stat">
                <strong><?= $tareas_pendientes ?></strong>
                Pendientes
            </div>
        </div>
        <a href="tasks.php" class="link">Ver Tareas</a>
    </div>

</body>

</html>