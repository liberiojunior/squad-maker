iniciarAvisos();

document.addEventListener('DOMContentLoaded', function() {
    iniciarAvisos();
    iniciarBioPublica();
    iniciarAcoesAmizadePerfil();
    iniciarAtualizacaoAmizadeTempoReal();
    iniciarPerfil();
    iniciarOrdenacaoPlataformas();
    iniciarAvatarPerfil();
    iniciarGenerosPerfil();
    iniciarBuscasCatalogo();
    iniciarBuscaJogosPerfil();
    iniciarCadastroJogos();
    iniciarImportacaoSteam();
    iniciarConfirmacoesFormulario();
    iniciarAtualizacoesChat();
});

const NOMES_NIVEIS_JOGO = {
    1: 'Iniciante',
    2: 'Casual',
    3: 'Engajado',
    4: 'Competitivo',
    5: 'Hardcore'
};

const CORES_NIVEIS_JOGO = {
    1: '#aaa1b8',
    2: '#39d353',
    3: '#25c2e8',
    4: '#4f7cff',
    5: '#ec3bbd'
};

const ATRASO_BUSCA = 300;

function atualizarVisualNivel(range, nomeElemento, descricaoElemento = null, descricoes = {}) {
    if (!range || !nomeElemento) {
        return;
    }

    const nivel = Number(range.value);
    const percentual = ((nivel - 1) / 4) * 100;
    const cor = CORES_NIVEIS_JOGO[nivel];
    const wrapper = range.closest('.game-level-range-wrap');

    nomeElemento.textContent = NOMES_NIVEIS_JOGO[nivel];
    nomeElemento.style.color = cor;

    if (descricaoElemento) {
        descricaoElemento.textContent = descricoes[nivel] || '';
    }

    if (wrapper) {
        wrapper.style.setProperty('--level-color', cor);
        wrapper.style.setProperty('--level-percent', percentual + '%');
    }

    range.style.setProperty('--level-color', cor);
    range.style.setProperty('--level-percent', percentual + '%');
}

function atualizarErro(elemento, mensagem = '') {
    if (!elemento) {
        return;
    }

    elemento.textContent = mensagem;
    elemento.hidden = mensagem === '';
}

function mensagemDaResposta(data, fallback) {
    if (data && data.message) {
        return data.message;
    }

    if (data && data.errors) {
        const primeiroErro = Object.values(data.errors)[0];

        if (Array.isArray(primeiroErro) && primeiroErro.length > 0) {
            return primeiroErro[0];
        }
    }

    return fallback;
}

async function requisicaoJson(url, options = {}, csrf = null, fallback = 'Não foi possível concluir a operação.') {
    const headers = {
        'Accept': 'application/json',
        ...(options.headers || {})
    };

    if (csrf) {
        headers['X-CSRF-TOKEN'] = csrf;
    }

    if (typeof options.body === 'string' && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        ...options,
        headers
    });

    const data = await response.json().catch(function() {
        return {};
    });

    if (!response.ok) {
        throw new Error(mensagemDaResposta(data, fallback));
    }

    return data;
}

async function atualizarAcoesAmizadePerfil() {
    const areaAtual = document.querySelector('.profile-social-actions');

    if (!areaAtual) {
        return;
    }

    const response = await fetch(window.location.href, {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        },
        cache: 'no-store'
    });

    if (!response.ok) {
        throw new Error('Não foi possível atualizar as ações do perfil.');
    }

    const html = await response.text();
    const documento = new DOMParser().parseFromString(html, 'text/html');
    const novaArea = documento.querySelector('.profile-social-actions');

    if (!novaArea) {
        throw new Error('Não foi possível atualizar as ações do perfil.');
    }

    areaAtual.innerHTML = novaArea.innerHTML;
    iniciarAcoesAmizadePerfil();
}

function iniciarAcoesAmizadePerfil() {
    const forms = document.querySelectorAll('[data-profile-friendship-form]');

    forms.forEach(function(form) {
        if (form.dataset.friendshipBound === '1') {
            return;
        }

        form.dataset.friendshipBound = '1';

        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');

            if (button?.disabled) {
                return;
            }

            if (button) {
                button.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json().catch(function() {
                    return {};
                });

                if (!response.ok) {
                    throw new Error(
                        mensagemDaResposta(
                            data,
                            'Não foi possível atualizar a amizade.'
                        )
                    );
                }

                await atualizarAcoesAmizadePerfil();
                mostrarAviso('success', data.message || 'Amizade atualizada.');
            } catch (error) {
                mostrarAviso(
                    'danger',
                    error.message || 'Não foi possível atualizar a amizade.'
                );

                if (button) {
                    button.disabled = false;
                }
            }
        });
    });
}

function iniciarAtualizacaoAmizadeTempoReal() {
    if (!document.querySelector('.profile-social-actions')) {
        return;
    }

    window.addEventListener('squad:amizade', function() {
        atualizarAcoesAmizadePerfil().catch(function() {
            return null;
        });
    });
}

function iniciarBuscasCatalogo() {
    const configuracoes = [
        {
            formId: 'gameSearchForm',
            inputId: 'gameSearchInput',
            resultsId: 'gameSearchResults',
            loadingId: 'gameSearchLoading',
            defaultOrder: 'popularidade'
        },
        {
            formId: 'adminGameSearchForm',
            inputId: 'adminGameSearchInput',
            resultsId: 'adminGameResults',
            loadingId: 'adminGameSearchLoading',
            defaultOrder: 'az'
        }
    ];

    configuracoes.forEach(function(configuracao) {
        const form = document.getElementById(configuracao.formId);

        if (!form) {
            return;
        }

        iniciarBuscaCatalogo(form, configuracao);
    });
}

function iniciarBuscaCatalogo(form, configuracao) {
    const input = document.getElementById(configuracao.inputId);
    const results = document.getElementById(configuracao.resultsId);
    const loading = document.getElementById(configuracao.loadingId);

    if (!results) {
        return;
    }

    let searchTimer = null;
    let searchController = null;
    let appliedUrl = new URL(window.location.href);

    function montarUrl() {
        const url = new URL(form.action, window.location.origin);
        const formData = new FormData(form);

        formData.forEach(function(value, key) {
            if (String(value).trim() === '') {
                return;
            }

            url.searchParams.append(key, value);
        });

        return url;
    }

    function mostrarLoading(ativo) {
        if (!loading) {
            return;
        }

        loading.hidden = !ativo;
    }

    function atualizarContadorFiltros() {
        let contador = form.querySelector(
            '[data-filter-count], .catalog-filter-count'
        );

        const toggle = form.querySelector('.catalog-filter-toggle');

        let quantidade = 0;

        const generos = form.querySelectorAll(
            'input[name="generos[]"]:checked'
        );

        quantidade += generos.length;

        const modos = form.querySelectorAll(
            'input[name="modos[]"]:checked, input[name="modos_filtro[]"]:checked'
        );

        quantidade += modos.length;

        const origem = form.querySelector(
            'input[name="origem"]:checked'
        );

        if (origem && origem.value !== '') {
            quantidade++;
        }

        const ordem = form.querySelector(
            'input[name="ordem"]:checked'
        );

        if (
            ordem
            && ordem.value !== configuracao.defaultOrder
        ) {
            quantidade++;
        }

        if (!contador && toggle) {
            contador = document.createElement('span');
            contador.className = 'catalog-filter-count';
            contador.dataset.filterCount = '';

            toggle.appendChild(contador);
        }

        if (contador) {
            contador.textContent = quantidade;
            contador.hidden = quantidade === 0;
        }

        if (toggle) {
            toggle.classList.toggle(
                'active',
                quantidade > 0
            );
        }
    }

    function mostrarErro() {
        const antigo = results.querySelector(
            '.catalog-search-error'
        );

        if (antigo) {
            antigo.remove();
        }

        const erro = document.createElement('div');

        erro.className = 'catalog-search-error';
        erro.textContent = 'Não foi possível atualizar os jogos.';

        results.prepend(erro);
    }

    async function buscar(url, fecharFiltros = false) {
        if (searchController) {
            searchController.abort();
        }

        const controller = new AbortController();
        searchController = controller;
        mostrarLoading(true);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error(
                    'Não foi possível atualizar os jogos.'
                );
            }

            const html = await response.text();

            const documento = new DOMParser()
                .parseFromString(
                    html,
                    'text/html'
                );

            const novosResultados = documento.getElementById(
                configuracao.resultsId
            );

            if (!novosResultados) {
                throw new Error(
                    'Não foi possível carregar os resultados.'
                );
            }

            if (searchController !== controller) {
                return;
            }

            results.innerHTML = novosResultados.innerHTML;
            appliedUrl = new URL(url.toString());
            window.history.replaceState({}, '', appliedUrl.toString());
            atualizarContadorFiltros();

            if (fecharFiltros) {
                const filtros = form.querySelector('.catalog-filter-panel')
                    ?.closest('.collapse');

                if (filtros) {
                    bootstrap.Collapse.getOrCreateInstance(
                        filtros,
                        { toggle: false }
                    ).hide();
                }

                window.scrollTo({
                    top: Math.max(0, form.offsetTop - 18),
                    behavior: 'smooth'
                });
            }

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(error);

            mostrarErro();

        } finally {
            if (searchController === controller) {
                mostrarLoading(false);
            }
        }
    }

    function agendarBusca() {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(function() {
            buscar(montarUrl());
        }, ATRASO_BUSCA);
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        clearTimeout(searchTimer);

        const fecharFiltros = event.submitter
            ?.classList.contains('catalog-filter-apply') === true;

        buscar(montarUrl(), fecharFiltros);
    });

    if (input) {
        input.addEventListener(
            'input',
            agendarBusca
        );
    }

    form.addEventListener('change', function() {
        atualizarContadorFiltros();
    });

    results.addEventListener('click', function(event) {
        const link = event.target.closest(
            '.squad-pagination a'
        );

        if (!link) {
            return;
        }

        event.preventDefault();

        const paginaUrl = new URL(
            link.href,
            window.location.origin
        );

        const url = new URL(appliedUrl.toString());
        const pagina = paginaUrl.searchParams.get('page');

        if (pagina) {
            url.searchParams.set('page', pagina);
        } else {
            url.searchParams.delete('page');
        }

        buscar(url);

        window.scrollTo({
            top: form.offsetTop - 20,
            behavior: 'smooth'
        });
    });

    atualizarContadorFiltros();
}

