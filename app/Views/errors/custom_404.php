<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Eqqua</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.min.css') ?>">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .error-container {
            text-align: center;
        }

        .error-container h1 {
            font-size: 10rem;
            margin-bottom: 0;
        }

        .error-container p {
            font-size: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <h1>404</h1>
        <p>Lo sentimos, la página que estás buscando no se pudo encontrar.</p>
        <a href="<?= base_url('/') ?>" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>

</html>