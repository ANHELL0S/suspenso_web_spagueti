<?php
require 'config/Session.php';
require 'database.php';

Session::start();

if (!Session::isActive()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-nav {
            margin: 20px 0;
        }

        .logout-btn {
            display: inline-block;
            background: #f44336;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #d32f2f;
        }
    </style>
</head>

<body>
    <h1>Dashboard</h1>
    <p>Bienvenido al panel de control, <?= htmlspecialchars($user['username']) ?></p>

    <div class="dashboard-nav">
        <a href="index.php">Volver al Inicio</a> |
        <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
    </div>
</body>

</html>