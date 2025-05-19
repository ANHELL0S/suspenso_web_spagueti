<?php
require 'config/Session.php';
require 'database.php';

Session::start();

if (!Session::isActive()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Obtener el ID de la tarea que se desea editar (modo edición)
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        if ($title !== '') {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description]);
        }
    } elseif (isset($_POST['complete_task'])) {
        $task_id = (int)$_POST['task_id'];
        $stmt = $pdo->prepare("UPDATE tasks SET completed = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $_SESSION['user_id']]);
    } elseif (isset($_POST['delete_task'])) {
        $task_id = (int)$_POST['task_id'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $_SESSION['user_id']]);
    } elseif (isset($_POST['edit_task'])) {
        $task_id = (int)$_POST['task_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        if ($title !== '') {
            // Actualizar la tarea solo si pertenece al usuario
            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $description, $task_id, $_SESSION['user_id']]);
        }
    }

    // Redirigir para evitar resubmisión de formulario y salir modo edición
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $url);
    exit;
}

// Obtener tareas del usuario
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tiempo restante de sesión para mostrar (opcional)
$remaining_time = Session::getRemainingTime();
$minutes = floor($remaining_time / 60);
$seconds = $remaining_time % 60;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Lista de Tareas</title>
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

        h2 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .card {
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        form input,
        form textarea {
            width: 100%;
            padding: 0.5rem;
            margin: 0.5rem 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
        }

        form button {
            background: #000;
            color: #fff;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .task {
            border-top: 1px solid #eee;
            padding: 1rem 0;
        }

        .task.completed {
            opacity: 0.6;
        }

        .task h3 {
            margin: 0 0 0.3rem;
        }

        .task p {
            margin: 0.2rem 0;
        }

        .actions {
            margin-top: 0.5rem;
        }

        .actions form {
            display: inline;
        }

        .actions button {
            background: rgb(235, 235, 235);
            color: #333;
            border: none;
            padding: 0.3rem 0.6rem;
            margin-right: 0.4rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .actions button:hover {
            background: #ccc;
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
        <div>Sesión: <?= htmlspecialchars("$minutes:$seconds") ?> min</div>
        <a href="logout.php" class="btn">Cerrar sesión</a>
    </div>

    <div>
        <h1>Lista de Tareas</h1>
        <a href="index.php" class="link">← Volver al inicio</a>
    </div>

    <br />

    <div class="card">
        <h2>Nueva Tarea</h2>
        <form method="POST" action="">
            <input type="text" name="title" placeholder="Título" required />
            <textarea name="description" rows="3" placeholder="Descripción (opcional)"></textarea>
            <button type="submit" name="add_task">Agregar</button>
        </form>
    </div>

    <div class="card">
        <h2>Tus Tareas</h2>
        <?php if (empty($tasks)) : ?>
            <p>No tienes tareas pendientes.</p>
        <?php else : ?>
            <?php foreach ($tasks as $task) : ?>
                <div class="task <?= $task['completed'] ? 'completed' : '' ?>">
                    <?php if ($edit_id === (int)$task['id']) : ?>
                        <!-- Modo edición -->
                        <form method="POST" action="">
                            <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
                            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required />
                            <textarea name="description" rows="3"><?= htmlspecialchars($task['description']) ?></textarea>
                            <div class="actions">
                                <button type="submit" name="edit_task">Guardar</button>
                                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn" style="background:#ccc; color:#000; text-decoration:none; padding:0.3rem 0.6rem; border-radius:4px; margin-left:0.5rem;">Cancelar</a>
                            </div>
                        </form>
                    <?php else : ?>
                        <!-- Modo normal -->
                        <h3><?= htmlspecialchars($task['title']) ?></h3>
                        <?php if (!empty($task['description'])) : ?>
                            <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                        <?php endif; ?>
                        <div class="actions">
                            <?php if (!$task['completed']) : ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
                                    <button type="submit" name="complete_task">Completar</button>
                                </form>
                                <a href="?edit_id=<?= (int)$task['id'] ?>" class="btn" style="background:#111; margin-left:0.5rem;">Editar</a>
                            <?php endif; ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
                                <button type="submit" name="delete_task" onclick="return confirm('¿Seguro que deseas eliminar esta tarea?')">Eliminar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>