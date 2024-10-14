<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
</head>

<body>
    <h1>Restablecer contraseña</h1>
    <form action="<?= base_url('reset-password-submit') ?>" method="post">
        <input type="hidden" name="token" value="<?= esc($token) ?>">
        <label for="contrasena">Nueva Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" required>
        <label for="confirmar_contrasena">Confirmar Nueva Contraseña</label>
        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
        <button type="submit">Restablecer Contraseña</button>
    </form>
    <?php if (session()->getFlashdata('msg')): ?>
        <div class="alert"><?= session()->getFlashdata('msg') ?></div>
    <?php endif; ?>
</body>

</html>