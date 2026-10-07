@php
    $reagiu = in_array((int) $post->id_post, $postsReagidos ?? [], true);
    $feedMode = $feedMode ?? false;
    $perfilAutor = $isOwner
        ? route('perfil')
        : route('usuarios.perfil', ['user' => $user->nickname]);
@endphp

<article class="profile-post-card {{ $feedMode ? 'profile-post-feed-card' : '' }}">
    @if ($feedMode)
        <header class="profile-post-author">
            <a href="{{ $perfilAutor }}" class="profile-post-author-avatar" aria-label="Abrir perfil de {{ $user->nickname }}">
                <img
                    src="{{ $user->avatar ?: asset('images/icone.png') }}"
                    alt="Foto de {{ $user->nickname }}"
                >
            </a>

            <div class="profile-post-author-copy">
                <a href="{{ $perfilAutor }}" class="profile-post-author-name">
                    {{ $user->nickname }}
                </a>

                <time datetime="{{ $post->data_publicacao?->toIso8601String() }}">
                    {{ $post->data_publicacao?->format('d/m/Y H:i') }}
                </time>
            </div>
        </header>
    @endif
    <div class="profile-post-media">
        <button
            type="button"
            class="profile-post-image-open"
            data-bs-toggle="modal"
            data-bs-target="#postImageModal{{ $post->id_post }}"
            title="Abrir imagem"
            aria-label="Abrir imagem da publicação"
        >
            <img src="{{ $post->foto }}" alt="Publicação de {{ $user->nickname }}">
        </button>

        <span class="profile-post-hover-likes" aria-hidden="true">
            <i class="bi bi-heart-fill"></i>
            <span data-post-reaction-count="{{ $post->id_post }}">{{ $post->reacoes_count }}</span>
        </span>

        @if ($post->fixado_em && ! $feedMode)
            <span
                class="profile-post-pinned-indicator"
                aria-hidden="true"
                title="Publicação fixada"
            >
                <i class="bi bi-pin-angle-fill"></i>
            </span>
        @endif
    </div>

    <div class="profile-post-content">
        @if ($post->descricao)
            <p class="profile-post-description">{{ $post->descricao }}</p>
        @endif

        <div class="profile-post-footer">
            @if (! $feedMode)
                <time datetime="{{ $post->data_publicacao?->toIso8601String() }}">
                    {{ $post->data_publicacao?->format('d/m/Y H:i') }}
                </time>
            @endif

            @if ($podeReagir)
                <form
                    method="POST"
                    action="{{ $reagiu ? route('posts.reacoes.destroy', $post) : route('posts.reacoes.store', $post) }}"
                    class="profile-post-reaction-form"
                    data-post-id="{{ $post->id_post }}"
                    data-reaction-store-url="{{ route('posts.reacoes.store', $post) }}"
                    data-reaction-destroy-url="{{ route('posts.reacoes.destroy', $post) }}"
                >
                    @csrf
                    @if ($reagiu)
                        @method('DELETE')
                    @endif

                    <button
                        type="submit"
                        class="profile-post-reaction {{ $reagiu ? 'active' : '' }}"
                        data-post-reaction-button
                        aria-label="Curtir publicação"
                        aria-pressed="{{ $reagiu ? 'true' : 'false' }}"
                        title="{{ $reagiu ? 'Remover curtida' : 'Curtir publicação' }}"
                    >
                        <i class="bi {{ $reagiu ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                        <span data-post-reaction-count="{{ $post->id_post }}" aria-live="polite">{{ $post->reacoes_count }}</span>
                    </button>
                </form>
            @else
                <span
                    class="profile-post-reaction profile-post-reaction-static profile-post-reaction-locked"
                    title="Torne-se parceiro para curtir publicações."
                >
                    <i class="bi bi-heart"></i>
                    <span data-post-reaction-count="{{ $post->id_post }}">{{ $post->reacoes_count }}</span>
                </span>
            @endif
        </div>
    </div>
</article>

