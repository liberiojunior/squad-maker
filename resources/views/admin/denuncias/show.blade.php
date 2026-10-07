@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-report-detail-heading">
        <div>
            <a href="{{ route('admin.denuncias.index') }}" class="admin-report-back"><i class="bi bi-arrow-left"></i> Voltar para denúncias</a>
            <h1>Denúncia #{{ $denuncia->id_denuncia }}</h1>
            <p>{{ $denuncia->tipoTexto() }} · {{ $denuncia->data_denuncia?->format('d/m/Y H:i') }}</p>
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

    <div class="admin-report-detail-grid">
        <section class="admin-panel admin-report-detail-main">
            <div class="admin-report-info-grid">
                <div>
                    <span>Denunciante</span>
                    <strong>{{ $denuncia->denunciante?->nickname ?? 'Usuário não encontrado' }}</strong>
                </div>
                <div>
                    <span>Denunciado</span>
                    <strong>{{ $denuncia->denunciado?->nickname ?? 'Usuário não encontrado' }}</strong>
                </div>
                <div>
                    <span>Motivo</span>
                    <strong>{{ $denuncia->motivoTexto() }}</strong>
                </div>
                <div>
                    <span>Tipo</span>
                    <strong>{{ $denuncia->tipoTexto() }}</strong>
                </div>
            </div>

            <div class="admin-report-description">
                <h2>Relato do usuário</h2>
                <p>{{ $denuncia->descricao }}</p>
            </div>

            @if ($denuncia->anexo)
                <div class="admin-report-evidence">
                    <h2>Imagem enviada pelo usuário</h2>
                    <a href="{{ route('admin.denuncias.arquivo', [$denuncia, 'anexo']) }}" target="_blank" rel="noopener">
                        <img src="{{ route('admin.denuncias.arquivo', [$denuncia, 'anexo']) }}" alt="Anexo da denúncia">
                    </a>
                </div>
            @endif

            @if ($denuncia->tipo_denuncia === 'conversa' && ! empty(data_get($denuncia->contexto, 'mensagens')))
                <div class="admin-report-context">
                    <h2>Contexto preservado da conversa</h2>
                    <p class="admin-report-context-help">Últimas mensagens disponíveis no momento em que a denúncia foi enviada.</p>

                    <div class="admin-report-messages squad-scrollbar">
                        @foreach (data_get($denuncia->contexto, 'mensagens', []) as $mensagem)
                            <div class="admin-report-message">
                                <div>
                                    <strong>{{ $mensagem['remetente'] ?? 'Usuário' }}</strong>
                                    <time>{{ ! empty($mensagem['data_envio']) ? \Carbon\Carbon::parse($mensagem['data_envio'])->format('d/m/Y H:i') : '' }}</time>
                                </div>
                                <p>{{ $mensagem['mensagem'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($denuncia->tipo_denuncia === 'perfil' && ! empty(data_get($denuncia->contexto, 'perfil')))
                <div class="admin-report-context">
                    <h2>Perfil preservado</h2>

                    <div class="admin-report-profile-snapshot">
                        @if (data_get($denuncia->contexto, 'perfil.avatar_evidencia'))
                            <a href="{{ route('admin.denuncias.arquivo', [$denuncia, 'perfil']) }}" target="_blank" rel="noopener">
                                <img src="{{ route('admin.denuncias.arquivo', [$denuncia, 'perfil']) }}" alt="Avatar preservado do perfil">
                            </a>
                        @endif

                        <div>
                            <strong>{{ data_get($denuncia->contexto, 'perfil.nickname', 'Usuário') }}</strong>
                            <p>{{ data_get($denuncia->contexto, 'perfil.bio') ?: 'Sem bio no momento da denúncia.' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($denuncia->tipo_denuncia === 'post' && ! empty(data_get($denuncia->contexto, 'post')))
                <div class="admin-report-context">
                    <h2>Publicação preservada</h2>

                    @if (data_get($denuncia->contexto, 'post.foto_evidencia'))
                        <div class="admin-report-evidence">
                            <a href="{{ route('admin.denuncias.arquivo', [$denuncia, 'post']) }}" target="_blank" rel="noopener">
                                <img src="{{ route('admin.denuncias.arquivo', [$denuncia, 'post']) }}" alt="Imagem preservada da publicação">
                            </a>
                        </div>
                    @endif

                    <div class="admin-report-description">
                        <p>{{ data_get($denuncia->contexto, 'post.descricao') ?: 'Publicação sem legenda.' }}</p>

                        @if (data_get($denuncia->contexto, 'post.data_publicacao'))
                            <small>
                                Publicada em {{ \Carbon\Carbon::parse(data_get($denuncia->contexto, 'post.data_publicacao'))->format('d/m/Y H:i') }}
                            </small>
                        @endif
                    </div>
                </div>
            @endif

            @if ($denuncia->tipo_denuncia === 'mensagem' || ($denuncia->tipo_denuncia === 'post' && empty(data_get($denuncia->contexto, 'post'))))
                <div class="admin-report-context">
                    <h2>Registro anterior</h2>
                    <p>Esta denúncia veio da estrutura antiga do banco e foi preservada durante a atualização.</p>
                </div>
            @endif
        </section>

        <aside class="admin-report-detail-side">
            @if ($denuncia->status_denuncia === 'pendente')
                <section class="admin-panel admin-report-decision">
                    <h2>Decisão</h2>
                    <p>Registre a conclusão da análise. Uma denúncia procedente não precisa obrigatoriamente gerar banimento.</p>

                    <form method="POST" action="{{ route('admin.denuncias.aceitar', $denuncia) }}">
                        @csrf
                        @method('PATCH')

                        <label for="reportDecisionJustification" class="form-label">Justificativa da decisão</label>
                        <textarea id="reportDecisionJustification" name="justificativa" class="form-control squad-scrollbar" rows="5" minlength="10" maxlength="2000" required></textarea>

                        <div class="admin-report-decision-actions">
                            <button type="submit" formaction="{{ route('admin.denuncias.negar', $denuncia) }}" class="btn btn-secondary">Não procede</button>
                            <button type="submit" class="btn admin-game-save">Procede sem banimento</button>
                        </div>
                    </form>
                </section>

                @if ($denuncia->denunciado && $denuncia->denunciado->status_conta === 'ativo')
                    <section class="admin-panel admin-report-ban">
                        <h2>Aplicar banimento</h2>
                        <p>O banimento também concluirá esta denúncia como procedente.</p>

                        <form method="POST" action="{{ route('admin.usuarios.banir', $denuncia->denunciado) }}">
                            @csrf
                            <input type="hidden" name="id_denuncia" value="{{ $denuncia->id_denuncia }}">

                            <div>
                                <label for="reportBanReason" class="form-label">Motivo</label>
                                <input type="text" id="reportBanReason" name="motivo" class="form-control" maxlength="500" value="Denúncia #{{ $denuncia->id_denuncia }} - {{ $denuncia->motivoTexto() }}" required>
                            </div>

                            <div>
                                <label for="reportBanUntil" class="form-label">Banido até</label>
                                <input type="date" id="reportBanUntil" name="data_fim" class="form-control" required>
                            </div>

                            <div>
                                <label for="reportBanJustification" class="form-label">Justificativa</label>
                                <textarea id="reportBanJustification" name="justificativa" class="form-control squad-scrollbar" rows="4" maxlength="2000" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-danger">Banir usuário</button>
                        </form>
                    </section>
                @elseif ($denuncia->denunciado?->status_conta === 'banido')
                    <section class="admin-panel admin-report-already-banned">
                        <i class="bi bi-person-x-fill"></i>
                        <div>
                            <strong>Usuário já está banido</strong>
                            <span>Você ainda pode concluir esta denúncia como procedente ou não procedente.</span>
                        </div>
                    </section>
                @endif
            @else
                <section class="admin-panel admin-report-resolution">
                    <h2>Resultado da análise</h2>
                    <p>{{ $denuncia->justificativa }}</p>
                    <small>
                        {{ $administrador ?: 'Administrador' }} · {{ $denuncia->data_resolucao?->format('d/m/Y H:i') }}
                    </small>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
