<?php
require 'config/Session.php';
require 'database.php';

Session::start();

if (Session::isActive()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['flash_error'] = "Las contraseñas no coinciden";
        header("Location: register.php");
        exit;
    } elseif (strlen($password) < 8) {
        $_SESSION['flash_error'] = "La contraseña debe tener al menos 8 caracteres";
        header("Location: register.php");
        exit;
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_error'] = "El usuario o email ya existe";
            header("Location: register.php");
            exit;
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

            if ($stmt->execute([$username, $email, $hashed_password])) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $_SESSION['flash_error'] = "Error al registrar el usuario";
                header("Location: register.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f9f9f9;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
        }

        .message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
            animation: fadeOut 1s forwards;
            animation-delay: 1s;
            opacity: 1;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                height: 0;
                padding: 0;
                margin: 0;
            }
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
        }

        form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 15px;
        }

        button {
            background-color: #3498db;
            color: white;
            padding: 14px;
            margin: 20px 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        p {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }

        a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h2>Registro de Usuario</h2>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Nombre de usuario" required>
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" required>
        <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
        <button type="submit">Registrarse</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
</body>

</html>