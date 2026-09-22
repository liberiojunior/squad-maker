@extends('layouts.admin')
@section('content')
    @php
        $manualAberto =
            session('open_form') === 'manual'
            || $errors->has('nome')
            || $errors->has('capa')
            || $errors->has('dt_lancamento')
            || $errors->has('descricao')
            || $errors->has('generos_texto')
            || $errors->has('modos');
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
                        <div class="admin-game-description">
                            <label
                                for="generos_texto"
                                class="form-label"
                            >
                                Gêneros
                            </label>
                            <input
                                type="text"
                                id="generos_texto"
                                name="generos_texto"
                                class="form-control"
                                value="{{ old('generos_texto') }}"
                                placeholder="Ex.: Ação; RPG; Mundo Aberto"
                            >
                            <small class="admin-game-help">
                                Separe os gêneros por ponto e vírgula.
                            </small>
                        </div>

                        <div class="admin-game-description">
                            <label class="form-label">
                                Modos de jogo
                            </label>
                            <div class="admin-game-edit-genres">
                                <div>
                                    @foreach ($modosDisponiveis as $modo)
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="modos[]"
                                                value="{{ $modo->id_modo_jogo }}"
                                                @checked(
                                                    in_array(
                                                        $modo->id_modo_jogo,
                                                        old('modos', [])
                                                    )
                                                )
                                            >
                                            {{ $modo->nome }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
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
            @php
                $ordemAdmin =
                    request(
                        'ordem',
                        'az'
                    );

                $origemAdmin =
                    request('origem');

                $generosAdminAtivos =
                    $generosSelecionados ?? [];

                $modosAdminAtivos =
                    $modosSelecionados ?? [];

                $quantidadeFiltrosAdmin =
                    count($generosAdminAtivos)
                    + count($modosAdminAtivos)
                    + (
                        $origemAdmin
                            ? 1
                            : 0
                    )
                    + (
                        $ordemAdmin !== 'az'
                            ? 1
                            : 0
                    );

                $filtrosAdminAbertos =
                    $quantidadeFiltrosAdmin > 0;
            @endphp

            <form
                method="GET"
                action="{{ route('admin.jogos.index') }}"
                class="
                    catalog-search-form
                    admin-catalog-search-form
                "
                id="adminGameSearchForm"
            >
                <div class="catalog-search-toolbar">
                    <div
                        class="
                            catalog-search-bar
                            admin-game-search-input
                        "
                    >
                        <button
                            type="submit"
                            class="admin-game-search-button"
                            title="Buscar"
                            aria-label="Buscar"
                        >
                            <i class="bi bi-search"></i>
                        </button>

                        <input
                            type="text"
                            name="q"
                            id="adminGameSearchInput"
                            value="{{ request('q') }}"
                            placeholder="Buscar jogo..."
                            autocomplete="off"
                            data-live-search-input
                        >
                    </div>

                    <button
                        type="button"
                        class="
                            catalog-filter-toggle
                            {{ $filtrosAdminAbertos
                                ? 'active'
                                : '' }}
                        "
                        data-bs-toggle="collapse"
                        data-bs-target="#adminGameFilters"
                        aria-expanded="{{ $filtrosAdminAbertos
                            ? 'true'
                            : 'false' }}"
                        aria-controls="adminGameFilters"
                    >
                        <i class="bi bi-sliders"></i>

                        <span>
                            Filtros
                        </span>

                        <span
                            class="catalog-filter-count"
                            data-filter-count
                            @if ($quantidadeFiltrosAdmin === 0) hidden @endif
                        >
                            {{ $quantidadeFiltrosAdmin }}
                        </span>
                    </button>
                </div>

                <div
                    class="
                        collapse
                        {{ $filtrosAdminAbertos
                            ? 'show'
                            : '' }}
                    "
                    id="adminGameFilters"
                >
                    <div class="catalog-filter-panel">

                        <div class="catalog-filter-group">
                            <div class="catalog-filter-title">
                                <i class="bi bi-database"></i>

                                <span>
                                    Origem
                                </span>
                            </div>

                            <div class="catalog-filter-options">
                                <label class="catalog-filter-chip">
                                    <input
                                        type="radio"
                                        name="origem"
                                        value=""
                                        @checked(! $origemAdmin)
                                    >

                                    <span>
                                        Todas
                                    </span>
                                </label>

                                <label class="catalog-filter-chip">
                                    <input
                                        type="radio"
                                        name="origem"
                                        value="steam"
                                        @checked(
                                            $origemAdmin === 'steam'
                                        )
                                    >

                                    <span>
                                        Steam
                                    </span>
                                </label>

                                <label class="catalog-filter-chip">
                                    <input
                                        type="radio"
                                        name="origem"
                                        value="manual"
                                        @checked(
                                            $origemAdmin === 'manual'
                                        )
                                    >

                                    <span>
                                        Manual
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="catalog-filter-group">
                            <div class="catalog-filter-title">
                                <i class="bi bi-sort-down"></i>

                                <span>
                                    Ordenar por
                                </span>
                            </div>

                            <div class="catalog-filter-options">
                                <label class="catalog-filter-chip">
                                    <input
                                        type="radio"
                                        name="ordem"
                                        value="az"
                                        @checked(
                                            $ordemAdmin === 'az'
                                        )
                                    >

                                    <span>
                                        Nome A-Z
                                    </span>
                                </label>

                                <label class="catalog-filter-chip">
                                    <input
                                        type="radio"
                                        name="ordem"
                                        value="za"
                                        @checked(
                                            $ordemAdmin === 'za'
                                        )
                                    >

                                    <span>
                                        Nome Z-A
                                    </span>
                                </label>

                                <label class="catalog-filter-chip">
                                    <input
                                        type="radio"
                                        name="ordem"
                                        value="recentes"
                                        @checked(
                                            $ordemAdmin === 'recentes'
                                        )
                                    >

                                    <span>
                                        Adicionados recentemente
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="catalog-filter-group">
                            <div class="catalog-filter-title">
                                <i class="bi bi-tags"></i>

                                <span>
                                    Gêneros
                                </span>
                            </div>

                            <div
                                class="
                                    catalog-filter-options
                                    catalog-filter-options-scroll
                                "
                            >
                                @forelse (
                                    $generosFiltro
                                    as $genero
                                )
                                    <label class="catalog-filter-chip">
                                        <input
                                            type="checkbox"
                                            name="generos[]"
                                            value="{{ $genero->id_genero }}"
                                            @checked(
                                                in_array(
                                                    (int) $genero->id_genero,
                                                    $generosAdminAtivos,
                                                    true
                                                )
                                            )
                                        >

                                        <span>
                                            {{ $genero->genero }}
                                        </span>
                                    </label>
                                @empty
                                    <span class="catalog-filter-empty">
                                        Nenhum gênero disponível.
                                    </span>
                                @endforelse
                            </div>
                        </div>

                        <div class="catalog-filter-group">
                            <div class="catalog-filter-title">
                                <i class="bi bi-controller"></i>

                                <span>
                                    Modos de jogo
                                </span>
                            </div>

                            <div
                                class="
                                    catalog-filter-options
                                    catalog-filter-options-scroll
                                "
                            >
                                @forelse (
                                    $modosFiltro
                                    as $modo
                                )
                                    <label class="catalog-filter-chip">
                                        <input
                                            type="checkbox"
                                            name="modos_filtro[]"
                                            value="{{ $modo->id_modo_jogo }}"
                                            @checked(
                                                in_array(
                                                    (int) $modo->id_modo_jogo,
                                                    $modosAdminAtivos,
                                                    true
                                                )
                                            )
                                        >

                                        <span>
                                            {{ $modo->nome }}
                                        </span>
                                    </label>
                                @empty
                                    <span class="catalog-filter-empty">
                                        Nenhum modo disponível.
                                    </span>
                                @endforelse
                            </div>
                        </div>

                        <div class="catalog-filter-actions">
                            <a
                                href="{{ route(
                                    'admin.jogos.index',
                                    request('q')
                                        ? [
                                            'q' => request('q'),
                                        ]
                                        : []
                                ) }}"
                                class="catalog-filter-clear"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>

                                Limpar filtros
                            </a>

                            <button
                                type="submit"
                                class="catalog-filter-apply"
                            >
                                <i class="bi bi-check2"></i>

                                Aplicar filtros
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div
                class="catalog-search-loading"
                id="adminGameSearchLoading"
                hidden
            >
                <div
                    class="spinner-border spinner-border-sm"
                    role="status"
                    aria-hidden="true"
                ></div>

                <span>
                    Buscando jogos...
                </span>
            </div>

            <div
                id="adminGameResults"
                class="catalog-search-results"
                aria-live="polite"
            >
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
                                    title="Excluir jogo"
                                    aria-label="Excluir {{ $jogo->nome }}"
                                >
                                    <i class="bi bi-trash3"></i>
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
                                        <div class="admin-game-modal-genres">
                                            <strong>Modos de jogo</strong>
                                            <div>
                                                @forelse ($jogo->modos as $modo)
                                                    <span>{{ $modo->nome }}</span>
                                                @empty
                                                    <span class="admin-game-no-genres">Nenhum modo cadastrado.</span>
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
                                                <div class="admin-game-edit-description">
                                                    <label class="form-label">
                                                        Gêneros
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="generos_texto"
                                                        class="form-control"
                                                        value="{{ $jogo->generos->pluck('genero')->implode('; ') }}"
                                                        placeholder="Ex.: Ação; RPG; Mundo Aberto"
                                                    >
                                                    <small class="admin-game-help">
                                                        Separe os gêneros por ponto e vírgula.
                                                    </small>
                                                </div>

                                                <div class="admin-game-edit-genres">
                                                    <label class="form-label">
                                                        Modos de jogo
                                                    </label>
                                                    <div>
                                                        @foreach ($modosDisponiveis as $modo)
                                                            <label>
                                                                <input
                                                                    type="checkbox"
                                                                    name="modos[]"
                                                                    value="{{ $modo->id_modo_jogo }}"
                                                                    @checked(
                                                                        $jogo->modos->contains(
                                                                            'id_modo_jogo',
                                                                            $modo->id_modo_jogo
                                                                        )
                                                                    )
                                                                >
                                                                {{ $modo->nome }}
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
            </div>
        </section>
    </div>
@endsection
