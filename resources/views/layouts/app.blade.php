<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Squad Maker</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>

<nav class="navbar navbar-expand-md navbar-dark squad-navbar">
    <div class="container-fluid position-relative px-3">

        <a class="navbar-brand" href="{{ route('login') }}">
            <img
                src="{{ asset('images/icone.png') }}"
                alt="Squad Maker"
                class="navbar-logo"
            >
        </a>

        <button
            class="navbar-toggler ms-auto"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse justify-content-center"
            id="mainNavbar"
        >
            <ul class="navbar-nav text-center">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('sobre-nos') }}">
                        Sobre Nós
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('equipe') }}">
                        A Equipe
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('contato') }}">
                        Contato
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>

@yield('content')

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
