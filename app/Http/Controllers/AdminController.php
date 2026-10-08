<?php

namespace App\Http\Controllers;

use App\Models\Banimento;
use App\Models\Denuncia;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function geral()
    {
        $totalUsuarios = User::where('status_conta', '!=', 'excluido')->count();
        $usuariosAtivos = User::where('status_conta', 'ativo')->count();
        $usuariosBanidos = User::where('status_conta', 'banido')->count();
        $denunciasPendentes = Denuncia::where('status_denuncia', 'pendente')->count();

        return view('admin.geral', [
            'totalUsuarios' => $totalUsuarios,
            'usuariosAtivos' => $usuariosAtivos,
            'usuariosBanidos' => $usuariosBanidos,
            'denunciasPendentes' => $denunciasPendentes,
        ]);
    }

    public function dashboard(Request $request)
    {
        $data = $request->validate([
            'periodo' => ['nullable', 'in:7,30,90,todos'],
        ]);

        $periodo = $data['periodo'] ?? '30';

        $inicio = match ($periodo) {
            '7' => now()->subDays(6)->startOfDay(),
            '30' => now()->subDays(29)->startOfDay(),
            '90' => now()->subDays(89)->startOfDay(),
            default => null,
        };

        $novosUsuarios = User::where('status_conta', '!=', 'excluido')
            ->when($inicio, fn($query) => $query->where('data_criacao', '>=', $inicio))
            ->count();

        $mensagensEnviadas = DB::table('tb_mensagem')
            ->when($inicio, fn($query) => $query->where('data_envio', '>=', $inicio))
            ->count();

        $postsPublicados = DB::table('tb_post')
            ->when($inicio, fn($query) => $query->where('data_publicacao', '>=', $inicio))
            ->count();

        $jogosAdicionados = DB::table('tb_jogo_usuario')
            ->when($inicio, fn($query) => $query->where('data_adicao', '>=', $inicio))
            ->count();

        if ($periodo === 'todos') {
            $cadastros = User::query()
                ->selectRaw("DATE_FORMAT(data_criacao, '%Y-%m') as periodo, COUNT(*) as total")
                ->where('status_conta', '!=', 'excluido')
                ->groupByRaw("DATE_FORMAT(data_criacao, '%Y-%m')")
                ->orderBy('periodo')
                ->get();

            $cadastrosLabels = $cadastros
                ->map(fn($item) => Carbon::createFromFormat('Y-m', $item->periodo)->format('m/Y'))
                ->values();

            $cadastrosValores = $cadastros
                ->pluck('total')
                ->map(fn($total) => (int) $total)
                ->values();
        } else {
            $cadastros = User::query()
                ->selectRaw('DATE(data_criacao) as periodo, COUNT(*) as total')
                ->where('status_conta', '!=', 'excluido')
                ->where('data_criacao', '>=', $inicio)
                ->groupByRaw('DATE(data_criacao)')
                ->orderBy('periodo')
                ->get()
                ->keyBy('periodo');

            $cadastrosLabels = collect();
            $cadastrosValores = collect();

            foreach (CarbonPeriod::create($inicio, now()->startOfDay()) as $dia) {
                $chave = $dia->format('Y-m-d');
                $cadastrosLabels->push($dia->format('d/m'));
                $cadastrosValores->push((int) ($cadastros[$chave]->total ?? 0));
            }
        }

        $jogosPopulares = DB::table('tb_jogo_usuario as jogo_usuario')
            ->join('tb_jogo as jogo', 'jogo.id_jogo', '=', 'jogo_usuario.id_jogo')
            ->when($inicio, fn($query) => $query->where('jogo_usuario.data_adicao', '>=', $inicio))
            ->select('jogo.nome')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('jogo.id_jogo', 'jogo.nome')
            ->orderByDesc('total')
            ->orderBy('jogo.nome')
            ->limit(5)
            ->get();

        $plataformasPopulares = DB::table('tb_usuario_plataforma as usuario_plataforma')
            ->join('tb_plataforma as plataforma', 'plataforma.id_plataforma', '=', 'usuario_plataforma.id_plataforma')
            ->when($inicio, fn($query) => $query->where('usuario_plataforma.data_adicao', '>=', $inicio))
            ->select('plataforma.nome')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('plataforma.id_plataforma', 'plataforma.nome')
            ->orderByDesc('total')
            ->orderBy('plataforma.nome')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'periodo' => $periodo,
            'novosUsuarios' => $novosUsuarios,
            'mensagensEnviadas' => $mensagensEnviadas,
            'postsPublicados' => $postsPublicados,
            'jogosAdicionados' => $jogosAdicionados,
            'cadastrosLabels' => $cadastrosLabels,
            'cadastrosValores' => $cadastrosValores,
            'jogosPopulares' => $jogosPopulares,
            'plataformasPopulares' => $plataformasPopulares,
        ]);
    }

    public function usuarios(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:ativo,banido'],
            'ordem' => ['nullable', 'in:recentes,antigos,az,za'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]);

        $query = User::query()
            ->where('status_conta', '!=', 'excluido');

        $busca = trim($data['q'] ?? '');

        if ($busca !== '') {
            $query->where(function ($query) use ($busca) {
                $query->where('nickname', 'like', '%' . $busca . '%')
                    ->orWhere('email', 'like', '%' . $busca . '%');
            });
        }

        if (!empty($data['status'])) {
            $query->where('status_conta', $data['status']);
        }

        if (!empty($data['data_inicio'])) {
            $query->whereDate('data_criacao', '>=', $data['data_inicio']);
        }

        if (!empty($data['data_fim'])) {
            $query->whereDate('data_criacao', '<=', $data['data_fim']);
        }

        switch ($data['ordem'] ?? 'recentes') {
            case 'antigos':
                $query->orderBy('data_criacao');
                break;
            case 'az':
                $query->orderBy('nickname');
                break;
            case 'za':
                $query->orderBy('nickname', 'desc');
                break;
            default:
                $query->orderBy('data_criacao', 'desc');
                break;
        }

        $usuarios = $query
            ->paginate(10)
            ->withQueryString();

        return view('admin.usuarios', [
            'usuarios' => $usuarios,
        ]);
    }

    public function noticias()
    {
        return view('admin.noticias');
    }

    public function banir(Request $request, User $user)
    {
        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return back()->withErrors(['usuario' => 'Este usuário já foi excluído.']);
        }

        if ($user->status_conta === 'banido') {
            return back()->withErrors(['usuario' => 'Este usuário já possui um banimento ativo.']);
        }

        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
            'justificativa' => ['required', 'string', 'max:2000'],
            'data_fim' => ['required', 'date', 'after:today'],
            'id_denuncia' => ['nullable', 'integer', 'exists:tb_denuncia,id_denuncia'],
        ], [
            'motivo.required' => 'Informe o motivo do banimento.',
            'justificativa.required' => 'Informe uma justificativa.',
            'data_fim.required' => 'Informe quando o banimento termina.',
            'data_fim.after' => 'A data final deve ser posterior a hoje.',
        ]);

        DB::transaction(function () use ($data, $user) {
            $usuario = User::where('id_usuario', $user->id_usuario)->lockForUpdate()->firstOrFail();
            $usuario->sincronizarStatusBanimento();

            if ($usuario->status_conta === 'excluido') {
                throw ValidationException::withMessages(['usuario' => 'Este usuário já foi excluído.']);
            }

            if ($usuario->status_conta === 'banido') {
                throw ValidationException::withMessages(['usuario' => 'Este usuário já possui um banimento ativo.']);
            }

            $denuncia = null;

            if (! empty($data['id_denuncia'])) {
                $denuncia = Denuncia::where('id_denuncia', $data['id_denuncia'])->lockForUpdate()->firstOrFail();

                if ($denuncia->id_denunciado !== $usuario->id_usuario || $denuncia->status_denuncia !== 'pendente') {
                    throw ValidationException::withMessages(['denuncia' => 'Esta denúncia não pode ser vinculada a este banimento.']);
                }
            }

            Banimento::create([
                'motivo' => $data['motivo'],
                'data_inicio' => now(),
                'data_fim' => $data['data_fim'],
                'justificativa' => $data['justificativa'],
                'status_banimento' => 'ativo',
                'id_administrador' => Auth::guard('admin')->id(),
                'id_usuario_banido' => $usuario->id_usuario,
            ]);

            $usuario->update(['status_conta' => 'banido']);

            if ($denuncia) {
                $denuncia->update([
                    'status_denuncia' => 'aceita',
                    'data_resolucao' => now(),
                    'justificativa' => trim($data['justificativa']),
                    'id_administrador' => Auth::guard('admin')->id(),
                ]);
            }
        });

        if (! empty($data['id_denuncia'])) {
            return redirect()->route('admin.denuncias.show', $data['id_denuncia'])->with('success', 'Usuário banido e denúncia concluída.');
        }

        return back()->with('success', 'Usuário banido com sucesso.');
    }

    public function desbanir(User $user)
    {
        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        if ($user->status_conta !== 'banido') {
            return back()->withErrors([
                'usuario' => 'Este usuário não possui um banimento ativo.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $usuario = User::where('id_usuario', $user->id_usuario)
                ->lockForUpdate()
                ->firstOrFail();

            $usuario->sincronizarStatusBanimento();

            if ($usuario->status_conta !== 'banido') {
                return;
            }

            Banimento::where('id_usuario_banido', $usuario->id_usuario)
                ->where('status_banimento', 'ativo')
                ->update([
                    'status_banimento' => 'encerrado',
                    'data_fim' => now(),
                ]);

            $usuario->update([
                'status_conta' => 'ativo',
            ]);
        });

        return back()->with('success', 'Usuário desbanido com sucesso.');
    }

    public function excluirUsuario(Request $request, User $user)
    {
        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        if ($user->status_conta === 'banido') {
            return back()->withErrors([
                'usuario' => 'Um usuário banido não pode ser excluído. Encerre o banimento primeiro.',
            ]);
        }

        $data = $request->validate([
            'confirmacao' => ['required', 'string'],
        ]);

        if ($data['confirmacao'] !== $user->nickname) {
            return back()->withErrors([
                'confirmacao' => 'O nome digitado não corresponde ao usuário.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $usuario = User::where('id_usuario', $user->id_usuario)
                ->lockForUpdate()
                ->firstOrFail();

            $usuario->sincronizarStatusBanimento();

            if ($usuario->status_conta === 'banido') {
                throw ValidationException::withMessages([
                    'usuario' => 'Um usuário banido não pode ser excluído. Encerre o banimento primeiro.',
                ]);
            }

            if ($usuario->status_conta === 'excluido') {
                throw ValidationException::withMessages([
                    'usuario' => 'Este usuário já foi excluído.',
                ]);
            }

            $usuario->excluirConta();
        });

        return back()->with('success', 'Usuário excluído com sucesso.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
