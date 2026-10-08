document.addEventListener('DOMContentLoaded', function() {
    const modalSelector = '.profile-post-image-modal';
    const modais = Array.from(document.querySelectorAll(modalSelector));
    const conteudosOriginais = new Map();

    modais.forEach(function(modal) {
        conteudosOriginais.set(
            modal,
            modal.querySelector('.modal-content')
        );
    });

    function mostrarErroReacao(mensagem) {
        if (window.SquadAviso && typeof window.SquadAviso.mostrar === 'function') {
            window.SquadAviso.mostrar('danger', mensagem);
            return;
        }

        console.error(mensagem);
    }

    const deleteConfirm = document.getElementById('postDeleteConfirm');
    const deleteConfirmCancel = document.getElementById('postDeleteConfirmCancel');
    const deleteConfirmAccept = document.getElementById('postDeleteConfirmAccept');
    let pendingDeleteForm = null;

    function fecharConfirmacaoExclusao() {
        if (!deleteConfirm) {
            return;
        }

        deleteConfirm.hidden = true;
        pendingDeleteForm = null;
    }

    document.addEventListener('submit', function(event) {
        const form = event.target.closest('[data-post-delete-form]');

        if (!form || form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingDeleteForm = form;

        const dropdown = form.closest('.dropdown')
            ?.querySelector('[data-bs-toggle="dropdown"]');

        if (dropdown) {
            bootstrap.Dropdown.getInstance(dropdown)?.hide();
        }

        if (deleteConfirm) {
            deleteConfirm.hidden = false;
        }
    });

    if (deleteConfirmCancel) {
        deleteConfirmCancel.addEventListener('click', fecharConfirmacaoExclusao);
    }

    if (deleteConfirmAccept) {
        deleteConfirmAccept.addEventListener('click', function() {
            if (!pendingDeleteForm) {
                return;
            }

            const form = pendingDeleteForm;
            form.dataset.confirmed = '1';
            deleteConfirmAccept.disabled = true;
            deleteConfirmAccept.textContent = 'Excluindo...';
            form.requestSubmit();
        });
    }

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', function(event) {
            if (event.target === deleteConfirm) {
                fecharConfirmacaoExclusao();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !deleteConfirm.hidden) {
                fecharConfirmacaoExclusao();
            }
        });
    }

    function abrirEdicaoPublicacao(trigger) {
        const content = trigger.closest('.modal-content');
        const area = content?.querySelector('[data-post-edit-area]');
        const display = area?.querySelector('[data-post-edit-display]');
        const form = area?.querySelector('[data-post-edit-form]');
        const textarea = form?.querySelector('[data-post-edit-description]');

        if (!area || !form || !textarea) {
            return;
        }

        if (display) {
            display.hidden = true;
        }

        form.hidden = false;
        area.classList.add('is-editing');
        textarea.focus();
        textarea.setSelectionRange(
            textarea.value.length,
            textarea.value.length
        );
    }

    function cancelarEdicaoPublicacao(button) {
        const area = button.closest('[data-post-edit-area]');
        const display = area?.querySelector('[data-post-edit-display]');
        const form = area?.querySelector('[data-post-edit-form]');
        const textarea = form?.querySelector('[data-post-edit-description]');

        if (!area || !form || !textarea) {
            return;
        }

        textarea.value = textarea.dataset.postEditOriginal ?? '';
        form.hidden = true;
        area.classList.remove('is-editing');

        if (display) {
            display.hidden = display.textContent.trim() === '';
        }

        fecharEmojisEdicao();
    }

    function fecharEmojisEdicao(excecao = null) {
        document.querySelectorAll('[data-post-edit-emoji-wrap]').forEach(function(wrap) {
            if (wrap === excecao) {
                return;
            }

            const picker = wrap.querySelector('[data-post-edit-emoji-picker]');
            const button = wrap.querySelector('[data-post-edit-emoji-button]');

            if (picker) {
                picker.hidden = true;
            }

            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('click', function(event) {
        const editTrigger = event.target.closest('[data-post-edit-trigger]');

        if (editTrigger) {
            abrirEdicaoPublicacao(editTrigger);
            return;
        }

        const editCancel = event.target.closest('[data-post-edit-cancel]');

        if (editCancel) {
            cancelarEdicaoPublicacao(editCancel);
            return;
        }

        const emojiButton = event.target.closest('[data-post-edit-emoji-button]');

        if (emojiButton) {
            const wrap = emojiButton.closest('[data-post-edit-emoji-wrap]');
            const picker = wrap?.querySelector('[data-post-edit-emoji-picker]');

            if (!wrap || !picker) {
                return;
            }

            const abrir = picker.hidden;
            fecharEmojisEdicao(wrap);
            picker.hidden = !abrir;
            emojiButton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            return;
        }

        const emoji = event.target.closest('[data-post-edit-emoji-picker] [data-emoji]');

        if (emoji) {
            const form = emoji.closest('form');
            const textarea = form?.querySelector('[data-post-edit-description]');

            if (!textarea) {
                return;
            }

            const inicio = textarea.selectionStart ?? textarea.value.length;
            const fim = textarea.selectionEnd ?? inicio;
            const novoValor = textarea.value.slice(0, inicio)
                + emoji.dataset.emoji
                + textarea.value.slice(fim);

            if (novoValor.length <= textarea.maxLength) {
                textarea.value = novoValor;
                const posicao = inicio + emoji.dataset.emoji.length;
                textarea.focus();
                textarea.setSelectionRange(posicao, posicao);
            }

            fecharEmojisEdicao();
            return;
        }

        if (!event.target.closest('[data-post-edit-emoji-wrap]')) {
            fecharEmojisEdicao();
        }
    });

    function atualizarReacao(postId, reagiu, quantidade) {
        const forms = document.querySelectorAll(
            '.profile-post-reaction-form[data-post-id="' + postId + '"]'
        );

        forms.forEach(function(form) {
            form.action = reagiu
                ? form.dataset.reactionDestroyUrl
                : form.dataset.reactionStoreUrl;

            let method = form.querySelector('input[name="_method"]');

            if (reagiu && !method) {
                method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);
            } else if (!reagiu && method) {
                method.remove();
            }

            const button = form.querySelector('[data-post-reaction-button]');
            const icon = button?.querySelector('i');

            if (!button || !icon) {
                return;
            }

            button.classList.toggle('active', reagiu);
            button.setAttribute('aria-pressed', reagiu ? 'true' : 'false');
            button.title = reagiu ? 'Remover curtida' : 'Curtir publicação';
            icon.classList.toggle('bi-heart-fill', reagiu);
            icon.classList.toggle('bi-heart', !reagiu);

            button.classList.remove('profile-post-reaction-pop');
            requestAnimationFrame(function() {
                button.classList.add('profile-post-reaction-pop');
            });
        });

        document
            .querySelectorAll('[data-post-reaction-count="' + postId + '"]')
            .forEach(function(count) {
                count.textContent = quantidade;
            });
    }

    document.addEventListener('submit', async function(event) {
        const form = event.target.closest('.profile-post-reaction-form');

        if (!form) {
            return;
        }

        event.preventDefault();

        const postId = form.dataset.postId;
        const forms = document.querySelectorAll(
            '.profile-post-reaction-form[data-post-id="' + postId + '"]'
        );

        forms.forEach(function(item) {
            const button = item.querySelector('[data-post-reaction-button]');
            if (button) {
                button.disabled = true;
            }
        });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Não foi possível atualizar a curtida.');
            }

            const data = await response.json();
            atualizarReacao(postId, data.reagiu, data.reacoes_count);
        } catch (error) {
            mostrarErroReacao(error.message);
        } finally {
            forms.forEach(function(item) {
                const button = item.querySelector('[data-post-reaction-button]');
                if (button) {
                    button.disabled = false;
                }
            });
        }
    });

    function removerClassesDeTransicao(conteudo) {
        if (!conteudo) {
            return;
        }

        conteudo.classList.remove(
            'gallery-slide-out-left',
            'gallery-slide-out-right',
            'gallery-slide-enter-left',
            'gallery-slide-enter-right',
            'gallery-no-transition'
        );
    }

    function restaurarConteudosOriginais() {
        const conteudos = modais
            .map(function(modal) {
                return conteudosOriginais.get(modal);
            })
            .filter(Boolean);

        conteudos.forEach(function(conteudo) {
            removerClassesDeTransicao(conteudo);
            conteudo.remove();
        });

        modais.forEach(function(modal) {
            const conteudo = conteudosOriginais.get(modal);
            const dialog = modal.querySelector('.modal-dialog');

            if (conteudo && dialog) {
                dialog.appendChild(conteudo);
            }

            modal.dataset.galleryCurrentIndex =
                String(modais.indexOf(modal));
            delete modal.dataset.gallerySwitching;
        });
    }

    function atualizarNavegacao() {
        const ocultar = modais.length <= 1;

        modais.forEach(function(modal) {
            const conteudo = conteudosOriginais.get(modal);

            if (!conteudo) {
                return;
            }

            conteudo
                .querySelectorAll('[data-post-nav]')
                .forEach(function(botao) {
                    botao.hidden = ocultar;
                });
        });
    }

    function trocarConteudo(modalHost, direcao) {
        if (
            modais.length <= 1 ||
            modalHost.dataset.gallerySwitching === '1'
        ) {
            return;
        }

        const dialogHost = modalHost.querySelector('.modal-dialog');
        const conteudoAtual = dialogHost?.querySelector('.modal-content');

        if (!dialogHost || !conteudoAtual) {
            return;
        }

        let indiceAtual = Number(
            modalHost.dataset.galleryCurrentIndex
        );

        if (!Number.isInteger(indiceAtual)) {
            indiceAtual = modais.indexOf(modalHost);
        }

        const deslocamento = direcao === 'previous' ? -1 : 1;
        const indiceDestino =
            (indiceAtual + deslocamento + modais.length) % modais.length;

        const modalDestino = modais[indiceDestino];
        const conteudoDestino = conteudosOriginais.get(modalDestino);

        if (
            !conteudoDestino ||
            conteudoDestino === conteudoAtual
        ) {
            return;
        }

        const dialogDestinoAtual = conteudoDestino.parentElement;

        if (!dialogDestinoAtual) {
            return;
        }

        const saida =
            direcao === 'previous'
                ? 'gallery-slide-out-right'
                : 'gallery-slide-out-left';

        const entrada =
            direcao === 'previous'
                ? 'gallery-slide-enter-left'
                : 'gallery-slide-enter-right';

        modalHost.dataset.gallerySwitching = '1';
        conteudoAtual.classList.add(saida);

        window.setTimeout(function() {
            removerClassesDeTransicao(conteudoDestino);
            conteudoDestino.classList.add(
                'gallery-no-transition',
                entrada
            );

            /*
             * Troca os nós de lugar em vez de duplicar HTML.
             * Assim IDs dos painéis de edição e menus permanecem únicos.
             */
            dialogHost.appendChild(conteudoDestino);
            dialogDestinoAtual.appendChild(conteudoAtual);

            removerClassesDeTransicao(conteudoAtual);

            modalHost.dataset.galleryCurrentIndex =
                String(indiceDestino);

            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    conteudoDestino.classList.remove(
                        'gallery-no-transition'
                    );

                    requestAnimationFrame(function() {
                        conteudoDestino.classList.remove(entrada);

                        window.setTimeout(function() {
                            delete modalHost.dataset.gallerySwitching;
                        }, 230);
                    });
                });
            });
        }, 170);
    }

    document.addEventListener('click', function(event) {
        const botao = event.target.closest('[data-post-nav]');

        if (!botao) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const modal = botao.closest(modalSelector);

        if (!modal) {
            return;
        }

        trocarConteudo(
            modal,
            botao.dataset.postNav
        );
    });

    document.addEventListener('keydown', function(event) {
        if (
            event.key !== 'ArrowLeft' &&
            event.key !== 'ArrowRight'
        ) {
            return;
        }

        const alvo = event.target;

        if (
            alvo instanceof HTMLInputElement ||
            alvo instanceof HTMLTextAreaElement ||
            alvo instanceof HTMLSelectElement
        ) {
            return;
        }

        const modalAberto = document.querySelector(
            modalSelector + '.show'
        );

        if (!modalAberto) {
            return;
        }

        event.preventDefault();

        trocarConteudo(
            modalAberto,
            event.key === 'ArrowLeft'
                ? 'previous'
                : 'next'
        );
    });

    modais.forEach(function(modal, indice) {
        modal.dataset.galleryCurrentIndex = String(indice);

        modal.addEventListener('show.bs.modal', function() {
            restaurarConteudosOriginais();
            modal.dataset.galleryCurrentIndex = String(indice);
        });

        modal.addEventListener('hidden.bs.modal', function() {
            restaurarConteudosOriginais();
        });
    });



    function iniciarEnquadramentoInteligente() {
        /*
         * Compatibilidade com publicações antigas:
         * o zoom automático fica restrito à miniatura quadrada.
         * Publicações novas já são salvas em 3:4 pelo editor.
         */
        const seletor =
            '.profile-post-image-open img';

        const cache = new Map();

        function linhaPreta(
            pixels,
            largura,
            altura,
            indice,
            horizontal
        ) {
            let escuros = 0;
            let total = 0;

            const tamanho = horizontal
                ? largura
                : altura;

            const passo = Math.max(
                1,
                Math.floor(tamanho / 90)
            );

            for (
                let posicao = 0;
                posicao < tamanho;
                posicao += passo
            ) {
                const x = horizontal
                    ? posicao
                    : indice;

                const y = horizontal
                    ? indice
                    : posicao;

                const offset =
                    (y * largura + x) * 4;

                const r = pixels[offset];
                const g = pixels[offset + 1];
                const b = pixels[offset + 2];
                const a = pixels[offset + 3];

                if (a < 220) {
                    continue;
                }

                total += 1;

                if (
                    r <= 24
                    && g <= 24
                    && b <= 24
                ) {
                    escuros += 1;
                }
            }

            return (
                total >= 6
                && escuros / total >= 0.97
            );
        }

        function medirBorda(
            pixels,
            largura,
            altura,
            lado
        ) {
            const horizontal =
                lado === 'top'
                || lado === 'bottom';

            const limite = Math.floor(
                (horizontal ? altura : largura)
                * 0.18
            );

            let tamanho = 0;

            for (
                let deslocamento = 0;
                deslocamento < limite;
                deslocamento += 1
            ) {
                let indice;

                if (lado === 'top') {
                    indice = deslocamento;
                } else if (lado === 'bottom') {
                    indice =
                        altura - 1 - deslocamento;
                } else if (lado === 'left') {
                    indice = deslocamento;
                } else {
                    indice =
                        largura - 1 - deslocamento;
                }

                if (
                    !linhaPreta(
                        pixels,
                        largura,
                        altura,
                        indice,
                        horizontal
                    )
                ) {
                    break;
                }

                tamanho += 1;
            }

            return tamanho;
        }

        function calcularZoom(img) {
            if (
                !img.naturalWidth
                || !img.naturalHeight
            ) {
                return 1;
            }

            const maiorLado = 180;
            const proporcao =
                img.naturalWidth
                / img.naturalHeight;

            let largura;
            let altura;

            if (proporcao >= 1) {
                largura = maiorLado;
                altura = Math.max(
                    1,
                    Math.round(
                        maiorLado / proporcao
                    )
                );
            } else {
                altura = maiorLado;
                largura = Math.max(
                    1,
                    Math.round(
                        maiorLado * proporcao
                    )
                );
            }

            const canvas =
                document.createElement('canvas');

            canvas.width = largura;
            canvas.height = altura;

            const context =
                canvas.getContext(
                    '2d',
                    {
                        willReadFrequently: true
                    }
                );

            if (!context) {
                return 1;
            }

            try {
                context.drawImage(
                    img,
                    0,
                    0,
                    largura,
                    altura
                );

                const pixels =
                    context.getImageData(
                        0,
                        0,
                        largura,
                        altura
                    ).data;

                const topo = medirBorda(
                    pixels,
                    largura,
                    altura,
                    'top'
                );

                const baixo = medirBorda(
                    pixels,
                    largura,
                    altura,
                    'bottom'
                );

                const esquerda = medirBorda(
                    pixels,
                    largura,
                    altura,
                    'left'
                );

                const direita = medirBorda(
                    pixels,
                    largura,
                    altura,
                    'right'
                );

                const corteVertical = Math.max(
                    topo,
                    baixo
                );

                const corteHorizontal = Math.max(
                    esquerda,
                    direita
                );

                const minimoVertical =
                    Math.max(
                        2,
                        Math.round(altura * 0.015)
                    );

                const minimoHorizontal =
                    Math.max(
                        2,
                        Math.round(largura * 0.015)
                    );

                let zoomVertical = 1;
                let zoomHorizontal = 1;

                if (
                    corteVertical
                    >= minimoVertical
                    && altura
                        - 2 * corteVertical
                        > 0
                ) {
                    zoomVertical =
                        altura
                        / (
                            altura
                            - 2 * corteVertical
                        );
                }

                if (
                    corteHorizontal
                    >= minimoHorizontal
                    && largura
                        - 2 * corteHorizontal
                        > 0
                ) {
                    zoomHorizontal =
                        largura
                        / (
                            largura
                            - 2 * corteHorizontal
                        );
                }

                return Math.min(
                    1.28,
                    Math.max(
                        1,
                        zoomVertical,
                        zoomHorizontal
                    )
                );
            } catch (error) {
                return 1;
            }
        }

        function aplicar(img) {
            const chave =
                img.currentSrc
                || img.src;

            if (!chave) {
                return;
            }

            if (cache.has(chave)) {
                img.style.setProperty(
                    '--post-smart-zoom',
                    String(cache.get(chave))
                );

                return;
            }

            const zoom = calcularZoom(img);

            cache.set(chave, zoom);

            img.style.setProperty(
                '--post-smart-zoom',
                String(zoom)
            );
        }

        document
            .querySelectorAll(seletor)
            .forEach(function(img) {
                if (img.complete) {
                    aplicar(img);
                    return;
                }

                img.addEventListener(
                    'load',
                    function() {
                        aplicar(img);
                    },
                    {
                        once: true
                    }
                );
            });
    }

    iniciarEnquadramentoInteligente();

    atualizarNavegacao();
});
