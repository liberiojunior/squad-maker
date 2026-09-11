<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\Cpf;
use App\Services\ApiCpfService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function show()
    {
        return view('cadastro');
    }

    public function store(
        Request $request,
        ApiCpfService $apiCpf
    ) {
        $data = $request->validate([
            'nickname' => [
                'required',
                'string',
                'max:80',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
                'unique:tb_usuario,email',
            ],

            'cpf' => [
                'required',
                new Cpf,
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'termos' => [
                'accepted',
            ],

        ], [
            'nickname.required' => 'Informe o nome que será mostrado.',

            'nickname.max' => 'O nome pode ter no máximo 80 caracteres.',

            'email.required' => 'Informe seu e-mail.',

            'email.email' => 'Informe um e-mail válido.',

            'email.unique' => 'Este e-mail já está cadastrado.',

            'cpf.required' => 'Informe seu CPF para realizar a verificação de idade.',

            'password.required' => 'Informe uma senha.',

            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',

            'password.confirmed' => 'As senhas não coincidem.',

            'termos.accepted' => 'Você precisa aceitar os Termos de Uso e Política de Privacidade.',
        ]);

        $resultadoCpf = $apiCpf->consultar($data['cpf']);

        if (! $resultadoCpf['success']) {

            Log::warning(
                'Falha na verificação de idade via API CPF.',
                [
                    'reason' => $resultadoCpf['reason'],
                ]
            );

            $mensagem = match ($resultadoCpf['reason']) {

                'not_found' => 'O CPF informado não foi localizado.',
                'invalid_cpf' => 'O CPF informado é inválido.',
                'rate_limit' => 'O serviço de verificação atingiu temporariamente o limite de consultas. Tente novamente em alguns minutos.',
                'invalid_api_key',
                'expired_api_key',
                'configuration_error' => 'O serviço de verificação de idade está temporariamente indisponível.',
                default => 'Não foi possível realizar a verificação de idade no momento. Tente novamente.',
            };

            throw ValidationException::withMessages([
                'cpf' => $mensagem,
            ]);
        }

        try {

            $dataNascimento = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $resultadoCpf['data_nascimento']
            )->startOfDay();

            if (
                $dataNascimento->format('Y-m-d')
                !== $resultadoCpf['data_nascimento']
            ) {
                throw new \Exception(
                    'Data de nascimento em formato inválido.'
                );
            }

        } catch (\Throwable $e) {

            Log::warning(
                'API CPF retornou uma data de nascimento inválida.'
            );

            throw ValidationException::withMessages([
                'cpf' => 'Não foi possível concluir a verificação de idade no momento.',
            ]);
        }

        $limiteMaioridade = CarbonImmutable::today()
            ->subYears(18)
            ->startOfDay();

        if ($dataNascimento->gt($limiteMaioridade)) {

            throw ValidationException::withMessages([
                'cpf' => 'O cadastro no Squad Maker está disponível apenas para maiores de 18 anos.',
            ]);
        }

        $user = new User;

        $user->nickname = $data['nickname'];
        $user->email = $data['email'];
        $user->senha = Hash::make($data['password']);
        $user->bio = null;
        $user->data_criacao = now();
        $user->status_conta = 'ativo';

        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('perfil');
    }
}
