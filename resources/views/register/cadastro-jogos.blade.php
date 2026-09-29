@extends('layouts.app')

@section('content')

    <main
        class="game-setup-page"
        id="gameSetupPage"
        data-csrf="{{ csrf_token() }}"
        data-select-url="{{ route('cadastro.jogos.selecionar') }}"
        data-limit="{{ $limiteJogos }}"
        data-selected-count="{{ count($selecionados) }}"
        data-level-descriptions='@json($descricoesNiveis)'
    >

        <div class="game-setup-container">

            <div class="game-setup-header">

                <h1>
                    Escolha seus jogos
                </h1>

                <p>
                    Escolha até 3 jogos para começar
                    e informe seu nível em cada um.
                </p>

            </div>


            @if ($errors->any())

                <div class="alert alert-danger">

                    @foreach ($errors->all() as $error)

                        <div>
                            {{ $error }}
                        </div>

                    @endforeach

                </div>

            @endif


            <div class="game-setup-toolbar">

                <form
                    method="GET"
                    action="{{ route('cadastro.jogos') }}"
                    class="game-setup-search"
                    id="gameSetupSearchForm"
                >

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="q"
                        id="gameSetupSearchInput"
                        value="{{ request('q') }}"
                        placeholder="Digite o nome de um jogo..."
                        autocomplete="off"
                    >

                </form>


                <span
                    class="game-setup-count"
                    id="gameSetupCount"
                >
                    {{ count($selecionados) }}
                    de
                    {{ $limiteJogos }}
                    jogos selecionados
                </span>

            </div>


            <div
                class="game-setup-search-loading"
                id="gameSetupSearchLoading"
                hidden
            >

                <div
                    class="
                        spinner-border
                        spinner-border-sm
                    "
                    role="status"
                    aria-hidden="true"
                ></div>

                <span>
                    Buscando jogos...
                </span>

            </div>


            <div
                id="gameSetupResults"
                aria-live="polite"
            >

                <div class="game-setup-grid">

                    @forelse ($jogos as $jogo)

                        @php
                            $nivelSelecionado =
                                $selecionados[
                                    $jogo->id_jogo
                                ]
                                ?? null;
                        @endphp


                        <button
                            type="button"
                            class="
                                game-setup-card
                                {{ $nivelSelecionado
                                    ? 'selected'
                                    : '' }}
                            "
                            data-game-card
                            data-id="{{ $jogo->id_jogo }}"
                            data-name="{{ $jogo->nome }}"
                            data-cover="{{ $jogo->capa }}"
                            data-selected="{{ $nivelSelecionado
                                ? '1'
                                : '0' }}"
                            data-level="{{ $nivelSelecionado ?? 1 }}"
                            data-remove-url="{{ route(
                                'cadastro.jogos.remover',
                                $jogo
                            ) }}"
                            data-bs-toggle="modal"
                            data-bs-target="#nivelJogoModal"
                        >

                            <div class="game-setup-cover">

                                <img
                                    src="{{ $jogo->capa }}"
                                    alt="{{ $jogo->nome }}"
                                >

                                <span class="game-setup-check">
                                    <i class="bi bi-check-lg"></i>
                                </span>

                            </div>


                            <div class="game-setup-card-info">

                                <strong>
                                    {{ $jogo->nome }}
                                </strong>

                                <span
                                    class="game-setup-level-badge"
                                    {{ $nivelSelecionado
                                        ? ''
                                        : 'hidden' }}
                                >
                                    {{ $nivelSelecionado
                                        ? $niveis[
                                            $nivelSelecionado
                                        ]
                                        : '' }}
                                </span>

                            </div>

                        </button>

                    @empty

                        <div class="game-setup-empty">

                            <i class="bi bi-search"></i>

                            <strong>
                                Nenhum jogo encontrado
                            </strong>

                            <span>
                                Tente pesquisar outro nome.
                            </span>

                        </div>

                    @endforelse

                </div>

                <x-paginacao :paginator="$jogos" />


            </div>


            <form
                method="POST"
                action="{{ route(
                    'cadastro.jogos.store'
                ) }}"
                class="game-setup-footer"
                id="gameSetupFinishForm"
            >

                @csrf

                <button
                    type="submit"
                    class="
                        btn
                        game-setup-continue
                    "
                >
                    Concluir

                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

        </div>

    </main>


    <div
        class="modal fade game-setup-guide-modal"
        id="gameSetupIntroModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-dialog-centered game-setup-guide-dialog">
            <div class="modal-content profile-games-modal game-setup-guide-content">
                <div class="modal-body game-setup-guide-body">

                    <div class="game-setup-guide-mascot">
                        @if (file_exists(public_path('images/astronauta-inicio.png')))
                            <img
                                src="{{ asset('images/astronauta-inicio.png') }}"
                                alt="Astronauta do Squad Maker"
                            >
                        @else
                            <i class="bi bi-rocket-takeoff"></i>
                        @endif
                    </div>

                    <h2>
                        Vamos montar seu perfil
                    </h2>

                    <p>
                        Escolha até 3 jogos que você costuma jogar e informe seu nível em cada um. Isso ajuda o Squad Maker a começar a encontrar jogadores com interesses e experiências parecidas com as suas.
                    </p>

                    <div class="game-setup-guide-note">
                        <i class="bi bi-info-circle"></i>

                        <span>
                            Fique tranquilo, você poderá adicionar mais jogos depois pelo seu perfil. Aqui vamos escolher apenas os primeiros para começar.
                        </span>
                    </div>

                    <button
                        type="button"
                        class="btn profile-games-save game-setup-guide-primary"
                        id="gameSetupIntroStart"
                        data-bs-dismiss="modal"
                    >
                        Começar
                    </button>

                </div>
            </div>
        </div>
    </div>


    <div
        class="modal fade"
        id="nivelJogoModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div
            class="
                modal-dialog
                modal-dialog-centered
            "
        >

            <div
                class="
                    modal-content
                    profile-games-modal
                "
            >

                <div class="modal-header">

                    <h2 class="modal-title">
                        Nível de proficiência
                    </h2>

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

                    <div class="profile-level-detail-game">

                        <img
                            src=""
                            alt=""
                            id="levelGameCover"
                        >

                        <strong
                            id="levelGameName"
                        ></strong>

                    </div>


                    <div class="profile-level-edit-current">
                        <strong id="levelGameNameValue">Iniciante</strong>
                    </div>

                    <p
                        class="game-level-selection-description"
                        id="levelGameDescription"
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
                            id="levelGameRange"
                        >

                        <i
                            class="
                                bi
                                bi-star-fill
                                game-level-star
                            "
                            aria-hidden="true"
                        ></i>

                    </div>


                    <div class="game-level-points">

                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>

                    </div>


                    <div
                        class="game-level-error"
                        id="levelGameError"
                        hidden
                    ></div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-danger"
                        id="removeGameButton"
                        hidden
                    >
                        Remover
                    </button>


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>


                    <button
                        type="button"
                        class="
                            btn
                            profile-games-save
                        "
                        id="saveGameLevelButton"
                    >
                        Confirmar
                    </button>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade game-setup-guide-modal"
        id="gameSetupCompleteModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-dialog-centered game-setup-guide-dialog">
            <div class="modal-content profile-games-modal game-setup-guide-content">
                <div class="modal-body game-setup-guide-body">

                    <div class="game-setup-guide-mascot game-setup-guide-mascot-ready">
                        @if (file_exists(public_path('images/astronauta-pronto.png')))
                            <img
                                src="{{ asset('images/astronauta-pronto.png') }}"
                                alt="Astronauta do Squad Maker"
                            >
                        @else
                            <i class="bi bi-stars"></i>
                        @endif
                    </div>

                    <h2>
                        Tudo pronto!
                    </h2>

                    <p>
                        Você escolheu seus 3 primeiros jogos. Seu perfil já tem uma base para começar.
                    </p>

                    <div class="game-setup-guide-note">
                        <i class="bi bi-controller"></i>

                        <span>
                            Depois você poderá adicionar, remover ou reorganizar outros jogos diretamente pelo seu perfil.
                        </span>
                    </div>

                    <div class="game-setup-guide-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Revisar escolhas
                        </button>

                        <button
                            type="button"
                            class="btn profile-games-save"
                            id="gameSetupCompleteFinish"
                        >
                            Concluir cadastro
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
