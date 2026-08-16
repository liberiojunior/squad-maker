@extends('layouts.app')

@section('content')

    <main class="contact-page">

        <div class="container">

            <div class="contact-card">

                <div class="row">

                    <div class="col-lg-5 contact-info">

                        <h1>Contato</h1>

                        <p>
                            Tem alguma sugestão ou crítica sobre o projeto?
                            Entre em contato com a equipe do Squad Maker e envie
                            sua mensagem para a equipe através do formulário ao lado.
                        </p>
                    </div>

                    <div class="col-lg-7">

                        <h2 class="contact-form-title">
                            Envie-nos uma mensagem!
                        </h2>

                        <form class="contact-form">

                            <div>
                                <label for="nome" class="form-label">
                                    Nome
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    class="form-control"
                                >
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
                                >
                            </div>

                            <div>
                                <label for="mensagem" class="form-label">
                                    Mensagem
                                </label>

                                <textarea
                                    id="mensagem"
                                    name="mensagem"
                                    class="form-control"
                                    rows="5"
                                ></textarea>
                            </div>

                            <button
                                type="button"
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
