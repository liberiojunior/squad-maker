@extends('layouts.app')

@section('content')

    <main class="team-page">

        <div class="container">

            <div class="team-intro">
                <h1 class="team-title">Nossa Equipe</h1>

                <p>
                    Estudantes de Sistemas de Informação da faculdade Cotemig, tiveram a ideia
                    do Squad Maker após um tempo que se conheceram, e viram a dificuldade que
                    é achar um amigo que combine com você para jogar os jogos que ambos gostem.
                </p>
            </div>

            <div class="row justify-content-center g-4">

                <div class="col-lg-5 col-md-6">
                    <div class="team-card">

                        <img
                            src="{{ asset('images/equipe/alan.png') }}"
                            alt="Alan Johny"
                            class="team-photo"
                        >

                        <h2>Alan Johny</h2>

                        <p>
                            Responsável pela parte criativa e visual do projeto, atuando no frontend e no
                            design das interfaces. Apaixonado por jogos desde criança, tem como favoritos
                            Bioshock, Dark Souls, Bioshock, OneShot, Skyrim, Shadow of The Colossus, Batman,
                            Pathologic 2 e Dispatch.
                        </p>

                    </div>
                </div>

                <div class="col-lg-5 col-md-6">
                    <div class="team-card">

                        <img
                            src="{{ asset('images/equipe/liberio.png') }}"
                            alt="Libério"
                            class="team-photo"
                        >

                        <h2>Libério</h2>

                        <p>
                            Responsável pela estrutura interna do projeto, atuando no banco de dados
                            e no backend. Nas horas livres, gosta de treinar na academia  e jogar títulos
                            que misturam desafio e humor como Zort, Backrooms e Labyrinthine,
                            ocasionalmente abraçando um velho amigo, Albion Online.
                        </p>

                    </div>
                </div>

            </div>

        </div>

    </main>

@endsection
