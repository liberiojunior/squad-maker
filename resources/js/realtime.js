import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
});

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

const idUsuario = Number(document.body.dataset.userId || 0);

if (idUsuario > 0) {
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

            if (window.location.pathname.startsWith('/usuarios/')) {
                window.location.reload();
            }
        });
}

window.dispatchEvent(new CustomEvent('squad:echo-pronto'));
