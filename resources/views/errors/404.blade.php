<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Página não encontrada - Squad Maker</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="error-page">

<div class="error-card">

    <h1>404</h1>

    <h2>Página não encontrada</h2>

    <p>
        A página que você tentou acessar não existe ou não está disponível.
    </p>

    <a href="{{ route('login') }}" class="btn error-button">
        Voltar ao início
    </a>

</div>

</body>
</html>
