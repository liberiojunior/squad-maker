@extends('layouts.internal')

@section('content')
    @php
        $jogosPerfil = $user->jogos
            ->map(function ($jogo) {
                return [
                    'id' => (int) $jogo->id_jogo,
                    'nome' => $jogo->nome,
                    'capa' => $jogo->capa,
                    'nivel' => $jogo->pivot->nivel_proficiencia
                        ? (int) $jogo->pivot->nivel_proficiencia
                        : null,
                    'ordem' => $jogo->pivot->ordem_perfil
                        ? (int) $jogo->pivot->ordem_perfil
                        : null,
                    'update_url' => route(
                        'perfil.jogos.nivel.update',
                        $jogo
                    ),
                    'remove_url' => route(
                        'perfil.jogos.remove',
                        $jogo
                    ),
                ];
            })
            ->values();
    @endphp

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

    <div
        class="profile-page"
        id="profilePage"
        data-csrf="{{ csrf_token() }}"
        data-game-search-url="{{ route('perfil.jogos.buscar') }}"
        data-games-update-url="{{ route('perfil.jogos.update') }}"
        data-games-order-url="{{ route('perfil.jogos.ordem.update') }}"
    >
        <div class="profile-top">
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
                    <strong>Meus Gêneros:</strong>

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

        <section class="profile-section profile-feed">
            <div class="profile-section-title">
                <h2>Feed do Usuário</h2>
            </div>

            <div class="profile-feed-empty">
                <i class="bi bi-images"></i>
                <p>Nenhuma publicação ainda.</p>
            </div>
        </section>

        <section class="profile-section">
            <div class="profile-section-title">
                <h2>Meus Jogos</h2>

                @if ($user->jogos->isNotEmpty())
                    <button
                        type="button"
                        class="profile-view-all"
                        id="profileViewAllButton"
                        data-total-games="{{ $user->jogos->count() }}"
                        data-bs-toggle="modal"
                        data-bs-target="#todosJogosModal"
                        hidden
                    >
                        Ver todos ({{ $user->jogos->count() }})
                    </button>
                @endif
            </div>

            <div class="profile-games-row">
                @if ($user->jogos->isNotEmpty())
                    <div
                        class="profile-games-list"
                        id="profileGamesList"
                    >
                        @foreach ($user->jogos->take(4) as $jogo)
                            <div class="profile-game-card">
                                <button
                                    type="button"
                                    class="profile-game-detail"
                                    data-profile-game-detail
                                    data-game-id="{{ $jogo->id_jogo }}"
                                    data-name="{{ $jogo->nome }}"
                                    data-cover="{{ $jogo->capa }}"
                                    data-level="{{ $jogo->pivot->nivel_proficiencia ?? '' }}"
                                    data-update-url="{{ route('perfil.jogos.nivel.update', $jogo) }}"
                                >
                                    <div class="profile-game-cover">
                                        <img
                                            src="{{ $jogo->capa }}"
                                            alt="{{ $jogo->nome }}"
                                        >
                                    </div>

                                    <span>
                                        {{ $jogo->nome }}
                                    </span>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('perfil.jogos.remove', $jogo) }}"
                                    class="profile-game-remove-form"
                                    onsubmit="return confirm('Deseja remover este jogo do seu perfil?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="profile-game-remove"
                                        title="Remover jogo"
                                        aria-label="Remover {{ $jogo->nome }}"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <button
                    type="button"
                    class="profile-add-card profile-game-add"
                    data-bs-toggle="modal"
                    data-bs-target="#adicionarJogosModal"
                    title="Gerenciar jogos"
                >
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </section>

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

                        <span>{{ $plataforma->nome }}</span>
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

        <script
            type="application/json"
            id="profileGamesState"
        >@json($jogosPerfil)</script>
    </div>

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
                        <h2 class="modal-title">Meus Gêneros</h2>

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

                                    <span>{{ $genero->genero }}</span>
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

    <div
        class="modal fade"
        id="adicionarJogosModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content profile-games-modal">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title">Meus Jogos</h2>

                        <p class="profile-modal-subtitle">
                            Pesquise, adicione ou remova jogos do seu perfil.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body profile-game-manager-body">
                    <div class="profile-game-manager-top">
                        <input
                            type="text"
                            id="profileGameSearch"
                            class="form-control profile-game-search"
                            placeholder="Buscar jogo..."
                            autocomplete="off"
                        >

                        <span
                            class="profile-game-manager-count"
                            id="profileGameManagerCount"
                        ></span>
                    </div>

                    <div
                        class="profile-game-search-results"
                        id="profileGameSearchResults"
                    >
                        <div class="profile-game-search-empty">
                            Digite pelo menos 2 letras para pesquisar.
                        </div>
                    </div>

                    <div
                        class="profile-level-popup"
                        id="profileLevelPopup"
                        hidden
                    >
                        <div class="profile-level-popup-card">
                            <button
                                type="button"
                                class="profile-level-popup-close"
                                id="profileLevelPopupClose"
                                aria-label="Fechar"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>

                            <div class="profile-level-popup-game">
                                <img
                                    src=""
                                    alt=""
                                    id="profileLevelGameCover"
                                >

                                <div>
                                    <span>Nível em</span>
                                    <strong id="profileLevelGameName"></strong>
                                </div>
                            </div>

                            <div class="profile-level-popup-current">
                                <span>Seu nível</span>

                                <strong id="profileLevelName">
                                    Iniciante
                                </strong>
                            </div>

                            <div class="game-level-range-wrap">
                                <input
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="1"
                                    value="1"
                                    class="game-level-range"
                                    id="profileLevelRange"
                                >

                                <i class="bi bi-star-fill game-level-star"></i>
                            </div>

                            <div class="game-level-points">
                                <span>1</span>
                                <span>2</span>
                                <span>3</span>
                                <span>4</span>
                                <span>5</span>
                            </div>

                            <div class="profile-level-popup-actions">
                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    id="profileLevelRemove"
                                    hidden
                                >
                                    Remover jogo
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    id="profileLevelCancel"
                                >
                                    Cancelar
                                </button>

                                <button
                                    type="button"
                                    class="btn profile-games-save"
                                    id="profileLevelConfirm"
                                >
                                    Confirmar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer profile-game-manager-footer">
                    <span
                        class="profile-modal-status"
                        id="profileGamesSaveStatus"
                    ></span>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Fechar
                    </button>

                    <button
                        type="button"
                        class="btn profile-games-save"
                        id="profileGamesSave"
                    >
                        Salvar alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="modal fade"
        id="todosJogosModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content profile-games-modal">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title">Meus Jogos</h2>

                        <p class="profile-modal-subtitle">
                            Arraste os cards para escolher quais jogos aparecem primeiro no perfil.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body">
                    <div
                        class="profile-order-list"
                        id="profileOrderList"
                    >
                        @forelse ($user->jogos as $jogo)
                            <div
                                class="profile-order-item"
                                data-game-id="{{ $jogo->id_jogo }}"
                                data-remove-url="{{ route('perfil.jogos.remove', $jogo) }}"
                                draggable="true"
                            >
                                <div class="profile-order-cover">
                                    <button
                                        type="button"
                                        class="profile-order-game-button"
                                        data-profile-game-detail
                                        data-game-id="{{ $jogo->id_jogo }}"
                                        data-name="{{ $jogo->nome }}"
                                        data-cover="{{ $jogo->capa }}"
                                        data-level="{{ $jogo->pivot->nivel_proficiencia ?? '' }}"
                                        data-update-url="{{ route('perfil.jogos.nivel.update', $jogo) }}"
                                        title="Editar nível"
                                    >
                                        <img
                                            src="{{ $jogo->capa }}"
                                            alt="{{ $jogo->nome }}"
                                        >
                                    </button>

                                    <button
                                        type="button"
                                        class="profile-order-remove"
                                        data-order-remove
                                        title="Remover jogo"
                                        aria-label="Remover {{ $jogo->nome }}"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="profile-order-title"
                                    data-profile-game-detail
                                    data-game-id="{{ $jogo->id_jogo }}"
                                    data-name="{{ $jogo->nome }}"
                                    data-cover="{{ $jogo->capa }}"
                                    data-level="{{ $jogo->pivot->nivel_proficiencia ?? '' }}"
                                    data-update-url="{{ route('perfil.jogos.nivel.update', $jogo) }}"
                                    title="Editar nível"
                                >
                                    {{ $jogo->nome }}
                                </button>
                            </div>
                        @empty
                            <div class="profile-games-empty">
                                Você ainda não adicionou jogos.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="modal-footer">
                    <span
                        class="profile-modal-status"
                        id="profileOrderStatus"
                    ></span>

                    <button
                        type="button"
                        class="btn profile-games-save"
                        id="profileOrderSave"
                        @disabled($user->jogos->isEmpty())
                    >
                        Salvar ordem
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="modal fade"
        id="perfilJogoNivelModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content profile-games-modal">
                <div class="modal-header">
                    <h2 class="modal-title">
                        Nível de proficiência
                    </h2>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="profile-level-detail-game">
                        <img
                            src=""
                            alt=""
                            id="profileDetailGameCover"
                        >

                        <strong id="profileDetailGameName"></strong>
                    </div>

                    <div class="profile-level-edit-current">
                        <span>Seu nível</span>

                        <strong id="profileDetailLevelName">
                            Iniciante
                        </strong>
                    </div>

                    <div class="game-level-range-wrap">
                        <input
                            type="range"
                            min="1"
                            max="5"
                            step="1"
                            value="1"
                            class="game-level-range"
                            id="profileDetailLevelRange"
                        >

                        <i class="bi bi-star-fill game-level-star"></i>
                    </div>

                    <div class="game-level-points">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>

                    <div
                        class="profile-level-detail-error"
                        id="profileDetailLevelError"
                        hidden
                    ></div>
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
                        type="button"
                        class="btn profile-games-save"
                        id="profileDetailLevelSave"
                    >
                        Salvar nível
                    </button>
                </div>
            </div>
        </div>
    </div>

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

                                        <span>{{ $plataforma->nome }}</span>
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
@endsection
