@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <main class="password-page">
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
@endsection
