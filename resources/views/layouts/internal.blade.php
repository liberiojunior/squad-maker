<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="https://res.cloudinary.com/daqcpj88/image/upload/v1792785349/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Squad Maker</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('styles')
</head>

<body
    class="internal-body"
    data-user-id="{{ auth()->id() }}"
    data-chat-updates-url="{{ route('mensagens.atualizacoes') }}"
    data-reverb-key="{{ config('broadcasting.connections.reverb.key') }}"
    data-reverb-host="{{ app()->isProduction() ? (parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost()) : config('broadcasting.connections.reverb.options.host') }}"
    data-reverb-port="{{ app()->isProduction() ? 443 : config('broadcasting.connections.reverb.options.port') }}"
    data-reverb-scheme="{{ app()->isProduction() ? 'https' : config('broadcasting.connections.reverb.options.scheme') }}"
>

<aside class="internal-sidebar">

    <div class="sidebar-top">

        <div class="sidebar-logo-link">
            <img
                src="{{ asset('images/icone.png') }}"
                alt="Squad Maker"
                class="sidebar-logo"
            >
        </div>

        <a
            href="{{ route('perfil') }}"
            class="sidebar-item {{ request()->routeIs('perfil', 'perfil.*') ? 'active' : '' }}"
            title="Perfil"
        >
            <i class="bi bi-person-circle"></i>
        </a>

        <a
            href="{{ route('noticias.index') }}"
            class="sidebar-item {{ request()->routeIs('noticias.*') ? 'active' : '' }}"
            title="Notícias"
        >
            <i class="bi bi-newspaper"></i>
        </a>

        <a
            href="{{ route('jogos.buscar') }}"
            class="sidebar-item {{ request()->routeIs('jogos.*') ? 'active' : '' }}"
            title="Buscar jogos"
        >
            <i class="bi bi-search"></i>
        </a>

        @php($notificacoesChat = auth()->user()->totalNotificacoesChat())

        <a
            href="{{ route('conversas.index') }}"
            class="sidebar-item {{ request()->routeIs('conversas.*') ? 'active' : '' }}"
            title="Chat"
        >
            <i class="bi bi-chat-dots-fill"></i>

            <span
                class="sidebar-notification-badge"
                id="sidebarChatBadge"
                aria-label="{{ $notificacoesChat }} notificações"
                @if ($notificacoesChat === 0) hidden @endif
            >
                {{ $notificacoesChat > 99 ? '99+' : $notificacoesChat }}
            </span>
        </a>

        <a
            href="{{ route('posts.feed') }}"
            class="sidebar-item {{ request()->routeIs('posts.*') ? 'active' : '' }}"
            title="Mural de publicações"
        >
            <i class="bi bi-house-fill"></i>
        </a>

    </div>

    <div class="sidebar-bottom">

        <a
            href="{{ route('ajuda') }}"
            class="sidebar-item {{ request()->routeIs('ajuda') ? 'active' : '' }}"
            title="Central de Ajuda"
        >
            <i class="bi bi-question-circle-fill"></i>
        </a>

        <div class="dropup sidebar-settings">

            <button
                type="button"
                class="sidebar-item sidebar-settings-button"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-label="Configurações"
                title="Configurações"
            >
                <i class="bi bi-gear-fill"></i>
            </button>

            <div class="dropdown-menu sidebar-settings-menu">

                <a href="{{ route('ajuda') }}" class="dropdown-item sidebar-settings-help {{ request()->routeIs('ajuda') ? 'active' : '' }}">
                    <i class="bi bi-question-circle-fill"></i>
                    Central de Ajuda
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="button"
                        class="dropdown-item sidebar-password"
                        data-bs-toggle="modal"
                        data-bs-target="#changePasswordModal"
                    >
                        <i class="bi bi-key-fill"></i>
                        Trocar senha
                    </button>

                    <button
                        type="button"
                        class="dropdown-item sidebar-delete-account"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteAccountModal"
                    >
                        <i class="bi bi-trash3"></i>
                        Excluir conta
                    </button>

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
    @if (session('settings_success'))
        <div class="alert alert-success">
            {{ session('settings_success') }}
        </div>
    @endif

    @yield('content')
</main>

<div
    class="modal fade"
    id="changePasswordModal"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content squad-modal account-delete-modal">
            <form method="POST" action="{{ route('senha.update') }}">
                @csrf
                @method('PATCH')

                <div class="modal-header">
                    <h2 class="modal-title">Trocar senha</h2>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body settings-password-body">
                    <div>
                        <label for="currentPassword" class="form-label">
                            Senha atual
                        </label>

                        <input
                            type="password"
                            name="current_password"
                            id="currentPassword"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        >

                        @error('current_password', 'updatePassword')
                            <span class="settings-password-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div>
                        <label for="newPassword" class="form-label">
                            Nova senha
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="newPassword"
                            class="form-control"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        @error('password', 'updatePassword')
                            <span class="settings-password-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div>
                        <label for="newPasswordConfirmation" class="form-label">
                            Confirmar nova senha
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            id="newPasswordConfirmation"
                            class="form-control"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button type="submit" class="btn squad-primary-button">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div
    class="modal fade"
    id="deleteAccountModal"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content squad-modal account-delete-modal">

            <form
                method="POST"
                action="{{ route('conta.destroy') }}"
            >
                @csrf
                @method('DELETE')

                <div class="modal-header">

                    <h2 class="modal-title">
                        Excluir minha conta
                    </h2>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <p>
                        Esta ação removerá sua conta do Squad Maker.
                    </p>

                    <p>
                        Para confirmar, digite o nome do usuário.
                    </p>

                    <input
                        type="text"
                        name="confirmacao"
                        class="form-control mt-3"
                        autocomplete="off"
                        required
                    >

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Excluir minha conta
                    </button>

                </div>

            </form>

        </div>

    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

@if ($errors->updatePassword->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('changePasswordModal')
        ).show();
    });
</script>
@endif

@vite('resources/js/realtime.js')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')

</body>
</html>
