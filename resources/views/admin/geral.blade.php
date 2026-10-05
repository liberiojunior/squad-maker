@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-title">
        <h1>Visão Geral</h1>
        <p>Acompanhe um resumo da plataforma e os itens que precisam de atenção.</p>
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

    <section class="admin-metrics">
        <a href="{{ route('admin.usuarios.index') }}" class="admin-metric-card admin-metric-link">
            <i class="bi bi-people-fill"></i>
            <div>
                <span>Total de usuários</span>
                <strong>{{ $totalUsuarios }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.usuarios.index', ['status' => 'ativo']) }}" class="admin-metric-card admin-metric-link">
            <i class="bi bi-person-check-fill"></i>
            <div>
                <span>Usuários ativos</span>
                <strong>{{ $usuariosAtivos }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.usuarios.index', ['status' => 'banido']) }}" class="admin-metric-card admin-metric-link">
            <i class="bi bi-person-x-fill"></i>
            <div>
                <span>Usuários banidos</span>
                <strong>{{ $usuariosBanidos }}</strong>
            </div>
        </a>

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
            <h2>Denúncias pendentes</h2>
            <span>{{ $denunciasPendentes }}</span>
        </div>

        @forelse ($denuncias as $denuncia)
            <div class="admin-report">
                <div>
                    <strong>Denúncia #{{ $denuncia->id_denuncia }}</strong>
                    <span>{{ ucfirst($denuncia->tipo_denuncia) }}</span>
                </div>

                <p>
                    <strong>Denunciante:</strong>
                    {{ $denuncia->denunciante?->nickname ?? 'Usuário não encontrado' }}
                </p>

                <p>
                    <strong>Denunciado:</strong>
                    {{ $denuncia->denunciado?->nickname ?? 'Usuário não encontrado' }}
                </p>

                <p>{{ $denuncia->descricao }}</p>
            </div>
        @empty
            <div class="admin-empty">Não existem denúncias pendentes.</div>
        @endforelse
    </section>
</div>
@endsection
