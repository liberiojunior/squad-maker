<?php

namespace App\Http\Controllers;

use App\Models\Denuncia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminDenunciaController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['pendente', 'aceita', 'negada'])],
            'tipo' => ['nullable', Rule::in(['conversa', 'perfil', 'mensagem', 'post'])],
        ]);

        $query = Denuncia::with(['denunciante', 'denunciado']);
        $busca = trim($data['q'] ?? '');

        if ($busca !== '') {
            $query->where(function ($query) use ($busca) {
                $query->whereHas('denunciante', fn($usuario) => $usuario->where('nickname', 'like', '%' . $busca . '%'))
                    ->orWhereHas('denunciado', fn($usuario) => $usuario->where('nickname', 'like', '%' . $busca . '%'));
            });
        }

        if (! empty($data['status'])) {
            $query->where('status_denuncia', $data['status']);
        }

        if (! empty($data['tipo'])) {
            $query->where('tipo_denuncia', $data['tipo']);
        }

        $denuncias = $query
            ->orderByRaw("status_denuncia = 'pendente' DESC")
            ->orderBy('data_denuncia', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('admin.denuncias.index', ['denuncias' => $denuncias]);
    }

    public function show(Denuncia $denuncia)
    {
        $denuncia->load(['denunciante', 'denunciado']);
        $denuncia->denunciado?->sincronizarStatusBanimento();
        $administrador = null;

        if ($denuncia->id_administrador) {
            $administrador = DB::table('tb_administrador')
                ->where('id_administrador', $denuncia->id_administrador)
                ->value('nome');
        }

        return view('admin.denuncias.show', [
            'denuncia' => $denuncia,
            'administrador' => $administrador,
        ]);
    }

    public function aceitar(Request $request, Denuncia $denuncia)
    {
        $this->resolver($request, $denuncia, 'aceita');

        return back()->with('success', 'Denúncia marcada como procedente.');
    }

    public function negar(Request $request, Denuncia $denuncia)
    {
        $this->resolver($request, $denuncia, 'negada');

        return back()->with('success', 'Denúncia marcada como não procedente.');
    }

    public function arquivo(Denuncia $denuncia, string $tipo)
    {
        $caminho = match ($tipo) {
            'anexo' => $denuncia->anexo,
            'perfil' => data_get($denuncia->contexto, 'perfil.avatar_evidencia'),
            default => null,
        };

        if (! $caminho || ! Storage::disk('local')->exists($caminho)) {
            abort(404);
        }

        return Storage::disk('local')->response($caminho);
    }

    private function resolver(Request $request, Denuncia $denuncia, string $status): void
    {
        $data = $request->validate([
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'justificativa.required' => 'Informe a justificativa da decisão.',
            'justificativa.min' => 'A justificativa deve ter pelo menos 10 caracteres.',
            'justificativa.max' => 'A justificativa pode ter no máximo 2000 caracteres.',
        ]);

        DB::transaction(function () use ($data, $denuncia, $status) {
            $registro = Denuncia::where('id_denuncia', $denuncia->id_denuncia)
                ->lockForUpdate()
                ->firstOrFail();

            if ($registro->status_denuncia !== 'pendente') {
                throw ValidationException::withMessages(['denuncia' => 'Esta denúncia já foi analisada.']);
            }

            $registro->update([
                'status_denuncia' => $status,
                'data_resolucao' => now(),
                'justificativa' => trim($data['justificativa']),
                'id_administrador' => Auth::guard('admin')->id(),
            ]);
        });
    }
}
