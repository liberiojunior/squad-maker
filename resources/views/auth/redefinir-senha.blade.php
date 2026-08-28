@extends('layouts.app')

@section('content')

    <main class="password-page">

        <div class="password-card">

            <h1>Nova senha</h1>

            <p>
                Escolha uma nova senha para sua conta.
            </p>

            <form
                method="POST"
                action="{{ route('password.update') }}"
                class="password-form"
            >
                @csrf

                <input
                    type="hidden"
                    name="token"
                    value="{{ $token }}"
                >

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="{{ old('email', $email) }}"
                    readonly
                >

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Nova senha"
                >

                <input
                    type="password"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Confirmar nova senha"
                >

                @error('password')
                <span class="register-error">
                    {{ $message }}
                </span>
                @enderror

                @error('email')
                <span class="register-error">
                    {{ $message }}
                </span>
                @enderror

                <button
                    type="submit"
                    class="btn password-button"
                >
                    Alterar senha
                </button>

            </form>

        </div>

    </main>

@endsection
