@extends('layouts.app')

@section('content')

    <main class="login-page">

        <div class="login-container">

            <img
                src="{{ asset('images/logo.png') }}"
                alt="Squad Maker"
                class="login-logo"
            >

            <p class="login-subtitle">
                Encontre quem joga do seu jeito!
            </p>

            <form
                method="POST"
                action="{{ route('login.submit') }}"
                class="login-form"
            >
                @csrf

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="email"
                    value="{{ old('email') }}"
                >

                @error('email')
                <span class="login-error">
                    {{ $message }}
                </span>
                @enderror

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="senha"
                >

                @error('password')
                <span class="login-error">
                    {{ $message }}
                </span>
                @enderror

                <button
                    type="submit"
                    class="btn login-button"
                >
                    Iniciar sessão
                </button>

            </form>

            <a
                href="{{ route('google.redirect') }}"
                class="google-link"
            >
                Entrar com Google
            </a>

            <a href="#" class="register-link">
                Ou cadastre-se gratuitamente
            </a>

        </div>

    </main>

@endsection
