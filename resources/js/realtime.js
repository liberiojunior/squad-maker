import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const body = document.body;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const idUsuario = Number(body.dataset.userId || 0);
const key = body.dataset.reverbKey || import.meta.env.VITE_REVERB_APP_KEY || '';
const scheme = body.dataset.reverbScheme
    || import.meta.env.VITE_REVERB_SCHEME
    || (window.location.protocol === 'https:' ? 'https' : 'http');

let host = body.dataset.reverbHost
    || import.meta.env.VITE_REVERB_HOST
    || window.location.hostname;

if (
    ['127.0.0.1', 'localhost'].includes(host)
    && !['127.0.0.1', 'localhost'].includes(window.location.hostname)
) {
    host = window.location.hostname;
}

const port = Number(
    body.dataset.reverbPort
    || import.meta.env.VITE_REVERB_PORT
    || (scheme === 'https' ? 443 : 8080)
);

function atualizarBadgeChat(total) {
    const badge = document.getElementById('sidebarChatBadge');

    if (!badge || total === null || total === undefined) {
        return;
    }

    const quantidade = Number(total) || 0;

    badge.hidden = quantidade <= 0;
    badge.textContent = quantidade > 99 ? '99+' : String(quantidade);
    badge.setAttribute('aria-label', quantidade + ' notificações');
}

window.SquadRealtime = {
    atualizarBadgeChat,
};

if (idUsuario > 0 && key) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    window.Echo
        .private(`usuarios.${idUsuario}`)
        .listen('.mensagem.enviada', function(evento) {
            atualizarBadgeChat(evento.notificacoes);

            window.dispatchEvent(new CustomEvent('squad:mensagem-usuario', {
                detail: evento,
            }));
        })
        .listen('.amizade.solicitada', function(evento) {
            atualizarBadgeChat(evento.notificacoes);

            window.dispatchEvent(new CustomEvent('squad:amizade', {
                detail: {
                    tipo: 'solicitada',
                    ...evento,
                },
            }));
        })
        .listen('.amizade.atualizada', function(evento) {
            atualizarBadgeChat(evento.notificacoes);

            window.dispatchEvent(new CustomEvent('squad:amizade', {
                detail: {
                    tipo: 'atualizada',
                    ...evento,
                },
            }));
        });
}

window.dispatchEvent(new CustomEvent('squad:echo-pronto'));
