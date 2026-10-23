<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BrevoMailer
{
    public static function send(
        string $email,
        ?string $nome,
        string $assunto,
        string $html,
        ?array $replyTo = null,
        array $tags = []
    ): string {
        $apiKey = config('brevo.api_key');
        $fromEmail = config('brevo.from_email');
        $fromName = config('brevo.from_name');

        if (! $apiKey || ! $fromEmail) {
            throw new RuntimeException('Brevo não está configurado.');
        }

        $payload = [
            'sender' => [
                'email' => $fromEmail,
                'name' => $fromName,
            ],
            'to' => [[
                'email' => $email,
                'name' => $nome ?: $email,
            ]],
            'subject' => $assunto,
            'htmlContent' => $html,
        ];

        if ($replyTo) {
            $payload['replyTo'] = [
                'email' => $replyTo['email'],
                'name' => $replyTo['name'] ?? $replyTo['email'],
            ];
        }

        if ($tags) {
            $payload['tags'] = $tags;
        }

        $response = Http::acceptJson()
            ->withHeaders([
                'api-key' => $apiKey,
            ])
            ->timeout(15)
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $response->successful()) {
            $message = $response->json('message') ?: trim($response->body());
            $details = $message ? ': '.$message : '';

            throw new RuntimeException(
                'Falha ao enviar e-mail pela Brevo. HTTP '.$response->status().$details
            );
        }

        $messageId = $response->json('messageId');

        if (! is_string($messageId) || $messageId === '') {
            throw new RuntimeException('A Brevo aceitou a requisição sem retornar o identificador da mensagem.');
        }

        Log::info('Brevo aceitou e-mail transacional.', [
            'message_id' => $messageId,
            'tags' => $tags,
        ]);

        return $messageId;
    }
}
