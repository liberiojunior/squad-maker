@extends('layouts.admin')

@section('content')

    <div class="admin-dashboard">

        <div class="admin-title">

            <h1>Catálogo</h1>

            <p>
                Gerencie as plataformas e os gêneros disponíveis no Squad Maker.
            </p>

        </div>


        {{-- Mensagens --}}

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


        {{-- Plataformas --}}

        <section class="admin-panel">

            <div class="admin-panel-header">
                <h2>Plataformas</h2>
            </div>


            {{-- Cadastrar plataforma --}}

            <form
                method="POST"
                action="{{ route('admin.plataformas.store') }}"
                enctype="multipart/form-data"
                class="admin-catalog-create-platform"
            >
                @csrf


                <div>

                    <label
                        for="platformName"
                        class="form-label"
                    >
                        Nome
                    </label>

                    <input
                        type="text"
                        id="platformName"
                        name="nome"
                        class="form-control"
                        placeholder="Ex.: Steam"
                        required
                    >

                </div>


                <div>

                    <label
                        for="platformImage"
                        class="form-label"
                    >
                        Imagem
                    </label>

                    <input
                        type="file"
                        id="platformImage"
                        name="icone"
                        class="form-control"
                        accept="image/png,image/jpeg,image/webp"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn admin-game-save"
                >
                    Cadastrar
                </button>

            </form>


            {{-- Plataformas cadastradas --}}

            @if ($plataformas->isNotEmpty())

                <div class="admin-catalog-platforms">

                    @foreach ($plataformas as $plataforma)

                        <div class="admin-catalog-card">


                            <div class="admin-catalog-platform">

                                <img
                                    src="{{ $plataforma->icone }}"
                                    alt="{{ $plataforma->nome }}"
                                >

                                <strong>
                                    {{ $plataforma->nome }}
                                </strong>

                            </div>


                            {{-- Editar plataforma --}}

                            <form
                                id="plataformaUpdate{{ $plataforma->id_plataforma }}"
                                method="POST"
                                action="{{ route('admin.plataformas.update', $plataforma) }}"
                                enctype="multipart/form-data"
                                class="admin-catalog-platform-form"
                            >
                                @csrf
                                @method('PATCH')


                                <div>

                                    <label
                                        for="nomePlataforma{{ $plataforma->id_plataforma }}"
                                        class="form-label"
                                    >
                                        Nome
                                    </label>

                                    <input
                                        type="text"
                                        id="nomePlataforma{{ $plataforma->id_plataforma }}"
                                        name="nome"
                                        class="form-control"
                                        value="{{ $plataforma->nome }}"
                                        required
                                    >

                                </div>


                                <div>

                                    <label
                                        for="iconePlataforma{{ $plataforma->id_plataforma }}"
                                        class="form-label"
                                    >
                                        Trocar imagem
                                    </label>

                                    <input
                                        type="file"
                                        id="iconePlataforma{{ $plataforma->id_plataforma }}"
                                        name="icone"
                                        class="form-control"
                                        accept="image/png,image/jpeg,image/webp"
                                    >

                                </div>

                            </form>


                            {{-- Ações da plataforma --}}

                            <div class="admin-catalog-platform-actions">

                                <button
                                    type="submit"
                                    form="plataformaUpdate{{ $plataforma->id_plataforma }}"
                                    class="btn admin-game-save"
                                >
                                    Salvar
                                </button>


                                <form
                                    method="POST"
                                    action="{{ route('admin.plataformas.destroy', $plataforma) }}"
                                    class="admin-catalog-platform-delete"
                                    onsubmit="return confirm('Deseja realmente excluir a plataforma {{ $plataforma->nome }}?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        Excluir
                                    </button>

                                </form>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="admin-empty">
                    Nenhuma plataforma cadastrada.
                </div>

            @endif

        </section>


        {{-- Gêneros --}}

        <section class="admin-panel">

            <div class="admin-panel-header">
                <h2>Gêneros</h2>
            </div>


            {{-- Adicionar gêneros --}}

            <form
                method="POST"
                action="{{ route('admin.generos.store') }}"
                class="admin-catalog-create-genres"
            >
                @csrf


                <label
                    for="generos"
                    class="form-label"
                >
                    Adicionar gêneros
                </label>


                <div class="input-group">

                    <input
                        type="text"
                        id="generos"
                        name="generos"
                        class="form-control"
                        placeholder="Ex.: Ação; Aventura; RPG; Soulslike"
                        required
                    >

                    <button
                        type="submit"
                        class="btn admin-game-save"
                    >
                        Adicionar
                    </button>

                </div>


                <small class="admin-game-help">
                    Para cadastrar vários de uma vez, separe os nomes com ponto e vírgula.
                </small>

            </form>


            {{-- Gêneros cadastrados --}}

            @if ($generos->isNotEmpty())

                <div class="admin-catalog-genres">

                    @foreach ($generos as $genero)

                        <div class="admin-catalog-genre">


                            {{-- Editar gênero --}}

                            <form
                                method="POST"
                                action="{{ route('admin.generos.update', $genero) }}"
                                class="admin-catalog-genre-form"
                            >
                                @csrf
                                @method('PATCH')


                                <input
                                    type="text"
                                    name="genero"
                                    class="form-control"
                                    value="{{ $genero->genero }}"
                                    required
                                >


                                <button
                                    type="submit"
                                    class="btn admin-game-save admin-catalog-genre-button"
                                    title="Salvar"
                                >
                                    <i class="bi bi-check-lg"></i>
                                </button>

                            </form>


                            {{-- Excluir gênero --}}

                            <form
                                method="POST"
                                action="{{ route('admin.generos.destroy', $genero) }}"
                                class="admin-catalog-genre-delete"
                                onsubmit="return confirm('Deseja realmente excluir o gênero {{ $genero->genero }}?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-danger admin-catalog-genre-button"
                                    title="Excluir gênero"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>

                            </form>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="admin-empty">
                    Nenhum gênero cadastrado.
                </div>

            @endif

        </section>

    </div>

@endsection
