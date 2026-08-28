<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Administração - Squad Maker</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/app.css') }}"
    >
</head>

<body class="admin-body">

<header class="admin-header">

    <div class="admin-header-title">
        <img
            src="{{ asset('images/icone.png') }}"
            alt="Squad Maker"
            class="admin-logo"
        >

        <div>
            <strong>Squad Maker</strong>
            <span>Painel Administrativo</span>
        </div>
    </div>

    <nav class="admin-nav">

        <a href="{{ route('admin.dashboard') }}">
            Visão Geral
        </a>

        <a href="{{ route('admin.jogos.index') }}">
            Jogos
        </a>

    </nav>

    <div class="admin-header-user">

        <span>
            {{ Auth::guard('admin')->user()->nome }}
        </span>

        <form
            method="POST"
            action="{{ route('admin.logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="admin-logout-button"
            >
                <i class="bi bi-box-arrow-right"></i>
                Sair
            </button>
        </form>

    </div>

</header>

<main class="admin-content">
    @yield('content')
</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>
