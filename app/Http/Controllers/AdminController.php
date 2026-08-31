<?php

namespace App\Http\Controllers;

use App\Models\Banimento;
use App\Models\Denuncia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function geral()
    {
        $totalUsuarios = User::where(
            'status_conta',
            '!=',
            'excluido'
        )->count();

        $usuariosAtivos = User::where(
            'status_conta',
            'ativo'
        )->count();

        $usuariosBanidos = User::where(
            'status_conta',
            'banido'
        )->count();

        $denunciasPendentes = Denuncia::where(
            'status_denuncia',
            'pendente'
        )->count();

        $usuarios = User::where(
            'status_conta',
            '!=',
            'excluido'
        )
            ->orderBy('data_criacao', 'desc')
            ->paginate(10);

        $denuncias = Denuncia::with([
            'denunciante',
            'denunciado',
        ])
            ->where('status_denuncia', 'pendente')
            ->orderBy('data_denuncia', 'desc')
            ->limit(5)
            ->get();

        return view('admin.geral', [
            'totalUsuarios' => $totalUsuarios,
            'usuariosAtivos' => $usuariosAtivos,
            'usuariosBanidos' => $usuariosBanidos,
            'denunciasPendentes' => $denunciasPendentes,
            'usuarios' => $usuarios,
            'denuncias' => $denuncias,
        ]);
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function banir(Request $request, User $user)
    {
        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        $data = $request->validate([
            'motivo' => [
                'required',
                'string',
                'max:500',
            ],

            'justificativa' => [
                'required',
                'string',
                'max:2000',
            ],

            'data_fim' => [
                'required',
                'date',
                'after:today',
            ],
        ], [
            'motivo.required' =>
                'Informe o motivo do banimento.',

            'justificativa.required' =>
                'Informe uma justificativa.',

            'data_fim.required' =>
                'Informe quando o banimento termina.',

            'data_fim.after' =>
                'A data final deve ser posterior a hoje.',
        ]);

        DB::transaction(function () use ($data, $user) {

            Banimento::create([
                'motivo' => $data['motivo'],
                'data_inicio' => now(),
                'data_fim' => $data['data_fim'],
                'justificativa' => $data['justificativa'],
                'status_banimento' => 'ativo',
                'id_administrador' =>
                    Auth::guard('admin')->id(),
                'id_usuario_banido' =>
                    $user->id_usuario,
            ]);

            $user->update([
                'status_conta' => 'banido',
            ]);
        });

        return back()->with(
            'success',
            'Usuário banido com sucesso.'
        );
    }

    public function desbanir(User $user)
    {
        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        DB::transaction(function () use ($user) {

            $banimento = Banimento::where(
                'id_usuario_banido',
                $user->id_usuario
            )
                ->where(
                    'status_banimento',
                    'ativo'
                )
                ->orderBy(
                    'data_inicio',
                    'desc'
                )
                ->first();

            if ($banimento) {
                $banimento->update([
                    'status_banimento' => 'encerrado',
                    'data_fim' => now(),
                ]);
            }

            $user->update([
                'status_conta' => 'ativo',
            ]);
        });

        return back()->with(
            'success',
            'Usuário desbanido com sucesso.'
        );
    }

    public function excluirUsuario(
        Request $request,
        User $user
    ) {
        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        $data = $request->validate([
            'confirmacao' => [
                'required',
                'string',
            ],
        ]);

        if ($data['confirmacao'] !== $user->nickname) {
            return back()->withErrors([
                'confirmacao' =>
                    'O nome digitado não corresponde ao usuário.',
            ]);
        }

        $user->excluirConta();

        return back()->with(
            'success',
            'Usuário excluído com sucesso.'
        );
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
