@extends('layouts.internal')

@section('content')

    <div class="game-search-page">

        <form
            method="GET"
            action="{{ route('jogos.buscar') }}"
            class="game-search-form"
        >

            <div class="game-search-controls">

                <div class="game-search-bar">

                    <button
                        type="submit"
                        class="game-search-submit"
                        title="Buscar"
                    >
                        <i class="bi bi-search"></i>
                    </button>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Digite o nome de um jogo..."
                        autocomplete="off"
                    >

                </div>

                <select
                    name="ordem"
                    class="form-select game-sort-select"
                    onchange="this.form.submit()"
                >

                    <option
                        value="popularidade"
                        @selected(
                            request('ordem', 'popularidade')
                            === 'popularidade'
                        )
                    >
                        Populares
                    </option>

                    <option
                        value="az"
                        @selected(request('ordem') === 'az')
                    >
                        Nome A-Z
                    </option>

                    <option
                        value="za"
                        @selected(request('ordem') === 'za')
                    >
                        Nome Z-A
                    </option>

                </select>

            </div>

        </form>


        <div class="game-search-panel">

            <div class="game-search-grid">

                @forelse ($jogos as $jogo)

                    <article class="game-search-card">

                        <img
                            src="{{ $jogo->capa }}"
                            alt="{{ $jogo->nome }}"
                            class="game-search-cover"
                        >

                        <span class="game-search-name">
                        {{ $jogo->nome }}
                    </span>

                        <div class="game-search-online">

                            @if (
                                $jogo->steam_app_id
                                && $jogo->jogadores_online !== null
                            )

                                <span class="game-online-dot"></span>

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

                                <span class="game-online-unavailable">
                                Jogadores Steam indisponíveis
                            </span>

                            @else

                                <span class="game-online-unavailable">
                                Jogo externo à Steam
                            </span>

                            @endif

                        </div>

                    </article>

                @empty

                    <div class="game-search-empty">
                        Nenhum jogo encontrado.
                    </div>

                @endforelse

            </div>


            @if ($jogos->hasPages())

                <div class="game-pagination">

                    {{ $jogos->links('pagination::bootstrap-5') }}

                </div>

            @endif

        </div>

    </div>

@endsection
