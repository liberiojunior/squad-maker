@extends('layouts.internal')

@section('content')
    @if (session('success'))
        <div class="alert alert-success profile-public-feedback">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger profile-public-feedback">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="profile-page profile-public-page">
        @if ($contaSuspensa)
            <div class="profile-top">
                <div class="profile-avatar-area">
                    <div class="profile-avatar-wrapper profile-public-avatar-wrapper">
                        <img
                            src="{{ $user->avatar ?: asset('images/icone.png') }}"
                            alt="{{ $user->nickname }}"
                            class="profile-avatar"
                        >
                    </div>
                </div>

                <div class="profile-main-info">
                    <section class="profile-info-card">
                        <h1 class="profile-public-name">{{ $user->nickname }}</h1>

                        <div class="mt-4 p-3 d-flex align-items-start gap-3 border border-warning rounded">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>

                            <div>
                                <strong class="d-block mb-1 text-warning">
                                    Conta temporariamente suspensa
                                </strong>

                                <span>
                                    Esta conta está temporariamente suspensa. Não é possível interagir com este usuário no momento.
                                </span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        @else
            <div class="profile-top">
                <div class="profile-avatar-area">
                    <div class="profile-avatar-wrapper profile-public-avatar-wrapper">
                        <img
                            src="{{ $user->avatar ?: asset('images/icone.png') }}"
                            alt="{{ $user->nickname }}"
                            class="profile-avatar"
                        >

                        <span
                            class="game-partner-presence game-partner-presence-{{ $presenca['status'] }} profile-public-presence"
                            title="{{ $presenca['texto'] }}"
                            aria-label="{{ $presenca['texto'] }}"
                        ></span>
                    </div>
                </div>

                <div class="profile-main-info">
                    <section class="profile-info-card profile-public-info-card">
                        <div class="profile-social-actions" aria-label="Ações de amizade">
                            @if ($estadoAmizade === 'nenhuma')
                                <form method="POST" action="{{ route('amizades.enviar', $user) }}">
                                    @csrf

                                    <button type="submit" class="profile-social-action" title="Adicionar amigo"
                                            aria-label="Adicionar amigo">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </button>
                                </form>
                            @elseif ($estadoAmizade === 'enviada')
                                <span class="profile-social-action profile-social-status" title="Solicitação enviada"
                                      aria-label="Solicitação enviada">
                                    <i class="bi bi-clock-fill"></i>
                                </span>

                                <form method="POST" action="{{ route('amizades.cancelar', $amizade) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="profile-social-action profile-social-action-danger"
                                            title="Cancelar solicitação" aria-label="Cancelar solicitação">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @elseif ($estadoAmizade === 'recebida')
                                <form method="POST" action="{{ route('amizades.aceitar', $amizade) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="profile-social-action profile-social-action-success"
                                            title="Aceitar solicitação" aria-label="Aceitar solicitação">
                                        <i class="bi bi-person-check-fill"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('amizades.recusar', $amizade) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="profile-social-action profile-social-action-danger"
                                            title="Recusar solicitação" aria-label="Recusar solicitação">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @elseif ($estadoAmizade === 'amigos')
                                <form method="POST" action="{{ route('conversas.iniciar', $user) }}">
                                    @csrf

                                    <button type="submit" class="profile-social-action profile-social-action-chat"
                                            title="Conversar" aria-label="Conversar">
                                        <i class="bi bi-chat-dots-fill"></i>
                                    </button>
                                </form>

                                <span class="profile-social-action profile-social-status profile-social-status-friends"
                                      title="Amigos" aria-label="Amigos">
                                    <i class="bi bi-people-fill"></i>
                                </span>

                                <form method="POST" action="{{ route('amizades.remover', $amizade) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="profile-social-action profile-social-action-danger"
                                            title="Remover amizade" aria-label="Remover amizade">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endif
                        </div>

                        <h1 class="profile-public-name">{{ $user->nickname }}</h1>

                        <div class="profile-public-bio-area">
                            <p class="profile-public-bio"
                               id="publicBio">{{ $user->bio ?: 'Este usuário ainda não adicionou uma bio.' }}</p>

                            <button
                                type="button"
                                class="profile-bio-toggle"
                                id="publicBioToggle"
                                data-bs-toggle="modal"
                                data-bs-target="#publicBioModal"
                                hidden
                            >
                                Ver mais
                            </button>
                        </div>
                    </section>

                    <section class="profile-genres-card">
                        <strong>Gêneros Favoritos:</strong>

                        <div class="profile-genre-list">
                            @forelse ($user->generos as $genero)
                                <span class="profile-genre-tag">{{ $genero->genero }}</span>
                            @empty
                                <span class="profile-empty-inline">Nenhum gênero favorito.</span>
                            @endforelse
                        </div>
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
                    <h2>Jogos</h2>

                    @if ($user->jogos->count() > 4)
                        <button
                            type="button"
                            class="profile-view-all"
                            data-bs-toggle="modal"
                            data-bs-target="#publicGamesModal"
                        >
                            Ver todos ({{ $user->jogos->count() }})
                        </button>
                    @endif
                </div>

                <div class="profile-games-row">
                    @if ($user->jogos->isNotEmpty())
                        <div class="profile-games-list">
                            @foreach ($user->jogos->take(4) as $jogo)
                                <div class="profile-game-card profile-public-game-card">
                                    <div class="profile-game-cover">
                                        <img src="{{ $jogo->capa }}" alt="{{ $jogo->nome }}">
                                    </div>

                                    <span>{{ $jogo->nome }}</span>

                                    <small class="profile-public-game-level">
                                        <i class="bi bi-star-fill"></i>
                                        {{ $niveis[$jogo->pivot->nivel_proficiencia] ?? 'Não definido' }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span class="profile-empty-inline">Nenhum jogo adicionado.</span>
                    @endif
                </div>
            </section>

            <section class="profile-section">
                <div class="profile-section-title">
                    <h2>Plataformas</h2>
                </div>

                <div class="profile-platforms-list">
                    @forelse ($user->plataformas as $plataforma)
                        <div class="profile-platform-card">
                            <img src="{{ $plataforma->icone }}" alt="{{ $plataforma->nome }}">
                            <span>{{ $plataforma->nome }}</span>
                        </div>
                    @empty
                        <span class="profile-empty-inline">Nenhuma plataforma configurada.</span>
                    @endforelse
                </div>
            </section>
        @endif
    </div>

    @if (! $contaSuspensa)
        <div class="modal fade" id="publicBioModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content profile-bio-modal">
                    <div class="modal-header">
                        <h2 class="modal-title">Sobre {{ $user->nickname }}</h2>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <div class="profile-bio-modal-text">{{ $user->bio ?: 'Este usuário ainda não adicionou uma bio.' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (! $contaSuspensa && $user->jogos->count() > 4)
        <div class="modal fade" id="publicGamesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content profile-games-modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Jogos de {{ $user->nickname }}</h2>
                            <p class="profile-modal-subtitle">Jogos adicionados ao perfil.</p>
                        </div>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <div class="profile-public-games-grid">
                            @foreach ($user->jogos as $jogo)
                                <div class="profile-game-card profile-public-game-card">
                                    <div class="profile-game-cover">
                                        <img src="{{ $jogo->capa }}" alt="{{ $jogo->nome }}">
                                    </div>

                                    <span>{{ $jogo->nome }}</span>

                                    <small class="profile-public-game-level">
                                        <i class="bi bi-star-fill"></i>
                                        {{ $niveis[$jogo->pivot->nivel_proficiencia] ?? 'Não definido' }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
