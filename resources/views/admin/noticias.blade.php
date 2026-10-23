@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-title admin-news-title">
        <div>
            <h1>Notícias</h1>
            <p>Publique e gerencie as matérias exibidas para os usuários.</p>
        </div>

        <a href="{{ route('admin.noticias.create') }}" class="btn admin-game-save admin-news-create">
            <i class="bi bi-plus-lg"></i>
            Nova notícia
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="admin-panel">
        <div class="admin-panel-header">
            <h2>Publicadas</h2>
            <span>{{ $noticias->total() }}</span>
        </div>

        @if ($noticias->isNotEmpty())
            <div class="admin-news-list">
                @foreach ($noticias as $noticia)
                    <article class="admin-news-card">
                        <img src="{{ $noticia->imagem }}" alt="{{ $noticia->titulo }}">

                        <div class="admin-news-card-content">
                            <div class="admin-news-card-meta">
                                <span>{{ $noticia->data_noticia->format('d/m/Y H:i') }}</span>
                                <span>{{ $noticia->administrador->nome }}</span>
                            </div>

                            <h3>{{ $noticia->titulo }}</h3>
                            <p>{{ $noticia->descricao }}</p>
                        </div>

                        <div class="admin-news-card-actions">
                            <a href="{{ route('admin.noticias.edit', $noticia) }}" class="btn admin-game-save">
                                Editar
                            </a>

                            <form
                                method="POST"
                                action="{{ route('admin.noticias.destroy', $noticia) }}"
                                data-confirm-title="Excluir notícia?"
                                data-confirm-message="Deseja realmente excluir a notícia {{ $noticia->titulo }}?"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Excluir</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($noticias->hasPages())
                <div class="squad-pagination">
                    {{ $noticias->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="admin-empty">Nenhuma notícia publicada.</div>
        @endif
    </section>
</div>
@endsection
