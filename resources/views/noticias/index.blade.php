@extends('layouts.internal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/news.css') }}?v={{ filemtime(public_path('css/news.css')) }}">
@endpush

@section('content')
<div class="news-page">
    <header class="news-heading">
        <span>Squad Maker</span>
        <h1>Últimas Notícias</h1>
        <p>Novidades, lançamentos e acontecimentos do universo dos jogos.</p>
    </header>

    @if ($noticias->isNotEmpty())
        <section class="news-grid" aria-label="Últimas notícias">
            @foreach ($noticias as $noticia)
                <article class="news-card">
                    <a href="{{ route('noticias.show', $noticia) }}" class="news-card-link">
                        <div class="news-card-image">
                            <img src="{{ $noticia->imagem }}" alt="{{ $noticia->titulo }}" loading="lazy">
                        </div>

                        <div class="news-card-body">
                            <div class="news-card-meta">
                                <span>{{ $noticia->data_noticia->format('d/m/Y') }}</span>
                                <span>{{ $noticia->administrador->nome }}</span>
                            </div>

                            <h2>{{ $noticia->titulo }}</h2>
                            <p>{{ $noticia->descricao }}</p>

                            <span class="news-card-read">
                                Ler matéria
                                <i class="bi bi-arrow-right"></i>
                            </span>
                        </div>
                    </a>
                </article>
            @endforeach
        </section>

        @if ($noticias->hasPages())
            <div class="squad-pagination news-pagination">
                {{ $noticias->links('pagination::bootstrap-5') }}
            </div>
        @endif
    @else
        <div class="news-empty">
            <i class="bi bi-newspaper"></i>
            <h2>Nenhuma notícia publicada ainda.</h2>
            <p>As novidades do Squad Maker aparecerão aqui.</p>
        </div>
    @endif
</div>
@endsection