function iniciarConfirmacoesFormulario() {
    const modalElement = document.getElementById('adminConfirmModal');
    const title = document.getElementById('adminConfirmTitle');
    const message = document.getElementById('adminConfirmMessage');
    const accept = document.getElementById('adminConfirmAccept');

    if (!modalElement || !message || !accept) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    let pendingForm = null;

    document.addEventListener('submit', function(event) {
        const form = event.target.closest('form[data-confirm-message]');

        if (!form || form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingForm = form;
        message.textContent = form.dataset.confirmMessage;

        if (title) {
            title.textContent = form.dataset.confirmTitle || 'Confirmar ação';
        }

        modal.show();
    });

    accept.addEventListener('click', function() {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = '1';
        modal.hide();
        form.requestSubmit();
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        pendingForm = null;
    });
}

function iniciarBuscaJogosPerfil() {
    const page = document.getElementById('gameSearchPage');
    const modalElement = document.getElementById('gameSearchLevelModal');
    const levelRange = document.getElementById('gameSearchLevelRange');
    const levelName = document.getElementById('gameSearchLevelName');
    const levelDescription = document.getElementById('gameSearchLevelDescription');
    const gameName = document.getElementById('gameSearchLevelGame');
    const gameCover = document.getElementById('gameSearchLevelCover');
    const modalTitle = document.getElementById('gameSearchLevelTitle');
    const saveButton = document.getElementById('gameSearchLevelSave');
    const errorElement = document.getElementById('gameSearchLevelError');

    if (!page || !modalElement || !levelRange || !levelName || !gameName || !gameCover || !saveButton) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const csrf = page.dataset.csrf;
    const descriptions = page.dataset.levelDescriptions
        ? JSON.parse(page.dataset.levelDescriptions)
        : {};

    let currentButton = null;

    page.addEventListener('click', function(event) {
        const button = event.target.closest('[data-game-profile-action]');

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        currentButton = button;

        const selected = button.dataset.selected === '1';

        gameName.textContent = button.dataset.name;
        gameCover.src = button.dataset.cover;
        gameCover.alt = button.dataset.name;
        levelRange.value = button.dataset.level || '1';

        if (modalTitle) {
            modalTitle.textContent = selected
                ? 'Editar nível'
                : 'Adicionar aos Meus Jogos';
        }

        saveButton.textContent = selected
            ? 'Salvar nível'
            : 'Adicionar';

        atualizarErro(errorElement);
        atualizarVisualNivel(levelRange, levelName, levelDescription, descriptions);
        modal.show();
    });

    levelRange.addEventListener('input', function() {
        atualizarVisualNivel(levelRange, levelName, levelDescription, descriptions);
    });

    saveButton.addEventListener('click', async function() {
        if (!currentButton) {
            return;
        }

        const selected = currentButton.dataset.selected === '1';
        const url = selected
            ? currentButton.dataset.updateUrl
            : currentButton.dataset.addUrl;

        saveButton.disabled = true;
        atualizarErro(errorElement);

        try {
            const data = await requisicaoJson(url, {
                method: selected ? 'PATCH' : 'POST',
                body: JSON.stringify({
                    nivel: Number(levelRange.value)
                })
            }, csrf, 'Não foi possível atualizar Meus Jogos.');

            currentButton.dataset.selected = '1';
            currentButton.dataset.level = String(data.nivel || levelRange.value);
            currentButton.classList.add('selected');
            currentButton.title = 'Editar nível em Meus Jogos';
            currentButton.setAttribute(
                'aria-label',
                'Editar nível de ' + currentButton.dataset.name
            );

            const icon = currentButton.querySelector('i');

            if (icon) {
                icon.className = 'bi bi-check-lg';
            }

            modal.hide();

        } catch (error) {
            atualizarErro(errorElement, error.message);

        } finally {
            saveButton.disabled = false;
        }
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        currentButton = null;
        atualizarErro(errorElement);
    });

    atualizarVisualNivel(levelRange, levelName, levelDescription, descriptions);
}

function iniciarCadastroJogos() {
    const page = document.getElementById('gameSetupPage');

    if (!page) {
        return;
    }

    const modalElement = document.getElementById('nivelJogoModal');
    const levelRange = document.getElementById('levelGameRange');
    const levelName = document.getElementById('levelGameNameValue');
    const levelDescription = document.getElementById('levelGameDescription');
    const gameName = document.getElementById('levelGameName');
    const gameCover = document.getElementById('levelGameCover');
    const gameError = document.getElementById('levelGameError');
    const saveButton = document.getElementById('saveGameLevelButton');
    const removeButton = document.getElementById('removeGameButton');
    const countElement = document.getElementById('gameSetupCount');
    const searchForm = document.getElementById('gameSetupSearchForm');
    const searchInput = document.getElementById('gameSetupSearchInput');
    const searchResults = document.getElementById('gameSetupResults');
    const searchLoading = document.getElementById('gameSetupSearchLoading');
    const finishForm = document.getElementById('gameSetupFinishForm');
    const introModalElement = document.getElementById('gameSetupIntroModal');
    const introStart = document.getElementById('gameSetupIntroStart');
    const completeModalElement = document.getElementById('gameSetupCompleteModal');
    const completeFinish = document.getElementById('gameSetupCompleteFinish');

    if (!modalElement || !levelRange || !levelName || !gameName || !gameCover || !saveButton || !removeButton || !searchForm || !searchInput || !searchResults) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const introModal = introModalElement
        ? bootstrap.Modal.getOrCreateInstance(introModalElement)
        : null;
    const completeModal = completeModalElement
        ? bootstrap.Modal.getOrCreateInstance(completeModalElement)
        : null;

    const csrf = page.dataset.csrf;
    const selectUrl = page.dataset.selectUrl;
    const limit = Number(page.dataset.limit || 3);
    const descriptions = page.dataset.levelDescriptions
        ? JSON.parse(page.dataset.levelDescriptions)
        : {};

    let currentCard = null;
    let selectedCount = Number(page.dataset.selectedCount || 0);
    let searchTimer = null;
    let searchController = null;
    let showCompleteAfterLevel = false;

    function updateCount() {
        if (!countElement) {
            return;
        }

        countElement.textContent = selectedCount
            + ' de '
            + limit
            + ' jogos selecionados';
    }

    function updateModalLimit(selected) {
        const limitReached = !selected && selectedCount >= limit;

        saveButton.disabled = limitReached;

        if (gameError) {
            if (limitReached) {
                gameError.textContent = 'Você já escolheu '
                    + limit
                    + ' jogos. Remova um deles para adicionar outro.';
                gameError.hidden = false;
            } else {
                gameError.textContent = '';
                gameError.hidden = true;
            }
        }
    }

    function showSearchLoading(active) {
        if (searchLoading) {
            searchLoading.hidden = !active;
        }
    }

    function buildSearchUrl() {
        const url = new URL(searchForm.action, window.location.origin);
        const term = searchInput.value.trim();

        if (term !== '') {
            url.searchParams.set('q', term);
        }

        return url;
    }

    async function searchGames(url) {
        if (searchController) {
            searchController.abort();
        }

        const controller = new AbortController();
        searchController = controller;
        showSearchLoading(true);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error('Não foi possível buscar os jogos.');
            }

            const html = await response.text();
            const documentResponse = new DOMParser().parseFromString(
                html,
                'text/html'
            );
            const newResults = documentResponse.getElementById('gameSetupResults');

            if (!newResults) {
                throw new Error('Não foi possível atualizar os resultados.');
            }

            if (searchController !== controller) {
                return;
            }

            searchResults.innerHTML = newResults.innerHTML;
            window.history.replaceState({}, '', url.toString());

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }

        } finally {
            if (searchController === controller) {
                showSearchLoading(false);
            }
        }
    }

    function scheduleSearch() {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(function() {
            searchGames(buildSearchUrl());
        }, ATRASO_BUSCA);
    }

    searchInput.addEventListener('input', scheduleSearch);

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        clearTimeout(searchTimer);
        searchGames(buildSearchUrl());
    });

    searchResults.addEventListener('click', function(event) {
        const link = event.target.closest('.squad-pagination a');

        if (!link) {
            return;
        }

        event.preventDefault();

        searchGames(
            new URL(link.href, window.location.origin)
        );
    });

    modalElement.addEventListener('show.bs.modal', function(event) {
        currentCard = event.relatedTarget;

        if (!currentCard) {
            return;
        }

        const selected = currentCard.dataset.selected === '1';

        gameName.textContent = currentCard.dataset.name;
        gameCover.src = currentCard.dataset.cover;
        gameCover.alt = currentCard.dataset.name;
        levelRange.value = currentCard.dataset.level || 1;

        if (gameError) {
            gameError.hidden = true;
            gameError.textContent = '';
        }

        removeButton.hidden = !selected;
        removeButton.disabled = false;
        updateModalLimit(selected);
        atualizarVisualNivel(levelRange, levelName, levelDescription, descriptions);
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        currentCard = null;

        if (showCompleteAfterLevel && completeModal) {
            showCompleteAfterLevel = false;
            completeModal.show();
        }
    });

    levelRange.addEventListener('input', function() {
        atualizarVisualNivel(levelRange, levelName, levelDescription, descriptions);
    });

    saveButton.addEventListener('click', async function() {
        if (!currentCard || saveButton.disabled) {
            return;
        }

        if (gameError) {
            gameError.hidden = true;
            gameError.textContent = '';
        }

        saveButton.disabled = true;

        try {
            const data = await requisicaoJson(selectUrl, {
                method: 'POST',
                body: JSON.stringify({
                    id_jogo: Number(currentCard.dataset.id),
                    nivel: Number(levelRange.value)
                })
            }, csrf, 'Não foi possível salvar o jogo.');

            const wasSelected = currentCard.dataset.selected === '1';

            currentCard.dataset.selected = '1';
            currentCard.dataset.level = String(data.nivel);
            currentCard.classList.add('selected');

            const badge = currentCard.querySelector('.game-setup-level-badge');

            if (badge) {
                badge.textContent = data.nivel_nome;
                badge.hidden = false;
            }

            selectedCount = Number(data.count);
            updateCount();

            if (!wasSelected && selectedCount === limit) {
                showCompleteAfterLevel = true;
            }

            modal.hide();

        } catch (error) {
            if (gameError) {
                gameError.textContent = error.message;
                gameError.hidden = false;
            }

            const selected = currentCard
                && currentCard.dataset.selected === '1';

            updateModalLimit(selected);

        } finally {
            const selected = currentCard
                && currentCard.dataset.selected === '1';

            if (selected) {
                saveButton.disabled = false;
            }
        }
    });

    removeButton.addEventListener('click', async function() {
        if (!currentCard) {
            return;
        }

        removeButton.disabled = true;

        if (gameError) {
            gameError.hidden = true;
            gameError.textContent = '';
        }

        try {
            const data = await requisicaoJson(currentCard.dataset.removeUrl, {
                method: 'DELETE'
            }, csrf, 'Não foi possível remover o jogo.');

            currentCard.dataset.selected = '0';
            currentCard.dataset.level = '1';
            currentCard.classList.remove('selected');

            const badge = currentCard.querySelector('.game-setup-level-badge');

            if (badge) {
                badge.textContent = '';
                badge.hidden = true;
            }

            selectedCount = Number(data.count);
            updateCount();
            modal.hide();

        } catch (error) {
            if (gameError) {
                gameError.textContent = error.message;
                gameError.hidden = false;
            }

            removeButton.disabled = false;
        }
    });

    if (introModal && !sessionStorage.getItem('squadmakerCadastroJogosIntro')) {
        introModal.show();
    }

    if (introStart) {
        introStart.addEventListener('click', function() {
            sessionStorage.setItem(
                'squadmakerCadastroJogosIntro',
                '1'
            );
        });
    }

    if (completeFinish && finishForm) {
        completeFinish.addEventListener('click', function() {
            completeFinish.disabled = true;
            finishForm.requestSubmit();
        });
    }

    atualizarVisualNivel(levelRange, levelName, levelDescription, descriptions);
    updateCount();
}