<div class="modal fade profile-post-image-modal" id="postImageModal{{ $post->id_post }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="profile-post-image-modal-toolbar">
                <button
                    type="button"
                    class="profile-post-image-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar publicação"
                    title="Fechar"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

                @if ($feedMode)
                    <div class="profile-post-modal-author">
                        <a href="{{ $perfilAutor }}" class="profile-post-modal-author-avatar" aria-label="Abrir perfil de {{ $user->nickname }}">
                            <img
                                src="{{ $user->avatar ?: asset('images/icone.png') }}"
                                alt="Foto de {{ $user->nickname }}"
                            >
                        </a>

                        <div class="profile-post-modal-author-copy">
                            <a href="{{ $perfilAutor }}">{{ $user->nickname }}</a>
                            <time datetime="{{ $post->data_publicacao?->toIso8601String() }}">
                                {{ $post->data_publicacao?->format('d/m/Y H:i') }}
                            </time>
                        </div>
                    </div>
                @endif

                <div class="profile-post-image-modal-toolbar-right">
                    @if ($podeReagir)
                        <form
                            method="POST"
                            action="{{ $reagiu ? route('posts.reacoes.destroy', $post) : route('posts.reacoes.store', $post) }}"
                            class="profile-post-reaction-form profile-post-modal-reaction-form"
                            data-post-id="{{ $post->id_post }}"
                            data-reaction-store-url="{{ route('posts.reacoes.store', $post) }}"
                            data-reaction-destroy-url="{{ route('posts.reacoes.destroy', $post) }}"
                        >
                            @csrf
                            @if ($reagiu)
                                @method('DELETE')
                            @endif

                            <button
                                type="submit"
                                class="profile-post-modal-reaction {{ $reagiu ? 'active' : '' }}"
                                data-post-reaction-button
                                aria-label="Curtir publicação"
                                aria-pressed="{{ $reagiu ? 'true' : 'false' }}"
                                title="{{ $reagiu ? 'Remover curtida' : 'Curtir publicação' }}"
                            >
                                <i class="bi {{ $reagiu ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                <span data-post-reaction-count="{{ $post->id_post }}" aria-live="polite">{{ $post->reacoes_count }}</span>
                            </button>
                        </form>
                    @else
                        <span
                            class="profile-post-modal-reaction profile-post-modal-reaction-static profile-post-reaction-locked"
                            title="Torne-se parceiro para curtir publicações."
                        >
                            <i class="bi bi-heart"></i>
                            <span data-post-reaction-count="{{ $post->id_post }}">{{ $post->reacoes_count }}</span>
                        </span>
                    @endif

                    <div class="dropdown">
                        <button
                            type="button"
                            class="profile-post-more-button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Opções da publicação"
                            title="Opções"
                        >
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end profile-post-options-menu">
                            @if ($isOwner)
                                <form method="POST" action="{{ route('posts.pin', $post) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="dropdown-item">
                                        <i class="bi {{ $post->fixado_em ? 'bi-pin-angle' : 'bi-pin-angle-fill' }}"></i>
                                        {{ $post->fixado_em ? 'Desafixar do topo' : 'Fixar no topo' }}
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#postEditPanel{{ $post->id_post }}"
                                    aria-controls="postEditPanel{{ $post->id_post }}"
                                >
                                    <i class="bi bi-pencil-fill"></i>
                                    Editar descrição
                                </button>

                                <div class="dropdown-divider"></div>

                                <form
                                    method="POST"
                                    action="{{ route('posts.destroy', $post) }}"
                                    onsubmit="return confirm('Deseja remover esta publicação?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="dropdown-item profile-post-option-danger">
                                        <i class="bi bi-trash3"></i>
                                        Excluir publicação
                                    </button>
                                </form>
                            @else
                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reportPostModal{{ $post->id_post }}"
                                >
                                    <i class="bi bi-flag-fill"></i>
                                    Denunciar publicação
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($post->descricao)
                <div class="profile-post-image-modal-caption">
                    <p>{{ $post->descricao }}</p>
                </div>
            @endif

            @if ($isOwner)
                <div class="collapse profile-post-edit-panel" id="postEditPanel{{ $post->id_post }}">
                    <form method="POST" action="{{ route('posts.update', $post) }}">
                        @csrf
                        @method('PATCH')

                        <textarea
                            name="descricao"
                            class="form-control squad-scrollbar"
                            rows="3"
                            maxlength="280"
                            placeholder="Adicione uma descrição..."
                        >{{ $post->descricao }}</textarea>

                        <div class="profile-post-edit-actions">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-toggle="collapse"
                                data-bs-target="#postEditPanel{{ $post->id_post }}"
                            >
                                Cancelar
                            </button>

                            <button type="submit" class="btn profile-post-submit">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="profile-post-image-modal-media">
                <button
                    type="button"
                    class="profile-post-modal-nav profile-post-modal-nav-prev"
                    data-post-nav="previous"
                    aria-label="Publicação anterior"
                    title="Publicação anterior"
                >
                    <i class="bi bi-chevron-left"></i>
                </button>

                <img src="{{ $post->foto }}" alt="Publicação de {{ $user->nickname }}">

                <button
                    type="button"
                    class="profile-post-modal-nav profile-post-modal-nav-next"
                    data-post-nav="next"
                    aria-label="Próxima publicação"
                    title="Próxima publicação"
                >
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@if (! $isOwner)
    <div class="modal fade" id="reportPostModal{{ $post->id_post }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content squad-modal profile-report-modal">
                <form method="POST" action="{{ route('denuncias.post', $post) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Denunciar publicação</h2>
                            <p>A imagem e o conteúdo atual da publicação serão preservados para análise.</p>
                        </div>

                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body profile-report-body">
                        <div>
                            <label for="postReportReason{{ $post->id_post }}" class="form-label">Motivo</label>
                            <select id="postReportReason{{ $post->id_post }}" name="motivo" class="form-select" required>
                                <option value="">Selecione</option>
                                @foreach (\App\Models\Denuncia::MOTIVOS_POST as $valor => $rotulo)
                                    <option value="{{ $valor }}">{{ $rotulo }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="postReportDescription{{ $post->id_post }}" class="form-label">Explique o motivo</label>
                            <textarea id="postReportDescription{{ $post->id_post }}" name="descricao" class="form-control squad-scrollbar" rows="5" minlength="20" maxlength="2000" required></textarea>
                            <small>Informe apenas o necessário para a apuração.</small>
                        </div>

                        <div>
                            <label for="postReportAttachment{{ $post->id_post }}" class="form-label">Print ou imagem <span>(opcional)</span></label>
                            <input type="file" id="postReportAttachment{{ $post->id_post }}" name="anexo" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <small>JPG, PNG ou WebP de até 5 MB.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn profile-report-submit">Enviar denúncia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif


@once
    <script src="{{ asset('js/post-gallery.js') }}"></script>
@endonce
