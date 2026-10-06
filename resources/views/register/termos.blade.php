@extends('layouts.app')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')

    <main class="register-page">

        <div class="register-container">

            <img
                src="{{ asset('images/logo.png') }}"
                alt="Squad Maker"
                class="register-logo"
            >

            <div class="register-card">

                <h1>Termos de Uso e Política de Privacidade</h1>

                <p class="register-subtitle">
                    Última atualização: 6 de outubro de 2026.
                </p>

                <p>
                    Ao criar uma conta ou utilizar o Squad Maker, o usuário declara ter lido
                    e estar de acordo com os termos e regras descritos nesta página.
                </p>

                <h2>1. Sobre o Squad Maker</h2>

                <p>
                    O Squad Maker é uma plataforma voltada à conexão entre jogadores,
                    permitindo encontrar pessoas com interesses em comum, personalizar o
                    perfil, adicionar jogos e plataformas, criar amizades e conversar com
                    outros usuários.
                </p>

                <p>
                    A plataforma faz parte de um projeto acadêmico em desenvolvimento.
                    Funcionalidades poderão ser ajustadas ao longo da evolução do sistema.
                </p>

                <h2>2. Cadastro e uso da conta</h2>

                <p>
                    Para utilizar as funcionalidades restritas da plataforma, o usuário deverá
                    fornecer informações válidas, manter seus dados de acesso protegidos e não
                    compartilhar sua conta com terceiros.
                </p>

                <p>
                    O usuário é responsável pelas ações realizadas por meio de sua conta e deve
                    comunicar qualquer suspeita de uso indevido pelos canais disponibilizados
                    pela plataforma.
                </p>

                <h2>3. Verificação de idade</h2>

                <p>
                    O Squad Maker é destinado exclusivamente a pessoas com 18 anos ou mais.
                    Durante o cadastro, o CPF informado é utilizado em uma consulta externa
                    destinada à verificação da maioridade.
                </p>

                <p>
                    Para essa finalidade, são utilizadas somente as informações necessárias à
                    aferição da idade. O CPF informado e a data de nascimento obtida durante a
                    consulta não são persistidos no banco de dados do Squad Maker após a
                    conclusão da verificação.
                </p>

                <p>
                    Não é permitido utilizar CPF, documento ou identidade de terceiros para
                    contornar a verificação de idade ou qualquer outro mecanismo de segurança.
                </p>

                <h2>4. Dados tratados pela plataforma</h2>

                <p>
                    A plataforma poderá tratar informações necessárias ao funcionamento da
                    conta e de seus recursos, como e-mail, nickname, avatar, bio, jogos,
                    plataformas, preferências de perfil, amizades, mensagens e informações
                    técnicas relacionadas ao uso e à segurança da aplicação.
                </p>

                <p>
                    Os dados são utilizados para disponibilizar as funcionalidades solicitadas,
                    manter a segurança da plataforma, prevenir abusos, permitir moderação e
                    cumprir obrigações aplicáveis ao projeto.
                </p>

                <p>
                    O Squad Maker adota o princípio de utilizar somente os dados necessários
                    para cada finalidade e não comercializa dados pessoais de seus usuários.
                </p>

                <h2>5. Perfil e conteúdo do usuário</h2>

                <p>
                    Informações adicionadas ao perfil, como nickname, avatar, bio, jogos,
                    gêneros e plataformas, poderão ser exibidas a outros usuários conforme o
                    funcionamento normal da plataforma.
                </p>

                <p>
                    O usuário não deve publicar imagens, textos ou informações ilícitas,
                    ofensivas, discriminatórias, sexualmente inadequadas, fraudulentas ou que
                    violem direitos de terceiros.
                </p>

                <h2>6. Amizades e mensagens</h2>

                <p>
                    O chat é destinado à comunicação entre usuários que possuam uma relação de
                    amizade aceita dentro da plataforma. As mensagens são armazenadas para
                    permitir o histórico da conversa e o funcionamento do serviço.
                </p>

                <p>
                    O usuário deve utilizar o chat de forma respeitosa. Assédio, ameaças,
                    discriminação, spam, fraude e outras formas de abuso poderão resultar em
                    denúncia, análise administrativa e aplicação de medidas de moderação.
                </p>

                <h2>7. Denúncias e moderação</h2>

                <p>
                    Usuários poderão denunciar perfis e conversas quando identificarem conteúdo
                    ou comportamento incompatível com as regras da plataforma.
                </p>

                <p>
                    Em denúncias de conversa, o usuário será informado antes do envio de que
                    até as 20 mensagens mais recentes poderão ser anexadas ao registro da
                    denúncia para fornecer contexto à equipe responsável pela análise.
                </p>

                <p>
                    O envio de imagem ou print como evidência é opcional. Quando fornecido, o
                    anexo é armazenado de forma privada e destinado à análise administrativa da
                    denúncia.
                </p>

                <p>
                    Em denúncias de perfil, informações necessárias para registrar o estado do
                    perfil no momento da denúncia, como nickname, bio e avatar, poderão ser
                    preservadas para auxiliar na apuração.
                </p>

                <p>
                    As evidências de moderação devem ser acessadas somente por pessoas
                    autorizadas e mantidas pelo período necessário à análise, segurança e
                    registro das medidas aplicadas.
                </p>

                <h2>8. Suspensões e medidas administrativas</h2>

                <p>
                    O Squad Maker poderá aplicar advertências, restrições ou suspensões quando
                    forem identificadas violações das regras da plataforma.
                </p>

                <p>
                    A tentativa de excluir a conta, criar uma nova conta ou utilizar dados de
                    terceiros com a finalidade de contornar uma suspensão constitui violação
                    destes termos e poderá resultar em novas medidas administrativas.
                </p>

                <h2>9. Exclusão da conta</h2>

                <p>
                    O usuário poderá solicitar a exclusão de sua conta conforme as opções
                    disponibilizadas pela plataforma, observadas restrições necessárias à
                    segurança, à prevenção de fraude e à conclusão de procedimentos de
                    moderação em andamento.
                </p>

                <p>
                    A exclusão da conta não implica necessariamente a eliminação imediata de
                    registros que precisem ser mantidos temporariamente para segurança,
                    prevenção de abuso, apuração de denúncias ou cumprimento de obrigações
                    aplicáveis.
                </p>

                <h2>10. Serviços externos</h2>

                <p>
                    Algumas funcionalidades podem utilizar serviços externos, como autenticação
                    com Google, dados públicos relacionados a jogos da Steam e serviços
                    utilizados na verificação de idade.
                </p>

                <p>
                    Esses serviços possuem seus próprios termos, políticas e condições de
                    funcionamento, podendo ocorrer indisponibilidades fora do controle do
                    Squad Maker.
                </p>

                <h2>11. Segurança</h2>

                <p>
                    O Squad Maker utiliza medidas técnicas e organizacionais compatíveis com o
                    estágio atual do projeto para proteger contas, dados e funcionalidades
                    contra acesso indevido, abuso e perda acidental.
                </p>

                <p>
                    Nenhum sistema é totalmente imune a falhas. O usuário também deve colaborar
                    com a segurança utilizando senha adequada e evitando compartilhar suas
                    credenciais.
                </p>

                <h2>12. Conduta na plataforma</h2>

                <p>
                    É proibido utilizar o Squad Maker para praticar fraude, assédio,
                    discriminação, ameaça, perseguição, exploração, envio abusivo de conteúdo,
                    tentativa de invasão, manipulação das funcionalidades ou qualquer conduta
                    ilícita.
                </p>

                <p>
                    O usuário também não deve explorar falhas, automatizar ações abusivas,
                    tentar acessar dados de terceiros ou interferir propositalmente no
                    funcionamento da plataforma.
                </p>

                <h2>13. Disponibilidade do serviço</h2>

                <p>
                    Por se tratar de um projeto acadêmico em desenvolvimento, funcionalidades
                    poderão ser modificadas, suspensas ou descontinuadas. Atualizações,
                    manutenções e falhas de serviços externos também poderão afetar
                    temporariamente a disponibilidade da plataforma.
                </p>

                <h2>14. Alterações destes termos</h2>

                <p>
                    Estes termos poderão ser atualizados conforme novas funcionalidades,
                    requisitos de segurança, mecanismos de moderação ou necessidades do projeto
                    forem incorporados ao Squad Maker.
                </p>

                <p>
                    Alterações relevantes deverão ser apresentadas de maneira clara aos
                    usuários quando necessário.
                </p>

                <h2>15. Contato</h2>

                <p>
                    Dúvidas sobre a plataforma, privacidade ou funcionamento dos recursos
                    poderão ser encaminhadas pelos canais de contato disponibilizados pelo
                    Squad Maker.
                </p>

                <a
                    href="{{ route('cadastro') }}"
                    class="register-login-link"
                >
                    Voltar para o cadastro
                </a>

            </div>

        </div>

    </main>

@endsection
