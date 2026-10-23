<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contato pelo site</title>
</head>
<body style="margin:0;padding:0;background:#f4f1f6;font-family:Arial,Helvetica,sans-serif;color:#26142f;">
    <div style="max-width:620px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:14px;padding:32px;">
            <h1 style="margin:0 0 22px;color:#650095;font-size:24px;">Nova mensagem pelo site</h1>

            <p><strong>Nome:</strong> {{ $nome }}</p>
            <p><strong>E-mail:</strong> {{ $email }}</p>

            <div style="margin-top:24px;padding:18px;background:#f7f4f8;border-radius:9px;white-space:pre-wrap;line-height:1.6;">{{ $mensagem }}</div>

            <p style="margin-top:24px;font-size:13px;color:#6d6172;">
                Responda este e-mail para falar diretamente com {{ $nome }}.
            </p>
        </div>
    </div>
</body>
</html>
