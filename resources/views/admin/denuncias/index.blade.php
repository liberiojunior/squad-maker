@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-title">
        <h1>Denúncias</h1>
        <p>Analise denúncias feitas pelos usuários e acompanhe as decisões da moderação.</p>
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
        <form method="GET" action="{{ route('admin.denuncias.index') }}" class="admin-report-filters">
            <div class="admin-report-search">
                <label for="reportSearch" class="form-label">Buscar usuário</label>
                <input type="search" id="reportSearch" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nickname do denunciante ou denunciado">
            </div>

            <div>
                <label for="reportStatus" class="form-label">Status</label>
                <select id="reportStatus" name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendente" @selected(request('status') === 'pendente')>Pendentes</option>
                    <option value="aceita" @selected(request('status') === 'aceita')>Procedentes</option>
                    <option value="negada" @selected(request('status') === 'negada')>Não procedentes</option>
                </select>
            </div>

            <div>
                <label for="reportType" class="form-label">Tipo</label>
                <select id="reportType" name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="conversa" @selected(request('tipo') === 'conversa')>Conversa</option>
                    <option value="perfil" @selected(request('tipo') === 'perfil')>Perfil</option>
                    <option value="mensagem" @selected(request('tipo') === 'mensagem')>Mensagem antiga</option>
                    <option value="post" @selected(request('tipo') === 'post')>Publicação antiga</option>
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="btn admin-game-save">Filtrar</button>
                <a href="{{ route('admin.denuncias.index') }}" class="btn btn-secondary">Limpar</a>
            </div>
        </form>

        <div class="admin-report-list">
            @forelse ($denuncias as $denuncia)
                <a href="{{ route('admin.denuncias.show', $denuncia) }}" class="admin-report-card">
                    <div class="admin-report-card-top">
                        <div>
                            <strong>Denúncia #{{ $denuncia->id_denuncia }}</strong>
                            <span>{{ $denuncia->tipoTexto() }}</span>
                        </div>

                        <span class="admin-report-status admin-report-status-{{ $denuncia->status_denuncia }}">
                            @if ($denuncia->status_denuncia === 'pendente')
                                Pendente
                            @elseif ($denuncia->status_denuncia === 'aceita')
                                Procedente
                            @else
                                Não procede
                            @endif
                        </span>
                    </div>

                    <div class="admin-report-card-body">
                        <strong>{{ $denuncia->motivoTexto() }}</strong>
                        <p>{{ \Illuminate\Support\Str::limit($denuncia->descricao, 150) }}</p>
                    </div>

                    <div class="admin-report-card-meta">
                        <span>{{ $denuncia->denunciante?->nickname ?? 'Usuário não encontrado' }} → {{ $denuncia->denunciado?->nickname ?? 'Usuário não encontrado' }}</span>
                        <time>{{ $denuncia->data_denuncia?->format('d/m/Y H:i') }}</time>
                    </div>
                </a>
            @empty
                <div class="admin-empty">Nenhuma denúncia encontrada.</div>
            @endforelse
        </div>

        @if ($denuncias->hasPages())
            <div class="squad-pagination">
                {{ $denuncias->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
</div>
@endsection
