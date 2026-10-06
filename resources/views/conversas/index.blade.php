@extends('layouts.internal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success chat-feedback">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->has('denuncia') || $errors->has('motivo') || $errors->has('descricao') || $errors->has('anexo'))
        <div class="alert alert-danger chat-feedback">
            {{ $errors->first('denuncia') ?: $errors->first('motivo') ?: $errors->first('descricao') ?: $errors->first('anexo') }}
        </div>
    @endif

    <div
        class="chat-page"
        id="chatPage"
        data-conversation-id="{{ $conversaAtual?->id_conversa }}"
        data-read-url="{{ $conversaAtual ? route('mensagens.lidas', $conversaAtual) : '' }}"
    >
        <aside class="chat-sidebar">
            <div class="chat-search">
                <i class="bi bi-search"></i>
                <input
                    type="search"
                    id="chatSearchInput"
                    placeholder="Pesquisar conversa ou amigo..."
                    autocomplete="off"
                >
            </div>

            <div class="chat-sidebar-scroll squad-scrollbar" id="chatSidebarScroll">
                @if ($solicitacoes->isNotEmpty())
                    <section class="chat-sidebar-section">
                        <h2>Solicitações</h2>

                        <div class="chat-sidebar-list">
                            @foreach ($solicitacoes as $item)
                                <article
                                    class="chat-request-card chat-search-item"
                                    data-chat-search="{{ mb_strtolower($item['usuario']->nickname) }}"
                                >
                                    <a href="{{ route('usuarios.perfil', $item['usuario']) }}" class="chat-avatar-link">
                                        <span class="chat-avatar-wrap">
                                            <img
                                                src="{{ $item['usuario']->avatar ?: asset('images/icone.png') }}"
                                                alt="{{ $item['usuario']->nickname }}"
                                                class="chat-avatar"
                                            >
                                            <span
                                                class="game-partner-presence game-partner-presence-{{ $item['presenca']['status'] }} chat-presence"
                                                title="{{ $item['presenca']['texto'] }}"
                                            ></span>
                                        </span>
                                    </a>

                                    <div class="chat-request-content">
                                        <strong>{{ $item['usuario']->nickname }}</strong>
                                        <span>quer ser seu amigo</span>

                                        <div class="chat-request-actions">
                                            <form method="POST" action="{{ route('amizades.recusar', $item['amizade']) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="chat-request-button chat-request-button-secondary">
                                                    Recusar
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('amizades.aceitar', $item['amizade']) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="chat-request-button chat-request-button-primary">
                                                    Aceitar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="chat-sidebar-section">
                    <h2>Conversas</h2>

                    <div class="chat-sidebar-list">
                        @forelse ($conversas as $item)
                            <a
                                href="{{ route('conversas.show', $item['conversa']) }}"
                                class="chat-conversation-item chat-search-item {{ $conversaAtual?->id_conversa === $item['conversa']->id_conversa ? 'active' : '' }}"
                                data-chat-search="{{ mb_strtolower($item['usuario']->nickname) }}"
                                data-chat-conversation-user="{{ $item['usuario']->id_usuario }}"
                                data-conversation-id="{{ $item['conversa']->id_conversa }}"
                            >
                                <span class="chat-avatar-wrap">
                                    <img
                                        src="{{ $item['usuario']->avatar ?: asset('images/icone.png') }}"
                                        alt="{{ $item['usuario']->nickname }}"
                                        class="chat-avatar"
                                    >
                                    <span
                                        class="game-partner-presence game-partner-presence-{{ $item['presenca']['status'] }} chat-presence"
                                        title="{{ $item['presenca']['texto'] }}"
                                    ></span>
                                </span>

                                <span class="chat-conversation-copy">
                                    <strong>{{ $item['usuario']->nickname }}</strong>
                                    <small class="chat-conversation-preview">{{ $item['ultima_mensagem']?->mensagem ?: 'Conversa iniciada' }}</small>
                                </span>

                                @if ($item['nao_lidas'] > 0)
                                    <span class="chat-unread-badge">
                                        {{ $item['nao_lidas'] > 99 ? '99+' : $item['nao_lidas'] }}
                                    </span>
                                @endif
                            </a>
                        @empty
                            <p class="chat-sidebar-empty">Nenhuma conversa ainda.</p>
                        @endforelse
                    </div>
                </section>

                <p class="chat-search-empty" id="chatSearchEmpty" hidden>Nenhum resultado encontrado.</p>
            </div>

            @if ($amigos->isNotEmpty())
                <section class="chat-partners-panel" id="chatPartnersPanel">
                    <div class="chat-partners-header">
                        <h2>Parceiros</h2>

                        @if ($amigos->count() > 3)
                            <button
                                type="button"
                                class="chat-partners-all"
                                data-bs-toggle="modal"
                                data-bs-target="#chatPartnersModal"
                            >
                                Ver todos
                            </button>
                        @endif
                    </div>

                    <div class="chat-friends-list">
                        @foreach ($amigos as $item)
                            <form
                                method="POST"
                                action="{{ route('conversas.iniciar', $item['usuario']) }}"
                                class="chat-friend-form chat-search-item"
                                data-chat-search="{{ mb_strtolower($item['usuario']->nickname) }}"
                                data-chat-partner
                                data-chat-partner-user="{{ $item['usuario']->id_usuario }}"
                                data-chat-partner-default="{{ $loop->index < 3 ? '1' : '0' }}"
                                @if ($loop->index >= 3) hidden @endif
                            >
                                @csrf

                                <button type="submit" class="chat-friend-item" title="Conversar com {{ $item['usuario']->nickname }}">
                                    <span class="chat-avatar-wrap">
                                        <img
                                            src="{{ $item['usuario']->avatar ?: asset('images/icone.png') }}"
                                            alt="{{ $item['usuario']->nickname }}"
                                            class="chat-avatar"
                                        >
                                        <span
                                            class="game-partner-presence game-partner-presence-{{ $item['presenca']['status'] }} chat-presence"
                                            title="{{ $item['presenca']['texto'] }}"
                                        ></span>
                                    </span>

                                    <span>{{ $item['usuario']->nickname }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>

        <section class="chat-main">
            @if ($conversaAtual && $outroUsuarioAtual)
                <header class="chat-header">
                    <a href="{{ route('usuarios.perfil', $outroUsuarioAtual) }}" class="chat-header-user">
                        <span class="chat-avatar-wrap chat-header-avatar-wrap">
                            <img
                                src="{{ $outroUsuarioAtual->avatar ?: asset('images/icone.png') }}"
                                alt="{{ $outroUsuarioAtual->nickname }}"
                                class="chat-avatar"
                            >
                            <span
                                class="game-partner-presence game-partner-presence-{{ $presencaAtual['status'] }} chat-presence"
                                title="{{ $presencaAtual['texto'] }}"
                            ></span>
                        </span>

                        <span>
                            <strong>{{ $outroUsuarioAtual->nickname }}</strong>
                            <small>{{ $presencaAtual['texto'] }}</small>
                        </span>
                    </a>

                    <div class="dropdown">
                        <button
                            type="button"
                            class="chat-more-button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Mais opções"
                            aria-label="Mais opções"
                        >
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end chat-options-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('usuarios.perfil', $outroUsuarioAtual) }}">
                                    <i class="bi bi-person-fill"></i>
                                    Ver perfil
                                </a>
                            </li>

                            <li>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#reportConversationModal">
                                    <i class="bi bi-flag-fill"></i>
                                    Denunciar
                                </button>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item chat-option-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConversationModal"
                                >
                                    <i class="bi bi-trash3-fill"></i>
                                    Excluir conversa
                                </button>
                            </li>
                        </ul>
                    </div>
                </header>

                <div class="chat-messages squad-scrollbar" id="chatMessages">
                    <div class="chat-messages-content" id="chatMessagesContent">
                    @forelse ($conversaAtual->mensagens as $mensagem)
                        <div
                            class="chat-message-row {{ $mensagem->id_remetente === auth()->id() ? 'mine' : 'theirs' }}"
                            data-message-id="{{ $mensagem->id_mensagem }}"
                        >
                            <div class="chat-message-bubble">
                                <p>{{ $mensagem->mensagem }}</p>
                                <time datetime="{{ $mensagem->data_envio->toIso8601String() }}">
                                    {{ $mensagem->data_envio->format('H:i') }}
                                </time>
                            </div>
                        </div>
                    @empty
                        <div class="chat-conversation-empty">
                            <i class="bi bi-chat-heart"></i>
                            <strong>Comece a conversa.</strong>
                            <span>Vocês já são amigos. Mande a primeira mensagem.</span>
                        </div>
                    @endforelse

                    <div id="chatMessagesEnd"></div>
                    </div>
                </div>

                <div class="chat-composer-wrap">
                    @if ($errors->has('mensagem'))
                        <div class="chat-message-error">{{ $errors->first('mensagem') }}</div>
                    @endif

                    @if ($podeEnviar)
                        <form
                            method="POST"
                            action="{{ route('mensagens.store', $conversaAtual) }}"
                            class="chat-composer"
                            id="chatMessageForm"
                        >
                            @csrf

                            <textarea
                                name="mensagem"
                                id="chatMessageInput"
                                maxlength="1000"
                                rows="1"
                                placeholder="Digite uma mensagem..."
                                required
                            >{{ old('mensagem') }}</textarea>

                            <button type="submit" class="chat-send-button" title="Enviar mensagem" aria-label="Enviar mensagem">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    @else
                        <div class="chat-composer-blocked">
                            <i class="bi bi-lock-fill"></i>
                            <span>Não é possível enviar mensagens nesta conversa no momento.</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="chat-empty-state">
                    <img src="{{ asset($dica['imagem']) }}" alt="{{ $dica['titulo'] }}">
                    <span>Dicas da SquadMaker</span>
                    <h1>{{ $dica['titulo'] }}</h1>
                    <p>{{ $dica['texto'] }}</p>
                </div>
            @endif
        </section>
    </div>

    @if ($amigos->count() > 3)
        <div class="modal fade" id="chatPartnersModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content squad-modal chat-partners-modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Todos os parceiros</h2>
                            <p>Escolha um amigo para conversar.</p>
                        </div>

                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <div class="chat-partners-all-list">
                            @foreach ($amigos as $item)
                                <form method="POST" action="{{ route('conversas.iniciar', $item['usuario']) }}">
                                    @csrf

                                    <button type="submit" class="chat-partner-all-item">
                                        <span class="chat-avatar-wrap">
                                            <img
                                                src="{{ $item['usuario']->avatar ?: asset('images/icone.png') }}"
                                                alt="{{ $item['usuario']->nickname }}"
                                                class="chat-avatar"
                                            >
                                            <span
                                                class="game-partner-presence game-partner-presence-{{ $item['presenca']['status'] }} chat-presence"
                                                title="{{ $item['presenca']['texto'] }}"
                                            ></span>
                                        </span>

                                        <span class="chat-partner-all-copy">
                                            <strong>{{ $item['usuario']->nickname }}</strong>
                                            <small>{{ $item['presenca']['texto'] }}</small>
                                        </span>

                                        <i class="bi bi-chat-dots-fill"></i>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($conversaAtual && $outroUsuarioAtual)
        <div class="modal fade" id="reportConversationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content squad-modal chat-report-modal">
                    <form method="POST" action="{{ route('denuncias.conversa', $conversaAtual) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Denunciar conversa</h2>
                                <p>Denúncia referente à conversa com {{ $outroUsuarioAtual->nickname }}.</p>
                            </div>

                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>

                        <div class="modal-body chat-report-body">
                            <div>
                                <label for="chatReportReason" class="form-label">Motivo</label>
                                <select id="chatReportReason" name="motivo" class="form-select" required>
                                    <option value="">Selecione</option>
                                    @foreach (\App\Models\Denuncia::MOTIVOS_CONVERSA as $valor => $rotulo)
                                        <option value="{{ $valor }}" @selected(old('motivo') === $valor)>{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="chatReportDescription" class="form-label">Explique o ocorrido</label>
                                <textarea id="chatReportDescription" name="descricao" class="form-control squad-scrollbar" rows="5" minlength="20" maxlength="2000" required>{{ old('descricao') }}</textarea>
                                <small>Informe apenas o necessário para a apuração.</small>
                            </div>

                            <div>
                                <label for="chatReportAttachment" class="form-label">Print ou imagem <span>(opcional)</span></label>
                                <input type="file" id="chatReportAttachment" name="anexo" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <small>JPG, PNG ou WebP de até 5 MB.</small>
                            </div>

                            <div class="chat-report-context">
                                <div class="chat-report-context-heading">
                                    <i class="bi bi-shield-check"></i>
                                    <strong>Contexto da denúncia</strong>
                                </div>

                                <p>
                                    Para permitir a apuração, até as 20 mensagens mais recentes desta conversa serão anexadas ao registro da denúncia e poderão ser acessadas pela equipe administrativa responsável pela análise.
                                </p>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="confirmar_contexto" value="1" id="chatReportContextConfirmation" required>
                                    <label class="form-check-label" for="chatReportContextConfirmation">
                                        Confirmo que entendi e quero continuar com o envio da denúncia.
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn chat-report-submit">Enviar denúncia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content squad-modal chat-delete-modal">
                    <form method="POST" action="{{ route('conversas.destroy', $conversaAtual) }}">
                        @csrf
                        @method('DELETE')

                        <div class="modal-header">
                            <h2 class="modal-title">Excluir conversa?</h2>

                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal"
                                aria-label="Fechar"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <p>
                                A conversa com <strong>{{ $outroUsuarioAtual->nickname }}</strong> será removida apenas da sua lista.
                            </p>

                            <p>
                                Se vocês voltarem a conversar, ela poderá aparecer novamente com as novas mensagens.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit" class="btn btn-danger">
                                Excluir conversa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/chat.js') }}"></script>
@endpush
