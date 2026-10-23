@extends('layouts.admin')

@section('content')
@php
    $editando = isset($noticia);
    $conteudoAtual = old('conteudo', $noticia->conteudo ?? '');

    if ($conteudoAtual !== '' && $conteudoAtual === strip_tags($conteudoAtual)) {
        $paragrafos = preg_split('/\r\n|\r|\n/', $conteudoAtual);
        $conteudoAtual = collect($paragrafos)
            ->filter(fn ($paragrafo) => trim($paragrafo) !== '')
            ->map(fn ($paragrafo) => '<p>' . e($paragrafo) . '</p>')
            ->implode('');
    }
@endphp

<div class="admin-dashboard">
    <div class="admin-title admin-news-form-heading">
        <div>
            <h1>{{ $editando ? 'Editar notícia' : 'Nova notícia' }}</h1>
            <p>{{ $editando ? 'Atualize o conteúdo da matéria.' : 'Crie uma matéria para o feed de notícias.' }}</p>
        </div>

        <a href="{{ route('admin.noticias') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="admin-panel admin-news-form-panel">
        <form
            method="POST"
            action="{{ $editando ? route('admin.noticias.update', $noticia) : route('admin.noticias.store') }}"
            enctype="multipart/form-data"
            class="admin-news-form"
        >
            @csrf
            @if ($editando)
                @method('PATCH')
            @endif

            <div class="admin-news-form-main">
                <div>
                    <label for="newsTitle" class="form-label">Título</label>
                    <input
                        type="text"
                        id="newsTitle"
                        name="titulo"
                        class="form-control"
                        maxlength="100"
                        value="{{ old('titulo', $noticia->titulo ?? '') }}"
                        required
                    >
                </div>

                <div>
                    <label for="newsSummary" class="form-label">Resumo</label>
                    <textarea
                        id="newsSummary"
                        name="descricao"
                        class="form-control"
                        maxlength="255"
                        rows="3"
                        required
                    >{{ old('descricao', $noticia->descricao ?? '') }}</textarea>
                    <small class="admin-game-help">Esse texto aparece no card do feed.</small>
                </div>

                <div>
                    <label class="form-label" for="newsContentEditor">Conteúdo da matéria</label>

                    <div
                        class="admin-news-editor"
                        data-news-editor
                        data-image-upload-url="{{ route('admin.noticias.imagem') }}"
                    >
                        <button
                            type="button"
                            class="admin-news-editor-toggle"
                            data-news-editor-toggle
                            aria-expanded="false"
                        >
                            <span>
                                <i class="bi bi-type"></i>
                                Formatação
                            </span>
                            <i class="bi bi-chevron-down"></i>
                        </button>

                        <div class="admin-news-editor-toolbar" data-news-editor-toolbar hidden>
                            <select class="admin-news-editor-select" data-news-format aria-label="Estilo do texto">
                                <option value="p">Parágrafo</option>
                                <option value="h2">Título</option>
                                <option value="h3">Subtítulo</option>
                            </select>

                            <select class="admin-news-editor-select" data-news-font aria-label="Fonte do texto">
                                <option value="">Fonte padrão</option>
                                <option value="Arial">Arial</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Trebuchet MS">Trebuchet</option>
                                <option value="Courier New">Courier</option>
                            </select>

                            <label class="admin-news-font-size" title="Tamanho do texto em pixels">
                                <input
                                    type="number"
                                    min="10"
                                    max="48"
                                    step="1"
                                    value="16"
                                    data-news-size
                                    aria-label="Tamanho do texto em pixels"
                                >
                                <span>px</span>
                            </label>

                            <select
                                class="admin-news-editor-select admin-news-image-size"
                                data-news-image-size
                                aria-label="Tamanho da imagem selecionada"
                                disabled
                            >
                                <option value="">Tamanho da imagem</option>
                                <option value="small">Pequena</option>
                                <option value="medium">Média</option>
                                <option value="large">Grande</option>
                                <option value="full">Largura total</option>
                            </select>

                            <span class="admin-news-editor-separator"></span>

                            <button type="button" data-news-command="bold" title="Negrito" aria-label="Negrito">
                                <i class="bi bi-type-bold"></i>
                            </button>
                            <button type="button" data-news-command="italic" title="Itálico" aria-label="Itálico">
                                <i class="bi bi-type-italic"></i>
                            </button>
                            <button type="button" data-news-command="underline" title="Sublinhado" aria-label="Sublinhado">
                                <i class="bi bi-type-underline"></i>
                            </button>

                            <span class="admin-news-editor-separator"></span>

                            <button type="button" data-news-command="justifyLeft" title="Alinhar à esquerda" aria-label="Alinhar à esquerda">
                                <i class="bi bi-text-left"></i>
                            </button>
                            <button type="button" data-news-command="justifyCenter" title="Centralizar" aria-label="Centralizar">
                                <i class="bi bi-text-center"></i>
                            </button>
                            <button type="button" data-news-command="justifyRight" title="Alinhar à direita" aria-label="Alinhar à direita">
                                <i class="bi bi-text-right"></i>
                            </button>
                            <button type="button" data-news-command="justifyFull" title="Justificar" aria-label="Justificar texto">
                                <i class="bi bi-justify"></i>
                            </button>

                            <span class="admin-news-editor-separator"></span>

                            <button type="button" data-news-command="insertUnorderedList" title="Lista" aria-label="Lista com marcadores">
                                <i class="bi bi-list-ul"></i>
                            </button>
                            <button type="button" data-news-command="insertOrderedList" title="Lista numerada" aria-label="Lista numerada">
                                <i class="bi bi-list-ol"></i>
                            </button>
                            <button type="button" data-news-link title="Adicionar hyperlink" aria-label="Adicionar hyperlink">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                            <button type="button" data-news-image title="Inserir imagem" aria-label="Inserir imagem">
                                <i class="bi bi-image"></i>
                            </button>
                            <button type="button" data-news-video title="Inserir vídeo do YouTube" aria-label="Inserir vídeo do YouTube">
                                <i class="bi bi-youtube"></i>
                            </button>
                            <button type="button" data-news-video-remove title="Remover vídeo selecionado" aria-label="Remover vídeo selecionado" disabled>
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>

                        <div class="admin-news-link-panel" data-news-link-panel hidden>
                            <label for="newsLinkUrl">Hyperlink</label>
                            <div>
                                <input
                                    type="url"
                                    id="newsLinkUrl"
                                    class="form-control"
                                    data-news-link-url
                                    placeholder="https://exemplo.com"
                                >
                                <button type="button" class="btn admin-news-link-apply" data-news-link-apply>Aplicar</button>
                                <button type="button" class="btn btn-secondary" data-news-link-remove disabled>Remover link</button>
                                <button type="button" class="btn btn-secondary" data-news-link-cancel>Cancelar</button>
                            </div>
                            <small>Selecione um texto ou uma imagem. Links abrem em uma nova guia.</small>
                        </div>

                        <div class="admin-news-video-panel" data-news-video-panel hidden>
                            <label for="newsVideoUrl">Link do YouTube</label>
                            <div>
                                <input
                                    type="url"
                                    id="newsVideoUrl"
                                    class="form-control"
                                    data-news-video-url
                                    placeholder="https://www.youtube.com/watch?v=..."
                                >
                                <button type="button" class="btn admin-news-video-insert" data-news-video-insert>Inserir</button>
                                <button type="button" class="btn btn-secondary" data-news-video-cancel>Cancelar</button>
                            </div>
                            <small>Cole um link do YouTube. O vídeo será exibido dentro da matéria.</small>
                        </div>

                        <div
                            id="newsContentEditor"
                            class="admin-news-editor-content"
                            contenteditable="true"
                            role="textbox"
                            aria-multiline="true"
                            data-placeholder="Escreva a matéria aqui..."
                        >{!! $conteudoAtual !!}</div>

                        <textarea name="conteudo" data-news-editor-input hidden required>{{ $conteudoAtual }}</textarea>
                        <input type="file" data-news-image-input accept="image/jpeg,image/png,image/webp" hidden>

                        <div class="admin-news-editor-status" data-news-editor-status aria-live="polite"></div>
                    </div>

                    <small class="admin-game-help">Use a guia de formatação para destacar trechos, adicionar hyperlinks, inserir imagens ou vídeos do YouTube no meio da matéria.</small>
                </div>
            </div>

            <aside class="admin-news-form-side">
                <div>
                    <label for="newsImage" class="form-label">Imagem de capa</label>

                    @if ($editando)
                        <img src="{{ $noticia->imagem }}" alt="Capa atual" class="admin-news-current-image">
                    @endif

                    <input
                        type="file"
                        id="newsImage"
                        name="imagem"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        @required(! $editando)
                    >
                    <small class="admin-game-help">JPG, PNG ou WebP de até 5 MB. Prefira imagens em 16:9.</small>
                </div>

                <div>
                    <label for="newsSource" class="form-label">Fonte</label>
                    <input
                        type="text"
                        id="newsSource"
                        name="fonte"
                        class="form-control"
                        maxlength="255"
                        value="{{ old('fonte', $noticia->fonte ?? '') }}"
                        placeholder="Ex.: PlayStation Blog"
                        required
                    >
                </div>

                <div>
                    <label for="newsSourceUrl" class="form-label">Link da fonte <span class="text-muted">(opcional)</span></label>
                    <input
                        type="url"
                        id="newsSourceUrl"
                        name="fonte_url"
                        class="form-control"
                        maxlength="500"
                        value="{{ old('fonte_url', $noticia->fonte_url ?? '') }}"
                        placeholder="https://..."
                    >
                </div>

                <div class="admin-news-form-author">
                    <span>Autor</span>
                    <strong>{{ auth('admin')->user()->nome }}</strong>
                    <small>O autor é definido automaticamente pela conta administrativa.</small>
                </div>

                <button type="submit" class="btn admin-game-save admin-news-submit">
                    {{ $editando ? 'Salvar' : 'Publicar notícia' }}
                </button>
            </aside>
        </form>
    </section>
</div>
@endsection


@push('scripts')
    <script src="{{ asset('js/admin-news-editor.js') }}?v={{ filemtime(public_path('js/admin-news-editor.js')) }}"></script>
@endpush
