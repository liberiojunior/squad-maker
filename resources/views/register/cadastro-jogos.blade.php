@extends('layouts.app')

@section('content')
    <main class="game-setup-page">
        <div class="game-setup-container">
            <div class="game-setup-header">
                <span class="game-setup-step">Etapa 2 de 2</span>
                <h1>Escolha seus jogos</h1>
                <p>
                    Escolha até 3 jogos para começar e informe seu nível em cada um.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="game-setup-toolbar">
                <form
                    method="GET"
                    action="{{ route('cadastro.jogos') }}"
                    class="game-setup-search"
                >
                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar jogo..."
                        autocomplete="off"
                    >
                </form>

                <span
                    class="game-setup-count"
                    id="gameSetupCount"
                >
                    {{ count($selecionados) }} de {{ $limiteJogos }} jogos selecionados
                </span>
            </div>

            <div class="game-setup-grid">
                @forelse ($jogos as $jogo)
                    @php
                        $nivelSelecionado =
                            $selecionados[$jogo->id_jogo]
                            ?? null;
                    @endphp

                    <button
                        type="button"
                        class="game-setup-card {{ $nivelSelecionado ? 'selected' : '' }}"
                        data-game-card
                        data-id="{{ $jogo->id_jogo }}"
                        data-name="{{ $jogo->nome }}"
                        data-cover="{{ $jogo->capa }}"
                        data-selected="{{ $nivelSelecionado ? '1' : '0' }}"
                        data-level="{{ $nivelSelecionado ?? 1 }}"
                        data-remove-url="{{ route('cadastro.jogos.remover', $jogo) }}"
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
                            <strong>{{ $jogo->nome }}</strong>

                            <span
                                class="game-setup-level-badge"
                                {{ $nivelSelecionado ? '' : 'hidden' }}
                            >
                                {{ $nivelSelecionado
                                    ? $niveis[$nivelSelecionado]
                                    : ''
                                }}
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="game-setup-empty">
                        Nenhum jogo encontrado.
                    </div>
                @endforelse
            </div>

            @if ($jogos->hasPages())
                <div class="squad-pagination">
                    {{ $jogos->links('pagination::bootstrap-5') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('cadastro.jogos.store') }}"
                class="game-setup-footer"
            >
                @csrf

                <button
                    type="submit"
                    class="btn game-setup-continue"
                >
                    Concluir
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </main>

    <div
        class="modal fade"
        id="nivelJogoModal"
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
                            id="levelGameCover"
                        >

                        <strong id="levelGameName"></strong>
                    </div>

                    <div class="profile-level-edit-current">
                        <span>Seu nível</span>

                        <strong id="levelGameNameValue">
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
                            id="levelGameRange"
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
                        class="btn profile-games-save"
                        id="saveGameLevelButton"
                    >
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const levelNames = {
            1: 'Iniciante',
            2: 'Casual',
            3: 'Engajado',
            4: 'Competitivo',
            5: 'Hardcore'
        };

        const levelColors = {
            1: '#aaa1b8',
            2: '#39d353',
            3: '#25c2e8',
            4: '#4f7cff',
            5: '#ec3bbd'
        };

        const modalElement = document.getElementById('nivelJogoModal');
        const levelRange = document.getElementById('levelGameRange');
        const levelName = document.getElementById('levelGameNameValue');
        const gameName = document.getElementById('levelGameName');
        const gameCover = document.getElementById('levelGameCover');
        const gameError = document.getElementById('levelGameError');
        const saveButton = document.getElementById('saveGameLevelButton');
        const removeButton = document.getElementById('removeGameButton');
        const countElement = document.getElementById('gameSetupCount');

        let currentCard = null;
        let selectedCount = {{ count($selecionados) }};

        function updateRangeVisual(range, nameElement) {
            const value = Number(range.value);
            const percent = ((value - 1) / 4) * 100;
            const wrapper = range.closest('.game-level-range-wrap');

            nameElement.textContent = levelNames[value];
            nameElement.style.color = levelColors[value];

            wrapper.style.setProperty(
                '--level-color',
                levelColors[value]
            );

            wrapper.style.setProperty(
                '--level-percent',
                percent + '%'
            );

            range.style.setProperty(
                '--level-color',
                levelColors[value]
            );

            range.style.setProperty(
                '--level-percent',
                percent + '%'
            );
        }

        function updateCount() {
            countElement.textContent =
                selectedCount
                + ' de {{ $limiteJogos }} jogos selecionados';
        }

        function updateModalLimit(selected) {
            const limitReached =
                !selected
                && selectedCount >= {{ $limiteJogos }};

            saveButton.disabled = limitReached;

            if (limitReached) {
                gameError.textContent =
                    'Você já escolheu {{ $limiteJogos }} jogos. Remova um deles para adicionar outro.';
                gameError.hidden = false;
            }
        }

        modalElement.addEventListener(
            'show.bs.modal',
            function(event) {
                currentCard = event.relatedTarget;

                if (!currentCard) {
                    return;
                }

                const selected =
                    currentCard.dataset.selected === '1';

                gameName.textContent =
                    currentCard.dataset.name;

                gameCover.src =
                    currentCard.dataset.cover;

                gameCover.alt =
                    currentCard.dataset.name;

                levelRange.value =
                    currentCard.dataset.level || 1;

                gameError.hidden = true;
                gameError.textContent = '';

                removeButton.hidden = !selected;
                removeButton.disabled = false;

                updateModalLimit(selected);
                updateRangeVisual(levelRange, levelName);
            }
        );

        levelRange.addEventListener(
            'input',
            function() {
                updateRangeVisual(
                    levelRange,
                    levelName
                );
            }
        );

        saveButton.addEventListener(
            'click',
            async function() {
                if (
                    !currentCard
                    || saveButton.disabled
                ) {
                    return;
                }

                gameError.hidden = true;
                gameError.textContent = '';
                saveButton.disabled = true;

                try {
                    const response = await fetch(
                        '{{ route('cadastro.jogos.selecionar') }}',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                id_jogo: currentCard.dataset.id,
                                nivel: Number(levelRange.value)
                            })
                        }
                    );

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message
                            || 'Não foi possível salvar o jogo.'
                        );
                    }

                    const wasSelected =
                        currentCard.dataset.selected === '1';

                    currentCard.dataset.selected = '1';
                    currentCard.dataset.level = String(data.nivel);
                    currentCard.classList.add('selected');

                    const badge =
                        currentCard.querySelector(
                            '.game-setup-level-badge'
                        );

                    badge.textContent =
                        data.nivel_nome;

                    badge.hidden = false;

                    if (!wasSelected) {
                        selectedCount++;
                    }

                    updateCount();

                    bootstrap.Modal
                        .getOrCreateInstance(modalElement)
                        .hide();
                } catch (error) {
                    gameError.textContent =
                        error.message;

                    gameError.hidden = false;

                    const selected =
                        currentCard.dataset.selected === '1';

                    updateModalLimit(selected);
                } finally {
                    const selected =
                        currentCard
                        && currentCard.dataset.selected === '1';

                    if (selected) {
                        saveButton.disabled = false;
                    }
                }
            }
        );

        removeButton.addEventListener(
            'click',
            async function() {
                if (!currentCard) {
                    return;
                }

                removeButton.disabled = true;
                gameError.hidden = true;
                gameError.textContent = '';

                try {
                    const response = await fetch(
                        currentCard.dataset.removeUrl,
                        {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }
                    );

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message
                            || 'Não foi possível remover o jogo.'
                        );
                    }

                    currentCard.dataset.selected = '0';
                    currentCard.dataset.level = '1';
                    currentCard.classList.remove('selected');

                    const badge =
                        currentCard.querySelector(
                            '.game-setup-level-badge'
                        );

                    badge.textContent = '';
                    badge.hidden = true;

                    selectedCount = data.count;

                    updateCount();

                    bootstrap.Modal
                        .getOrCreateInstance(modalElement)
                        .hide();
                } catch (error) {
                    gameError.textContent =
                        error.message;

                    gameError.hidden = false;
                    removeButton.disabled = false;
                }
            }
        );

        updateRangeVisual(
            levelRange,
            levelName
        );
    </script>
@endsection
