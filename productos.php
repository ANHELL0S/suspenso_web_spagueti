<?php
require 'config/Session.php';
require 'database.php';

Session::start();

if (!Session::isActive()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Obtener el ID del producto que se desea editar (modo edición)
$actualizar_producto_id = isset($_GET['actualizar_producto_id']) ? (int)$_GET['actualizar_producto_id'] : null;

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear_producto'])) {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidad = (int)$_POST['cantidad'];
        $precio = (float)$_POST['precio'];

        if ($nombre !== '' && $cantidad >= 0 && $precio >= 0) {
            $stmt = $pdo->prepare("INSERT INTO productos (user_id, nombre, descripcion, cantidad, precio) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $nombre, $descripcion, $cantidad, $precio]);
        }
    } elseif (isset($_POST['actualizar_stock'])) {
        $producto_id = (int)$_POST['producto_id'];
        $cantidad_nueva = (int)$_POST['cantidad_nueva'];

        // Actualizar el stock solo si pertenece al usuario
        $stmt = $pdo->prepare("UPDATE productos SET cantidad = cantidad + ? 
                               WHERE id = ? AND user_id = ?");
        $stmt->execute([$cantidad_nueva, $producto_id, $_SESSION['user_id']]);
    } elseif (isset($_POST['delete_product'])) {
        $producto_id = (int)$_POST['producto_id'];
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ? AND user_id = ?");
        $stmt->execute([$producto_id, $_SESSION['user_id']]);
    } elseif (isset($_POST['actualizar_producto'])) {
        $producto_id = (int)$_POST['producto_id'];
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidad = (int)$_POST['cantidad'];
        $precio = (float)$_POST['precio'];

        if ($nombre !== '' && $cantidad >= 0 && $precio >= 0) {
            // Actualizar el producto solo si pertenece al usuario
            $stmt = $pdo->prepare("UPDATE productos SET 
                                  nombre = ?, descripcion = ?, cantidad = ?, 
                                  precio = ?
                                  WHERE id = ? AND user_id = ?");
            $stmt->execute([$nombre, $descripcion, $cantidad, $precio, $producto_id, $_SESSION['user_id']]);
        }
    }

    // Redirigir para evitar resubmisión de formulario y salir modo edición
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $url);
    exit;
}

// Obtener productos del usuario
$stmt = $pdo->prepare("SELECT * FROM productos WHERE user_id = ? ORDER BY nombre ASC");
$stmt->execute([$_SESSION['user_id']]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tiempo restante de sesión para mostrar (opcional)
$remaining_time = Session::getRemainingTime();
$minutes = floor($remaining_time / 60);
$seconds = $remaining_time % 60;
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

    <div class="top-bar">
        <div>Sesión: <?= htmlspecialchars("$minutes:$seconds") ?> min</div>
        <a href="logout.php" class="btn">Cerrar sesión</a>
    </div>

    <div>
        <h1>Inventario de Productos</h1>
        <a href="index.php" class="link">← Volver al inicio</a>
    </div>

    <br />

    <div class="card">
        <h2>Agregar Producto</h2>
        <form method="POST" action="">
            <input type="text" name="nombre" placeholder="Nombre del producto" required />
            <textarea name="descripcion" rows="3" placeholder="Descripción (opcional)"></textarea>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="cantidad">Cantidad</label>
                    <input type="number" name="cantidad" min="0" value="0" required />
                </div>
                <div>
                    <label for="precio">Precio</label>
                    <input type="number" name="precio" min="0" step="0.01" placeholder="0.00" required />
                </div>
            </div>
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
                            <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required />
                            <textarea name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="cantidad">Cantidad</label>
                                    <input type="number" name="cantidad" min="0" value="<?= (int)$producto['cantidad'] ?>" required />
                                </div>
                                <div>
                                    <label for="precio">Precio</label>
                                    <input type="number" name="precio" min="0" step="0.01" value="<?= number_format($producto['precio'], 2) ?>" required />
                                </div>
                            </div>
                            <div class="actions">
                                <button type="submit" name="actualizar_producto">Guardar</button>
                                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn" style="background:#ccc; color:#000; text-decoration:none; padding:0.3rem 0.6rem; border-radius:4px; margin-left:0.5rem;">Cancelar</a>
                            </div>
                        </form>
                    <?php else : ?>
                        <!-- Modo normal -->
                        <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <?php if (!empty($producto['descripcion'])) : ?>
                            <p><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
                        <?php endif; ?>

                        <div class="product-info">
                            <div>
                                <span>Cantidad:</span>
                                <span class="<?= $producto['cantidad'] <= 5 ? 'low-stock' : '' ?>">
                                    <?= (int)$producto['cantidad'] ?>
                                </span>
                            </div>
                            <div>
                                <span>Precio:</span> $<?= number_format($producto['precio'], 2) ?>
                            </div>
                            <div>
                                <span>Registrado:</span> <?= date('d/m/Y', strtotime($producto['created_at'])) ?>
                            </div>
                            <div>
                                <span>Actualizado:</span> <?= date('d/m/Y', strtotime($producto['updated_at'])) ?>
                            </div>
                        </div>

                        <div class="actions">
                            <form method="POST" action="" class="stock-form">
                                <input type="hidden" name="producto_id" value="<?= (int)$producto['id'] ?>" />
                                <input type="number" name="cantidad_nueva" value="1" min="-1000" max="1000" />
                                <button type="submit" name="actualizar_stock">Actualizar Stock</button>
                            </form>

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