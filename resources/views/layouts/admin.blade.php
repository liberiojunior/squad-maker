<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Squad Maker - Administração
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/app.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/admin.css') }}"
    >

</head>


<body class="admin-body">

<header class="admin-header">

    {{-- Identificação do painel --}}

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


    {{-- Menu --}}

    <div class="admin-nav-wrapper">

        <button
            type="button"
            class="admin-nav-toggle"
            data-bs-toggle="collapse"
            data-bs-target="#adminNavMenu"
            aria-expanded="false"
            aria-controls="adminNavMenu"
        >
            <i class="bi bi-list"></i>
            Menu
        </button>


        <nav
            class="admin-nav collapse"
            id="adminNavMenu"
        >

            <a
                href="{{ route('admin.geral') }}"
                class="{{ request()->routeIs('admin.geral') ? 'active' : '' }}"
            >
                Visão Geral
            </a>

            <a
                href="{{ route('admin.dashboard') }}"
                class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
            >
                Dashboard
            </a>

            <a
                href="{{ route('admin.usuarios.index') }}"
                class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}"
            >
                Usuários
            </a>

            <a
                href="{{ route('admin.denuncias.index') }}"
                class="{{ request()->routeIs('admin.denuncias.*') ? 'active' : '' }}"
            >
                Denúncias
            </a>

            <a
                href="{{ route('admin.jogos.index') }}"
                class="{{ request()->routeIs('admin.jogos.*') ? 'active' : '' }}"
            >
                Jogos
            </a>

            <a
                href="{{ route('admin.catalogo.index') }}"
                class="{{
                    request()->routeIs('admin.catalogo.*')
                    || request()->routeIs('admin.plataformas.*')
                    || request()->routeIs('admin.generos.*')
                        ? 'active'
                        : ''
                }}"
            >
                Catálogo
            </a>

            <a
                href="{{ route('admin.noticias') }}"
                class="{{ request()->routeIs('admin.noticias') ? 'active' : '' }}"
            >
                Notícias
            </a>

        </nav>

    </div>


    {{-- Administrador --}}

    <div class="admin-header-user">

        @if (auth('admin')->check())
            <span>
                {{ auth('admin')->user()->nome }}
            </span>
        @endif

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

<div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content squad-modal admin-confirm-modal">
            <div class="modal-header">
                <h2 class="modal-title" id="adminConfirmTitle">Confirmar ação</h2>
                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>
            </div>

            <div class="modal-body">
                <p id="adminConfirmMessage"></p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="button" class="btn btn-danger" id="adminConfirmAccept">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>

<script src="{{ asset('js/app.js') }}"></script>

@stack('scripts')

</body>
</html>
