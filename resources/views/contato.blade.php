@extends('layouts.app')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
@endpush

@section('content')

    <main class="contact-page">

        <div class="container">

            <div class="contact-card">

                <div class="row">

                    <div class="col-lg-5 contact-info">

                        <h1>Contato</h1>

                        <p>
                            Tem alguma sugestão, dúvida ou crítica sobre o projeto?
                            Envie uma mensagem para a equipe do Squad Maker.
                            A resposta será enviada para o e-mail informado no formulário.
                        </p>

                        <p>
                            Você também pode falar com a equipe pelo e-mail
                            <strong>suportesquadmaker@gmail.com</strong>.
                        </p>
                    </div>

                    <div class="col-lg-7">

                        <h2 class="contact-form-title">
                            Envie-nos uma mensagem!
                        </h2>

                        @if (session('status'))
                            <div class="alert alert-success" role="status">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('contato'))
                            <div class="alert alert-danger" role="alert">
                                {{ $errors->first('contato') }}
                            </div>
                        @endif

                        <form
                            class="contact-form"
                            method="POST"
                            action="{{ route('contato.enviar') }}"
                        >
                            @csrf

                            <div>
                                <label for="nome" class="form-label">
                                    Nome
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    class="form-control @error('nome') is-invalid @enderror"
                                    value="{{ old('nome') }}"
                                    maxlength="100"
                                    required
                                >

                                @error('nome')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
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
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    maxlength="150"
                                    required
                                >

                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div>
                                <label for="mensagem" class="form-label">
                                    Mensagem
                                </label>

                                <textarea
                                    id="mensagem"
                                    name="mensagem"
                                    class="form-control @error('mensagem') is-invalid @enderror"
                                    rows="5"
                                    maxlength="3000"
                                    required
                                >{{ old('mensagem') }}</textarea>

                                @error('mensagem')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="btn contact-button"
                            >
                                Enviar
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </main>

@endsection
