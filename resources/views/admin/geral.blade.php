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

        <a href="{{ route('admin.denuncias.index', ['status' => 'pendente']) }}" class="admin-metric-card admin-metric-link">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <span>Denúncias pendentes</span>
                <strong>{{ $denunciasPendentes }}</strong>
            </div>
        </a>
    </section>

    <section class="admin-overview-mascot">
        @if (file_exists(public_path('images/admin/mascote-responsabilidade.png')))
            <img src="{{ asset('images/admin/mascote-responsabilidade.png') }}" alt="Mascote da Squad Maker">
        @else
            <div class="admin-overview-mascot-placeholder" aria-hidden="true">
                <i class="bi bi-stars"></i>
            </div>
        @endif

        <p>“Com grandes poderes vêm grandes responsabilidades.”</p>
    </section>
</div>
@endsection
