<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña</title>
</head>

<body>
    <h1>Recuperar contraseña</h1>
    <form action="<?= base_url('forgot-password-submit') ?>" method="post">
        <label for="correo_electronico">Correo Electrónico</label>
        <input type="email" id="correo_electronico" name="correo_electronico" required>
        <button type="submit">Enviar</button>
    </form>
    <?php if (session()->getFlashdata('msg')): ?>
        <div class="alert"><?= session()->getFlashdata('msg') ?></div>
    <?php endif; ?>
</body>

</html>