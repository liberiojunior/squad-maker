<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ApiCpfService
{
    public function consultar(string $cpf): array
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        $apiKey = config('services.apicpf.key');
        $baseUrl = rtrim(config('services.apicpf.base_url'), '/');

        if (blank($apiKey)) {
            return [
                'success' => false,
                'reason' => 'configuration_error',
            ];
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                ])
                ->connectTimeout(5)
                ->timeout(10)
                ->get($baseUrl . '/api/consulta', [
                    'cpf' => $cpf,
                ]);
        } catch (ConnectionException $e) {
            return [
                'success' => false,
                'reason' => 'unavailable',
            ];
        }

        if ($response->status() === 404) {
            return [
                'success' => false,
                'reason' => 'not_found',
            ];
        }
/* Talvez isso aqui seja redundante, pelo que verifiquei
nem chega nessa parte por termos verificação antes
  */
        if ($response->status() === 400) {
            return [
                'success' => false,
                'reason' => 'invalid_cpf',
            ];
        }

        if ($response->status() === 401) {
            return [
                'success' => false,
                'reason' => 'invalid_api_key',
            ];
        }

        if ($response->status() === 403) {
            return [
                'success' => false,
                'reason' => 'expired_api_key',
            ];
        }

        if ($response->status() === 429) {
            return [
                'success' => false,
                'reason' => 'rate_limit',
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'reason' => 'unavailable',
            ];
        }

        $data = $response->json('data');

        if (
            ! is_array($data) ||
            empty($data['cpf']) ||
            empty($data['data_nascimento'])
        ) {
            return [
                'success' => false,
                'reason' => 'invalid_response',
            ];
        }

        $cpfRetornado = preg_replace(
            '/\D/',
            '',
            (string) $data['cpf']
        );

        if ($cpfRetornado !== $cpf) {
            return [
                'success' => false,
                'reason' => 'invalid_response',
            ];
        }

        return [
            'success' => true,
            'data_nascimento' => $data['data_nascimento'],
        ];
    }
}
