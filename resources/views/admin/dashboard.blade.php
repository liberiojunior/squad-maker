@extends('layouts.admin')

@section('content')

    <div class="admin-dashboard">

        <div class="admin-title">

            <div>
                <h1>Visão Geral</h1>

                <p>
                    Acompanhe os usuários e atividades da plataforma.
                </p>
            </div>

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

        <section class="admin-panel">

            <div class="admin-panel-header">
                <h2>Usuários</h2>
            </div>

            <div class="table-responsive">

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

                                @if ($user->status_conta === 'banido')

                                    <form
                                        method="POST"
                                        action="{{ route('admin.usuarios.desbanir', $user) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-success"
                                        >
                                            Desbanir
                                        </button>
                                    </form>

                                @else

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#banir-{{ $user->id_usuario }}"
                                    >
                                        Banir
                                    </button>

                                @endif

                            </td>

                        </tr>

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

                                        <input
                                            type="text"
                                            name="motivo"
                                            class="form-control"
                                            placeholder="Motivo"
                                        >

                                        <label
                                            for="data_fim_{{ $user->id_usuario }}"
                                            class="form-label"
                                        >
                                            Banido até
                                        </label>

                                        <input
                                            type="date"
                                            name="data_fim"
                                            class="form-control"
                                        >

                                        <textarea
                                            name="justificativa"
                                            class="form-control"
                                            placeholder="Justificativa"
                                            rows="2"
                                        ></textarea>

                                        <button
                                            type="submit"
                                            class="btn btn-danger"
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

            <div class="admin-pagination">
                {{ $usuarios->links() }}
            </div>

        </section>

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
                        <strong>Denunciante:</strong>
                        {{ $denuncia->denunciante?->nickname ?? 'Usuário não encontrado' }}
                    </p>

                    <p>
                        <strong>Denunciado:</strong>
                        {{ $denuncia->denunciado?->nickname ?? 'Usuário não encontrado' }}
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
