document.addEventListener('DOMContentLoaded', function() {
    iniciarAvisos();
    iniciarPerfil();
});

function iniciarAvisos() {
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(function(alert) {
        let tempo = 5000;

        if (alert.classList.contains('alert-success')) {
            tempo = 4000;
        }

        if (alert.classList.contains('alert-danger')) {
            tempo = 7000;
        }

        setTimeout(function() {
            alert.classList.add('alert-hide');

            setTimeout(function() {
                alert.remove();
            }, 350);
        }, tempo);
    });
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

    const profileForm = document.getElementById('profileForm');
    const nicknameInput = document.getElementById('nicknameInput');
    const bioInput = document.getElementById('bioInput');
    const profileEditButton = document.getElementById('profileEditButton');
    const profileEditIcon = document.getElementById('profileEditIcon');
    const avatarForm = document.getElementById('avatarForm');
    const avatarInput = document.getElementById('avatarInput');

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
    const detailLevelRange = document.getElementById('profileDetailLevelRange');
    const detailLevelError = document.getElementById('profileDetailLevelError');
    const detailLevelSave = document.getElementById('profileDetailLevelSave');

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
    let editingProfile = false;

    function criarMapaJogos(jogos) {
        const mapa = new Map();

        jogos.forEach(function(jogo) {
            mapa.set(Number(jogo.id), {
                id: Number(jogo.id),
                nome: jogo.nome,
                capa: jogo.capa,
                nivel: jogo.nivel !== null
                    ? Number(jogo.nivel)
                    : null,
                ordem: jogo.ordem !== null
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

    function updateRangeVisual(range, nameElement) {
        if (!range || !nameElement) {
            return;
        }

        const value = Number(range.value);
        const percent = ((value - 1) / 4) * 100;
        const wrapper = range.closest('.game-level-range-wrap');

        nameElement.textContent = levelNames[value];
        nameElement.style.color = levelColors[value];

        if (wrapper) {
            wrapper.style.setProperty(
                '--level-color',
                levelColors[value]
            );

            wrapper.style.setProperty(
                '--level-percent',
                percent + '%'
            );
        }

        range.style.setProperty(
            '--level-color',
            levelColors[value]
        );

        range.style.setProperty(
            '--level-percent',
            percent + '%'
        );
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

    function mensagemDaResposta(data, fallback) {
        if (data && data.message) {
            return data.message;
        }

        if (data && data.errors) {
            const primeiroErro = Object.values(
                data.errors
            )[0];

            if (
                Array.isArray(primeiroErro)
                && primeiroErro.length > 0
            ) {
                return primeiroErro[0];
            }
        }

        return fallback;
    }

    async function requisicaoJson(url, options = {}) {
        const response = await fetch(
            url,
            {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    ...(options.headers || {})
                }
            }
        );

        const data = await response
            .json()
            .catch(function() {
                return {};
            });

        if (!response.ok) {
            throw new Error(
                mensagemDaResposta(
                    data,
                    'Não foi possível concluir a operação.'
                )
            );
        }

        return data;
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
        if (
            !profileGamesList
            || !profileViewAllButton
        ) {
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

        empty.className =
            'profile-game-search-empty';

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

        item.className =
            'profile-game-search-item';

        if (selecionado) {
            item.classList.add('selected');
        }

        const card = document.createElement('button');

        card.type = 'button';
        card.className =
            'profile-game-search-card';

        const image = document.createElement('img');

        image.src = jogo.capa;
        image.alt = jogo.nome;

        const info = document.createElement('div');

        info.className =
            'profile-game-search-info';

        const name = document.createElement('strong');

        name.textContent = jogo.nome;

        const status = document.createElement('span');

        if (selecionado) {
            const nivel = atual.nivel;

            status.textContent = nivel
                ? levelNames[nivel]
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

        card.addEventListener(
            'click',
            function() {
                abrirPopupNivel(jogo);
            }
        );

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

            empty.className =
                'profile-game-search-empty';

            empty.textContent =
                'Nenhum jogo encontrado.';

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
            nivel: atual && atual.nivel
                ? atual.nivel
                : 1,
            ordem: atual
                ? atual.ordem
                : null,
            updateUrl: atual
                ? atual.updateUrl
                : null,
            removeUrl: atual
                ? atual.removeUrl
                : null
        };

        levelGameName.textContent =
            jogo.nome;

        levelGameCover.src =
            jogo.capa;

        levelGameCover.alt =
            jogo.nome;

        levelRange.value =
            currentManagerGame.nivel;

        if (levelRemove) {
            levelRemove.hidden =
                !workingGames.has(id);
        }

        updateRangeVisual(
            levelRange,
            levelName
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

        searchController =
            new AbortController();

        searchResults.innerHTML = '';

        const loading = document.createElement('div');

        loading.className =
            'profile-game-search-empty';

        loading.textContent =
            'Buscando jogos...';

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
                        'Accept':
                            'application/json'
                    },
                    signal:
                    searchController.signal
                }
            );

            const data =
                await response.json();

            if (!response.ok) {
                throw new Error(
                    mensagemDaResposta(
                        data,
                        'Não foi possível buscar os jogos.'
                    )
                );
            }

            lastSearchResults =
                data.jogos || [];

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

            searchResults.appendChild(
                message
            );
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
            id: Number(
                trigger.dataset.gameId
            ),
            nome:
            trigger.dataset.name,
            capa:
            trigger.dataset.cover,
            nivel:
                trigger.dataset.level
                    ? Number(
                        trigger.dataset.level
                    )
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

        updateRangeVisual(
            detailLevelRange,
            detailLevelName
        );

        detailModal.show();
    }

    function atualizarNivelNosBotoes(
        id,
        nivel
    ) {
        document
            .querySelectorAll(
                '[data-profile-game-detail][data-game-id="'
                + id
                + '"]'
            )
            .forEach(function(button) {
                button.dataset.level =
                    nivel;
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

    function elementoDepoisDoCursor(
        container,
        x,
        y
    ) {
        const items = [
            ...container.querySelectorAll(
                '.profile-order-item:not(.dragging)'
            )
        ];

        let itemMaisProximo = null;
        let menorDistancia = Infinity;

        items.forEach(function(item) {
            const box =
                item.getBoundingClientRect();

            const centroX =
                box.left
                + box.width / 2;

            const centroY =
                box.top
                + box.height / 2;

            const distancia = Math.hypot(
                x - centroX,
                y - centroY
            );

            if (
                distancia
                < menorDistancia
            ) {
                menorDistancia =
                    distancia;

                itemMaisProximo =
                    item;
            }
        });

        if (!itemMaisProximo) {
            return null;
        }

        const box =
            itemMaisProximo
                .getBoundingClientRect();

        const mesmaLinha =
            y >= box.top
            && y <= box.bottom;

        const antes =
            mesmaLinha
                ? x
                < box.left
                + box.width / 2
                : y
                < box.top
                + box.height / 2;

        return {
            element:
            itemMaisProximo,
            before:
            antes
        };
    }

    if (
        profileEditButton
        && profileForm
        && nicknameInput
        && bioInput
        && profileEditIcon
    ) {
        profileEditButton.addEventListener(
            'click',
            function() {
                if (!editingProfile) {
                    editingProfile = true;

                    nicknameInput.removeAttribute(
                        'readonly'
                    );

                    bioInput.removeAttribute(
                        'readonly'
                    );

                    nicknameInput.focus();

                    profileEditIcon.classList.remove(
                        'bi-pencil-fill'
                    );

                    profileEditIcon.classList.add(
                        'bi-check-lg'
                    );

                    profileEditButton.title =
                        'Salvar alterações';

                    return;
                }

                profileForm.requestSubmit();
            }
        );
    }

    if (avatarInput && avatarForm) {
        avatarInput.addEventListener(
            'change',
            function() {
                if (
                    avatarInput.files.length
                    > 0
                ) {
                    avatarForm.requestSubmit();
                }
            }
        );
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

                const descartar =
                    window.confirm(
                        'Descartar as alterações que ainda não foram salvas?'
                    );

                if (!descartar) {
                    event.preventDefault();

                    return;
                }

                managerDirty = false;
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

                clearTimeout(
                    searchTimer
                );

                if (termo.length < 2) {
                    mostrarInstrucaoBusca();

                    return;
                }

                searchTimer = setTimeout(
                    function() {
                        buscarJogos(
                            termo
                        );
                    },
                    300
                );
            }
        );
    }

    if (levelRange) {
        levelRange.addEventListener(
            'input',
            function() {
                updateRangeVisual(
                    levelRange,
                    levelName
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
                        nivel: Number(
                            levelRange.value
                        ),
                        ordem: anterior
                            ? anterior.ordem
                            : null,
                        updateUrl: anterior
                            ? anterior.updateUrl
                            : null,
                        removeUrl: anterior
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
                if (
                    event.target
                    === levelPopup
                ) {
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

                        niveis[id] =
                            jogo.nivel;
                    }
                );

                gamesSaveButton.disabled =
                    true;

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
                                        jogos:
                                        jogos,
                                        niveis:
                                        niveis
                                    })
                            }
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
                } catch (error) {
                    mostrarStatus(
                        gamesSaveStatus,
                        error.message,
                        'error'
                    );
                } finally {
                    gamesSaveButton.disabled =
                        false;
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
                abrirDetalheJogo(
                    trigger
                );

                return;
            }

            if (
                !orderModal
                || !orderModalElement
            ) {
                return;
            }

            openingDetailFromOrder =
                true;

            returnToOrder =
                true;

            orderModalElement
                .addEventListener(
                    'hidden.bs.modal',
                    function abrirDepois() {
                        openingDetailFromOrder =
                            false;

                        abrirDetalheJogo(
                            trigger
                        );
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
                updateRangeVisual(
                    detailLevelRange,
                    detailLevelName
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
                    || !currentDetailGame
                        .updateUrl
                ) {
                    if (detailLevelError) {
                        detailLevelError.textContent =
                            'Não foi possível identificar o jogo.';

                        detailLevelError.hidden =
                            false;
                    }

                    return;
                }

                detailLevelSave.disabled =
                    true;

                if (detailLevelError) {
                    detailLevelError.hidden =
                        true;
                }

                try {
                    await requisicaoJson(
                        currentDetailGame
                            .updateUrl,
                        {
                            method: 'PATCH',
                            body:
                                JSON.stringify({
                                    nivel:
                                        Number(
                                            detailLevelRange
                                                .value
                                        )
                                })
                        }
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
                        ).nivel =
                            nivel;
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

                        detailLevelError.hidden =
                            false;
                    }
                } finally {
                    detailLevelSave.disabled =
                        false;
                }
            }
        );
    }

    if (detailModalElement) {
        detailModalElement.addEventListener(
            'hidden.bs.modal',
            function() {
                currentDetailGame = null;

                if (
                    returnToOrder
                    && orderModal
                ) {
                    returnToOrder =
                        false;

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

                draggingItem =
                    item;

                item.classList.add(
                    'dragging'
                );

                event.dataTransfer
                    .effectAllowed =
                    'move';
            }
        );

        orderList.addEventListener(
            'dragend',
            function() {
                if (draggingItem) {
                    draggingItem
                        .classList
                        .remove(
                            'dragging'
                        );
                }

                draggingItem =
                    null;
            }
        );

        orderList.addEventListener(
            'dragover',
            function(event) {
                event.preventDefault();

                if (!draggingItem) {
                    return;
                }

                const destino =
                    elementoDepoisDoCursor(
                        orderList,
                        event.clientX,
                        event.clientY
                    );

                if (!destino) {
                    orderList.appendChild(
                        draggingItem
                    );
                } else if (
                    destino.before
                ) {
                    orderList.insertBefore(
                        draggingItem,
                        destino.element
                    );
                } else {
                    orderList.insertBefore(
                        draggingItem,
                        destino.element
                            .nextSibling
                    );
                }

                orderDirty = true;
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

                const confirmar =
                    window.confirm(
                        'Deseja remover este jogo do seu perfil?'
                    );

                if (!confirmar) {
                    return;
                }

                removeButton.disabled =
                    true;

                try {
                    const data =
                        await requisicaoJson(
                            item.dataset
                                .removeUrl,
                            {
                                method:
                                    'DELETE'
                            }
                        );

                    const id =
                        Number(
                            item.dataset
                                .gameId
                        );

                    persistedGames.delete(
                        id
                    );

                    workingGames.delete(
                        id
                    );

                    item.remove();

                    profileReloadRequired =
                        true;

                    mostrarStatus(
                        orderStatus,
                        data.message
                        || 'Jogo removido.',
                        'success'
                    );

                    if (
                        orderSaveButton
                        && orderList
                            .querySelectorAll(
                                '.profile-order-item'
                            )
                            .length === 0
                    ) {
                        orderSaveButton.disabled =
                            true;
                    }
                } catch (error) {
                    removeButton.disabled =
                        false;

                    mostrarStatus(
                        orderStatus,
                        error.message,
                        'error'
                    );
                }
            }
        );
    }

    if (orderSaveButton) {
        orderSaveButton.addEventListener(
            'click',
            async function() {
                const jogos =
                    idsDaOrdem();

                if (jogos.length === 0) {
                    return;
                }

                orderSaveButton.disabled =
                    true;

                mostrarStatus(
                    orderStatus,
                    'Salvando ordem...'
                );

                try {
                    const data =
                        await requisicaoJson(
                            updateOrderUrl,
                            {
                                method:
                                    'PATCH',
                                body:
                                    JSON.stringify({
                                        jogos:
                                        jogos
                                    })
                            }
                        );

                    jogos.forEach(
                        function(
                            id,
                            index
                        ) {
                            if (
                                persistedGames
                                    .has(id)
                            ) {
                                persistedGames
                                    .get(id)
                                    .ordem =
                                    index + 1;
                            }
                        }
                    );

                    orderDirty =
                        false;

                    profileReloadRequired =
                        true;

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
                    orderSaveButton.disabled =
                        false;
                }
            }
        );
    }

    if (orderModalElement) {
        orderModalElement.addEventListener(
            'hide.bs.modal',
            function(event) {
                if (
                    openingDetailFromOrder
                ) {
                    return;
                }

                if (!orderDirty) {
                    return;
                }

                const descartar =
                    window.confirm(
                        'Descartar a nova ordem que ainda não foi salva?'
                    );

                if (!descartar) {
                    event.preventDefault();

                    return;
                }

                orderDirty = false;
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
            clearTimeout(
                gamesResizeTimer
            );

            gamesResizeTimer =
                setTimeout(
                    atualizarJogosVisiveis,
                    120
                );
        }
    );
}
