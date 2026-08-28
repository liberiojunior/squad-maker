<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Erro - Squad Maker</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="error-page">

<div class="error-card">

    <h1>Ops!</h1>

    <h2>Algo deu errado</h2>

    <p>
        Não foi possível concluir esta ação. Tente novamente mais tarde.
    </p>

    <a href="{{ route('login') }}" class="btn error-button">
        Voltar ao início
    </a>

</div>

</body>
</html>