function iniciarImportacaoSteam() {
    const form = document.getElementById(
        'steamImportForm'
    );

    if (!form) {
        return;
    }

    const button = document.getElementById(
        'steamImportButton'
    );

    const buttonContent = document.getElementById(
        'steamImportButtonContent'
    );

    const loading = document.getElementById(
        'steamImportLoading'
    );

    form.addEventListener('submit', function() {
        if (button) {
            button.disabled = true;
        }

        if (buttonContent) {
            buttonContent.textContent = 'Importando...';
        }

        if (loading) {
            loading.hidden = false;
        }
    });
}

function obterPilhaAvisos() {
    let stack = document.querySelector(
        '.squad-notification-stack'
    );

    if (stack) {
        return stack;
    }

    stack = document.createElement('div');
    stack.className = 'squad-notification-stack';
    stack.setAttribute('aria-live', 'polite');
    stack.setAttribute('aria-relevant', 'additions');

    document.body.appendChild(stack);

    return stack;
}

function tipoDoAviso(alert) {
    if (alert.classList.contains('alert-danger')) {
        return 'danger';
    }

    if (alert.classList.contains('alert-warning')) {
        return 'warning';
    }

    if (alert.classList.contains('alert-info')) {
        return 'info';
    }

    return 'success';
}

function iconeDoAviso(tipo) {
    const icones = {
        success: 'bi-check-lg',
        danger: 'bi-exclamation-lg',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-lg'
    };

    return icones[tipo] || icones.info;
}

function tempoDoAviso(tipo) {
    const tempos = {
        success: 3500,
        danger: 6500,
        warning: 5500,
        info: 4500
    };

    return tempos[tipo] || 4500;
}

function removerAviso(alert) {
    if (!alert || alert.dataset.removing === '1') {
        return;
    }

    alert.dataset.removing = '1';
    alert.classList.add('alert-hide');

    setTimeout(function() {
        alert.remove();
    }, 300);
}

function iniciarTemporizadorAviso(alert) {
    if (!alert) {
        return;
    }

    clearTimeout(alert._squadNotificationTimer);

    const tipo = alert.dataset.notificationType
        || tipoDoAviso(alert);

    alert._squadNotificationTimer = setTimeout(
        function() {
            removerAviso(alert);
        },
        tempoDoAviso(tipo)
    );
}

function prepararAviso(alert) {
    if (
        !alert
        || alert.dataset.notificationReady === '1'
        || alert.dataset.alertStatic === '1'
    ) {
        return;
    }

    const tipo = tipoDoAviso(alert);
    const content = document.createElement('div');
    const icon = document.createElement('i');

    content.className = 'squad-notification-text';

    while (alert.firstChild) {
        content.appendChild(alert.firstChild);
    }

    icon.className =
        'bi '
        + iconeDoAviso(tipo)
        + ' squad-notification-icon';

    icon.setAttribute('aria-hidden', 'true');

    alert.classList.add('squad-notification');
    alert.dataset.notificationReady = '1';
    alert.dataset.notificationType = tipo;
    alert.tabIndex = 0;
    alert.setAttribute(
        'role',
        tipo === 'danger' ? 'alert' : 'status'
    );

    alert.appendChild(icon);
    alert.appendChild(content);

    obterPilhaAvisos().appendChild(alert);

    alert.addEventListener('mouseenter', function() {
        clearTimeout(alert._squadNotificationTimer);
    });

    alert.addEventListener('mouseleave', function() {
        if (!alert.classList.contains('squad-notification-open')) {
            iniciarTemporizadorAviso(alert);
        }
    });

    alert.addEventListener('focusin', function() {
        clearTimeout(alert._squadNotificationTimer);
    });

    alert.addEventListener('focusout', function() {
        iniciarTemporizadorAviso(alert);
    });

    alert.addEventListener('click', function() {
        alert.classList.toggle('squad-notification-open');

        if (alert.classList.contains('squad-notification-open')) {
            clearTimeout(alert._squadNotificationTimer);
            return;
        }

        iniciarTemporizadorAviso(alert);
    });

    iniciarTemporizadorAviso(alert);
}

function mostrarAviso(tipo, mensagem) {
    const tiposPermitidos = [
        'success',
        'danger',
        'warning',
        'info'
    ];

    const tipoNormalizado = tiposPermitidos.includes(tipo)
        ? tipo
        : 'info';

    const alert = document.createElement('div');

    alert.className =
        'alert alert-'
        + tipoNormalizado;

    alert.textContent = mensagem;

    document.body.appendChild(alert);
    prepararAviso(alert);
}

window.SquadAviso = {
    mostrar: mostrarAviso
};

function iniciarAvisos() {
    document
        .querySelectorAll('.alert')
        .forEach(function(alert) {
            prepararAviso(alert);
        });
}

function iniciarBioPublica() {
    const bio = document.getElementById('publicBio');
    const toggle = document.getElementById('publicBioToggle');

    if (!bio || !toggle) {
        return;
    }

    function atualizar() {
        toggle.textContent = 'Ver mais';
        toggle.setAttribute('aria-expanded', 'false');

        requestAnimationFrame(function() {
            toggle.hidden = bio.scrollHeight <= bio.clientHeight + 1;
        });
    }

    window.addEventListener('resize', atualizar);

    atualizar();
}

function iniciarAvatarPerfil() {
    const form = document.getElementById('avatarForm');
    const input = document.getElementById('avatarInput');
    const modalElement = document.getElementById('avatarCropModal');
    const canvas = document.getElementById('avatarCropCanvas');
    const zoomInput = document.getElementById('avatarCropZoom');
    const saveButton = document.getElementById('avatarCropSave');
    const errorElement = document.getElementById('avatarCropError');

    if (!form || !input || !modalElement || !canvas || !zoomInput || !saveButton) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const context = canvas.getContext('2d');

    let image = null;
    let objectUrl = null;
    let baseScale = 1;
    let zoom = 1;
    let offsetX = 0;
    let offsetY = 0;
    let dragging = false;
    let startX = 0;
    let startY = 0;
    let startOffsetX = 0;
    let startOffsetY = 0;
    let saving = false;

    function clearCrop() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }

        objectUrl = null;
        image = null;
        baseScale = 1;
        zoom = 1;
        offsetX = 0;
        offsetY = 0;
        dragging = false;
        zoomInput.value = '1';

        canvas.classList.remove('dragging');
        context.clearRect(0, 0, canvas.width, canvas.height);
        atualizarErro(errorElement);
    }

    function limitOffset() {
        if (!image) {
            return;
        }

        const width = image.naturalWidth * baseScale * zoom;
        const height = image.naturalHeight * baseScale * zoom;
        const limitX = Math.max(0, (width - canvas.width) / 2);
        const limitY = Math.max(0, (height - canvas.height) / 2);

        offsetX = Math.max(-limitX, Math.min(limitX, offsetX));
        offsetY = Math.max(-limitY, Math.min(limitY, offsetY));
    }

    function renderCrop() {
        if (!image) {
            return;
        }

        limitOffset();

        const width = image.naturalWidth * baseScale * zoom;
        const height = image.naturalHeight * baseScale * zoom;
        const x = (canvas.width - width) / 2 + offsetX;
        const y = (canvas.height - height) / 2 + offsetY;

        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = '#12001f';
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        context.drawImage(
            image,
            x,
            y,
            width,
            height
        );
    }

    function openCrop(file) {
        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {
            mostrarAviso(
                'danger',
                'Escolha uma imagem JPG, PNG ou WEBP.'
            );

            input.value = '';

            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            mostrarAviso(
                'danger',
                'A imagem deve ter no máximo 5 MB.'
            );

            input.value = '';

            return;
        }

        clearCrop();

        objectUrl = URL.createObjectURL(file);
        image = new Image();

        image.onload = function() {
            baseScale = Math.max(
                canvas.width / image.naturalWidth,
                canvas.height / image.naturalHeight
            );

            zoom = 1;
            offsetX = 0;
            offsetY = 0;

            zoomInput.value = '1';

            renderCrop();
            modal.show();
        };

        image.onerror = function() {
            clearCrop();

            input.value = '';

            mostrarAviso(
                'danger',
                'Não foi possível carregar essa imagem.'
            );
        };

        image.src = objectUrl;
    }

    function saveCrop() {
        if (!image || saving) {
            return;
        }

        saveButton.disabled = true;
        saveButton.textContent = 'Salvando...';

        atualizarErro(errorElement);

        canvas.toBlob(
            function(blob) {
                if (!blob) {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Salvar foto';

                    atualizarErro(errorElement, 'Não foi possível preparar a imagem. Tente novamente.');

                    return;
                }

                const file = new File(
                    [blob],
                    'avatar.webp',
                    {
                        type: 'image/webp'
                    }
                );

                const transfer = new DataTransfer();

                transfer.items.add(file);

                input.files = transfer.files;
                saving = true;

                form.requestSubmit();
            },
            'image/webp',
            0.92
        );
    }

    input.addEventListener('change', function() {
        const file = input.files[0];

        if (file) {
            openCrop(file);
        }
    });

    zoomInput.addEventListener('input', function() {
        zoom = Number(zoomInput.value);

        renderCrop();
    });

    canvas.addEventListener('pointerdown', function(event) {
        if (
            !image
            || (
                event.pointerType === 'mouse'
                && event.button !== 0
            )
        ) {
            return;
        }

        dragging = true;

        startX = event.clientX;
        startY = event.clientY;
        startOffsetX = offsetX;
        startOffsetY = offsetY;

        canvas.classList.add('dragging');
        canvas.setPointerCapture(event.pointerId);
    });

    canvas.addEventListener('pointermove', function(event) {
        if (!dragging) {
            return;
        }

        const rect = canvas.getBoundingClientRect();
        const scale = canvas.width / rect.width;

        offsetX =
            startOffsetX
            + (
                event.clientX
                - startX
            ) * scale;

        offsetY =
            startOffsetY
            + (
                event.clientY
                - startY
            ) * scale;

        renderCrop();
    });

    canvas.addEventListener('pointerup', function(event) {
        dragging = false;

        canvas.classList.remove(
            'dragging'
        );

        if (
            canvas.hasPointerCapture(
                event.pointerId
            )
        ) {
            canvas.releasePointerCapture(
                event.pointerId
            );
        }
    });

    canvas.addEventListener('pointercancel', function() {
        dragging = false;

        canvas.classList.remove(
            'dragging'
        );
    });

    saveButton.addEventListener(
        'click',
        saveCrop
    );

    modalElement.addEventListener(
        'hidden.bs.modal',
        function() {
            if (!saving) {
                input.value = '';

                clearCrop();
            }
        }
    );
}

