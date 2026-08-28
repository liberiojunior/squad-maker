@extends('layouts.app')

@section('content')

    <main class="password-page">

        <div class="password-card">

            <h1>Esqueci minha senha</h1>

            <p>
                Informe o e-mail cadastrado para receber
                um link de recuperação.
            </p>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('password.email') }}"
                class="password-form"
            >
                @csrf

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="E-mail"
                    value="{{ old('email') }}"
                >

                @error('email')
                <span class="register-error">
                    {{ $message }}
                </span>
                @enderror

                <button
                    type="submit"
                    class="btn password-button"
                >
                    Enviar link
                </button>

            </form>

            <a
                href="{{ route('login') }}"
                class="password-back"
            >
                Voltar para o login
            </a>

        </div>

    </main>

@endsection
