<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirme seu e-mail</title>
</head>
<body style="margin:0;padding:0;background:#14002b;font-family:Arial,Helvetica,sans-serif;color:#26142f;">
    <div style="max-width:620px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:14px;padding:32px;">
            <h1 style="margin:0 0 18px;color:#650095;font-size:26px;">Confirme seu e-mail</h1>

            <p>Olá, {{ $usuario->nickname }}!</p>

            <p>
                Confirme seu endereço de e-mail para concluir o cadastro e acessar o Squad Maker.
            </p>

            <p style="margin:28px 0;text-align:center;">
                <a
                    href="{{ $url }}"
                    style="display:inline-block;padding:12px 22px;background:#650095;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;"
                >
                    Confirmar e-mail
                </a>
            </p>

            <p style="font-size:13px;color:#6d6172;">
                Por segurança, este link expira em {{ $expiracao }} minutos.
            </p>

            <p style="font-size:13px;color:#6d6172;">
                Se você não criou uma conta no Squad Maker, ignore esta mensagem.
            </p>

            <p style="margin-bottom:0;">Equipe Squad Maker</p>
        </div>
    </div>
</body>
</html>
