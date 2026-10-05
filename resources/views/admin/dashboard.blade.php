@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-title admin-dashboard-title">
        <div>
            <h1>Dashboard</h1>
            <p>Métricas de uso e crescimento da plataforma.</p>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-dashboard-period">
            <label for="dashboardPeriod">Período</label>
            <select id="dashboardPeriod" name="periodo" class="form-select" onchange="this.form.submit()">
                <option value="7" @selected($periodo === '7')>7 dias</option>
                <option value="30" @selected($periodo === '30')>30 dias</option>
                <option value="90" @selected($periodo === '90')>90 dias</option>
                <option value="todos" @selected($periodo === 'todos')>Todo o período</option>
            </select>
        </form>
    </div>

    <section class="admin-metrics">
        <div class="admin-metric-card">
            <i class="bi bi-person-plus-fill"></i>
            <div>
                <span>Cadastros no período</span>
                <strong>{{ $novosUsuarios }}</strong>
            </div>
        </div>

        <div class="admin-metric-card">
            <i class="bi bi-chat-dots-fill"></i>
            <div>
                <span>Mensagens no período</span>
                <strong>{{ $mensagensEnviadas }}</strong>
            </div>
        </div>

        <div class="admin-metric-card">
            <i class="bi bi-people-fill"></i>
            <div>
                <span>Amizades aceitas</span>
                <strong>{{ $amizadesAceitas }}</strong>
            </div>
        </div>

        <div class="admin-metric-card">
            <i class="bi bi-controller"></i>
            <div>
                <span>Jogos adicionados aos perfis</span>
                <strong>{{ $jogosAdicionados }}</strong>
            </div>
        </div>
    </section>

    <section class="admin-dashboard-grid">
        <div class="admin-panel admin-chart-panel admin-chart-wide">
            <div class="admin-panel-header">
                <h2>Cadastros de usuários</h2>
            </div>
            <div class="admin-chart">
                <canvas id="adminUsersChart"></canvas>
            </div>
        </div>

        <div class="admin-panel admin-chart-panel">
            <div class="admin-panel-header">
                <h2>Jogos mais adicionados</h2>
            </div>
            <div class="admin-chart">
                <canvas id="adminGamesChart"></canvas>
            </div>
        </div>

        <div class="admin-panel admin-chart-panel">
            <div class="admin-panel-header">
                <h2>Plataformas mais usadas</h2>
            </div>
            <div class="admin-chart">
                <canvas id="adminPlatformsChart"></canvas>
            </div>
        </div>
    </section>

    <script type="application/json" id="adminDashboardData">
        {!! json_encode([
            'usuarios' => [
                'labels' => $cadastrosLabels,
                'valores' => $cadastrosValores,
            ],
            'jogos' => [
                'labels' => $jogosPopulares->pluck('nome'),
                'valores' => $jogosPopulares->pluck('total')->map(fn($total) => (int) $total),
            ],
            'plataformas' => [
                'labels' => $plataformasPopulares->pluck('nome'),
                'valores' => $plataformasPopulares->pluck('total')->map(fn($total) => (int) $total),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/admin.js') }}"></script>
@endpush
