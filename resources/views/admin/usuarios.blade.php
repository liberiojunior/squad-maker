@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-title">
        <h1>Usuários</h1>
        <p>Pesquise, filtre e gerencie as contas cadastradas na plataforma.</p>
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
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="admin-filter-grid">
            <div class="admin-filter-search">
                <label for="userSearch" class="form-label">Buscar</label>
                <input type="search" id="userSearch" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nickname ou e-mail">
            </div>

            <div>
                <label for="userStatus" class="form-label">Status</label>
                <select id="userStatus" name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="ativo" @selected(request('status') === 'ativo')>Ativos</option>
                    <option value="banido" @selected(request('status') === 'banido')>Banidos</option>
                </select>
            </div>

            <div>
                <label for="userStartDate" class="form-label">De</label>
                <input type="date" id="userStartDate" name="data_inicio" class="form-control" value="{{ request('data_inicio') }}">
            </div>

            <div>
                <label for="userEndDate" class="form-label">Até</label>
                <input type="date" id="userEndDate" name="data_fim" class="form-control" value="{{ request('data_fim') }}">
            </div>

            <div>
                <label for="userOrder" class="form-label">Ordenar</label>
                <select id="userOrder" name="ordem" class="form-select">
                    <option value="recentes" @selected(request('ordem', 'recentes') === 'recentes')>Mais recentes</option>
                    <option value="antigos" @selected(request('ordem') === 'antigos')>Mais antigos</option>
                    <option value="az" @selected(request('ordem') === 'az')>Nome A-Z</option>
                    <option value="za" @selected(request('ordem') === 'za')>Nome Z-A</option>
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="btn admin-game-save">Filtrar</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Limpar</a>
            </div>
        </form>

        <div class="admin-result-summary">
            {{ $usuarios->total() }} usuário(s) encontrado(s).
        </div>

        <div class="admin-users-table-wrapper squad-scrollbar">
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
                @forelse ($usuarios as $user)
                    <tr>
                        <td>{{ $user->nickname }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->data_criacao?->format('d/m/Y') }}</td>
                        <td>
                            <span class="admin-status admin-status-{{ $user->status_conta }}">
                                {{ ucfirst($user->status_conta) }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm admin-user-actions" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    Ações
                                    <i class="bi bi-chevron-down"></i>
                                </button>

                                <ul class="dropdown-menu admin-user-actions-menu">
                                    @if ($user->status_conta === 'banido')
                                        <li>
                                            <form method="POST" action="{{ route('admin.usuarios.desbanir', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">Desbanir</button>
                                            </form>
                                        </li>
                                    @else
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="collapse" data-bs-target="#banir-{{ $user->id_usuario }}" aria-expanded="false">
                                                Banir
                                            </button>
                                        </li>
                                    @endif

                                    <li>
                                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#excluirUsuario{{ $user->id_usuario }}">
                                            Excluir usuário
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    @if ($user->status_conta !== 'banido')
                        <tr class="collapse" id="banir-{{ $user->id_usuario }}">
                            <td colspan="5">
                                <form method="POST" action="{{ route('admin.usuarios.banir', $user) }}" class="admin-ban-form">
                                    @csrf

                                    <div class="admin-ban-field">
                                        <label for="motivo_{{ $user->id_usuario }}" class="form-label">Motivo</label>
                                        <input type="text" id="motivo_{{ $user->id_usuario }}" name="motivo" class="form-control" placeholder="Informe o motivo" required>
                                    </div>

                                    <div class="admin-ban-field">
                                        <label for="data_fim_{{ $user->id_usuario }}" class="form-label">Banido até</label>
                                        <input type="date" id="data_fim_{{ $user->id_usuario }}" name="data_fim" class="form-control" required>
                                    </div>

                                    <div class="admin-ban-field">
                                        <label for="justificativa_{{ $user->id_usuario }}" class="form-label">Justificativa</label>
                                        <input type="text" id="justificativa_{{ $user->id_usuario }}" name="justificativa" class="form-control" placeholder="Informe uma justificativa" required>
                                    </div>

                                    <button type="submit" class="btn btn-danger admin-ban-confirm">Confirmar banimento</button>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="admin-empty">Nenhum usuário encontrado com os filtros informados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @foreach ($usuarios as $user)
            <div class="modal fade" id="excluirUsuario{{ $user->id_usuario }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content squad-modal admin-delete-modal">
                        <form method="POST" action="{{ route('admin.usuarios.excluir', $user) }}">
                            @csrf
                            @method('DELETE')

                            <div class="modal-header">
                                <h2 class="modal-title">Excluir usuário</h2>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>

                            <div class="modal-body">
                                <p>Esta ação excluirá a conta do usuário.</p>
                                <p>Para confirmar, digite o nome do usuário.</p>
                                <input type="text" name="confirmacao" class="form-control mt-3" autocomplete="off" required>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger">Excluir usuário</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($usuarios->hasPages())
            <div class="squad-pagination">
                {{ $usuarios->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
</div>
@endsection