function iniciarGenerosPerfil() {
    const modalElement = document.getElementById('generosModal');

    if (!modalElement) {
        return;
    }

    const form = document.getElementById('profileGenresForm');
    const selectedContainer = document.getElementById('profileSelectedGenres');
    const selectedCount = document.getElementById('profileGenreSelectedCount');

    const maxGeneros = Number(
        form && form.dataset.maxGenres
            ? form.dataset.maxGenres
            : 5
    );

    const options = Array.from(
        modalElement.querySelectorAll('.profile-genre-option')
    );

    options.forEach(function(option) {
        const input = option.querySelector(
            'input[type="checkbox"]'
        );

        if (input) {
            input.dataset.initialChecked =
                input.checked ? '1' : '0';
        }
    });

    function obterSelecionados() {
        return options.filter(function(option) {
            const input = option.querySelector(
                'input[type="checkbox"]'
            );

            return input && input.checked;
        });
    }

    function atualizarDisponibilidade() {
        const quantidade = obterSelecionados().length;
        const limiteAtingido = quantidade >= maxGeneros;

        options.forEach(function(option) {
            const input = option.querySelector(
                'input[type="checkbox"]'
            );

            if (!input) {
                return;
            }

            input.disabled =
                limiteAtingido && !input.checked;
        });
    }

    function atualizarSelecionados() {
        if (!selectedContainer || !selectedCount) {
            return;
        }

        const selecionados = obterSelecionados();

        selectedContainer.innerHTML = '';

        selectedCount.textContent =
            selecionados.length
            + '/'
            + maxGeneros
            + ' selecionados';

        selectedCount.classList.toggle(
            'limit-reached',
            selecionados.length >= maxGeneros
        );

        if (selecionados.length === 0) {
            const vazio = document.createElement('p');

            vazio.className = 'profile-selected-genres-empty';
            vazio.textContent =
                'Nenhum gênero favorito selecionado.';

            selectedContainer.appendChild(vazio);

            atualizarDisponibilidade();

            return;
        }

        selecionados.forEach(function(option) {
            const input = option.querySelector(
                'input[type="checkbox"]'
            );

            const nome =
                option.dataset.genreName
                || option.textContent.trim();

            const botao = document.createElement('button');

            botao.type = 'button';
            botao.className = 'profile-selected-genre';

            botao.setAttribute(
                'aria-label',
                'Remover ' + nome
            );

            const texto = document.createElement('span');

            texto.textContent = nome;

            const icone = document.createElement('i');

            icone.className = 'bi bi-x-lg';

            botao.appendChild(texto);
            botao.appendChild(icone);

            botao.addEventListener('click', function() {
                input.checked = false;

                input.dispatchEvent(
                    new Event(
                        'change',
                        { bubbles: true }
                    )
                );
            });

            selectedContainer.appendChild(botao);
        });

        atualizarDisponibilidade();
    }

    function restaurarEstadoInicial() {
        options.forEach(function(option) {
            const input = option.querySelector(
                'input[type="checkbox"]'
            );

            if (!input) {
                return;
            }

            input.disabled = false;
            input.checked =
                input.dataset.initialChecked === '1';
        });

        atualizarSelecionados();
    }

    options.forEach(function(option) {
        const input = option.querySelector(
            'input[type="checkbox"]'
        );

        if (!input) {
            return;
        }

        input.addEventListener('change', function() {
            const selecionados = obterSelecionados();

            if (
                input.checked
                && selecionados.length > maxGeneros
            ) {
                input.checked = false;
            }

            atualizarSelecionados();
        });
    });

    if (form) {
        form.addEventListener('submit', function(event) {
            const quantidade = obterSelecionados().length;

            if (quantidade <= maxGeneros) {
                return;
            }

            event.preventDefault();

            if (selectedCount) {
                selectedCount.textContent =
                    'Escolha no máximo '
                    + maxGeneros
                    + ' gêneros.';

                selectedCount.classList.add(
                    'limit-reached'
                );
            }
        });
    }

    modalElement.addEventListener(
        'show.bs.modal',
        restaurarEstadoInicial
    );

    atualizarSelecionados();
}

