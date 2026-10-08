document.addEventListener('DOMContentLoaded', function() {
    iniciarBuscaChat();
    iniciarEnvioChat();
    iniciarTempoRealChat();
    iniciarPosicaoConversa();
});

function iniciarBuscaChat() {
    const input = document.getElementById('chatSearchInput');
    const empty = document.getElementById('chatSearchEmpty');

    if (!input) {
        return;
    }

    const items = Array.from(document.querySelectorAll('.chat-search-item'));
    const conversations = Array.from(document.querySelectorAll('[data-chat-conversation-user]'));
    const partners = Array.from(document.querySelectorAll('[data-chat-partner]'));

    function filtrar() {
        const termo = input.value.trim().toLocaleLowerCase('pt-BR');
        const usuariosComConversaVisivel = new Set();
        let visiveis = 0;

        items.forEach(function(item) {
            if (item.hasAttribute('data-chat-partner')) {
                return;
            }

            const texto = (item.dataset.chatSearch || '').toLocaleLowerCase('pt-BR');
            const mostrar = termo === '' || texto.includes(termo);

            item.hidden = !mostrar;

            if (mostrar) {
                visiveis++;
            }
        });

        conversations.forEach(function(item) {
            if (!item.hidden) {
                usuariosComConversaVisivel.add(item.dataset.chatConversationUser);
            }
        });

        partners.forEach(function(item) {
            const texto = (item.dataset.chatSearch || '').toLocaleLowerCase('pt-BR');
            const usuario = item.dataset.chatPartnerUser;
            const padrao = item.dataset.chatPartnerDefault === '1';

            let mostrar = padrao;

            if (termo !== '') {
                mostrar = texto.includes(termo) && !usuariosComConversaVisivel.has(usuario);
            }

            item.hidden = !mostrar;

            if (mostrar && termo !== '') {
                visiveis++;
            }
        });

        if (empty) {
            empty.hidden = termo === '' || visiveis > 0;
        }
    }

    input.addEventListener('input', filtrar);
    filtrar();
}

function iniciarEnvioChat() {
    const form = document.getElementById('chatMessageForm');
    const input = document.getElementById('chatMessageInput');

    if (!form || !input) {
        return;
    }

    const submit = form.querySelector('button[type="submit"]');
    const emojiButton = document.getElementById('chatEmojiButton');
    const emojiPicker = document.getElementById('chatEmojiPicker');

    function fecharEmojis() {
        if (!emojiButton || !emojiPicker) {
            return;
        }

        emojiPicker.hidden = true;
        emojiButton.setAttribute('aria-expanded', 'false');
    }

    function inserirEmoji(emoji) {
        if (!emoji) {
            return;
        }

        const inicio = input.selectionStart ?? input.value.length;
        const fim = input.selectionEnd ?? inicio;
        const novoValor = input.value.slice(0, inicio) + emoji + input.value.slice(fim);
        const limite = Number(input.maxLength) || 1000;

        if (novoValor.length > limite) {
            return;
        }

        input.value = novoValor;
        const novaPosicao = inicio + emoji.length;
        input.focus({ preventScroll: true });
        input.setSelectionRange(novaPosicao, novaPosicao);
        ajustarAltura();
    }

    function ajustarAltura() {
        const limite = 120;

        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, limite) + 'px';
        input.style.overflowY = input.scrollHeight > limite ? 'auto' : 'hidden';
    }

    async function enviar() {
        const texto = input.value.trim();

        if (texto === '' || submit?.disabled) {
            return;
        }

        if (submit) {
            submit.disabled = true;
        }

        try {
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };

            if (window.Echo?.socketId()) {
                headers['X-Socket-ID'] = window.Echo.socketId();
            }

            const response = await fetch(form.action, {
                method: 'POST',
                headers,
                body: new FormData(form),
            });

            const data = await response.json().catch(function() {
                return {};
            });

            if (!response.ok) {
                const primeiroErro = data.errors
                    ? Object.values(data.errors)[0]
                    : null;

                throw new Error(
                    Array.isArray(primeiroErro) && primeiroErro.length > 0
                        ? primeiroErro[0]
                        : data.message || 'Não foi possível enviar a mensagem.'
                );
            }

            input.value = '';
            ajustarAltura();
            fecharEmojis();

            if (data.mensagem) {
                adicionarMensagemNaConversa(data.mensagem);
                atualizarPreviewDaConversa(data.mensagem);
            }
        } catch (error) {
            if (
                window.SquadAviso
                && typeof window.SquadAviso.mostrar === 'function'
            ) {
                window.SquadAviso.mostrar(
                    'danger',
                    error.message
                );
            } else {
                console.error(error);
            }
        } finally {
            if (submit) {
                submit.disabled = false;
            }

            input.focus({
                preventScroll: true,
            });
        }
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        enviar();
    });

    if (emojiButton && emojiPicker) {
        emojiButton.addEventListener('click', function() {
            const abrir = emojiPicker.hidden;
            emojiPicker.hidden = !abrir;
            emojiButton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        });

        emojiPicker.addEventListener('click', function(event) {
            const button = event.target.closest('[data-emoji]');

            if (!button) {
                return;
            }

            inserirEmoji(button.dataset.emoji);
        });

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.chat-emoji-wrap')) {
                fecharEmojis();
            }
        });
    }

    input.addEventListener('input', ajustarAltura);

    input.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter' || event.shiftKey) {
            return;
        }

        event.preventDefault();
        enviar();
    });

    ajustarAltura();
}

