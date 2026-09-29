@extends('layouts.internal')

@section('content')

    @php
        $ordemAtual = request(
            'ordem',
            'popularidade'
        );

        $generosAtivos =
            $generosSelecionados ?? [];

        $modosAtivos =
            $modosSelecionados ?? [];

        $quantidadeFiltros =
            count($generosAtivos)
            + count($modosAtivos)
            + (
                $ordemAtual !== 'popularidade'
                    ? 1
                    : 0
            );

        $filtrosAbertos =
            $quantidadeFiltros > 0;
    @endphp


    <div
        class="game-search-page"
        id="gameSearchPage"
        data-csrf="{{ csrf_token() }}"
        data-level-descriptions='@json($descricoesNiveis)'
    >

        <form
            method="GET"
            action="{{ route('jogos.buscar') }}"
            class="
                catalog-search-form
                game-search-form
            "
            id="gameSearchForm"
        >

            <div class="catalog-search-toolbar">

                <div
                    class="
                        catalog-search-bar
                        game-search-bar
                    "
                >

                    <button
                        type="submit"
                        class="game-search-submit"
                        title="Buscar"
                        aria-label="Buscar"
                    >
                        <i class="bi bi-search"></i>
                    </button>


                    <input
                        type="text"
                        name="q"
                        id="gameSearchInput"
                        value="{{ request('q') }}"
                        placeholder="Digite o nome de um jogo..."
                        autocomplete="off"
                        data-live-search-input
                    >

                </div>


                <button
                    type="button"
                    class="
                        catalog-filter-toggle
                        {{ $filtrosAbertos
                            ? 'active'
                            : '' }}
                    "
                    data-bs-toggle="collapse"
                    data-bs-target="#gameSearchFilters"
                    aria-expanded="{{ $filtrosAbertos
                        ? 'true'
                        : 'false' }}"
                    aria-controls="gameSearchFilters"
                >

                    <i class="bi bi-sliders"></i>

                    <span>
                        Filtros
                    </span>


                    @if ($quantidadeFiltros > 0)

                        <span class="catalog-filter-count">
                            {{ $quantidadeFiltros }}
                        </span>

                    @endif

                </button>

            </div>


            <div
                class="
                    collapse
                    {{ $filtrosAbertos
                        ? 'show'
                        : '' }}
                "
                id="gameSearchFilters"
            >

                <div class="catalog-filter-panel">


                    {{-- Ordenação --}}

                    <div class="catalog-filter-group">

                        <div class="catalog-filter-title">

                            <i class="bi bi-sort-down"></i>

                            <span>
                                Ordenar por
                            </span>

                        </div>


                        <div class="catalog-filter-options">

                            <label class="catalog-filter-chip">

                                <input
                                    type="radio"
                                    name="ordem"
                                    value="popularidade"
                                    @checked(
                                        $ordemAtual
                                        === 'popularidade'
                                    )
                                >

                                <span>
                                    Populares
                                </span>

                            </label>


                            <label class="catalog-filter-chip">

                                <input
                                    type="radio"
                                    name="ordem"
                                    value="az"
                                    @checked(
                                        $ordemAtual === 'az'
                                    )
                                >

                                <span>
                                    Nome A-Z
                                </span>

                            </label>


                            <label class="catalog-filter-chip">

                                <input
                                    type="radio"
                                    name="ordem"
                                    value="za"
                                    @checked(
                                        $ordemAtual === 'za'
                                    )
                                >

                                <span>
                                    Nome Z-A
                                </span>

                            </label>

                        </div>

                    </div>


                    {{-- Gêneros --}}

                    <div class="catalog-filter-group">

                        <div class="catalog-filter-title">

                            <i class="bi bi-tags"></i>

                            <span>
                                Gêneros
                            </span>

                        </div>


                        <div
                            class="
                                catalog-filter-options
                                catalog-filter-options-scroll
                            "
                        >

                            @forelse ($generos as $genero)

                                <label class="catalog-filter-chip">

                                    <input
                                        type="checkbox"
                                        name="generos[]"
                                        value="{{ $genero->id_genero }}"
                                        @checked(
                                            in_array(
                                                (int) $genero->id_genero,
                                                $generosAtivos,
                                                true
                                            )
                                        )
                                    >

                                    <span>
                                        {{ $genero->genero }}
                                    </span>

                                </label>

                            @empty

                                <span class="catalog-filter-empty">
                                    Nenhum gênero disponível.
                                </span>

                            @endforelse

                        </div>

                    </div>


                    {{-- Modos de jogo --}}

                    <div class="catalog-filter-group">

                        <div class="catalog-filter-title">

                            <i class="bi bi-controller"></i>

                            <span>
                                Modos de jogo
                            </span>

                        </div>


                        <div
                            class="
                                catalog-filter-options
                                catalog-filter-options-scroll
                            "
                        >

                            @forelse ($modos as $modo)

                                <label class="catalog-filter-chip">

                                    <input
                                        type="checkbox"
                                        name="modos[]"
                                        value="{{ $modo->id_modo_jogo }}"
                                        @checked(
                                            in_array(
                                                (int) $modo->id_modo_jogo,
                                                $modosAtivos,
                                                true
                                            )
                                        )
                                    >

                                    <span>
                                        {{ $modo->nome }}
                                    </span>

                                </label>

                            @empty

                                <span class="catalog-filter-empty">
                                    Nenhum modo disponível.
                                </span>

                            @endforelse

                        </div>

                    </div>


                    <div class="catalog-filter-actions">

                        <a
                            href="{{ route(
                                'jogos.buscar',
                                request('q')
                                    ? [
                                        'q' => request('q'),
                                    ]
                                    : []
                            ) }}"
                            class="catalog-filter-clear"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>

                            Limpar filtros
                        </a>


                        <button
                            type="submit"
                            class="catalog-filter-apply"
                        >
                            <i class="bi bi-check2"></i>

                            Aplicar filtros
                        </button>

                    </div>

                </div>

            </div>

        </form>


        <div
            class="catalog-search-loading"
            id="gameSearchLoading"
            hidden
        >
            <div
                class="spinner-border spinner-border-sm"
                role="status"
                aria-hidden="true"
            ></div>

            <span>
                Buscando jogos...
            </span>
        </div>


        <div
            id="gameSearchResults"
            class="catalog-search-results"
            aria-live="polite"
        >

            <div class="game-search-panel">

                <div class="game-search-grid">

                    @forelse ($jogos as $jogo)

                        @php
                            $jogoPerfil = $jogosUsuario->get(
                                $jogo->id_jogo
                            );

                            $estaNoPerfil =
                                $jogoPerfil !== null;

                            $nivelPerfil =
                                $estaNoPerfil
                                && $jogoPerfil
                                    ->pivot
                                    ->nivel_proficiencia
                                    ? (int)$jogoPerfil
                                        ->pivot
                                        ->nivel_proficiencia
                                    : 1;
                        @endphp


                        <article class="game-search-card">

                            <a
                                href="{{ route(
                                    'jogos.show',
                                    $jogo
                                ) }}"
                                class="game-search-card-link"
                            >

                                <div class="game-search-cover-box">

                                    <img
                                        src="{{ $jogo->capa }}"
                                        alt="{{ $jogo->nome }}"
                                        class="game-search-cover"
                                    >

                                </div>


                                <div class="game-search-content">

                                    <span class="game-search-name">
                                        {{ $jogo->nome }}
                                    </span>


                                    <div class="game-search-online">

                                        @if (
                                            $jogo->steam_app_id
                                            && $jogo->jogadores_online
                                                !== null
                                        )

                                            <span
                                                class="game-online-dot"
                                            ></span>

                                            <span>
                                                {{ number_format(
                                                    $jogo->jogadores_online,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}

                                                na Steam
                                            </span>

                                        @elseif ($jogo->steam_app_id)

                                            <span
                                                class="game-online-unavailable"
                                            >
                                                Jogadores Steam indisponíveis
                                            </span>

                                        @else

                                            <span
                                                class="game-online-unavailable"
                                            >
                                                Jogo externo à Steam
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </a>


                            <button
                                type="button"
                                class="
                                    game-search-profile-action
                                    {{ $estaNoPerfil
                                        ? 'selected'
                                        : '' }}
                                "
                                data-game-profile-action
                                data-game-id="{{ $jogo->id_jogo }}"
                                data-name="{{ $jogo->nome }}"
                                data-cover="{{ $jogo->capa }}"
                                data-level="{{ $nivelPerfil }}"
                                data-selected="{{ $estaNoPerfil
                                    ? '1'
                                    : '0' }}"
                                data-add-url="{{ route(
                                    'perfil.jogos.add',
                                    $jogo
                                ) }}"
                                data-update-url="{{ route(
                                    'perfil.jogos.nivel.update',
                                    $jogo
                                ) }}"
                                title="{{ $estaNoPerfil
                                    ? 'Editar nível em Meus Jogos'
                                    : 'Adicionar aos Meus Jogos' }}"
                                aria-label="{{ $estaNoPerfil
                                    ? 'Editar nível de ' . $jogo->nome
                                    : 'Adicionar ' . $jogo->nome . ' aos Meus Jogos' }}"
                            >
                                <i
                                    class="bi {{ $estaNoPerfil
                                        ? 'bi-check-lg'
                                        : 'bi-plus-lg' }}"
                                ></i>
                            </button>

                        </article>

                    @empty

                        <div class="game-search-empty">

                            <i class="bi bi-search"></i>

                            <strong>
                                Nenhum jogo encontrado
                            </strong>

                            <span>
                                Tente alterar a pesquisa
                                ou remover algum filtro.
                            </span>

                        </div>

                    @endforelse

                </div>

                <x-paginacao :paginator="$jogos" />

            </div>

        </div>

    <div
        class="modal fade"
        id="gameSearchLevelModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content profile-games-modal">

                <div class="modal-header">
                    <h2
                        class="modal-title"
                        id="gameSearchLevelTitle"
                    >
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
                            id="gameSearchLevelCover"
                        >

                        <strong id="gameSearchLevelGame"></strong>
                    </div>

                    <div class="profile-level-edit-current">
                        <strong id="gameSearchLevelName">
                            Iniciante
                        </strong>
                    </div>

                    <p
                        class="game-level-selection-description"
                        id="gameSearchLevelDescription"
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
                            id="gameSearchLevelRange"
                        >

                        <i
                            class="bi bi-star-fill game-level-star"
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
                        id="gameSearchLevelError"
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
                        id="gameSearchLevelSave"
                    >
                        Adicionar
                    </button>
                </div>

            </div>
        </div>
    </div>

    </div>

@endsection
