<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <!-- Agrega aquí tus enlaces a CSS, meta tags, etc. -->
</head>

<body>
    <header>
        <h1>Información del Cliente</h1>
    </header>

    <main>
        <h2><?= esc($cliente['nombre_empresa']) ?></h2>
        <p><strong>Contacto:</strong> <?= esc($cliente['correo_contacto']) ?></p>
        <p><strong>Teléfono:</strong> <?= esc($cliente['telefono_contacto']) ?></p>
        <p><strong>Dirección:</strong> <?= esc($cliente['direccion']) ?></p>
        <!-- Añade más campos según sea necesario -->
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Eqqua</p>
    </footer>
</body>

</html>