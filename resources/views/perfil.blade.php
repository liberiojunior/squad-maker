@extends('layouts.internal')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

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
        data-level-descriptions='@json($descricoesNiveis)'
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
                            Ajustar foto
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

                        <div class="profile-bio-area">
                            <textarea
                                name="bio"
                                id="bioInput"
                                class="profile-bio"
                                maxlength="400"
                                readonly
                                placeholder="Você ainda não adicionou uma bio."
                            >{{ old('bio', $user->bio) }}</textarea>

                            <div class="profile-bio-footer">
                                <button
                                    type="button"
                                    class="profile-bio-toggle"
                                    id="bioToggle"
                                    data-bs-toggle="modal"
                                    data-bs-target="#bioModal"
                                    hidden
                                >
                                    Ver mais
                                </button>
                                <span class="profile-bio-counter" id="bioCounter" hidden>0 / 400</span>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="profile-genres-card">
                    <strong>Gêneros Favoritos:</strong>

                    <div class="profile-genre-list">
                        @forelse ($user->generos->take(5) as $genero)
                            <span class="profile-genre-tag">
                                {{ $genero->genero }}
                            </span>
                        @empty
                            <span class="profile-empty-inline">
                                Nenhum gênero favorito.
                            </span>
                        @endforelse
                    </div>

                    <button
                        type="button"
                        class="profile-small-add"
                        data-bs-toggle="modal"
                        data-bs-target="#generosModal"
                        title="Editar gêneros favoritos"
                    >
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </section>
            </div>
        </div>

        <section class="profile-section profile-games-summary">
            <div class="profile-section-title">
                <h2>Meus Jogos</h2>

                <button
                    type="button"
                    class="profile-view-all"
                    data-bs-toggle="modal"
                    data-bs-target="#todosJogosModal"
                >
                    {{ $user->jogos->isNotEmpty() ? 'Ver todos (' . $user->jogos->count() . ')' : 'Gerenciar jogos' }}
                </button>
            </div>

            @if ($user->jogos->isNotEmpty())
                <div class="profile-games-list profile-games-summary-list">
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
                                    <img src="{{ $jogo->capa }}" alt="{{ $jogo->nome }}">
                                </div>

                                <span>{{ $jogo->nome }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <span class="profile-empty-inline">Nenhum jogo adicionado.</span>
            @endif
        </section>

        <div class="profile-lower-grid">
            <section class="profile-section profile-compact-section">
                <div class="profile-section-title profile-platform-section-title">
                    <h2>Plataformas</h2>

                    @if ($user->plataformas->count() > 5)
                        <button
                            type="button"
                            class="profile-view-all"
                            data-bs-toggle="modal"
                            data-bs-target="#plataformasModal"
                        >
                            Ver todas ({{ $user->plataformas->count() }})
                        </button>
                    @endif
                </div>

                <div class="profile-platform-summary-wrap">
                    <div class="profile-platform-summary">
                        @foreach ($user->plataformas->take(5) as $plataforma)
                            <div class="profile-platform-card">
                                <img src="{{ $plataforma->icone }}" alt="{{ $plataforma->nome }}">
                                <span>{{ $plataforma->nome }}</span>
                            </div>
                        @endforeach

                        <button
                            type="button"
                            class="profile-add-card profile-summary-platform-add"
                            data-bs-toggle="modal"
                            data-bs-target="#plataformasModal"
                            title="Adicionar plataforma"
                            aria-label="Adicionar plataforma"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </section>

            <section class="profile-section profile-compact-section">
                <div class="profile-section-title">
                    <h2>Publicações</h2>

                    <a href="{{ route('posts.index', $user) }}" class="profile-view-all">
                        Ver todas ({{ $totalPosts }})
                    </a>
                </div>

                <div class="profile-post-preview-grid profile-post-preview-owner">
                    @foreach ($posts as $post)
                        @include('posts.card', [
                            'post' => $post,
                            'user' => $user,
                            'isOwner' => true,
                            'podeReagir' => true,
                            'postsReagidos' => $postsReagidos,
                        ])
                    @endforeach

                </div>
            </section>
        </div>

        <script
            type="application/json"
            id="profileGamesState"
        >@json($jogosPerfil)</script>
    </div>

    @include('posts.create-modal')

    <div
        class="modal fade"
        id="generosModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered profile-genres-dialog">
            <div class="modal-content squad-modal profile-genres-modal">
                <form
                    method="POST"
                    action="{{ route('perfil.generos.update') }}"
                    id="profileGenresForm"
                    data-max-genres="5"
                >
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Gêneros Favoritos</h2>

                            <p class="profile-modal-subtitle">
                                Escolha até 5 gêneros que mais combinam com você.
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
                        <section class="profile-selected-genres-block">
                            <div class="profile-selected-genres-heading">
                                <strong>Selecionados</strong>
                                <span
                                    class="profile-genre-selected-count"
                                    id="profileGenreSelectedCount"
                                ></span>
                            </div>

                            <div
                                class="profile-selected-genres squad-scrollbar"
                                id="profileSelectedGenres"
                            ></div>
                        </section>

                        <div class="profile-genre-all-heading">
                            Todos os gêneros
                        </div>

                        <div
                            class="profile-genre-options squad-scrollbar"
                            id="profileGenreOptions"
                        >
                            @foreach ($generos as $genero)
                                <label
                                    class="profile-genre-option"
                                    data-genre-name="{{ $genero->genero }}"
                                >
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
            <div class="modal-content squad-modal profile-games-modal">
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
                        class="profile-game-search-results squad-scrollbar"
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
                                <strong id="profileLevelName">Iniciante</strong>
                            </div>

                            <p
                                class="game-level-selection-description"
                                id="profileLevelDescription"
                            >
                                {{ $descricoesNiveis[1] }}
                            </p>

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
            <div class="modal-content squad-modal profile-games-modal">
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
                        class="profile-order-list squad-scrollbar"
                        id="profileOrderList"
                    >
                        @foreach ($user->jogos as $jogo)
                            <div
                                class="profile-order-item"
                                data-game-id="{{ $jogo->id_jogo }}"
                                data-game-name="{{ $jogo->nome }}"
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
                                        <img src="{{ $jogo->capa }}" alt="{{ $jogo->nome }}">
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
                        @endforeach

                        <button
                            type="button"
                            class="profile-add-card profile-order-add"
                            data-bs-toggle="modal"
                            data-bs-target="#adicionarJogosModal"
                            title="Adicionar jogos"
                            aria-label="Adicionar jogos"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>
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
            <div class="modal-content squad-modal profile-games-modal">
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
                        <strong id="profileDetailLevelName">Iniciante</strong>
                    </div>

                    <p
                        class="game-level-selection-description"
                        id="profileDetailLevelDescription"
                    >
                        {{ $descricoesNiveis[1] }}
                    </p>

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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content squad-modal profile-games-modal">
                <form
                    method="POST"
                    action="{{ route('perfil.plataformas.update') }}"
                    id="profilePlatformsForm"
                >
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Minhas Plataformas</h2>
                            <p class="profile-modal-subtitle">
                                Veja suas plataformas e altere quando precisar.
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
                        <div class="profile-platform-order-header">
                            <div>
                                <strong>Ordem no perfil</strong>
                                <small>
                                    Arraste as plataformas para definir a ordem em que aparecem.
                                </small>
                            </div>

                            <span
                                class="profile-platform-order-status"
                                id="profilePlatformOrderStatus"
                                aria-live="polite"
                            ></span>
                        </div>

                        <div
                            class="profile-platform-manager-list"
                            id="profilePlatformOrderList"
                        >
                            @forelse ($user->plataformas as $plataforma)
                                <div
                                    class="profile-platform-card profile-platform-order-item"
                                    draggable="true"
                                    data-platform-id="{{ $plataforma->id_plataforma }}"
                                >
                                    <input
                                        type="hidden"
                                        name="ordem_plataformas[]"
                                        value="{{ $plataforma->id_plataforma }}"
                                    >

                                    <span
                                        class="profile-platform-order-handle"
                                        aria-hidden="true"
                                    >
                                        <i class="bi bi-grip-vertical"></i>
                                    </span>

                                    <img
                                        src="{{ $plataforma->icone }}"
                                        alt="{{ $plataforma->nome }}"
                                    >
                                    <span>{{ $plataforma->nome }}</span>
                                </div>
                            @empty
                                <div class="profile-games-empty">
                                    Nenhuma plataforma adicionada.
                                </div>
                            @endforelse
                        </div>

                        <button
                            type="button"
                            class="profile-add-card profile-platform-add profile-platform-manager-add"
                            data-bs-toggle="collapse"
                            data-bs-target="#profilePlatformOptionsCollapse"
                            aria-expanded="false"
                            aria-controls="profilePlatformOptionsCollapse"
                            title="Adicionar ou remover plataformas"
                            aria-label="Adicionar ou remover plataformas"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>

                        <div class="collapse profile-platform-options-collapse" id="profilePlatformOptionsCollapse">
                            <div class="profile-platform-options-heading">
                                Adicionar ou remover plataformas
                            </div>

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
                                            <img src="{{ $plataforma->icone }}" alt="{{ $plataforma->nome }}">
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
                            Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div
        class="profile-confirm-overlay"
        id="profileRemoveGameConfirm"
        hidden
    >
        <div
            class="profile-confirm-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="profileRemoveGameTitle"
        >
            <div class="profile-confirm-icon">
                <i class="bi bi-trash3"></i>
            </div>

            <h2 id="profileRemoveGameTitle">
                Remover jogo?
            </h2>

            <p>
                <strong id="profileRemoveGameName"></strong>
                será removido de Meus Jogos. Você poderá adicioná-lo novamente depois.
            </p>

            <div
                class="profile-confirm-error"
                id="profileRemoveGameError"
                hidden
            ></div>

            <div class="profile-confirm-actions">
                <button
                    type="button"
                    class="btn btn-secondary"
                    id="profileRemoveGameCancel"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    id="profileRemoveGameAccept"
                >
                    Remover
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="avatarCropModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered avatar-crop-dialog">
            <div class="modal-content squad-modal profile-games-modal avatar-crop-modal">
                <div class="modal-body avatar-crop-body">
                    <div class="avatar-crop-stage">
                        <canvas id="avatarCropCanvas" width="512" height="512"
                                aria-label="Pré-visualização da foto"></canvas>
                    </div>

                    <div class="avatar-crop-zoom">
                        <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        <input type="range" id="avatarCropZoom" min="1" max="3" step="0.01" value="1"
                               aria-label="Zoom da foto">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    </div>

                    <p class="avatar-crop-hint">Arraste a foto dentro do círculo para escolher o enquadramento.</p>
                    <div class="avatar-crop-error" id="avatarCropError" hidden></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn profile-games-save" id="avatarCropSave">Salvar foto</button>
                </div>
            </div>
        </div>
    </div>
<div class="modal fade" id="bioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content squad-modal profile-bio-modal">
            <div class="modal-header">
                <h2 class="modal-title">Sobre mim</h2>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>
            </div>

            <div class="modal-body">
                <div class="profile-bio-modal-text squad-scrollbar">{{ old('bio', $user->bio) ?: 'Você ainda não adicionou uma bio.' }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
