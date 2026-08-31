@extends('layouts.admin')

@section('content')

    <div class="admin-dashboard">

        <div class="admin-title">
            <h1>Visão Geral</h1>

            <p>
                Acompanhe os usuários e atividades da plataforma.
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


        {{-- Métricas --}}

        <section class="admin-metrics">

            <div class="admin-metric-card">

                <i class="bi bi-people-fill"></i>

                <div>
                    <span>Total de usuários</span>
                    <strong>{{ $totalUsuarios }}</strong>
                </div>

            </div>


            <div class="admin-metric-card">

                <i class="bi bi-person-check-fill"></i>

                <div>
                    <span>Usuários ativos</span>
                    <strong>{{ $usuariosAtivos }}</strong>
                </div>

            </div>


            <div class="admin-metric-card">

                <i class="bi bi-person-x-fill"></i>

                <div>
                    <span>Usuários banidos</span>
                    <strong>{{ $usuariosBanidos }}</strong>
                </div>

            </div>


            <div class="admin-metric-card">

                <i class="bi bi-exclamation-triangle-fill"></i>

                <div>
                    <span>Denúncias pendentes</span>
                    <strong>{{ $denunciasPendentes }}</strong>
                </div>

            </div>

        </section>


        {{-- Usuários --}}

        <section class="admin-panel">

            <div class="admin-panel-header">
                <h2>Usuários</h2>
            </div>


            <div class="admin-users-table-wrapper">

                <table class="table admin-table align-middle">

                    <thead>

                    <tr>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Cadastro</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>

                    </thead>


                    <tbody>

                    @foreach ($usuarios as $user)

                        {{-- Linha principal do usuário --}}

                        <tr>

                            <td>
                                {{ $user->nickname }}
                            </td>

                            <td>
                                {{ $user->email }}
                            </td>

                            <td>
                                {{ $user->data_criacao?->format('d/m/Y') }}
                            </td>

                            <td>

                                <span
                                    class="admin-status admin-status-{{ $user->status_conta }}"
                                >
                                    {{ ucfirst($user->status_conta) }}
                                </span>

                            </td>

                            <td>

                                <div class="dropdown">

                                    <button
                                        type="button"
                                        class="btn btn-sm admin-user-actions"
                                        data-bs-toggle="dropdown"
                                        data-bs-boundary="viewport"
                                        aria-expanded="false"
                                    >
                                        Ações

                                        <i class="bi bi-chevron-down"></i>
                                    </button>


                                    <ul class="dropdown-menu admin-user-actions-menu">

                                        @if ($user->status_conta === 'banido')

                                            <li>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.usuarios.desbanir', $user) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="dropdown-item"
                                                    >
                                                        Desbanir
                                                    </button>

                                                </form>

                                            </li>

                                        @else

                                            <li>

                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#banir-{{ $user->id_usuario }}"
                                                    aria-expanded="false"
                                                >
                                                    Banir
                                                </button>

                                            </li>

                                        @endif


                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>


                                        <li>

                                            <button
                                                type="button"
                                                class="dropdown-item text-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#excluirUsuario{{ $user->id_usuario }}"
                                            >
                                                Excluir usuário
                                            </button>

                                        </li>

                                    </ul>

                                </div>

                            </td>

                        </tr>


                        {{-- Formulário de banimento --}}

                        @if ($user->status_conta !== 'banido')

                            <tr
                                class="collapse"
                                id="banir-{{ $user->id_usuario }}"
                            >

                                <td colspan="5">

                                    <form
                                        method="POST"
                                        action="{{ route('admin.usuarios.banir', $user) }}"
                                        class="admin-ban-form"
                                    >
                                        @csrf


                                        <div class="admin-ban-field">

                                            <label
                                                for="motivo_{{ $user->id_usuario }}"
                                                class="form-label"
                                            >
                                                Motivo
                                            </label>

                                            <input
                                                type="text"
                                                id="motivo_{{ $user->id_usuario }}"
                                                name="motivo"
                                                class="form-control"
                                                placeholder="Informe o motivo"
                                                required
                                            >

                                        </div>


                                        <div class="admin-ban-field">

                                            <label
                                                for="data_fim_{{ $user->id_usuario }}"
                                                class="form-label"
                                            >
                                                Banido até
                                            </label>

                                            <input
                                                type="date"
                                                id="data_fim_{{ $user->id_usuario }}"
                                                name="data_fim"
                                                class="form-control"
                                                required
                                            >

                                        </div>


                                        <div class="admin-ban-field">

                                            <label
                                                for="justificativa_{{ $user->id_usuario }}"
                                                class="form-label"
                                            >
                                                Justificativa
                                            </label>

                                            <input
                                                type="text"
                                                id="justificativa_{{ $user->id_usuario }}"
                                                name="justificativa"
                                                class="form-control"
                                                placeholder="Informe uma justificativa"
                                                required
                                            >

                                        </div>


                                        <button
                                            type="submit"
                                            class="btn btn-danger admin-ban-confirm"
                                        >
                                            Confirmar banimento
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @endif

                    @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Modais de exclusão --}}

            @foreach ($usuarios as $user)

                <div
                    class="modal fade"
                    id="excluirUsuario{{ $user->id_usuario }}"
                    tabindex="-1"
                    aria-hidden="true"
                >

                    <div class="modal-dialog modal-dialog-centered">

                        <div class="modal-content admin-delete-modal">

                            <form
                                method="POST"
                                action="{{ route('admin.usuarios.excluir', $user) }}"
                            >
                                @csrf
                                @method('DELETE')


                                <div class="modal-header">

                                    <h2 class="modal-title">
                                        Excluir usuário
                                    </h2>

                                    <button
                                        type="button"
                                        class="btn-close btn-close-white"
                                        data-bs-dismiss="modal"
                                        aria-label="Fechar"
                                    ></button>

                                </div>


                                <div class="modal-body">

                                    <p>
                                        Esta ação excluirá a conta do usuário.
                                    </p>

                                    <p>
                                        Para confirmar, digite o nome do usuário.
                                    </p>

                                    <input
                                        type="text"
                                        name="confirmacao"
                                        class="form-control mt-3"
                                        autocomplete="off"
                                        required
                                    >

                                </div>


                                <div class="modal-footer">

                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        data-bs-dismiss="modal"
                                    >
                                        Cancelar
                                    </button>

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        Excluir usuário
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            @endforeach


            {{-- Paginação --}}

            @if ($usuarios->hasPages())

                <div class="admin-pagination">
                    {{ $usuarios->links('pagination::bootstrap-5') }}
                </div>

            @endif

        </section>


        {{-- Denúncias --}}

        <section class="admin-panel">

            <div class="admin-panel-header">

                <h2>Denúncias pendentes</h2>

                <span>
                {{ $denunciasPendentes }}
            </span>

            </div>


            @forelse ($denuncias as $denuncia)

                <div class="admin-report">

                    <div>

                        <strong>
                            Denúncia #{{ $denuncia->id_denuncia }}
                        </strong>

                        <span>
                        {{ ucfirst($denuncia->tipo_denuncia) }}
                    </span>

                    </div>


                    <p>

                        <strong>
                            Denunciante:
                        </strong>

                        {{ $denuncia->denunciante?->nickname
                            ?? 'Usuário não encontrado'
                        }}

                    </p>


                    <p>

                        <strong>
                            Denunciado:
                        </strong>

                        {{ $denuncia->denunciado?->nickname
                            ?? 'Usuário não encontrado'
                        }}

                    </p>


                    <p>
                        {{ $denuncia->descricao }}
                    </p>

                </div>

            @empty

                <div class="admin-empty">
                    Não existem denúncias pendentes.
                </div>

            @endforelse

        </section>

    </div>

@endsection
