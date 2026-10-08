@extends('layouts.internal')

@section('content')
    @php
        $niveis = \App\Support\NivelProficiencia::detalhes();

        $descricoesAjuda = [
            1 => 'Você ainda está conhecendo o jogo, aprendendo mecânicas, mapas, personagens ou estratégias. É ideal para quem procura jogadores pacientes, dispostos a ensinar, experimentar e evoluir junto, sem cobrança por desempenho.',
            2 => 'Você já consegue aproveitar o jogo com autonomia, mas joga principalmente por diversão. Prefere partidas mais tranquilas, sem pressão constante por vitória, ranking ou desempenho perfeito. O objetivo principal é ter uma boa experiência com outras pessoas.',
            3 => 'Você conhece bem o jogo, participa com frequência e procura companheiros igualmente presentes. Gosta de melhorar, aprender novas estratégias e jogar em equipe, mantendo um equilíbrio entre diversão e desempenho.',
            4 => 'Você busca partidas mais sérias, com foco em desempenho, estratégia, comunicação e resultado. Espera que o grupo conheça bem o jogo, trabalhe em equipe e esteja disposto a analisar erros e evoluir constantemente.',
            5 => 'Você possui bastante experiência e alto envolvimento com o jogo. Procura jogadores igualmente dedicados para enfrentar conteúdos difíceis, desafios avançados ou ambientes altamente competitivos, com maior exigência de conhecimento, comunicação e comprometimento.',
        ];
    @endphp

    <div class="help-page">
        <header class="help-hero">
            <div>
                <span class="help-eyebrow">
                    <i class="bi bi-question-circle-fill"></i>
                    Central de Ajuda
                </span>

                <h1>Perguntas frequentes</h1>

                <p>
                    Abra apenas a dúvida que precisa. Aqui você encontra orientações rápidas
                    para montar seu perfil, encontrar jogadores e usar os principais recursos do Squad Maker.
                </p>
            </div>

            <a href="{{ route('jogos.buscar') }}" class="help-primary-action">
                Encontrar jogadores
                <i class="bi bi-arrow-right"></i>
            </a>
        </header>

        <section class="help-faq-section" aria-labelledby="helpFaqTitle">
            <div class="help-section-heading">
                <span>Ajuda rápida</span>
                <h2 id="helpFaqTitle">Como podemos ajudar?</h2>
                <p>
                    As respostas ficam minimizadas para você localizar o assunto sem precisar percorrer uma página longa.
                </p>
            </div>

            <div class="help-accordion-list">
                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-rocket-takeoff"></i>
                            Como começo a usar o Squad Maker?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content">
                        <div class="help-steps-grid">
                            <a href="{{ route('perfil') }}" class="help-step-card">
                                <span class="help-step-number">1</span>
                                <i class="bi bi-person-lines-fill"></i>
                                <strong>Complete seu perfil</strong>
                                <p>Adicione uma bio e seus gêneros favoritos para mostrar melhor como você joga.</p>
                            </a>

                            <a href="{{ route('perfil') }}" class="help-step-card">
                                <span class="help-step-number">2</span>
                                <i class="bi bi-controller"></i>
                                <strong>Adicione seus jogos</strong>
                                <p>Escolha seus principais jogos e informe seu nível em cada um deles.</p>
                            </a>

                            <a href="{{ route('perfil') }}" class="help-step-card">
                                <span class="help-step-number">3</span>
                                <i class="bi bi-display"></i>
                                <strong>Escolha plataformas</strong>
                                <p>Mostre onde você joga e organize a ordem em que elas aparecem no perfil.</p>
                            </a>

                            <a href="{{ route('jogos.buscar') }}" class="help-step-card">
                                <span class="help-step-number">4</span>
                                <i class="bi bi-people-fill"></i>
                                <strong>Encontre parceiros</strong>
                                <p>Procure um jogo, aplique filtros e visite os perfis de outros jogadores.</p>
                            </a>
                        </div>
                    </div>
                </details>

                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-search"></i>
                            Como encontro uma pessoa para jogar?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content">
                        <div class="help-search-guide">
                            <ol class="help-search-steps">
                                <li>
                                    <span>1</span>
                                    <div>
                                        <strong>Abra Buscar jogos</strong>
                                        <p>Pesquise pelo nome do jogo ou use os filtros disponíveis.</p>
                                    </div>
                                </li>

                                <li>
                                    <span>2</span>
                                    <div>
                                        <strong>Escolha um jogo</strong>
                                        <p>Na página do jogo você verá os usuários que também o adicionaram ao perfil.</p>
                                    </div>
                                </li>

                                <li>
                                    <span>3</span>
                                    <div>
                                        <strong>Refine pelo nível</strong>
                                        <p>Use o nível para encontrar pessoas que procuram uma experiência parecida com a sua.</p>
                                    </div>
                                </li>

                                <li>
                                    <span>4</span>
                                    <div>
                                        <strong>Confira o perfil</strong>
                                        <p>Veja bio, jogos, plataformas e publicações antes de enviar amizade.</p>
                                    </div>
                                </li>

                                <li>
                                    <span>5</span>
                                    <div>
                                        <strong>Conecte-se</strong>
                                        <p>Depois que a amizade for aceita, vocês podem conversar pelo chat.</p>
                                    </div>
                                </li>
                            </ol>

                            <aside class="help-tip-card">
                                <i class="bi bi-lightbulb-fill"></i>
                                <div>
                                    <strong>Dica</strong>
                                    <p>
                                        Quanto mais completo estiver seu perfil, mais fácil será para outra pessoa
                                        entender se vocês combinam antes de aceitar uma amizade.
                                    </p>
                                </div>
                            </aside>
                        </div>
                    </div>
                </details>

                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-bar-chart-steps"></i>
                            O que significa cada nível de jogo?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content">
                        <p class="help-level-intro">
                            O nível é definido separadamente para cada jogo. Ele representa como você joga e o tipo de parceiro
                            que procura; não precisa ser igual ao seu elo ou rank dentro do jogo.
                        </p>

                        <div class="help-level-grid">
                            @foreach ($niveis as $valor => $nivel)
                                <article
                                    class="help-level-card"
                                    style="--help-level-color: {{ $nivel['cor'] }};"
                                >
                                    <div class="help-level-image">
                                        @if (file_exists(public_path($nivel['icone'])))
                                            <img
                                                src="{{ asset($nivel['icone']) }}"
                                                alt="Nível {{ $nivel['nome'] }}"
                                            >
                                        @else
                                            <i class="bi bi-star-fill"></i>
                                        @endif
                                    </div>

                                    <div>
                                        <span class="help-level-label">Nível {{ $valor }}</span>
                                        <h3>{{ $nivel['nome'] }}</h3>
                                        <p>{{ $descricoesAjuda[$valor] }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="help-level-note">
                            <i class="bi bi-info-circle-fill"></i>
                            <p>
                                <strong>Não sabe qual escolher?</strong>
                                Selecione o nível que melhor representa a experiência que você procura atualmente.
                                Você pode alterá-lo depois no seu perfil. Também é normal ser Hardcore em um jogo e
                                Iniciante em outro.
                            </p>
                        </div>
                    </div>
                </details>

                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-person-gear"></i>
                            Como organizo meu perfil, jogos e plataformas?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content help-text-content">
                        <p>
                            Seu perfil reúne as informações que outros jogadores usam para conhecer você.
                            Mantenha a bio, gêneros, jogos, níveis e plataformas atualizados.
                        </p>
                        <p>
                            Em <strong>Meus Jogos</strong>, você pode adicionar títulos, alterar o nível,
                            remover jogos e definir a ordem em que aparecem. Em <strong>Plataformas</strong>,
                            também é possível escolher e reorganizar onde você joga.
                        </p>
                    </div>
                </details>

                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-chat-dots-fill"></i>
                            Como funcionam amizades e chat?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content help-text-content">
                        <p>
                            Para conversar com alguém, envie uma solicitação de amizade pelo perfil da pessoa.
                            Depois que ela aceitar, a conversa fica disponível no Chat.
                        </p>
                        <p>
                            As mensagens ficam salvas no sistema. Se houver uma instabilidade momentânea na entrega em tempo real,
                            o histórico continua preservado.
                        </p>
                    </div>
                </details>

                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-house-fill"></i>
                            Como funcionam o mural e as publicações?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content help-text-content">
                        <p>
                            No mural aparecem suas publicações e as dos seus parceiros. Você pode publicar somente texto,
                            somente imagem ou combinar os dois.
                        </p>
                        <p>
                            Publicações podem receber reações. Na sua galeria pessoal você também pode editar,
                            fixar ou excluir o que publicou.
                        </p>
                    </div>
                </details>

                <details class="help-accordion">
                    <summary>
                        <span>
                            <i class="bi bi-shield-check"></i>
                            Como funcionam segurança e denúncias?
                        </span>
                        <i class="bi bi-chevron-down help-accordion-arrow"></i>
                    </summary>

                    <div class="help-accordion-content help-text-content">
                        <p>
                            Use as opções de denúncia quando encontrar comportamento inadequado em perfis,
                            conversas, mensagens ou publicações. As denúncias são encaminhadas para análise da administração.
                        </p>
                        <p>
                            Evite compartilhar informações pessoais sensíveis e mantenha uma comunicação respeitosa
                            com os outros usuários da plataforma.
                        </p>
                    </div>
                </details>
            </div>
        </section>
    </div>
@endsection
