@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <main class="password-page" id="verificacaoEmail" data-status-url="{{ route('verification.status') }}">
        <div class="password-card email-verification-card">
            <i class="bi bi-envelope-check email-verification-icon"></i>

            <h1>Confirme seu e-mail</h1>

            <p>
                Enviamos um link de confirmação para
                <strong>{{ auth()->user()->email }}</strong>.
                Abra o e-mail e confirme seu cadastro para continuar.
            </p>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @error('email')
                <div class="alert alert-danger">
                    {{ $message }}
                </div>
            @enderror

            <form
                method="POST"
                action="{{ route('verification.send') }}"
                class="email-verification-actions"
            >
                @csrf

                <button type="submit" class="btn password-button">
                    Reenviar e-mail
                </button>
            </form>

            <form
                method="POST"
                action="{{ route('logout') }}"
                class="email-verification-actions"
            >
                @csrf

                <button type="submit" class="password-back email-verification-logout">
                    Sair da conta
                </button>
            </form>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const page = document.getElementById('verificacaoEmail');
            let consultando = false;

            async function conferirVerificacao() {
                if (document.hidden || consultando) {
                    return;
                }

                consultando = true;

                try {
                    const response = await fetch(page.dataset.statusUrl, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });

                    if (response.ok) {
                        const status = await response.json();

                        if (status.verified && status.redirect) {
                            window.location.replace(status.redirect);
                        }
                    }
                } catch (error) {
                    return;
                } finally {
                    consultando = false;
                }
            }

            document.addEventListener('visibilitychange', conferirVerificacao);
            window.addEventListener('focus', conferirVerificacao);
            setInterval(conferirVerificacao, 4000);
            conferirVerificacao();
        });
    </script>
@endsection
