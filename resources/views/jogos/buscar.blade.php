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

                        <a
                            href="{{ route(
                                'jogos.show',
                                $jogo
                            ) }}"
                            class="game-search-card"
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
                                            class="
                                                game-online-unavailable
                                            "
                                        >
                                            Jogadores Steam indisponíveis
                                        </span>

                                    @else

                                        <span
                                            class="
                                                game-online-unavailable
                                            "
                                        >
                                            Jogo externo à Steam
                                        </span>

                                    @endif

                                </div>

                            </div>

                        </a>

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

    </div>

@endsection
