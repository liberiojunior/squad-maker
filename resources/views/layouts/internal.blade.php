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

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="internal-body">

<aside class="internal-sidebar">

    <div class="sidebar-top">

        <div class="sidebar-logo-link">
            <img
                src="{{ asset('images/icone.png') }}"
                alt="Squad Maker"
                class="sidebar-logo"
            >
        </div>

        <a href="{{ route('perfil') }}" class="sidebar-item" title="Perfil">
            <i class="bi bi-person-circle"></i>
        </a>

        <a href="#" class="sidebar-item" title="Notícias">
            <i class="bi bi-newspaper"></i>
        </a>

        <a href="#" class="sidebar-item" title="Buscar jogos">
            <i class="bi bi-controller"></i>
        </a>

        <a href="#" class="sidebar-item" title="Chat">
            <i class="bi bi-chat-dots-fill"></i>
        </a>

        <a href="#" class="sidebar-item" title="Publicar">
            <i class="bi bi-camera-fill"></i>
        </a>

    </div>

    <div class="sidebar-bottom">

        <a href="#" class="sidebar-item" title="Ajuda">
            <i class="bi bi-question-circle-fill"></i>
        </a>

        <div class="dropup sidebar-settings">

            <button
                type="button"
                class="sidebar-item sidebar-settings-button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Configurações"
            >
                <i class="bi bi-gear-fill"></i>
            </button>

            <div class="dropdown-menu sidebar-settings-menu">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="dropdown-item sidebar-logout">
                        <i class="bi bi-box-arrow-left"></i>
                        Sair
                    </button>
                </form>

            </div>

        </div>

    </div>

</aside>

<main class="internal-content">
    @yield('content')
</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
