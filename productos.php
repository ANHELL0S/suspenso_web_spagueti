<?php
// DEPURACION
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'database.php';

// Obtener el ID del producto que se desea editar (modo edición)
$actualizar_producto_id = isset($_GET['actualizar_producto_id']) ? (int)$_GET['actualizar_producto_id'] : null;

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear_producto'])) {
        $titulo = trim($_POST['titulo']);
        $resumen = trim($_POST['resumen'] ?? '');

        if ($titulo !== '') {
            $stmt = $pdo->prepare("INSERT INTO productos (titulo, resumen) VALUES (?, ?)");
            $stmt->execute([$titulo, $resumen]);
        }
    } elseif (isset($_POST['delete_product'])) {
        $producto_id = (int)$_POST['producto_id'];
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
    } elseif (isset($_POST['actualizar_producto'])) {
        $producto_id = (int)$_POST['producto_id'];
        $titulo = trim($_POST['titulo']);
        $resumen = trim($_POST['resumen'] ?? '');

        if ($titulo !== '') {
            // Actualizar el producto solo si pertenece al usuario
            $stmt = $pdo->prepare("UPDATE productos SET 
                                  titulo = ?, resumen = ?
                                  WHERE id = ?");
            $stmt->execute([$titulo, $resumen, $producto_id]);
        }
    }

    // Redirigir para evitar resubmisión de formulario y salir modo edición
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $url);
    exit;
}

// Obtener productos del usuario
$stmt = $pdo->prepare("SELECT * FROM productos ORDER BY titulo ASC");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Inventario de Productos</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 800px;
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
        form textarea,
        form select {
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

        .product {
            border-top: 1px solid #eee;
            padding: 1rem 0;
        }

        .product h3 {
            margin: 0 0 0.3rem;
        }

        .product p {
            margin: 0.2rem 0;
        }

        .product-info {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
        }

        .product-info span {
            font-weight: bold;
        }

        .low-stock {
            color: #d9534f;
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

        .stock-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .stock-form input {
            width: 80px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Agregar Producto</h2>
        <form method="POST" action="">
            <input type="text" name="titulo" placeholder="Titulo del producto" required />
            <textarea name="resumen" rows="3" placeholder="Descripción (opcional)"></textarea>
            <button type="submit" name="crear_producto">Agregar Producto</button>
        </form>
    </div>

    <div class="card">
        <h2>Tus Productos</h2>
        <?php if (empty($productos)) : ?>
            <p>No tienes productos en tu inventario.</p>
        <?php else : ?>
            <?php foreach ($productos as $producto) : ?>
                <div class="product">
                    <?php if ($actualizar_producto_id === (int)$producto['id']) : ?>
                        <!-- Modo edición -->
                        <form method="POST" action="">
                            <input type="hidden" name="producto_id" value="<?= (int)$producto['id'] ?>" />
                            <input type="text" name="titulo" value="<?= htmlspecialchars($producto['titulo']) ?>" required />
                            <textarea name="resumen" rows="3"><?= htmlspecialchars($producto['resumen']) ?></textarea>
                            <div class="actions">
                                <button type="submit" name="actualizar_producto">Guardar</button>
                                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn" style="background:#ccc; color:#000; text-decoration:none; padding:0.3rem 0.6rem; border-radius:4px; margin-left:0.5rem;">Cancelar</a>
                            </div>
                        </form>
                    <?php else : ?>
                        <!-- Modo normal -->
                        <h3><?= htmlspecialchars($producto['titulo']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($producto['resumen'])) ?></p>
                        <div class="product-info">
                            <div>
                                <span>Registrado:</span> <?= date('d/m/Y', strtotime($producto['created_at'])) ?>
                            </div>
                            <div>
                                <span>Actualizado:</span> <?= date('d/m/Y', strtotime($producto['updated_at'])) ?>
                            </div>
                        </div>

                        <div class="actions">
                            <div style="margin-top: 0.5rem;">
                                <a href="?actualizar_producto_id=<?= (int)$producto['id'] ?>" class="btn" style="background:#111; margin-left:0.5rem;">Editar</a>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="producto_id" value="<?= (int)$producto['id'] ?>" />
                                    <button type="submit" name="delete_product" onclick="return confirm('¿Seguro que deseas eliminar este producto?')">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>