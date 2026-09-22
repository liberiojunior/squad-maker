@extends('layouts.internal')

@section('content')

    <div class="profile-page profile-public-page">

        <div class="profile-top">

            <div class="profile-avatar-area">

                <div
                    class="
                        profile-avatar-wrapper
                        profile-public-avatar-wrapper
                    "
                >
                    <img
                        src="{{ $user->avatar
                            ?: asset('images/icone.png') }}"
                        alt="{{ $user->nickname }}"
                        class="profile-avatar"
                    >

                    <span
                        class="
                            game-partner-presence
                            game-partner-presence-{{ $presenca['status'] }}
                            profile-public-presence
                        "
                        title="{{ $presenca['texto'] }}"
                        aria-label="{{ $presenca['texto'] }}"
                    ></span>
                </div>

            </div>


            <div class="profile-main-info">

                <section class="profile-info-card">

                    <h1 class="profile-public-name">
                        {{ $user->nickname }}
                    </h1>

                    <p class="profile-public-bio">
                        {{ $user->bio
                            ?: 'Este usuário ainda não adicionou uma bio.' }}
                    </p>

                </section>


                <section class="profile-genres-card">

                    <strong>
                        Gêneros Favoritos:
                    </strong>

                    <div class="profile-genre-list">

                        @forelse ($user->generos as $genero)

                            <span class="profile-genre-tag">
                                {{ $genero->genero }}
                            </span>

                        @empty

                            <span class="profile-empty-inline">
                                Nenhum gênero favorito.
                            </span>

                        @endforelse

                    </div>

                </section>

            </div>

        </div>


        <section class="profile-section profile-feed">

            <div class="profile-section-title">

                <h2>
                    Feed do Usuário
                </h2>

            </div>


            <div class="profile-feed-empty">

                <i class="bi bi-images"></i>

                <p>
                    Nenhuma publicação ainda.
                </p>

            </div>

        </section>


        <section class="profile-section">

            <div class="profile-section-title">

                <h2>
                    Jogos
                </h2>

                @if ($user->jogos->count() > 4)

                    <button
                        type="button"
                        class="profile-view-all"
                        data-bs-toggle="modal"
                        data-bs-target="#publicGamesModal"
                    >
                        Ver todos
                        ({{ $user->jogos->count() }})
                    </button>

                @endif

            </div>


            <div class="profile-games-row">

                @if ($user->jogos->isNotEmpty())

                    <div class="profile-games-list">

                        @foreach (
                            $user->jogos->take(4)
                            as $jogo
                        )

                            <div
                                class="
                                    profile-game-card
                                    profile-public-game-card
                                "
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

                                <small
                                    class="
                                        profile-public-game-level
                                    "
                                >
                                    <i class="bi bi-star-fill"></i>

                                    {{ $niveis[
                                        $jogo->pivot
                                            ->nivel_proficiencia
                                    ] ?? 'Não definido' }}
                                </small>

                            </div>

                        @endforeach

                    </div>

                @else

                    <span class="profile-empty-inline">
                        Nenhum jogo adicionado.
                    </span>

                @endif

            </div>

        </section>


        <section class="profile-section">

            <div class="profile-section-title">

                <h2>
                    Plataformas
                </h2>

            </div>


            <div class="profile-platforms-list">

                @forelse (
                    $user->plataformas
                    as $plataforma
                )

                    <div class="profile-platform-card">

                        <img
                            src="{{ $plataforma->icone }}"
                            alt="{{ $plataforma->nome }}"
                        >

                        <span>
                            {{ $plataforma->nome }}
                        </span>

                    </div>

                @empty

                    <span class="profile-empty-inline">
                        Nenhuma plataforma configurada.
                    </span>

                @endforelse

            </div>

        </section>

    </div>


    @if ($user->jogos->count() > 4)

        <div
            class="modal fade"
            id="publicGamesModal"
            tabindex="-1"
            aria-hidden="true"
        >

            <div
                class="
                    modal-dialog
                    modal-dialog-centered
                    modal-xl
                "
            >

                <div
                    class="
                        modal-content
                        profile-games-modal
                    "
                >

                    <div class="modal-header">

                        <div>

                            <h2 class="modal-title">
                                Jogos de
                                {{ $user->nickname }}
                            </h2>

                            <p class="profile-modal-subtitle">
                                Jogos adicionados ao perfil.
                            </p>

                        </div>


                        <button
                            type="button"
                            class="
                                btn-close
                                btn-close-white
                            "
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>

                    </div>


                    <div class="modal-body">

                        <div class="profile-public-games-grid">

                            @foreach (
                                $user->jogos
                                as $jogo
                            )

                                <div
                                    class="
                                        profile-game-card
                                        profile-public-game-card
                                    "
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

                                    <small
                                        class="
                                            profile-public-game-level
                                        "
                                    >
                                        <i class="bi bi-star-fill"></i>

                                        {{ $niveis[
                                            $jogo->pivot
                                                ->nivel_proficiencia
                                        ] ?? 'Não definido' }}
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