function iniciarPerfil() {
    const profilePage = document.getElementById('profilePage');

    if (!profilePage) {
        return;
    }

    const profileGamesList = document.getElementById('profileGamesList');
    const profileViewAllButton = document.getElementById('profileViewAllButton');

    const csrf = profilePage.dataset.csrf;
    const searchUrl = profilePage.dataset.gameSearchUrl;
    const updateGamesUrl = profilePage.dataset.gamesUpdateUrl;
    const updateOrderUrl = profilePage.dataset.gamesOrderUrl;

    const levelDescriptions =
        profilePage.dataset.levelDescriptions
            ? JSON.parse(profilePage.dataset.levelDescriptions)
            : {};

    const profileForm = document.getElementById('profileForm');
    const nicknameInput = document.getElementById('nicknameInput');
    const bioInput = document.getElementById('bioInput');
    const profileEditButton = document.getElementById('profileEditButton');
    const profileEditIcon = document.getElementById('profileEditIcon');
    const bioToggle = document.getElementById('bioToggle');
    const bioCounter = document.getElementById('bioCounter');
    const bioEmojiWrap = document.getElementById('profileBioEmojiWrap');
    const bioEmojiButton = document.getElementById('profileBioEmojiButton');
    const bioEmojiPicker = document.getElementById('profileBioEmojiPicker');

    const managerModalElement = document.getElementById('adicionarJogosModal');
    const gameSearch = document.getElementById('profileGameSearch');
    const searchResults = document.getElementById('profileGameSearchResults');
    const managerCount = document.getElementById('profileGameManagerCount');
    const gamesSaveButton = document.getElementById('profileGamesSave');
    const gamesSaveStatus = document.getElementById('profileGamesSaveStatus');

    const levelPopup = document.getElementById('profileLevelPopup');
    const levelPopupClose = document.getElementById('profileLevelPopupClose');
    const levelCancel = document.getElementById('profileLevelCancel');
    const levelConfirm = document.getElementById('profileLevelConfirm');
    const levelRemove = document.getElementById('profileLevelRemove');
    const levelRange = document.getElementById('profileLevelRange');
    const levelName = document.getElementById('profileLevelName');
    const levelDescription = document.getElementById('profileLevelDescription');
    const levelGameName = document.getElementById('profileLevelGameName');
    const levelGameCover = document.getElementById('profileLevelGameCover');

    const orderModalElement = document.getElementById('todosJogosModal');

    const orderModal = orderModalElement
        ? bootstrap.Modal.getOrCreateInstance(orderModalElement)
        : null;

    const orderList = document.getElementById('profileOrderList');
    const orderSaveButton = document.getElementById('profileOrderSave');
    const orderStatus = document.getElementById('profileOrderStatus');

    const detailModalElement = document.getElementById('perfilJogoNivelModal');

    const detailModal = detailModalElement
        ? bootstrap.Modal.getOrCreateInstance(detailModalElement)
        : null;

    const detailGameCover = document.getElementById('profileDetailGameCover');
    const detailGameName = document.getElementById('profileDetailGameName');
    const detailLevelName = document.getElementById('profileDetailLevelName');
    const detailLevelDescription = document.getElementById('profileDetailLevelDescription');
    const detailLevelRange = document.getElementById('profileDetailLevelRange');
    const detailLevelError = document.getElementById('profileDetailLevelError');
    const detailLevelSave = document.getElementById('profileDetailLevelSave');

    const removeConfirmElement = document.getElementById('profileRemoveGameConfirm');
    const removeConfirmName = document.getElementById('profileRemoveGameName');
    const removeConfirmCancel = document.getElementById('profileRemoveGameCancel');
    const removeConfirmAccept = document.getElementById('profileRemoveGameAccept');
    const removeConfirmError = document.getElementById('profileRemoveGameError');

    const discardConfirmElement = document.getElementById('profileDiscardChangesConfirm');
    const discardConfirmTitle = document.getElementById('profileDiscardChangesTitle');
    const discardConfirmText = document.getElementById('profileDiscardChangesText');
    const discardConfirmCancel = document.getElementById('profileDiscardChangesCancel');
    const discardConfirmAccept = document.getElementById('profileDiscardChangesAccept');

    const stateElement = document.getElementById('profileGamesState');

    const initialGames = stateElement
        ? JSON.parse(stateElement.textContent)
        : [];

    let persistedGames = criarMapaJogos(initialGames);
    let workingGames = clonarMapaJogos(persistedGames);

    let currentManagerGame = null;
    let currentDetailGame = null;
    let lastSearchResults = [];
    let searchTimer = null;
    let searchController = null;
    let gamesResizeTimer = null;

    let managerDirty = false;
    let managerSaved = false;
    let orderDirty = false;
    let profileReloadRequired = false;
    let openingDetailFromOrder = false;
    let returnToOrder = false;

    let draggingItem = null;
    let dropTarget = null;
    let dropBefore = true;
    let editingProfile = false;
    let pendingRemove = null;
    let pendingDiscard = null;

    function atualizarContadorBio() {
        if (!bioInput || !bioCounter) {
            return;
        }

        const quantidade = bioInput.value.length;
        const limite = Number(bioInput.maxLength) || 400;

        bioCounter.textContent = quantidade + ' / ' + limite;
        bioCounter.classList.toggle('is-limit', quantidade >= limite);
    }

    function ajustarBio() {
        if (!bioInput) {
            return;
        }

        bioInput.style.height = 'auto';

        const alturaCompleta = Math.max(bioInput.scrollHeight, 56);
        const alturaRecolhida = 84;
        const excede = alturaCompleta > alturaRecolhida + 1;

        bioInput.style.height = editingProfile || !excede
            ? alturaCompleta + 'px'
            : alturaRecolhida + 'px';

        if (bioToggle) {
            bioToggle.hidden = editingProfile || !excede;
            bioToggle.textContent = 'Ver mais';
            bioToggle.setAttribute('aria-expanded', 'false');
        }

        if (bioCounter) {
            bioCounter.hidden = !editingProfile;
        }
    }

    function ativarEdicaoPerfil(focarBio = false) {
        if (
            editingProfile
            || !nicknameInput
            || !bioInput
            || !profileEditButton
            || !profileEditIcon
        ) {
            return;
        }

        editingProfile = true;
        nicknameInput.removeAttribute('readonly');
        bioInput.removeAttribute('readonly');

        if (bioEmojiWrap) {
            bioEmojiWrap.hidden = false;
        }

        atualizarContadorBio();
        ajustarBio();

        profileEditIcon.classList.remove('bi-pencil-fill');
        profileEditIcon.classList.add('bi-check-lg');
        profileEditButton.title = 'Salvar alterações';

        if (focarBio) {
            bioInput.focus();
            const posicao = bioInput.value.length;
            bioInput.setSelectionRange(posicao, posicao);
        } else {
            nicknameInput.focus();
        }
    }

    function fecharSeletorEmojiBio() {
        if (!bioEmojiPicker || !bioEmojiButton) {
            return;
        }

        bioEmojiPicker.hidden = true;
        bioEmojiButton.setAttribute('aria-expanded', 'false');
    }

    function inserirEmojiBio(emoji) {
        if (!bioInput || !emoji) {
            return;
        }

        const inicio = bioInput.selectionStart ?? bioInput.value.length;
        const fim = bioInput.selectionEnd ?? inicio;
        const novoValor = bioInput.value.slice(0, inicio)
            + emoji
            + bioInput.value.slice(fim);

        if (novoValor.length > bioInput.maxLength) {
            return;
        }

        bioInput.value = novoValor;
        const novaPosicao = inicio + emoji.length;
        bioInput.focus();
        bioInput.setSelectionRange(novaPosicao, novaPosicao);
        atualizarContadorBio();
        ajustarBio();
    }

    function abrirConfirmacaoDescarte(tipo) {
        if (!discardConfirmElement || !discardConfirmTitle || !discardConfirmText) {
            return false;
        }

        pendingDiscard = tipo;

        if (tipo === 'manager') {
            discardConfirmTitle.textContent = 'Descartar alterações nos jogos?';
            discardConfirmText.textContent = 'As alterações que ainda não foram salvas em Meus Jogos serão perdidas.';
        } else {
            discardConfirmTitle.textContent = 'Descartar nova ordem?';
            discardConfirmText.textContent = 'A nova ordem dos jogos ainda não foi salva e será perdida.';
        }

        discardConfirmElement.hidden = false;
        return true;
    }

    function fecharConfirmacaoDescarte() {
        if (!discardConfirmElement) {
            return;
        }

        discardConfirmElement.hidden = true;
        pendingDiscard = null;
    }

    function restaurarOrdemJogosPersistida() {
        if (!orderList) {
            return;
        }

        const itens = Array.from(
            orderList.querySelectorAll('.profile-order-item')
        );

        itens.sort(function(a, b) {
            const jogoA = persistedGames.get(Number(a.dataset.gameId));
            const jogoB = persistedGames.get(Number(b.dataset.gameId));

            return (jogoA?.ordem ?? Number.MAX_SAFE_INTEGER)
                - (jogoB?.ordem ?? Number.MAX_SAFE_INTEGER);
        });

        itens.forEach(function(item) {
            orderList.appendChild(item);
        });

        if (orderSaveButton) {
            orderSaveButton.disabled = true;
        }
    }

    function confirmarDescarte() {
        const tipo = pendingDiscard;

        if (!tipo) {
            return;
        }

        fecharConfirmacaoDescarte();

        if (tipo === 'manager') {
            managerDirty = false;
            bootstrap.Modal.getOrCreateInstance(managerModalElement).hide();
            return;
        }

        orderDirty = false;
        restaurarOrdemJogosPersistida();

        if (orderModal) {
            orderModal.hide();
        }
    }

    function criarMapaJogos(jogos) {
        const mapa = new Map();

        jogos.forEach(function(jogo) {
            mapa.set(Number(jogo.id), {
                id: Number(jogo.id),
                nome: jogo.nome,
                capa: jogo.capa,

                nivel:
                    jogo.nivel !== null
                        ? Number(jogo.nivel)
                        : null,

                ordem:
                    jogo.ordem !== null
                        ? Number(jogo.ordem)
                        : null,

                updateUrl: jogo.update_url,
                removeUrl: jogo.remove_url
            });
        });

        return mapa;
    }

    function clonarMapaJogos(mapa) {
        const copia = new Map();

        mapa.forEach(function(jogo, id) {
            copia.set(id, {
                ...jogo
            });
        });

        return copia;
    }

    function mostrarStatus(element, mensagem, tipo) {
        if (!element) {
            return;
        }

        element.textContent = mensagem;

        element.classList.remove(
            'success',
            'error'
        );

        if (tipo) {
            element.classList.add(tipo);
        }
    }

    function abrirConfirmacaoRemocao(nome, dados) {
        if (!removeConfirmElement || !removeConfirmName || !removeConfirmAccept) {
            return false;
        }

        pendingRemove = dados;
        removeConfirmName.textContent = nome;
        removeConfirmAccept.disabled = false;
        removeConfirmAccept.textContent = 'Remover';

        if (removeConfirmError) {
            removeConfirmError.hidden = true;
            removeConfirmError.textContent = '';
        }

        removeConfirmElement.hidden = false;

        if (removeConfirmCancel) {
            removeConfirmCancel.focus();
        }

        return true;
    }

    function fecharConfirmacaoRemocao() {
        if (!removeConfirmElement) {
            return;
        }

        removeConfirmElement.hidden = true;
        pendingRemove = null;

        if (removeConfirmError) {
            removeConfirmError.hidden = true;
            removeConfirmError.textContent = '';
        }
    }

    async function confirmarRemocao() {
        if (!pendingRemove || !removeConfirmAccept) {
            return;
        }

        if (pendingRemove.type === 'form') {
            pendingRemove.form.dataset.confirmed = '1';
            removeConfirmAccept.disabled = true;
            removeConfirmAccept.textContent = 'Removendo...';
            pendingRemove.form.requestSubmit();

            return;
        }

        if (pendingRemove.type !== 'order') {
            return;
        }

        const item = pendingRemove.item;
        const removeButton = pendingRemove.button;

        removeConfirmAccept.disabled = true;
        removeConfirmAccept.textContent = 'Removendo...';
        removeButton.disabled = true;

        try {
            const data = await requisicaoJson(item.dataset.removeUrl, {
                method: 'DELETE'
            }, csrf);

            const id = Number(item.dataset.gameId);

            persistedGames.delete(id);
            workingGames.delete(id);
            item.remove();

            profileReloadRequired = true;

            mostrarStatus(
                orderStatus,
                data.message || 'Jogo removido.',
                'success'
            );

            if (orderSaveButton) {
                orderDirty = ordemJogosFoiAlterada();
                orderSaveButton.disabled = !orderDirty;
            }

            fecharConfirmacaoRemocao();

        } catch (error) {
            removeButton.disabled = false;
            removeConfirmAccept.disabled = false;
            removeConfirmAccept.textContent = 'Remover';

            if (removeConfirmError) {
                removeConfirmError.textContent = error.message;
                removeConfirmError.hidden = false;
            }
        }
    }

    function atualizarContadorJogos() {
        if (!managerCount) {
            return;
        }

        const quantidade = workingGames.size;

        managerCount.textContent =
            quantidade === 1
                ? '1 jogo no perfil'
                : quantidade + ' jogos no perfil';
    }

    function atualizarJogosVisiveis() {
        if (!profileGamesList || !profileViewAllButton) {
            return;
        }

        const cards = Array.from(
            profileGamesList.querySelectorAll(
                '.profile-game-card'
            )
        );

        if (cards.length === 0) {
            profileViewAllButton.hidden = true;

            return;
        }

        const gridStyle = window.getComputedStyle(
            profileGamesList
        );

        const colunas = gridStyle
            .gridTemplateColumns
            .split(' ')
            .filter(Boolean)
            .length;

        const limite = Math.max(
            1,
            Math.min(
                colunas,
                cards.length
            )
        );

        cards.forEach(function(card, index) {
            card.hidden = index >= limite;
        });

        const total = Number(
            profileViewAllButton.dataset.totalGames
        );

        profileViewAllButton.hidden =
            total <= limite;
    }

    function mostrarInstrucaoBusca() {
        if (!searchResults) {
            return;
        }

        lastSearchResults = [];
        searchResults.innerHTML = '';

        const empty = document.createElement('div');

        empty.className = 'profile-game-search-empty';
        empty.textContent =
            'Digite pelo menos 2 letras para pesquisar.';

        searchResults.appendChild(empty);
    }

    function criarResultadoJogo(jogo) {
        const id = Number(jogo.id_jogo);
        const selecionado = workingGames.has(id);

        const atual = selecionado
            ? workingGames.get(id)
            : null;

        const item = document.createElement('div');

        item.className = 'profile-game-search-item';

        if (selecionado) {
            item.classList.add('selected');
        }

        const card = document.createElement('button');

        card.type = 'button';
        card.className = 'profile-game-search-card';

        const image = document.createElement('img');

        image.src = jogo.capa;
        image.alt = jogo.nome;

        const info = document.createElement('div');

        info.className = 'profile-game-search-info';

        const name = document.createElement('strong');

        name.textContent = jogo.nome;

        const status = document.createElement('span');

        if (selecionado) {
            const nivel = atual.nivel;

            status.textContent = nivel
                ? NOMES_NIVEIS_JOGO[nivel]
                : 'Nível não definido';

            status.className =
                'profile-game-search-level';

        } else {
            status.textContent =
                'Adicionar ao perfil';

            status.className =
                'profile-game-search-add';
        }

        info.appendChild(name);
        info.appendChild(status);

        card.appendChild(image);
        card.appendChild(info);

        card.addEventListener('click', function() {
            abrirPopupNivel(jogo);
        });

        item.appendChild(card);

        return item;
    }

    function renderizarResultados() {
        if (!searchResults) {
            return;
        }

        searchResults.innerHTML = '';

        if (lastSearchResults.length === 0) {
            const empty = document.createElement('div');

            empty.className = 'profile-game-search-empty';
            empty.textContent = 'Nenhum jogo encontrado.';

            searchResults.appendChild(empty);

            return;
        }

        lastSearchResults.forEach(function(jogo) {
            searchResults.appendChild(
                criarResultadoJogo(jogo)
            );
        });
    }

    function abrirPopupNivel(jogo) {
        if (
            !levelPopup
            || !levelRange
            || !levelName
            || !levelGameName
            || !levelGameCover
        ) {
            return;
        }

        const id = Number(jogo.id_jogo);
        const atual = workingGames.get(id);

        currentManagerGame = {
            id: id,
            nome: jogo.nome,
            capa: jogo.capa,

            nivel:
                atual && atual.nivel
                    ? atual.nivel
                    : 1,

            ordem:
                atual
                    ? atual.ordem
                    : null,

            updateUrl:
                atual
                    ? atual.updateUrl
                    : null,

            removeUrl:
                atual
                    ? atual.removeUrl
                    : null
        };

        levelGameName.textContent = jogo.nome;
        levelGameCover.src = jogo.capa;
        levelGameCover.alt = jogo.nome;

        levelRange.value =
            currentManagerGame.nivel;

        if (levelRemove) {
            levelRemove.hidden =
                !workingGames.has(id);
        }

        atualizarVisualNivel(
            levelRange,
            levelName,
            levelDescription
        );

        levelPopup.hidden = false;
    }

    function fecharPopupNivel() {
        if (!levelPopup) {
            return;
        }

        levelPopup.hidden = true;
        currentManagerGame = null;
    }

    async function buscarJogos(termo) {
        if (!searchResults) {
            return;
        }

        if (searchController) {
            searchController.abort();
        }

        const controller = new AbortController();
        searchController = controller;
        searchResults.innerHTML = '';

        const loading = document.createElement('div');

        loading.className = 'profile-game-search-empty';
        loading.textContent = 'Buscando jogos...';

        searchResults.appendChild(loading);

        try {
            const url = new URL(
                searchUrl,
                window.location.origin
            );

            url.searchParams.set(
                'q',
                termo
            );

            const response = await fetch(
                url.toString(),
                {
                    headers: {
                        'Accept': 'application/json'
                    },

                    signal: controller.signal
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    mensagemDaResposta(
                        data,
                        'Não foi possível buscar os jogos.'
                    )
                );
            }

            if (searchController !== controller) {
                return;
            }

            lastSearchResults = data.jogos || [];
            renderizarResultados();

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            searchResults.innerHTML = '';

            const message =
                document.createElement('div');

            message.className =
                'profile-game-search-empty';

            message.textContent =
                error.message;

            searchResults.appendChild(message);
        }
    }

    function abrirDetalheJogo(trigger) {
        if (
            !detailModal
            || !detailGameCover
            || !detailGameName
            || !detailLevelRange
            || !detailLevelName
        ) {
            return;
        }

        currentDetailGame = {
            id: Number(trigger.dataset.gameId),
            nome: trigger.dataset.name,
            capa: trigger.dataset.cover,

            nivel:
                trigger.dataset.level
                    ? Number(trigger.dataset.level)
                    : 1,

            updateUrl:
            trigger.dataset.updateUrl
        };

        detailGameCover.src =
            currentDetailGame.capa;

        detailGameCover.alt =
            currentDetailGame.nome;

        detailGameName.textContent =
            currentDetailGame.nome;

        detailLevelRange.value =
            currentDetailGame.nivel;

        if (detailLevelError) {
            detailLevelError.hidden = true;
            detailLevelError.textContent = '';
        }

        atualizarVisualNivel(
            detailLevelRange,
            detailLevelName,
            detailLevelDescription
        );

        detailModal.show();
    }

    function atualizarNivelNosBotoes(id, nivel) {
        document
            .querySelectorAll(
                '[data-profile-game-detail][data-game-id="'
                + id
                + '"]'
            )
            .forEach(function(button) {
                button.dataset.level = nivel;
            });
    }

    function idsDaOrdem() {
        if (!orderList) {
            return [];
        }

        return Array
            .from(
                orderList.querySelectorAll(
                    '.profile-order-item'
                )
            )
            .map(function(item) {
                return Number(
                    item.dataset.gameId
                );
            });
    }

    function ordemJogosFoiAlterada() {
        const atual = idsDaOrdem();
        const salva = Array.from(persistedGames.values())
            .sort(function(a, b) {
                return (a.ordem ?? Number.MAX_SAFE_INTEGER)
                    - (b.ordem ?? Number.MAX_SAFE_INTEGER);
            })
            .map(function(jogo) {
                return jogo.id;
            });

        return atual.length !== salva.length
            || atual.some(function(id, index) {
                return id !== salva[index];
            });
    }

    function limparIndicadorDrop() {
        if (!orderList) {
            return;
        }

        orderList
            .querySelectorAll(
                '.drop-before, .drop-after'
            )
            .forEach(function(item) {
                item.classList.remove(
                    'drop-before',
                    'drop-after'
                );
            });

        dropTarget = null;
    }

    function mostrarIndicadorDrop(destino) {
        limparIndicadorDrop();

        if (!destino) {
            return;
        }

        dropTarget = destino.element;
        dropBefore = destino.before;

        dropTarget.classList.add(
            dropBefore
                ? 'drop-before'
                : 'drop-after'
        );
    }

    function encontrarDestinoDrop(container, x, y) {
        const items = [
            ...container.querySelectorAll(
                '.profile-order-item:not(.dragging)'
            )
        ];

        if (items.length === 0) {
            return null;
        }

        const dados = items.map(function(item) {
            const box = item.getBoundingClientRect();

            return {
                element: item,
                box: box,
                centerX: box.left + box.width / 2,
                centerY: box.top + box.height / 2
            };
        });

        const linhas = [];

        dados
            .sort(function(a, b) {
                if (
                    Math.abs(
                        a.box.top - b.box.top
                    ) < 10
                ) {
                    return a.box.left - b.box.left;
                }

                return a.box.top - b.box.top;
            })
            .forEach(function(dado) {
                let linha = linhas.find(
                    function(itemLinha) {
                        return (
                            Math.abs(
                                itemLinha.top
                                - dado.box.top
                            )
                            < dado.box.height / 2
                        );
                    }
                );

                if (!linha) {
                    linha = {
                        top: dado.box.top,
                        centerY: dado.centerY,
                        items: []
                    };

                    linhas.push(linha);
                }

                linha.items.push(dado);
            });

        linhas.sort(function(a, b) {
            return a.top - b.top;
        });

        let linhaEscolhida = linhas[0];

        let menorDistancia = Math.abs(
            y - linhas[0].centerY
        );

        linhas.forEach(function(linha) {
            const distancia = Math.abs(
                y - linha.centerY
            );

            if (distancia < menorDistancia) {
                menorDistancia = distancia;
                linhaEscolhida = linha;
            }
        });

        linhaEscolhida.items.sort(function(a, b) {
            return a.box.left - b.box.left;
        });

        for (
            let i = 0;
            i < linhaEscolhida.items.length;
            i++
        ) {
            const item =
                linhaEscolhida.items[i];

            if (x < item.centerX) {
                return {
                    element: item.element,
                    before: true
                };
            }
        }

        const ultimo =
            linhaEscolhida.items[
            linhaEscolhida.items.length - 1
                ];

        return {
            element: ultimo.element,
            before: false
        };
    }

    function moverItemComAnimacao(container, mover) {
        const items = [
            ...container.querySelectorAll(
                '.profile-order-item:not(.dragging)'
            )
        ];

        const posicoes = new Map();

        items.forEach(function(item) {
            posicoes.set(
                item,
                item.getBoundingClientRect()
            );
        });

        mover();

        items.forEach(function(item) {
            const antes =
                posicoes.get(item);

            const depois =
                item.getBoundingClientRect();

            const distanciaX =
                antes.left - depois.left;

            const distanciaY =
                antes.top - depois.top;

            if (
                distanciaX === 0
                && distanciaY === 0
            ) {
                return;
            }

            item.animate(
                [
                    {
                        transform:
                            'translate('
                            + distanciaX
                            + 'px, '
                            + distanciaY
                            + 'px)'
                    },
                    {
                        transform:
                            'translate(0, 0)'
                    }
                ],
                {
                    duration: 160,
                    easing: 'ease-out'
                }
            );
        });
    }

    profilePage.addEventListener('submit', function(event) {
        const form = event.target.closest('[data-profile-remove-form]');

        if (!form || form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();

        abrirConfirmacaoRemocao(
            form.dataset.gameName || 'Este jogo',
            {
                type: 'form',
                form: form
            }
        );
    });

    if (removeConfirmCancel) {
        removeConfirmCancel.addEventListener(
            'click',
            fecharConfirmacaoRemocao
        );
    }

    if (removeConfirmAccept) {
        removeConfirmAccept.addEventListener(
            'click',
            confirmarRemocao
        );
    }

    if (removeConfirmElement) {
        removeConfirmElement.addEventListener('click', function(event) {
            if (event.target === removeConfirmElement) {
                fecharConfirmacaoRemocao();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape') {
                return;
            }

            if (
                !removeConfirmElement.hidden
                && !(removeConfirmAccept && removeConfirmAccept.disabled)
            ) {
                fecharConfirmacaoRemocao();
                return;
            }

            if (discardConfirmElement && !discardConfirmElement.hidden) {
                fecharConfirmacaoDescarte();
            }
        });
    }

    if (bioInput) {
        bioInput.addEventListener('input', function() {
            atualizarContadorBio();
            ajustarBio();
        });

        atualizarContadorBio();
        ajustarBio();
    }

    if (
        profileEditButton
        && profileForm
        && nicknameInput
        && bioInput
        && profileEditIcon
    ) {
        profileEditButton.addEventListener('click', function() {
            if (!editingProfile) {
                ativarEdicaoPerfil();
                return;
            }

            profileForm.requestSubmit();
        });

        bioInput.addEventListener('click', function() {
            if (!editingProfile) {
                ativarEdicaoPerfil(true);
            }
        });
    }

    if (bioEmojiButton && bioEmojiPicker) {
        bioEmojiButton.addEventListener('click', function(event) {
            event.stopPropagation();
            const abrir = bioEmojiPicker.hidden;
            bioEmojiPicker.hidden = !abrir;
            bioEmojiButton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        });

        bioEmojiPicker.addEventListener('click', function(event) {
            const option = event.target.closest('[data-emoji]');

            if (!option) {
                return;
            }

            inserirEmojiBio(option.dataset.emoji);
        });

        document.addEventListener('click', function(event) {
            if (bioEmojiWrap && !bioEmojiWrap.contains(event.target)) {
                fecharSeletorEmojiBio();
            }
        });
    }

    if (discardConfirmCancel) {
        discardConfirmCancel.addEventListener('click', fecharConfirmacaoDescarte);
    }

    if (discardConfirmAccept) {
        discardConfirmAccept.addEventListener('click', confirmarDescarte);
    }

    if (discardConfirmElement) {
        discardConfirmElement.addEventListener('click', function(event) {
            if (event.target === discardConfirmElement) {
                fecharConfirmacaoDescarte();
            }
        });
    }

    if (managerModalElement) {
        managerModalElement.addEventListener(
            'show.bs.modal',
            function() {
                workingGames =
                    clonarMapaJogos(
                        persistedGames
                    );

                managerDirty = false;
                managerSaved = false;

                mostrarStatus(
                    gamesSaveStatus,
                    ''
                );

                atualizarContadorJogos();

                if (gameSearch) {
                    gameSearch.value = '';
                }

                mostrarInstrucaoBusca();
                fecharPopupNivel();

                if (gameSearch) {
                    setTimeout(function() {
                        gameSearch.focus();
                    }, 200);
                }
            }
        );

        managerModalElement.addEventListener(
            'hide.bs.modal',
            function(event) {
                if (!managerDirty) {
                    return;
                }

                event.preventDefault();
                abrirConfirmacaoDescarte('manager');
            }
        );

        managerModalElement.addEventListener(
            'hidden.bs.modal',
            function() {
                if (managerSaved) {
                    window.location.reload();
                }
            }
        );
    }

    if (gameSearch) {
        gameSearch.addEventListener(
            'input',
            function() {
                const termo =
                    gameSearch.value.trim();

                clearTimeout(searchTimer);

                if (termo.length < 2) {
                    mostrarInstrucaoBusca();

                    return;
                }

                searchTimer = setTimeout(
                    function() {
                        buscarJogos(termo);
                    },
                    ATRASO_BUSCA
                );
            }
        );
    }

    if (levelRange) {
        levelRange.addEventListener(
            'input',
            function() {
                atualizarVisualNivel(
                    levelRange,
                    levelName,
                    levelDescription,
                    levelDescriptions
                );
            }
        );
    }

    if (levelConfirm) {
        levelConfirm.addEventListener(
            'click',
            function() {
                if (!currentManagerGame) {
                    return;
                }

                const id =
                    currentManagerGame.id;

                const anterior =
                    workingGames.get(id);

                workingGames.set(
                    id,
                    {
                        ...currentManagerGame,

                        nivel:
                            Number(
                                levelRange.value
                            ),

                        ordem:
                            anterior
                                ? anterior.ordem
                                : null,

                        updateUrl:
                            anterior
                                ? anterior.updateUrl
                                : null,

                        removeUrl:
                            anterior
                                ? anterior.removeUrl
                                : null
                    }
                );

                managerDirty = true;

                atualizarContadorJogos();
                fecharPopupNivel();
                renderizarResultados();
            }
        );
    }

    if (levelRemove) {
        levelRemove.addEventListener(
            'click',
            function() {
                if (!currentManagerGame) {
                    return;
                }

                workingGames.delete(
                    currentManagerGame.id
                );

                managerDirty = true;

                atualizarContadorJogos();
                fecharPopupNivel();
                renderizarResultados();
            }
        );
    }

    if (levelPopupClose) {
        levelPopupClose.addEventListener(
            'click',
            fecharPopupNivel
        );
    }

    if (levelCancel) {
        levelCancel.addEventListener(
            'click',
            fecharPopupNivel
        );
    }

    if (levelPopup) {
        levelPopup.addEventListener(
            'click',
            function(event) {
                if (event.target === levelPopup) {
                    fecharPopupNivel();
                }
            }
        );
    }

    if (gamesSaveButton) {
        gamesSaveButton.addEventListener(
            'click',
            async function() {
                const jogos = [];
                const niveis = {};

                workingGames.forEach(
                    function(jogo, id) {
                        jogos.push(id);
                        niveis[id] = jogo.nivel;
                    }
                );

                gamesSaveButton.disabled = true;

                mostrarStatus(
                    gamesSaveStatus,
                    'Salvando...'
                );

                try {
                    const data =
                        await requisicaoJson(
                            updateGamesUrl,
                            {
                                method: 'PATCH',

                                body:
                                    JSON.stringify({
                                        jogos: jogos,
                                        niveis: niveis
                                    })
                            },
                            csrf
                        );

                    persistedGames =
                        clonarMapaJogos(
                            workingGames
                        );

                    managerDirty = false;
                    managerSaved = true;

                    mostrarStatus(
                        gamesSaveStatus,
                        data.message
                        || 'Jogos atualizados.',
                        'success'
                    );

                    renderizarResultados();

                    if (managerModalElement) {
                        bootstrap.Modal
                            .getOrCreateInstance(
                                managerModalElement
                            )
                            .hide();
                    }

                } catch (error) {
                    mostrarStatus(
                        gamesSaveStatus,
                        error.message,
                        'error'
                    );

                } finally {
                    gamesSaveButton.disabled = false;
                }
            }
        );
    }

    document.addEventListener(
        'click',
        function(event) {
            const trigger =
                event.target.closest(
                    '[data-profile-game-detail]'
                );

            if (!trigger) {
                return;
            }

            const insideOrder =
                trigger.closest(
                    '#todosJogosModal'
                );

            if (!insideOrder) {
                abrirDetalheJogo(trigger);

                return;
            }

            if (!orderModal || !orderModalElement) {
                return;
            }

            openingDetailFromOrder = true;
            returnToOrder = true;

            orderModalElement.addEventListener(
                'hidden.bs.modal',
                function abrirDepois() {
                    openingDetailFromOrder = false;

                    abrirDetalheJogo(trigger);
                },
                {
                    once: true
                }
            );

            orderModal.hide();
        }
    );

    if (detailLevelRange) {
        detailLevelRange.addEventListener(
            'input',
            function() {
                atualizarVisualNivel(
                    detailLevelRange,
                    detailLevelName,
                    detailLevelDescription,
                    levelDescriptions
                );
            }
        );
    }

    if (detailLevelSave) {
        detailLevelSave.addEventListener(
            'click',
            async function() {
                if (
                    !currentDetailGame
                    || !currentDetailGame.updateUrl
                ) {
                    if (detailLevelError) {
                        detailLevelError.textContent =
                            'Não foi possível identificar o jogo.';

                        detailLevelError.hidden = false;
                    }

                    return;
                }

                detailLevelSave.disabled = true;

                if (detailLevelError) {
                    detailLevelError.hidden = true;
                }

                try {
                    await requisicaoJson(
                        currentDetailGame.updateUrl,
                        {
                            method: 'PATCH',

                            body:
                                JSON.stringify({
                                    nivel:
                                        Number(
                                            detailLevelRange.value
                                        )
                                })
                        },
                        csrf
                    );

                    const nivel =
                        Number(
                            detailLevelRange.value
                        );

                    if (
                        persistedGames.has(
                            currentDetailGame.id
                        )
                    ) {
                        persistedGames.get(
                            currentDetailGame.id
                        ).nivel = nivel;
                    }

                    atualizarNivelNosBotoes(
                        currentDetailGame.id,
                        nivel
                    );

                    detailModal.hide();

                } catch (error) {
                    if (detailLevelError) {
                        detailLevelError.textContent =
                            error.message;

                        detailLevelError.hidden = false;
                    }

                } finally {
                    detailLevelSave.disabled = false;
                }
            }
        );
    }

    if (detailModalElement) {
        detailModalElement.addEventListener(
            'hidden.bs.modal',
            function() {
                currentDetailGame = null;

                if (returnToOrder && orderModal) {
                    returnToOrder = false;

                    orderModal.show();
                }
            }
        );
    }

    if (orderList) {
        orderList.addEventListener(
            'dragstart',
            function(event) {
                const item =
                    event.target.closest(
                        '.profile-order-item'
                    );

                if (!item) {
                    return;
                }

                draggingItem = item;

                item.classList.add(
                    'dragging'
                );

                orderList.classList.add(
                    'is-dragging'
                );

                event.dataTransfer.effectAllowed =
                    'move';

                event.dataTransfer.setData(
                    'text/plain',
                    item.dataset.gameId || ''
                );
            }
        );

        orderList.addEventListener(
            'dragover',
            function(event) {
                if (!draggingItem) {
                    return;
                }

                event.preventDefault();

                event.dataTransfer.dropEffect =
                    'move';

                const destino =
                    encontrarDestinoDrop(
                        orderList,
                        event.clientX,
                        event.clientY
                    );

                if (
                    destino
                    && (
                        destino.element !== dropTarget
                        || destino.before !== dropBefore
                    )
                ) {
                    mostrarIndicadorDrop(destino);
                }
            }
        );

        orderList.addEventListener(
            'dragleave',
            function(event) {
                if (!draggingItem) {
                    return;
                }

                if (
                    !orderList.contains(
                        event.relatedTarget
                    )
                ) {
                    limparIndicadorDrop();
                }
            }
        );

        orderList.addEventListener(
            'drop',
            function(event) {
                if (!draggingItem) {
                    return;
                }

                event.preventDefault();

                const destino =
                    encontrarDestinoDrop(
                        orderList,
                        event.clientX,
                        event.clientY
                    );

                if (!destino) {
                    limparIndicadorDrop();

                    return;
                }

                moverItemComAnimacao(
                    orderList,
                    function() {
                        if (destino.before) {
                            orderList.insertBefore(
                                draggingItem,
                                destino.element
                            );

                        } else {
                            orderList.insertBefore(
                                draggingItem,
                                destino.element.nextSibling
                            );
                        }
                    }
                );

                orderDirty = ordemJogosFoiAlterada();

                if (orderSaveButton) {
                    orderSaveButton.disabled = !orderDirty;
                }

                limparIndicadorDrop();
            }
        );

        orderList.addEventListener(
            'dragend',
            function() {
                if (draggingItem) {
                    draggingItem.classList.remove(
                        'dragging'
                    );
                }

                orderList.classList.remove(
                    'is-dragging'
                );

                limparIndicadorDrop();

                draggingItem = null;
            }
        );

        orderList.addEventListener(
            'click',
            async function(event) {
                const item =
                    event.target.closest(
                        '.profile-order-item'
                    );

                if (!item) {
                    return;
                }

                const removeButton =
                    event.target.closest(
                        '[data-order-remove]'
                    );

                if (!removeButton) {
                    return;
                }

                abrirConfirmacaoRemocao(
                    item.dataset.gameName || 'Este jogo',
                    {
                        type: 'order',
                        item: item,
                        button: removeButton
                    }
                );
            }
        );
    }

    if (orderSaveButton) {
        orderSaveButton.addEventListener(
            'click',
            async function() {
                const jogos = idsDaOrdem();

                if (jogos.length === 0) {
                    return;
                }

                orderSaveButton.disabled = true;

                mostrarStatus(
                    orderStatus,
                    'Salvando ordem...'
                );

                try {
                    const data =
                        await requisicaoJson(
                            updateOrderUrl,
                            {
                                method: 'PATCH',

                                body:
                                    JSON.stringify({
                                        jogos: jogos
                                    })
                            },
                            csrf
                        );

                    jogos.forEach(
                        function(id, index) {
                            if (
                                persistedGames.has(id)
                            ) {
                                persistedGames
                                    .get(id)
                                    .ordem =
                                    index + 1;
                            }
                        }
                    );

                    orderDirty = false;
                    profileReloadRequired = true;

                    if (orderSaveButton) {
                        orderSaveButton.disabled = true;
                    }

                    mostrarStatus(
                        orderStatus,
                        data.message
                        || 'Ordem atualizada.',
                        'success'
                    );

                    if (orderModal) {
                        orderModal.hide();
                    }

                } catch (error) {
                    mostrarStatus(
                        orderStatus,
                        error.message,
                        'error'
                    );

                } finally {
                    orderSaveButton.disabled = !orderDirty;
                }
            }
        );
    }

    if (orderModalElement) {
        orderModalElement.addEventListener('show.bs.modal', function() {
            if (!orderDirty && orderSaveButton) {
                orderSaveButton.disabled = true;
            }
        });

        orderModalElement.addEventListener(
            'hide.bs.modal',
            function(event) {
                if (openingDetailFromOrder || !orderDirty) {
                    return;
                }

                event.preventDefault();
                abrirConfirmacaoDescarte('order');
            }
        );

        orderModalElement.addEventListener(
            'hidden.bs.modal',
            function() {
                if (
                    profileReloadRequired
                    && !openingDetailFromOrder
                    && !returnToOrder
                ) {
                    window.location.reload();
                }
            }
        );
    }

    atualizarJogosVisiveis();

    window.addEventListener(
        'resize',
        function() {
            clearTimeout(gamesResizeTimer);

            gamesResizeTimer = setTimeout(
                function() {
                    atualizarJogosVisiveis();

                    if (!editingProfile) {
                        ajustarBio();
                    }
                },
                120
            );
        }
    );
}


function iniciarOrdenacaoPlataformas() {
    const modal = document.getElementById('plataformasModal');
    const form = document.getElementById('profilePlatformsForm');
    const list = document.getElementById('profilePlatformOrderList');
    const main = document.getElementById('profilePlatformManagerMain');
    const addPanel = document.getElementById('profilePlatformAddPanel');
    const addOpen = document.getElementById('profilePlatformAddOpen');
    const addBack = document.getElementById('profilePlatformAddBack');
    const addEmpty = document.getElementById('profilePlatformAddEmpty');
    const status = document.getElementById('profilePlatformOrderStatus');
    const saveButton = document.getElementById('profilePlatformsSave');

    if (!modal || !form || !list || !main || !addPanel || !addOpen || !addBack || !saveButton) {
        return;
    }

    const optionInputs = Array.from(form.querySelectorAll('input[name="plataformas[]"]'));
    const addOptions = Array.from(form.querySelectorAll('[data-platform-add]'));
    const selectionInputs = new Map(optionInputs.map(function(input) {
        return [Number(input.value), input];
    }));
    const optionsById = new Map(addOptions.map(function(option) {
        return [Number(option.dataset.platformId), option];
    }));

    let draggingItem = null;
    let dropTarget = null;
    let dropBefore = true;
    let initialOrder = [];
    let initialSelection = [];
    let submitting = false;

    function obterItens() {
        return Array.from(list.querySelectorAll('.profile-platform-order-item'));
    }

    function obterOrdem() {
        return obterItens().map(function(item) {
            return Number(item.dataset.platformId);
        });
    }

    function obterSelecao() {
        return optionInputs.filter(function(input) {
            return input.checked;
        }).map(function(input) {
            return Number(input.value);
        }).sort(function(a, b) {
            return a - b;
        });
    }

    function listasIguais(a, b) {
        return a.length === b.length && a.every(function(valor, indice) {
            return valor === b[indice];
        });
    }

    function haAlteracoes() {
        return !listasIguais(obterOrdem(), initialOrder)
            || !listasIguais(obterSelecao(), initialSelection);
    }

    function atualizarEstado() {
        const alterado = haAlteracoes();
        saveButton.disabled = !alterado;

        if (status) {
            status.textContent = alterado ? 'Alterações pendentes.' : '';
        }
    }

    function atualizarOpcoesAdicao() {
        let disponiveis = 0;

        addOptions.forEach(function(option) {
            const id = Number(option.dataset.platformId);
            const input = selectionInputs.get(id);
            const selecionada = Boolean(input?.checked);

            option.disabled = selecionada;

            if (!selecionada) {
                disponiveis += 1;
            }
        });

        if (addEmpty) {
            addEmpty.hidden = disponiveis > 0;
        }
    }

    function limparIndicador() {
        obterItens().forEach(function(item) {
            item.classList.remove('drop-before', 'drop-after');
        });

        dropTarget = null;
    }

    function criarItem(option) {
        const id = Number(option.dataset.platformId);
        const item = document.createElement('div');
        const orderInput = document.createElement('input');
        const cover = document.createElement('div');
        const image = document.createElement('img');
        const removeButton = document.createElement('button');
        const removeIcon = document.createElement('i');
        const name = document.createElement('span');

        item.className = 'profile-platform-card profile-platform-order-item';
        item.draggable = true;
        item.dataset.platformId = String(id);

        orderInput.type = 'hidden';
        orderInput.name = 'ordem_plataformas[]';
        orderInput.value = String(id);

        cover.className = 'profile-platform-order-cover';

        image.src = option.dataset.platformIcon || '';
        image.alt = option.dataset.platformName || 'Plataforma';

        removeButton.type = 'button';
        removeButton.className = 'profile-platform-remove';
        removeButton.dataset.platformRemove = '';
        removeButton.title = 'Remover plataforma';
        removeButton.setAttribute(
            'aria-label',
            'Remover ' + (option.dataset.platformName || 'plataforma')
        );

        removeIcon.className = 'bi bi-trash3';
        removeButton.appendChild(removeIcon);
        cover.append(image, removeButton);

        name.textContent = option.dataset.platformName || 'Plataforma';
        item.append(orderInput, cover, name);

        return item;
    }

    function mostrarLista() {
        addPanel.hidden = true;
        main.hidden = false;
    }

    function mostrarAdicao() {
        atualizarOpcoesAdicao();
        main.hidden = true;
        addPanel.hidden = false;
    }

    function restaurarEstadoInicial() {
        obterItens().forEach(function(item) {
            item.remove();
        });

        const selecionadas = new Set(initialSelection);

        optionInputs.forEach(function(input) {
            input.checked = selecionadas.has(Number(input.value));
        });

        initialOrder.forEach(function(id) {
            const option = optionsById.get(id);

            if (option) {
                list.insertBefore(criarItem(option), addOpen);
            }
        });

        atualizarOpcoesAdicao();
        mostrarLista();
        saveButton.disabled = true;

        if (status) {
            status.textContent = '';
        }
    }

    addOpen.addEventListener('click', mostrarAdicao);
    addBack.addEventListener('click', mostrarLista);

    addOptions.forEach(function(option) {
        option.addEventListener('click', function() {
            const id = Number(option.dataset.platformId);
            const input = selectionInputs.get(id);

            if (!input || input.checked) {
                return;
            }

            input.checked = true;
            list.insertBefore(criarItem(option), addOpen);
            atualizarOpcoesAdicao();
            atualizarEstado();
        });
    });

    list.addEventListener('click', function(event) {
        const removeButton = event.target.closest('[data-platform-remove]');

        if (!removeButton) {
            return;
        }

        const item = removeButton.closest('.profile-platform-order-item');

        if (!item) {
            return;
        }

        const id = Number(item.dataset.platformId);
        const input = selectionInputs.get(id);

        if (input) {
            input.checked = false;
        }

        item.remove();
        atualizarOpcoesAdicao();
        atualizarEstado();
    });

    list.addEventListener('dragstart', function(event) {
        if (event.target.closest('[data-platform-remove]')) {
            event.preventDefault();
            return;
        }

        const item = event.target.closest('.profile-platform-order-item');

        if (!item) {
            return;
        }

        draggingItem = item;
        item.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.dataset.platformId || '');
    });

    list.addEventListener('dragover', function(event) {
        if (!draggingItem) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';

        const target = event.target.closest('.profile-platform-order-item:not(.dragging)');
        limparIndicador();

        if (!target) {
            return;
        }

        const box = target.getBoundingClientRect();
        dropTarget = target;
        dropBefore = event.clientX < box.left + box.width / 2;
        target.classList.add(dropBefore ? 'drop-before' : 'drop-after');
    });

    list.addEventListener('drop', function(event) {
        if (!draggingItem) {
            return;
        }

        event.preventDefault();

        if (!dropTarget) {
            list.insertBefore(draggingItem, addOpen);
        } else if (dropBefore) {
            list.insertBefore(draggingItem, dropTarget);
        } else {
            list.insertBefore(draggingItem, dropTarget.nextSibling);
        }

        limparIndicador();
        atualizarEstado();
    });

    list.addEventListener('dragend', function() {
        if (draggingItem) {
            draggingItem.classList.remove('dragging');
        }

        draggingItem = null;
        limparIndicador();
    });

    modal.addEventListener('show.bs.modal', function() {
        initialOrder = obterOrdem();
        initialSelection = obterSelecao();
        submitting = false;
        saveButton.disabled = true;
        atualizarOpcoesAdicao();
        mostrarLista();

        if (status) {
            status.textContent = '';
        }
    });

    form.addEventListener('submit', function(event) {
        if (!haAlteracoes()) {
            event.preventDefault();
            return;
        }

        submitting = true;
        saveButton.disabled = true;
    });

    modal.addEventListener('hidden.bs.modal', function() {
        if (!submitting) {
            restaurarEstadoInicial();
        }

        submitting = false;
        draggingItem = null;
        limparIndicador();
    });
}


