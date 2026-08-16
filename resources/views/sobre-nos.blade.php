@extends('layouts.app')

@section('content')

    <main class="about-page">

        <div class="container">

            <div class="row align-items-center">

                <div class="col-lg-5 text-center">
                    <img
                        src="{{ asset('images/mascote.png') }}"
                        alt="Mascote Squad Maker"
                        class="about-mascot"
                    >
                </div>

                <div class="col-lg-7">

                    <div class="about-card">

                        <h1>Sobre Nós</h1>

                        <p>
                            No Squad Maker, acreditamos que jogar é muito mais
                            do que apenas diversão — é sobre conexão. Nossa missão
                            é ajudar você a encontrar o parceiro ideal, alguém que
                            jogue do mesmo jeito que você e compartilhe sua energia
                            dentro e fora das partidas.
                        </p>

                        <p>
                            Nosso mascote, Atlas, representa essa jornada. Assim
                            como existem milhares de estrelas espalhadas pelo
                            universo, existem também milhares de jogadores únicos
                            por aí. O nosso papel é simples: guiá-lo até aqueles
                            que brilham tão forte quanto você, transformando cada
                            partida em uma experiência inesquecível.
                        </p>

                        <p>
                            Com nossa estética espacial e um aplicativo pensado
                            para a comunidade gamer, queremos que cada jogador
                            encontre seu espaço, seu time e, acima de tudo,
                            pessoas que tornem cada jogo ainda mais divertido.
                        </p>

                        <div class="text-center">
                            <a href="#" class="btn about-button">
                                Junte-se a essa aventura!
                            </a>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

@endsection
