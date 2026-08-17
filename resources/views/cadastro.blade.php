@extends('layouts.app')

@section('content')

    <main class="register-page">

        <div class="register-container">

            <img
                src="{{ asset('images/logo.png') }}"
                alt="Squad Maker"
                class="register-logo"
            >

            <div class="register-card">

                <h1>Crie sua conta</h1>

                <p class="register-subtitle">
                    Preencha os dados para criar seu acesso.
                </p>

                <form
                    method="POST"
                    action="{{ route('cadastro.store') }}"
                    class="register-form"
                >
                    @csrf

                    <div>
                        <label for="nickname" class="form-label">
                            Nome que será mostrado
                        </label>

                        <input
                            type="text"
                            id="nickname"
                            name="nickname"
                            class="form-control"
                            maxlength="80"
                            value="{{ old('nickname') }}"
                        >

                        @error('nickname')
                        <span class="register-error">
                            {{ $message }}
                        </span>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="form-label">
                            E-mail
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email') }}"
                        >

                        @error('email')
                        <span class="register-error">
                            {{ $message }}
                        </span>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">
                            Senha
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                        >

                        @error('password')
                        <span class="register-error">
                            {{ $message }}
                        </span>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">
                            Confirmar senha
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                        >
                    </div>

                    <div class="form-check register-age">
                        <input
                            type="checkbox"
                            id="maior_idade"
                            name="maior_idade"
                            value="1"
                            class="form-check-input"
                            {{ old('maior_idade') ? 'checked' : '' }}
                        >

                        <label
                            for="maior_idade"
                            class="form-check-label"
                        >
                            Declaro que tenho 18 anos ou mais.
                        </label>
                    </div>

                    @error('maior_idade')
                    <span class="register-error">
                        {{ $message }}
                    </span>
                    @enderror

                    <button
                        type="submit"
                        class="btn register-button"
                    >
                        Criar conta
                    </button>

                </form>

                <a
                    href="{{ route('login') }}"
                    class="register-login-link"
                >
                    Já tenho uma conta
                </a>

            </div>

        </div>

    </main>

@endsection