function iniciarTempoRealChat() {
    const chatPage = document.getElementById('chatPage');

    if (!chatPage) {
        return;
    }

    const idConversa = Number(chatPage.dataset.conversationId || 0);

    window.addEventListener('squad:mensagem-usuario', function(event) {
        const evento = event.detail;

        if (!evento?.mensagem) {
            return;
        }

        const conversaMensagem = Number(evento.mensagem.id_conversa);
        const item = buscarItemConversa(conversaMensagem);

        if (conversaMensagem === idConversa) {
            adicionarMensagemNaConversa(evento.mensagem);
            atualizarPreviewDaConversa(evento.mensagem);
            marcarConversaComoLida();
            return;
        }

        if (!item) {
            window.location.reload();
            return;
        }

        atualizarPreviewDaConversa(evento.mensagem);
        incrementarNaoLidas(item);
    });

    window.addEventListener('squad:amizade', function() {
        window.location.reload();
    });
}

function adicionarMensagemNaConversa(mensagem) {
    const container = document.getElementById('chatMessages');
    const content = document.getElementById('chatMessagesContent');
    const end = document.getElementById('chatMessagesEnd');
    const chatPage = document.getElementById('chatPage');

    if (!container || !content || !end || !chatPage) {
        return;
    }

    const idConversaAtual = Number(chatPage.dataset.conversationId || 0);

    if (Number(mensagem.id_conversa) !== idConversaAtual) {
        return;
    }

    if (container.querySelector(`[data-message-id="${mensagem.id_mensagem}"]`)) {
        return;
    }

    const empty = container.querySelector('.chat-conversation-empty');

    if (empty) {
        empty.remove();
    }

    const idUsuario = Number(document.body.dataset.userId || 0);
    const row = document.createElement('div');
    const bubble = document.createElement('div');
    const texto = document.createElement('p');
    const horario = document.createElement('time');

    row.className = 'chat-message-row '
        + (Number(mensagem.id_remetente) === idUsuario ? 'mine' : 'theirs');
    row.dataset.messageId = mensagem.id_mensagem;

    bubble.className = 'chat-message-bubble';
    texto.textContent = mensagem.mensagem;

    horario.dateTime = mensagem.data_envio;
    horario.textContent = mensagem.hora;

    bubble.appendChild(texto);
    bubble.appendChild(horario);
    row.appendChild(bubble);

    content.appendChild(row);
    content.appendChild(end);
    rolarConversaParaFinal();
}

function iniciarPosicaoConversa() {
    const container = document.getElementById('chatMessages');

    if (!container) {
        return;
    }

    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            rolarConversaParaFinal();
        });
    });

    window.addEventListener('load', function() {
        rolarConversaParaFinal();
    }, {
        once: true,
    });
}

function rolarConversaParaFinal() {
    const container = document.getElementById('chatMessages');

    if (!container) {
        return;
    }

    container.scrollTop = container.scrollHeight;
}

function buscarItemConversa(idConversa) {
    return document.querySelector(
        `.chat-conversation-item[data-conversation-id="${idConversa}"]`
    );
}

function atualizarPreviewDaConversa(mensagem) {
    const item = buscarItemConversa(mensagem.id_conversa);

    if (!item) {
        return;
    }

    const preview = item.querySelector('.chat-conversation-preview');

    if (preview) {
        preview.textContent = mensagem.mensagem;
    }

    const lista = item.parentElement;

    if (lista) {
        lista.prepend(item);
    }
}

function incrementarNaoLidas(item) {
    let badge = item.querySelector('.chat-unread-badge');

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'chat-unread-badge';
        badge.textContent = '1';
        item.appendChild(badge);
        return;
    }

    const atual = Number(badge.textContent) || 0;
    const proximo = atual + 1;

    badge.textContent = proximo > 99 ? '99+' : String(proximo);
}

async function marcarConversaComoLida() {
    const chatPage = document.getElementById('chatPage');
    const url = chatPage?.dataset.readUrl;

    if (!url) {
        return;
    }

    try {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        };

        if (window.Echo?.socketId()) {
            headers['X-Socket-ID'] = window.Echo.socketId();
        }

        const response = await fetch(url, {
            method: 'PATCH',
            headers,
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();

        window.SquadRealtime?.atualizarBadgeChat(data.notificacoes);

        const item = buscarItemConversa(chatPage.dataset.conversationId);

        item?.querySelector('.chat-unread-badge')?.remove();
    } catch (error) {
        return;
    }
}
