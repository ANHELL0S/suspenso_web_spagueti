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

// Obtener comentarios de un artículo
function obtenerComentarios($articulo_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM comentarios WHERE articulo_id = ? ORDER BY fecha_publicacion DESC");
    $stmt->execute([$articulo_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener todos los artículos
function obtenerArticulos()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM articulos ORDER BY fecha_creacion DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Procesamiento de formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar_articulo':
                $id = agregarArticulo($_POST['titulo'], $_POST['resumen']);
                header("Location: ?articulo_id=$id");
                exit;
                break;

            case 'enviar_comentario':
                enviarComentario($_POST['articulo_id'], $_POST['autor'], $_POST['contenido']);
                header("Location: ?articulo_id=" . $_POST['articulo_id']);
                exit;
                break;
        }
    }
}

// 5. Obtener datos para mostrar
$articulo_id = $_GET['articulo_id'] ?? null;
$articulo_actual = null;
$comentarios = [];

if ($articulo_id) {
    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ?");
    $stmt->execute([$articulo_id]);
    $articulo_actual = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($articulo_actual) {
        $comentarios = obtenerComentarios($articulo_id);
    }
}

$articulos = obtenerArticulos();
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

        .container {
            display: grid;
            grid-template-columns: 30% 70%;
            gap: 20px;
        }

        .articulo {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .articulo h2 {
            margin-top: 0;
            color: #2c3e50;
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
        }

        form {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
        }

        input,
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
        }

        .articulo-list {
            list-style: none;
            padding: 0;
        }

        .articulo-list li {
            margin-bottom: 10px;
        }

        .articulo-list a {
            text-decoration: none;
            color: #2c3e50;
        }

        .articulo-list a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h1>Sistema de Artículos Científicos</h1>

    <div class="container">
        <!-- Columna izquierda: Lista de artículos -->
        <div>
            <h2>Artículos</h2>
            <ul class="articulo-list">
                <?php foreach ($articulos as $art): ?>
                    <li>
                        <a href="?articulo_id=<?= $art['id'] ?>"><?= htmlspecialchars($art['titulo']) ?></a>
                        <small><?= date('d/m/Y', strtotime($art['fecha_creacion'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h3>Agregar nuevo artículo</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="agregar_articulo">
                <input type="text" name="titulo" placeholder="Título del artículo" required>
                <textarea name="resumen" placeholder="Resumen del artículo" rows="5" required></textarea>
                <button type="submit">Publicar Artículo</button>
            </form>
        </div>

        <!-- Columna derecha: Artículo seleccionado y comentarios -->
        <div>
            <?php if ($articulo_actual): ?>
                <div class="articulo">
                    <h2><?= htmlspecialchars($articulo_actual['titulo']) ?></h2>
                    <p><?= nl2br(htmlspecialchars($articulo_actual['resumen'])) ?></p>
                    <small>Publicado el: <?= date('d/m/Y H:i', strtotime($articulo_actual['fecha_creacion'])) ?></small>
                </div>

                <h3>Comentarios</h3>
                <?php if ($comentarios): ?>
                    <?php foreach ($comentarios as $com): ?>
                        <div class="comentario">
                            <div class="meta">
                                <strong><?= htmlspecialchars($com['autor']) ?></strong>
                                <span><?= date('d/m/Y H:i', strtotime($com['fecha_publicacion'])) ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars($com['contenido'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
                <?php endif; ?>

                <h3>Enviar comentario</h3>
                <form method="POST">
                    <input type="hidden" name="accion" value="enviar_comentario">
                    <input type="hidden" name="articulo_id" value="<?= $articulo_actual['id'] ?>">
                    <input type="text" name="autor" placeholder="Tu nombre" required>
                    <textarea name="contenido" placeholder="Tu comentario" rows="3" required></textarea>
                    <button type="submit">Enviar Comentario</button>
                </form>
            <?php else: ?>
                <p>Selecciona un artículo de la lista o crea uno nuevo.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>