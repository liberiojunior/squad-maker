@extends('layouts.admin')
@section('content')
    @php
        $manualAberto =
            session('open_form') === 'manual'
            || $errors->has('nome')
            || $errors->has('capa')
            || $errors->has('dt_lancamento')
            || $errors->has('descricao');
            $steamAberto =
                session('open_form') === 'steam'
                || $errors->has('steam_app_ids');
    @endphp
    <div class="admin-dashboard">
        <div class="admin-title">
            <h1>Gerenciar Jogos</h1>
            <p>
                Cadastre jogos manualmente ou importe informações pela Steam.
            </p>
        </div>
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <section class="admin-game-actions">
            <button
                type="button"
                class="btn admin-game-add-button {{ $manualAberto ? '' : 'collapsed' }}"
                data-bs-toggle="collapse"
                data-bs-target="#manualGameForm"
                aria-expanded="{{ $manualAberto ? 'true' : 'false' }}"
                aria-controls="manualGameForm"
            >
                <i class="bi bi-plus-lg"></i>
                Cadastro manual
            </button>
            <button
                type="button"
                class="btn admin-game-steam-button {{ $steamAberto ? '' : 'collapsed' }}"
                data-bs-toggle="collapse"
                data-bs-target="#steamGameForm"
                aria-expanded="{{ $steamAberto ? 'true' : 'false' }}"
                aria-controls="steamGameForm"
            >
                <i class="bi bi-steam"></i>
                Importar pela Steam
            </button>
        </section>
        <div id="adminGameForms">
            <div
                class="collapse {{ $manualAberto ? 'show' : '' }}"
                id="manualGameForm"
                data-bs-parent="#adminGameForms"
            >
                <section class="admin-panel">
                    <h2>Cadastrar jogo manualmente</h2>
                    <form
                        method="POST"
                        action="{{ route('admin.jogos.manual') }}"
                        enctype="multipart/form-data"
                        class="admin-game-form"
                    >
                        @csrf
                        <div>
                            <label
                                for="nome"
                                class="form-label"
                            >
                                Nome
                            </label>
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-control"
                                value="{{ old('nome') }}"
                            >
                        </div>
                        <div>
                            <label
                                for="dt_lancamento"
                                class="form-label"
                            >
                                Data de lançamento
                            </label>
                            <input
                                type="date"
                                id="dt_lancamento"
                                name="dt_lancamento"
                                class="form-control"
                                value="{{ old('dt_lancamento') }}"
                            >
                        </div>
                        <div>
                            <label
                                for="capa"
                                class="form-label"
                            >
                                Capa
                            </label>
                            <input
                                type="file"
                                id="capa"
                                name="capa"
                                class="form-control"
                                accept="image/png,image/jpeg,image/webp"
                            >
                        </div>
                        <div class="admin-game-description">
                            <label
                                for="descricao"
                                class="form-label"
                            >
                                Descrição
                            </label>
                            <textarea
                                id="descricao"
                                name="descricao"
                                class="form-control"
                                rows="4"
                            >{{ old('descricao') }}</textarea>
                        </div>
                        <button
                            type="submit"
                            class="btn admin-game-save"
                        >
                            Cadastrar
                        </button>
                    </form>
                </section>
            </div>
            <div
                class="collapse {{ $steamAberto ? 'show' : '' }}"
                id="steamGameForm"
                data-bs-parent="#adminGameForms"
            >
                <section class="admin-panel">
                    <h2>Importar jogo da Steam</h2>
                    <p class="admin-game-help">
                        Informe um ou mais AppIDs da Steam, separados por vírgula ou ponto e vírgula.
                    </p>
                    <form
                        method="POST"
                        action="{{ route('admin.jogos.steam') }}"
                        class="admin-steam-form"
                        id="steamImportForm"
                    >
                        @csrf
                        <input
                            type="text"
                            name="steam_app_ids"
                            class="form-control"
                            placeholder="Ex.: 730, 440; 413150"
                            value="{{ old('steam_app_ids') }}"
                            autocomplete="off"
                        >
                        <button
                            type="submit"
                            class="btn admin-game-save"
                            id="steamImportButton"
                        >
                            <span id="steamImportButtonContent">
                                Importar
                            </span>
                        </button>
                    </form>

                    <div
                        class="admin-steam-loading"
                        id="steamImportLoading"
                        hidden
                    >
                        <div
                            class="spinner-border spinner-border-sm"
                            role="status"
                            aria-hidden="true"
                        ></div>

                        <span>
                            Importando jogos da Steam...
                        </span>
                    </div>
                </section>
            </div>
        </div>
        <section class="admin-panel">
            <form
                method="GET"
                action="{{ route('admin.jogos.index') }}"
                class="admin-game-search"
            >
                <div class="admin-game-search-input">
                    <button
                        type="submit"
                        class="admin-game-search-button"
                        title="Buscar"
                    >
                        <i class="bi bi-search"></i>
                    </button>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar jogo..."
                    >
                </div>
                <select
                    name="origem"
                    class="form-select"
                    onchange="this.form.submit()"
                >
                    <option value="">
                        Todas as origens
                    </option>
                    <option
                        value="steam"
                        @selected(request('origem') === 'steam')
                    >
                        Steam
                    </option>
                    <option
                        value="manual"
                        @selected(request('origem') === 'manual')
                    >
                        Manual
                    </option>
                </select>
                <select
                    name="ordem"
                    class="form-select"
                    onchange="this.form.submit()"
                >
                    <option
                        value="az"
                        @selected(request('ordem', 'az') === 'az')
                    >
                        Nome A-Z
                    </option>
                    <option
                        value="za"
                        @selected(request('ordem') === 'za')
                    >
                        Nome Z-A
                    </option>
                    <option
                        value="recentes"
                        @selected(request('ordem') === 'recentes')
                    >
                        Adicionados recentemente
                    </option>
                </select>
            </form>
            <div class="admin-game-grid">
                @forelse ($jogos as $jogo)
                    <div class="admin-game-card-wrapper">
                        <button
                            type="button"
                            class="admin-game-card"
                            data-bs-toggle="modal"
                            data-bs-target="#jogoModal{{ $jogo->id_jogo }}"
                        >
                            <img
                                src="{{ $jogo->capa }}"
                                alt="{{ $jogo->nome }}"
                            >
                            <div class="admin-game-card-info">
                                <strong>
                                    {{ $jogo->nome }}
                                </strong>
                                @if ($jogo->steam_app_id)
                                    <span class="admin-game-source steam">Steam</span>
                                    <small>
                                        AppID {{ $jogo->steam_app_id }}
                                    </small>
                                @else
                                    <span class="admin-game-source manual">Manual</span>
                                @endif
                            </div>
                        </button>
                        <form
                            method="POST"
                            action="{{ route('admin.jogos.destroy', $jogo) }}"
                            class="admin-game-delete-form"
                            onsubmit="return confirm('Deseja realmente excluir este jogo?')"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="admin-game-delete"
                                title="Excluir"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    </div>
                    <div
                        class="modal fade"
                        id="jogoModal{{ $jogo->id_jogo }}"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content admin-game-modal">
                                <div class="modal-header">
                                    <h2 class="modal-title">
                                        {{ $jogo->nome }}
                                    </h2>
                                    <button
                                        type="button"
                                        class="btn-close btn-close-white"
                                        data-bs-dismiss="modal"
                                        aria-label="Fechar"
                                    ></button>
                                </div>
                                <div class="modal-body">
                                    <img
                                        src="{{ $jogo->capa }}"
                                        alt="{{ $jogo->nome }}"
                                        class="admin-game-modal-cover"
                                    >
                                    @if ($jogo->steam_app_id)
                                        <p class="admin-game-modal-source">
                                            Steam AppID:
                                            {{ $jogo->steam_app_id }}
                                        </p>
                                    @else
                                        <p class="admin-game-modal-source">
                                            Cadastrado manualmente
                                        </p>
                                    @endif
                                    <div class="admin-game-modal-genres">
                                        <strong>Gêneros</strong>
                                        <div>
                                            @forelse ($jogo->generos as $genero)
                                                <span>{{ $genero->genero }}</span>
                                            @empty
                                                <span class="admin-game-no-genres">Nenhum gênero cadastrado.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="admin-game-modal-description">
                                        <strong>Descrição</strong>
                                        <p>
                                            {{ $jogo->descricao ?: 'Nenhuma descrição cadastrada.' }}
                                        </p>
                                    </div>
                                    <div class="admin-game-modal-date">
                                        <strong>Data de lançamento</strong>
                                        <p>
                                            {{ $jogo->dt_lancamento
                                                ? $jogo->dt_lancamento->format('d/m/Y')
                                                : 'Não informada'
                                            }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn admin-game-edit-button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#editarJogo{{ $jogo->id_jogo }}"
                                    >
                                        Editar
                                    </button>
                                    <div
                                        class="collapse admin-game-edit-area"
                                        id="editarJogo{{ $jogo->id_jogo }}"
                                    >
                                        <form
                                            method="POST"
                                            action="{{ route('admin.jogos.update', $jogo) }}"
                                            enctype="multipart/form-data"
                                            class="admin-game-edit-form"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="form-label">
                                                    Nome
                                                </label>
                                                <input
                                                    type="text"
                                                    name="nome"
                                                    class="form-control"
                                                    value="{{ $jogo->nome }}"
                                                >
                                            </div>
                                            <div>
                                                <label class="form-label">
                                                    Data de lançamento
                                                </label>
                                                <input
                                                    type="date"
                                                    name="dt_lancamento"
                                                    class="form-control"
                                                    value="{{ $jogo->dt_lancamento?->format('Y-m-d') }}"
                                                >
                                            </div>
                                            @if ($jogo->steam_app_id)
                                                <div>
                                                    <label class="form-label">
                                                        Steam AppID
                                                    </label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        value="{{ $jogo->steam_app_id }}"
                                                        readonly
                                                    >
                                                </div>
                                            @endif
                                            <div>
                                                <label class="form-label">
                                                    Nova capa
                                                </label>
                                                <input
                                                    type="file"
                                                    name="capa"
                                                    class="form-control"
                                                    accept="image/png,image/jpeg,image/webp"
                                                >
                                            </div>
                                            <div class="admin-game-edit-description">
                                                <label class="form-label">
                                                    Descrição
                                                </label>
                                                <textarea
                                                    name="descricao"
                                                    class="form-control"
                                                    rows="4"
                                                >{{ $jogo->descricao }}</textarea>
                                            </div>
                                            <div class="admin-game-edit-genres">
                                                <label class="form-label">
                                                    Gêneros
                                                </label>
                                                <div>
                                                    @foreach ($generosDisponiveis as $genero)
                                                        <label>
                                                            <input
                                                                type="checkbox"
                                                                name="generos[]"
                                                                value="{{ $genero->id_genero }}"
                                                                @checked(
                                                                    $jogo->generos->contains(
                                                                        'id_genero',
                                                                        $genero->id_genero
                                                                    )
                                                                )
                                                            >
                                                            {{ $genero->genero }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <button
                                                type="submit"
                                                class="btn admin-game-save"
                                            >
                                                Salvar alterações
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="admin-empty">
                        Nenhum jogo encontrado.
                    </div>
                @endforelse
            </div>
            @if ($jogos->hasPages())
                <div class="squad-pagination">
                    {{ $jogos->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>

    <script>
        const steamImportForm = document.getElementById('steamImportForm');
        const steamImportButton = document.getElementById('steamImportButton');
        const steamImportButtonContent = document.getElementById('steamImportButtonContent');
        const steamImportLoading = document.getElementById('steamImportLoading');

        if (steamImportForm) {
            steamImportForm.addEventListener('submit', function() {
                steamImportButton.disabled = true;
                steamImportButtonContent.textContent = 'Importando...';
                steamImportLoading.hidden = false;
            });
        }
    </script>
@endsection