function iniciarAtualizacoesChat() {
    const url = document.body.dataset.chatUpdatesUrl;

    if (!url || !document.body.dataset.userId) {
        return;
    }

    let ultimoId = null;
    let consultando = false;
    const mensagensConhecidas = new Set();

    window.addEventListener('squad:mensagem-usuario', function(event) {
        const idMensagem = Number(event.detail?.mensagem?.id_mensagem || 0);

        if (idMensagem <= 0) {
            return;
        }

        mensagensConhecidas.add(idMensagem);

        if (ultimoId !== null) {
            ultimoId = Math.max(ultimoId, idMensagem);
        }
    });

    function atualizarBadge(total) {
        if (window.SquadRealtime?.atualizarBadgeChat) {
            window.SquadRealtime.atualizarBadgeChat(total);
            return;
        }

        const badge = document.getElementById('sidebarChatBadge');

        if (!badge) {
            return;
        }

        const quantidade = Number(total) || 0;

        badge.hidden = quantidade <= 0;
        badge.textContent = quantidade > 99 ? '99+' : String(quantidade);
        badge.setAttribute('aria-label', quantidade + ' notificações');
    }

    async function consultar() {
        if (consultando) {
            return;
        }

        consultando = true;

        try {
            const endereco = new URL(url, window.location.origin);

            if (ultimoId !== null) {
                endereco.searchParams.set('apos', String(ultimoId));
            }

            const response = await fetch(endereco, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            atualizarBadge(data.notificacoes);

            if (Array.isArray(data.mensagens)) {
                data.mensagens.forEach(function(evento) {
                    const idMensagem = Number(
                        evento?.mensagem?.id_mensagem || 0
                    );

                    if (idMensagem <= 0 || mensagensConhecidas.has(idMensagem)) {
                        return;
                    }

                    mensagensConhecidas.add(idMensagem);
                    evento.notificacoes = data.notificacoes;

                    window.dispatchEvent(
                        new CustomEvent('squad:mensagem-usuario', {
                            detail: evento
                        })
                    );
                });
            }

            const novoUltimoId = Number(data.ultimo_id || 0);

            if (novoUltimoId > 0) {
                ultimoId = ultimoId === null
                    ? novoUltimoId
                    : Math.max(ultimoId, novoUltimoId);
            }
        } catch (error) {
            return;
        } finally {
            consultando = false;
        }
    }

    consultar();
    window.setInterval(consultar, 3000);
}
