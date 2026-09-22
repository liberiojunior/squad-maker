@extends('layouts.internal')

@section('content')
    <div class="game-detail-page">

        <a
            href="{{ route('jogos.buscar') }}"
            class="game-detail-back"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>


        <section class="game-detail-header">

            <div class="game-detail-cover">
                <img
                    src="{{ $jogo->capa }}"
                    alt="{{ $jogo->nome }}"
                >
            </div>


            <div class="game-detail-main">

                <h1>
                    {{ $jogo->nome }}
                </h1>


                @if ($jogo->generos->isNotEmpty())
                    <div class="game-detail-genres">
                        {{ $jogo->generos
                            ->pluck('genero')
                            ->implode(', ') }}
                    </div>
                @endif


                @if ($jogo->modos->isNotEmpty())
                    <div class="game-detail-modes">

                        <i class="bi bi-controller"></i>

                        <span>
                            {{ $jogo->modos
                                ->pluck('nome')
                                ->implode(' • ') }}
                        </span>

                    </div>
                @endif


                @if ($jogo->descricao)
                    <p class="game-detail-description">
                        {{ $jogo->descricao }}
                    </p>
                @endif

            </div>

        </section>


        <section class="game-level-panel">

            <div class="game-level-heading">

                <h2>
                    Nível de proficiência
                </h2>

                <span>
                    Escolha um nível para filtrar
                </span>

            </div>


            <div class="game-level-filters">

                @foreach ($niveis as $valor => $nivel)

                    @php
                        $selecionado =
                            $nivelFiltro === $valor;

                        $urlNivel = $selecionado
                            ? route(
                                'jogos.show',
                                $jogo
                            )
                            : route(
                                'jogos.show',
                                [
                                    'jogo' => $jogo,
                                    'nivel' => $valor,
                                ]
                            );
                    @endphp


                    <a
                        href="{{ $urlNivel }}"
                        class="
                            game-level-filter

                            {{ $selecionado
                                ? 'active'
                                : '' }}

                            {{ $nivelUsuario === $valor
                                ? 'user-level'
                                : '' }}
                        "
                        style="
                            --level-color:
                            {{ $nivel['cor'] }};
                        "
                    >

                        <div class="game-level-image">

                            @if (
                                file_exists(
                                    public_path(
                                        $nivel['icone']
                                    )
                                )
                            )

                                <img
                                    src="{{ asset(
                                        $nivel['icone']
                                    ) }}"
                                    alt="{{ $nivel['nome'] }}"
                                >

                            @else

                                <i class="bi bi-star-fill"></i>

                            @endif

                        </div>


                        <strong>
                            {{ $nivel['nome'] }}
                        </strong>

                        @if ($nivelUsuario === $valor)
                            <small class="game-level-user-label">
                                Seu nível
                            </small>
                        @endif

                        <div class="game-level-tooltip">
                            {{ $nivel['descricao'] }}
                        </div>

                    </a>

                @endforeach

            </div>

        </section>


        <section class="game-partners-section">

            <div class="game-partners-header">

                <h2>
                    Jogadores
                </h2>

                <span class="game-partners-count">

                    {{ $jogadores->total() }}

                    {{ $jogadores->total() === 1
                        ? 'jogador'
                        : 'jogadores' }}

                </span>

            </div>


            <div class="game-partners-grid">

                @forelse ($jogadores as $jogador)

                    <a
                        href="{{ route(
                            'usuarios.perfil',
                            $jogador
                        ) }}"
                        class="
                            game-partner-card
                            game-partner-card-link
                        "
                    >

                        <div class="game-partner-avatar-wrap">

                            @if ($jogador->avatar)

                                <img
                                    src="{{ $jogador->avatar }}"
                                    alt="{{ $jogador->nickname }}"
                                    class="game-partner-avatar"
                                >

                            @else

                                <div
                                    class="
                                        game-partner-avatar
                                        game-partner-avatar-placeholder
                                    "
                                >
                                    <i class="bi bi-person-fill"></i>
                                </div>

                            @endif


                            <span
                                class="
                                    game-partner-presence
                                    game-partner-presence-{{ $jogador->status_presenca }}
                                "
                                title="{{ $jogador->status_texto }}"
                                aria-label="{{ $jogador->status_texto }}"
                            ></span>

                        </div>


                        <strong class="game-partner-name">
                            {{ $jogador->nickname }}
                        </strong>


                        <span class="game-partner-level">

                            {{ $niveis[
                                $jogador->nivel_jogo
                            ]['nome']
                                ?? 'Não definido' }}

                        </span>

                    </a>

                @empty

                    <div class="game-partners-empty">

                        @if ($nivelFiltro !== null)

                            Nenhum jogador com o nível

                            <strong>
                                {{ $niveis[
                                    $nivelFiltro
                                ]['nome'] }}
                            </strong>

                            foi encontrado.

                        @else

                            Ainda não há outros jogadores
                            com este jogo em Meus Jogos.

                        @endif

                    </div>

                @endforelse

            </div>


            @if ($jogadores->hasPages())

                <div class="squad-pagination">

                    {{ $jogadores->links(
                        'pagination::bootstrap-5'
                    ) }}

                </div>

            @endif

        </section>

    </div>
@endsection
