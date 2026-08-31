@extends('layouts.internal')

@section('content')

    {{-- Mensagens --}}

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">

            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach

        </div>
    @endif


    <div class="profile-page">

        {{-- Informações principais do perfil --}}

        <div class="profile-top">

            {{-- Avatar --}}

            <div class="profile-avatar-area">

                <form
                    method="POST"
                    action="{{ route('perfil.avatar.update') }}"
                    enctype="multipart/form-data"
                    id="avatarForm"
                >
                    @csrf
                    @method('PATCH')

                    <label
                        for="avatarInput"
                        class="profile-avatar-wrapper"
                    >

                        <img
                            src="{{ $user->avatar ?: asset('images/icone.png') }}"
                            alt="{{ $user->nickname }}"
                            class="profile-avatar"
                        >

                        <span class="profile-avatar-overlay">
                            <i class="bi bi-camera-fill"></i>
                            Alterar foto
                        </span>

                    </label>

                    <input
                        type="file"
                        name="avatar"
                        id="avatarInput"
                        accept="image/png,image/jpeg,image/webp"
                        hidden
                    >

                </form>

            </div>


            {{-- Nome, bio e gêneros --}}

            <div class="profile-main-info">

                <section class="profile-info-card">

                    <form
                        method="POST"
                        action="{{ route('perfil.update') }}"
                        id="profileForm"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="text"
                            name="nickname"
                            id="nicknameInput"
                            class="profile-name-input"
                            value="{{ old('nickname', $user->nickname) }}"
                            maxlength="80"
                            readonly
                        >

                        <button
                            type="button"
                            class="profile-edit-button"
                            id="profileEditButton"
                            title="Editar perfil"
                        >
                            <i
                                class="bi bi-pencil-fill"
                                id="profileEditIcon"
                            ></i>
                        </button>

                        <textarea
                            name="bio"
                            id="bioInput"
                            class="profile-bio"
                            readonly
                            placeholder="Você ainda não adicionou uma bio."
                        >{{ old('bio', $user->bio) }}</textarea>

                    </form>

                </section>


                <section class="profile-genres-card">

                    <strong>
                        Meus Gêneros:
                    </strong>

                    <div class="profile-genre-list">

                        @forelse ($user->generos as $genero)

                            <span class="profile-genre-tag">
                                {{ $genero->genero }}
                            </span>

                        @empty

                            <span class="profile-empty-inline">
                                Nenhum gênero configurado.
                            </span>

                        @endforelse

                    </div>

                    <button
                        type="button"
                        class="profile-small-add"
                        data-bs-toggle="modal"
                        data-bs-target="#generosModal"
                        title="Editar gêneros"
                    >
                        <i class="bi bi-plus-lg"></i>
                    </button>

                </section>

            </div>

        </div>


        {{-- Feed --}}

        <section class="profile-section profile-feed">

            <div class="profile-section-title">
                <h2>Feed do Usuário</h2>
            </div>

            <div class="profile-feed-empty">

                <i class="bi bi-images"></i>

                <p>
                    Nenhuma publicação ainda.
                </p>

            </div>

        </section>


        {{-- Jogos --}}

        <section class="profile-section">

            <div class="profile-section-title">

                <h2>Meus Jogos</h2>

                @if ($user->jogos->count() > 4)

                    <button
                        type="button"
                        class="profile-view-all"
                        data-bs-toggle="modal"
                        data-bs-target="#todosJogosModal"
                    >
                        Ver todos ({{ $user->jogos->count() }})
                    </button>

                @endif

            </div>


            <div class="profile-games-row">

                @if ($user->jogos->isNotEmpty())

                    <div class="profile-games-list">

                        @foreach ($user->jogos->take(4) as $jogo)

                            <div class="profile-game-card">

                                <img
                                    src="{{ $jogo->capa }}"
                                    alt="{{ $jogo->nome }}"
                                >

                                <span>
                                    {{ $jogo->nome }}
                                </span>

                            </div>

                        @endforeach

                    </div>

                @endif


                <button
                    type="button"
                    class="profile-add-card profile-game-add"
                    data-bs-toggle="modal"
                    data-bs-target="#adicionarJogosModal"
                    title="Adicionar jogos"
                >
                    <i class="bi bi-plus-lg"></i>
                </button>

            </div>

        </section>


        {{-- Plataformas --}}

        <section class="profile-section">

            <div class="profile-section-title">
                <h2>Minhas Plataformas</h2>
            </div>


            <div class="profile-platforms-list">

                @foreach ($user->plataformas as $plataforma)

                    <div class="profile-platform-card">

                        <img
                            src="{{ $plataforma->icone }}"
                            alt="{{ $plataforma->nome }}"
                        >

                        <span>
                            {{ $plataforma->nome }}
                        </span>

                    </div>

                @endforeach


                <button
                    type="button"
                    class="profile-add-card profile-platform-add"
                    data-bs-toggle="modal"
                    data-bs-target="#plataformasModal"
                    title="Adicionar plataformas"
                >
                    <i class="bi bi-plus-lg"></i>
                </button>

            </div>

        </section>

    </div>


    {{-- Modal de gêneros --}}

    <div
        class="modal fade"
        id="generosModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content profile-genres-modal">

                <form
                    method="POST"
                    action="{{ route('perfil.generos.update') }}"
                >
                    @csrf
                    @method('PATCH')


                    <div class="modal-header">

                        <h2 class="modal-title">
                            Meus Gêneros
                        </h2>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>

                    </div>


                    <div class="modal-body">

                        <p class="profile-genres-description">
                            Escolha os gêneros de jogos que mais combinam com você.
                        </p>


                        <div class="profile-genre-options">

                            @foreach ($generos as $genero)

                                <label class="profile-genre-option">

                                    <input
                                        type="checkbox"
                                        name="generos[]"
                                        value="{{ $genero->id_genero }}"
                                        @checked(
                                            $user->generos->contains(
                                                'id_genero',
                                                $genero->id_genero
                                            )
                                        )
                                    >

                                    <span>
                                        {{ $genero->genero }}
                                    </span>

                                </label>

                            @endforeach

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

                        <button
                            type="submit"
                            class="btn profile-genres-save"
                        >
                            Salvar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    {{-- Modal para adicionar jogos --}}

    <div
        class="modal fade"
        id="adicionarJogosModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content profile-games-modal">

                <form
                    method="POST"
                    action="{{ route('perfil.jogos.update') }}"
                >
                    @csrf
                    @method('PATCH')


                    <div class="modal-header">

                        <h2 class="modal-title">
                            Meus Jogos
                        </h2>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>

                    </div>


                    <div class="modal-body">

                        <p class="profile-games-description">
                            Escolha os jogos que você joga ou tem interesse.
                        </p>


                        <input
                            type="text"
                            id="profileGameSearch"
                            class="form-control profile-game-search"
                            placeholder="Buscar jogo..."
                            autocomplete="off"
                        >


                        <div
                            class="profile-game-options"
                            id="profileGameOptions"
                        >

                            @forelse ($jogosDisponiveis as $jogo)

                                <label
                                    class="profile-game-option"
                                    data-name="{{ strtolower($jogo->nome) }}"
                                >

                                    <input
                                        type="checkbox"
                                        name="jogos[]"
                                        value="{{ $jogo->id_jogo }}"
                                        @checked(
                                            $user->jogos->contains(
                                                'id_jogo',
                                                $jogo->id_jogo
                                            )
                                        )
                                    >


                                    <div class="profile-game-option-card">

                                        <img
                                            src="{{ $jogo->capa }}"
                                            alt="{{ $jogo->nome }}"
                                        >

                                        <span>
                                            {{ $jogo->nome }}
                                        </span>

                                    </div>

                                </label>

                            @empty

                                <div class="profile-games-empty">
                                    Ainda não existem jogos cadastrados.
                                </div>

                            @endforelse

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

                        <button
                            type="submit"
                            class="btn profile-games-save"
                        >
                            Salvar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    {{-- Modal com todos os jogos --}}

    <div
        class="modal fade"
        id="todosJogosModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content profile-games-modal">

                <div class="modal-header">

                    <h2 class="modal-title">
                        Meus Jogos
                    </h2>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="profile-all-games">

                        @foreach ($user->jogos as $jogo)

                            <div class="profile-game-card">

                                <img
                                    src="{{ $jogo->capa }}"
                                    alt="{{ $jogo->nome }}"
                                >

                                <span>
                                    {{ $jogo->nome }}
                                </span>

                            </div>

                        @endforeach

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Modal de plataformas --}}

    <div
        class="modal fade"
        id="plataformasModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content profile-games-modal">

                <form
                    method="POST"
                    action="{{ route('perfil.plataformas.update') }}"
                >
                    @csrf
                    @method('PATCH')


                    <div class="modal-header">

                        <h2 class="modal-title">
                            Minhas Plataformas
                        </h2>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>

                    </div>


                    <div class="modal-body">

                        <p class="profile-games-description">
                            Escolha as plataformas em que você joga.
                        </p>


                        <div class="profile-platform-options">

                            @forelse ($plataformasDisponiveis as $plataforma)

                                <label class="profile-platform-option">

                                    <input
                                        type="checkbox"
                                        name="plataformas[]"
                                        value="{{ $plataforma->id_plataforma }}"
                                        @checked(
                                            $user->plataformas->contains(
                                                'id_plataforma',
                                                $plataforma->id_plataforma
                                            )
                                        )
                                    >

                                    <div>

                                        <img
                                            src="{{ $plataforma->icone }}"
                                            alt="{{ $plataforma->nome }}"
                                        >

                                        <span>
                                            {{ $plataforma->nome }}
                                        </span>

                                    </div>

                                </label>

                            @empty

                                <div class="profile-games-empty">
                                    Ainda não existem plataformas cadastradas.
                                </div>

                            @endforelse

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

                        <button
                            type="submit"
                            class="btn profile-games-save"
                        >
                            Salvar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    {{-- JavaScript do perfil --}}

    <script>
        const profileForm = document.getElementById('profileForm');
        const nicknameInput = document.getElementById('nicknameInput');
        const bioInput = document.getElementById('bioInput');
        const profileEditButton = document.getElementById('profileEditButton');
        const profileEditIcon = document.getElementById('profileEditIcon');

        const avatarForm = document.getElementById('avatarForm');
        const avatarInput = document.getElementById('avatarInput');

        let editingProfile = false;


        // Editar nome e bio

        profileEditButton.addEventListener('click', function () {

            if (!editingProfile) {

                editingProfile = true;

                nicknameInput.removeAttribute('readonly');
                bioInput.removeAttribute('readonly');

                nicknameInput.focus();

                profileEditIcon.classList.remove('bi-pencil-fill');
                profileEditIcon.classList.add('bi-check-lg');

                profileEditButton.title = 'Salvar alterações';

            } else {

                profileForm.requestSubmit();

            }

        });


        // Alterar avatar

        avatarInput.addEventListener('change', function () {

            if (avatarInput.files.length > 0) {
                avatarForm.requestSubmit();
            }

        });


        // Buscar jogos dentro do modal

        const profileGameSearch =
            document.getElementById('profileGameSearch');

        const profileGameOptions =
            document.querySelectorAll('.profile-game-option');


        if (profileGameSearch) {

            profileGameSearch.addEventListener(
                'input',
                function () {

                    const search =
                        this.value.toLowerCase().trim();

                    profileGameOptions.forEach(
                        function (option) {

                            const name =
                                option.dataset.name;

                            option.style.display =
                                name.includes(search)
                                    ? ''
                                    : 'none';

                        }
                    );

                }
            );

        }

    </script>

@endsection
