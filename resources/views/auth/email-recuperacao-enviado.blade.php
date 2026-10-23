@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')

    <main class="password-page">

        <div class="password-card password-sent-card">

            <div class="password-sent-icon" aria-hidden="true">
                <i class="bi bi-envelope-check"></i>
            </div>

            <h1>Confira seu e-mail</h1>

            <p>
                Enviamos um link para você redefinir sua senha.
                Confira também a caixa de spam caso não encontre a mensagem.
            </p>

            <div class="password-sent-note">
                <i class="bi bi-clock"></i>
                <span>O link é temporário e expira por segurança.</span>
            </div>

            <a
                href="{{ route('login') }}"
                class="btn password-button password-sent-button"
            >
                Voltar para o login
            </a>

            <a
                href="{{ route('password.request') }}"
                class="password-back"
            >
                Solicitar outro link
            </a>

        </div>

    </main>

@endsection
