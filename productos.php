<?php
// 1. Configuración de la base de datos
$host = 'localhost';
$db   = 'articulos_cientificos';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. Crear tablas si no existen
$pdo->exec("CREATE TABLE IF NOT EXISTS articulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    resumen TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    articulo_id INT NOT NULL,
    autor VARCHAR(100) NOT NULL,
    contenido TEXT NOT NULL,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (articulo_id) REFERENCES articulos(id) ON DELETE CASCADE
)");

// 3. Funciones principales

// Agregar artículo científico
function agregarArticulo($titulo, $resumen)
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO articulos (titulo, resumen) VALUES (?, ?)");
    $stmt->execute([$titulo, $resumen]);
    return $pdo->lastInsertId();
}

// Enviar comentario
function enviarComentario($articulo_id, $autor, $contenido)
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO comentarios (articulo_id, autor, contenido) VALUES (?, ?, ?)");
    return $stmt->execute([$articulo_id, $autor, $contenido]);
}

// Obtener todos los artículos con sus comentarios
function obtenerArticulosConComentarios()
{
    global $pdo;

    // Obtener todos los artículos
    $articulos = $pdo->query("SELECT * FROM articulos ORDER BY fecha_creacion DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Para cada artículo, obtener sus comentarios
    foreach ($articulos as &$articulo) {
        $stmt = $pdo->prepare("SELECT * FROM comentarios WHERE articulo_id = ? ORDER BY fecha_publicacion DESC");
        $stmt->execute([$articulo['id']]);
        $articulo['comentarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $articulos;
}

// 4. Procesamiento de formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar_articulo':
                $id = agregarArticulo($_POST['titulo'], $_POST['resumen']);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
                break;

            case 'enviar_comentario':
                enviarComentario($_POST['articulo_id'], $_POST['autor'], $_POST['contenido']);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
                break;
        }
    }
}

// 5. Obtener datos para mostrar
$articulos = obtenerArticulosConComentarios();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Artículos Científicos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .articulo {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .articulo h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .comentarios {
            margin-top: 20px;
        }

        .comentario {
            background: white;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 15px;
        }

        .comentario .meta {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        form {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        textarea {
            min-height: 100px;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background: #2980b9;
        }

        .no-comments {
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>

<body>
    <h1>Sistema de Artículos Científicos</h1>

    <!-- Formulario para agregar artículo -->
    <form method="POST">
        <h2>Agregar nuevo artículo científico</h2>
        <input type="hidden" name="accion" value="agregar_articulo">

        <div class="form-group">
            <label for="titulo">Título del artículo:</label>
            <input type="text" id="titulo" name="titulo" required>
        </div>

        <div class="form-group">
            <label for="resumen">Resumen:</label>
            <textarea id="resumen" name="resumen" required></textarea>
        </div>

        <button type="submit">Publicar Artículo</button>
    </form>

    <!-- Lista de artículos con sus comentarios -->
    <h2>Artículos Científicos</h2>

    <?php if (count($articulos) > 0): ?>
        <?php foreach ($articulos as $articulo): ?>
            <div class="articulo">
                <h2><?= htmlspecialchars($articulo['titulo']) ?></h2>
                <p><?= nl2br(htmlspecialchars($articulo['resumen'])) ?></p>
                <small>Publicado el: <?= date('d/m/Y H:i', strtotime($articulo['fecha_creacion'])) ?></small>

                <!-- Comentarios del artículo -->
                <div class="comentarios">
                    <h3>Comentarios (<?= count($articulo['comentarios']) ?>)</h3>

                    <?php if (count($articulo['comentarios']) > 0): ?>
                        <?php foreach ($articulo['comentarios'] as $comentario): ?>
                            <div class="comentario">
                                <div class="meta">
                                    <span class="autor"><?= htmlspecialchars($comentario['autor']) ?></span>
                                    <span class="fecha"><?= date('d/m/Y H:i', strtotime($comentario['fecha_publicacion'])) ?></span>
                                </div>
                                <p><?= nl2br(htmlspecialchars($comentario['contenido'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-comments">No hay comentarios aún.</p>
                    <?php endif; ?>

                    <!-- Formulario para nuevo comentario -->
                    <form method="POST">
                        <h4>Agregar comentario</h4>
                        <input type="hidden" name="accion" value="enviar_comentario">
                        <input type="hidden" name="articulo_id" value="<?= $articulo['id'] ?>">

                        <div class="form-group">
                            <label for="autor-<?= $articulo['id'] ?>">Tu nombre:</label>
                            <input type="text" id="autor-<?= $articulo['id'] ?>" name="autor" required>
                        </div>

                        <div class="form-group">
                            <label for="contenido-<?= $articulo['id'] ?>">Tu comentario:</label>
                            <textarea id="contenido-<?= $articulo['id'] ?>" name="contenido" required></textarea>
                        </div>

                        <button type="submit">Enviar Comentario</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay artículos publicados aún.</p>
    <?php endif; ?>
</body>

</html>