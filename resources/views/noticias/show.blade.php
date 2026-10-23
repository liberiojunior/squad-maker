@extends('layouts.internal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/news.css') }}?v={{ filemtime(public_path('css/news.css')) }}">
@endpush

@section('content')
<article class="news-article">
    <a href="{{ route('noticias.index') }}" class="news-back-link">
        <i class="bi bi-arrow-left"></i>
        Voltar para notícias
    </a>

    <header class="news-article-header">
        <span class="news-article-date">{{ $noticia->data_noticia->format('d/m/Y') }}</span>
        <h1>{{ $noticia->titulo }}</h1>
        <p>{{ $noticia->descricao }}</p>
    </header>

    <div class="news-article-cover">
        <img src="{{ $noticia->imagem }}" alt="{{ $noticia->titulo }}">
    </div>

    @php
        $conteudoNoticia = $noticia->conteudo;

        if ($conteudoNoticia !== '' && $conteudoNoticia === strip_tags($conteudoNoticia)) {
            $conteudoNoticia = collect(preg_split('/\r\n|\r|\n/', $conteudoNoticia))
                ->filter(fn ($paragrafo) => trim($paragrafo) !== '')
                ->map(fn ($paragrafo) => '<p>' . e($paragrafo) . '</p>')
                ->implode('');
        }
    @endphp

    <div class="news-article-content">
        {!! $conteudoNoticia !!}
    </div>

    <footer class="news-article-footer">
        <div>
            <span>Publicado por</span>
            <strong>{{ $noticia->administrador->nome }}</strong>
        </div>

        <div>
            <span>Fonte</span>
            @if ($noticia->fonte_url)
                <a href="{{ $noticia->fonte_url }}" target="_blank" rel="noopener noreferrer">
                    {{ $noticia->fonte }}
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            @else
                <strong>{{ $noticia->fonte }}</strong>
            @endif
        </div>

        <div>
            <span>Publicado em</span>
            <strong>{{ $noticia->data_noticia->format('d/m/Y') }} às {{ $noticia->data_noticia->format('H:i') }}</strong>
        </div>
    </footer>
</article>
@endsection
