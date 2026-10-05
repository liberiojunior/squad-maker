@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-title">
        <h1>Catálogo</h1>
        <p>Gerencie as plataformas e os gêneros disponíveis no Squad Maker.</p>
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
            <h2>Plataformas</h2>
            <span>{{ $plataformas->total() }}</span>
        </div>

        <form method="POST" action="{{ route('admin.plataformas.store') }}" enctype="multipart/form-data" class="admin-catalog-create-platform">
            @csrf

            <div>
                <label for="platformName" class="form-label">Nome</label>
                <input type="text" id="platformName" name="nome" class="form-control" placeholder="Ex.: Steam" required>
            </div>

            <div>
                <label for="platformImage" class="form-label">Imagem</label>
                <input type="file" id="platformImage" name="icone" class="form-control" accept="image/png,image/jpeg,image/webp" required>
            </div>

            <button type="submit" class="btn admin-game-save">Cadastrar</button>
        </form>

        @if ($plataformas->isNotEmpty())
            <div class="admin-catalog-platforms">
                @foreach ($plataformas as $plataforma)
                    <div class="admin-catalog-card">
                        <div class="admin-catalog-platform">
                            <img src="{{ $plataforma->icone }}" alt="{{ $plataforma->nome }}">
                            <strong>{{ $plataforma->nome }}</strong>
                        </div>

                        <form id="plataformaUpdate{{ $plataforma->id_plataforma }}" method="POST" action="{{ route('admin.plataformas.update', $plataforma) }}" enctype="multipart/form-data" class="admin-catalog-platform-form">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="nomePlataforma{{ $plataforma->id_plataforma }}" class="form-label">Nome</label>
                                <input type="text" id="nomePlataforma{{ $plataforma->id_plataforma }}" name="nome" class="form-control" value="{{ $plataforma->nome }}" required>
                            </div>

                            <div>
                                <label for="iconePlataforma{{ $plataforma->id_plataforma }}" class="form-label">Trocar imagem</label>
                                <input type="file" id="iconePlataforma{{ $plataforma->id_plataforma }}" name="icone" class="form-control" accept="image/png,image/jpeg,image/webp">
                            </div>
                        </form>

                        <div class="admin-catalog-platform-actions">
                            <button type="submit" form="plataformaUpdate{{ $plataforma->id_plataforma }}" class="btn admin-game-save">Salvar</button>

                            <form method="POST" action="{{ route('admin.plataformas.destroy', $plataforma) }}" class="admin-catalog-platform-delete" onsubmit="return confirm('Deseja realmente excluir a plataforma {{ $plataforma->nome }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Excluir</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($plataformas->hasPages())
                <div class="squad-pagination">
                    {{ $plataformas->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="admin-empty">Nenhuma plataforma cadastrada.</div>
        @endif
    </section>

    <section class="admin-panel">
        <div class="admin-panel-header">
            <h2>Gêneros</h2>
            <span>{{ $generos->total() }}</span>
        </div>

        <form method="POST" action="{{ route('admin.generos.store') }}" class="admin-catalog-create-genres">
            @csrf

            <label for="generos" class="form-label">Adicionar gêneros</label>

            <div class="input-group">
                <input type="text" id="generos" name="generos" class="form-control" placeholder="Ex.: Ação; Aventura; RPG; Soulslike" required>
                <button type="submit" class="btn admin-game-save">Adicionar</button>
            </div>

            <small class="admin-game-help">Para cadastrar vários de uma vez, separe os nomes com ponto e vírgula.</small>
        </form>

        <form method="GET" action="{{ route('admin.catalogo.index') }}" class="admin-catalog-filter">
            <div class="admin-catalog-filter-search">
                <label for="genreSearch" class="form-label">Buscar gênero</label>
                <input type="search" id="genreSearch" name="genero_q" class="form-control" value="{{ request('genero_q') }}" placeholder="Ex.: RPG">
            </div>

            <div>
                <label for="genreOrder" class="form-label">Ordenar</label>
                <select id="genreOrder" name="genero_ordem" class="form-select">
                    <option value="az" @selected(request('genero_ordem', 'az') === 'az')>A-Z</option>
                    <option value="za" @selected(request('genero_ordem') === 'za')>Z-A</option>
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="btn admin-game-save">Filtrar</button>
                <a href="{{ route('admin.catalogo.index') }}" class="btn btn-secondary">Limpar</a>
            </div>
        </form>

        @if ($generos->isNotEmpty())
            <div class="admin-catalog-genres">
                @foreach ($generos as $genero)
                    <div class="admin-catalog-genre">
                        <form method="POST" action="{{ route('admin.generos.update', $genero) }}" class="admin-catalog-genre-form">
                            @csrf
                            @method('PATCH')

                            <input type="text" name="genero" class="form-control" value="{{ $genero->genero }}" required>

                            <button type="submit" class="btn admin-game-save admin-catalog-genre-button" title="Salvar">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.generos.destroy', $genero) }}" class="admin-catalog-genre-delete" onsubmit="return confirm('Deseja realmente excluir o gênero {{ $genero->genero }}?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger admin-catalog-genre-button" title="Excluir gênero">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            @if ($generos->hasPages())
                <div class="squad-pagination">
                    {{ $generos->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="admin-empty">Nenhum gênero encontrado.</div>
        @endif
    </section>
</div>
@endsection
