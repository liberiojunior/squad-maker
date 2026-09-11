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

                <h1>Termos de Uso e Política de Privacidade</h1>

                <p>
                    Ao utilizar o Squad Maker, o usuário declara estar de acordo
                    com os termos descritos nesta página.
                </p>

                <h2>1. Sobre o Squad Maker</h2>

                <p>
                    O Squad Maker é uma plataforma desenvolvida com a finalidade
                    de facilitar a criação e organização de grupos de jogadores,
                    permitindo que usuários encontrem outras pessoas para jogar
                    em conjunto.
                </p>

                <h2>2. Cadastro</h2>

                <p>
                    Para utilizar determinadas funcionalidades da plataforma,
                    o usuário deverá realizar um cadastro fornecendo informações
                    válidas e mantendo a segurança de sua conta e senha.
                </p>

                <h2>3. Verificação de idade</h2>

                <p>
                    O Squad Maker é destinado exclusivamente a usuários com 18 anos ou mais.
                    Durante o cadastro, o CPF informado será consultado por meio de um serviço
                    externo de consulta cadastral, exclusivamente para fins de verificação de idade.
                </p>

                <p>
                    Para essa finalidade, são utilizadas apenas as informações necessárias à aferição
                    da maioridade. O CPF informado e a data de nascimento obtida durante a consulta
                    não são persistidos no banco de dados do Squad Maker.
                </p>

                <h2>4. Tratamento de dados</h2>

                <p>
                    Os dados fornecidos durante o cadastro serão utilizados para
                    permitir o funcionamento da conta e das funcionalidades da
                    plataforma.
                </p>

                <p>
                    Informações utilizadas exclusivamente para aferição de idade
                    serão tratadas somente durante o processo necessário para
                    essa finalidade, observadas as medidas técnicas aplicáveis
                    ao projeto.
                </p>

                <h2>5. Responsabilidades do usuário</h2>

                <p>
                    O usuário é responsável pelas informações fornecidas durante
                    o cadastro, pela utilização de sua conta e pela manutenção
                    da confidencialidade de sua senha.
                </p>

                <p>
                    Não é permitido utilizar dados pessoais ou documentos de
                    terceiros com o objetivo de contornar os mecanismos de
                    verificação da plataforma.
                </p>

                <h2>6. Conduta na plataforma</h2>

                <p>
                    O usuário deverá utilizar o Squad Maker de maneira respeitosa
                    e lícita, não sendo permitidas práticas abusivas, tentativas
                    de fraude, assédio, discriminação ou utilização indevida das
                    funcionalidades disponibilizadas.
                </p>

                <h2>7. Disponibilidade</h2>

                <p>
                    Por se tratar de um projeto acadêmico em desenvolvimento,
                    funcionalidades poderão ser modificadas, suspensas ou
                    descontinuadas durante a evolução do sistema.
                </p>

                <h2>8. Alterações dos termos</h2>

                <p>
                    Estes termos poderão ser atualizados conforme novas
                    funcionalidades e requisitos forem incorporados ao
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
