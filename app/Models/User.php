<?php

namespace App\Models;

use App\Support\BrevoMailer;
use App\Support\CloudinaryMedia;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Hidden(['senha'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $table = 'tb_usuario';

    protected $primaryKey = 'id_usuario';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'data_criacao';

    const UPDATED_AT = null;

    protected $fillable = [
        'nickname',
        'email',
        'senha',
        'google_id',
        'avatar',
        'email_verified_at',
        'bio',
        'status_conta',
        'data_criacao',
        'ultima_atividade',
    ];

    protected $hidden = [
        'senha',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
        'ultima_atividade' => 'datetime',
        'data_exclusao' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        $expiracao = config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire'
        );

        BrevoMailer::send(
            $this->email,
            $this->nickname,
            'Redefina sua senha - Squad Maker',
            view('emails.reset-password', [
                'usuario' => $this,
                'url' => $url,
                'expiracao' => $expiracao,
            ])->render()
        );
    }

    public function sendEmailVerificationNotification(): void
    {
        $expiracao = (int) config('auth.verification.expire', 60);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($expiracao),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        BrevoMailer::send(
            $this->email,
            $this->nickname,
            'Confirme seu e-mail - Squad Maker',
            view('emails.verificacao', [
                'usuario' => $this,
                'url' => $url,
                'expiracao' => $expiracao,
            ])->render()
        );
    }

    public function generos()
    {
        return $this->belongsToMany(
            Genero::class,
            'tb_usuario_genero',
            'id_usuario',
            'id_genero'
        )->withPivot('data_adicao');
    }

    public function jogos()
    {
        return $this->belongsToMany(
            Jogo::class,
            'tb_jogo_usuario',
            'id_usuario',
            'id_jogo'
        )->withPivot([
            'data_adicao',
            'nivel_proficiencia',
            'ordem_perfil',
        ]);
    }

    public function plataformas()
    {
        return $this->belongsToMany(
            Plataforma::class,
            'tb_usuario_plataforma',
            'id_usuario',
            'id_plataforma'
        )->withPivot('data_adicao');
    }

    public function posts()
    {
        return $this->hasMany(
            Post::class,
            'id_usuario',
            'id_usuario'
        );
    }

    public function banimentos()
    {
        return $this->hasMany(
            Banimento::class,
            'id_usuario_banido',
            'id_usuario'
        );
    }

    public function amizadesComoUsuario1()
    {
        return $this->hasMany(
            Amizade::class,
            'id_usuario_1',
            'id_usuario'
        );
    }

    public function amizadesComoUsuario2()
    {
        return $this->hasMany(
            Amizade::class,
            'id_usuario_2',
            'id_usuario'
        );
    }

    public function conversasComoUsuario1()
    {
        return $this->hasMany(
            Conversa::class,
            'id_usuario_1',
            'id_usuario'
        );
    }

    public function conversasComoUsuario2()
    {
        return $this->hasMany(
            Conversa::class,
            'id_usuario_2',
            'id_usuario'
        );
    }

    public function mensagensEnviadas()
    {
        return $this->hasMany(
            Mensagem::class,
            'id_remetente',
            'id_usuario'
        );
    }

    public function mensagensRecebidas()
    {
        return $this->hasMany(
            Mensagem::class,
            'id_destinatario',
            'id_usuario'
        );
    }

    public function bloqueiosFeitos()
    {
        return $this->hasMany(
            UsuarioBloqueio::class,
            'id_bloqueador',
            'id_usuario'
        );
    }

    public function bloqueiosRecebidos()
    {
        return $this->hasMany(
            UsuarioBloqueio::class,
            'id_bloqueado',
            'id_usuario'
        );
    }

    public function temBloqueioCom(User $user): bool
    {
        return UsuarioBloqueio::where(function ($query) use ($user) {
            $query
                ->where('id_bloqueador', $this->id_usuario)
                ->where('id_bloqueado', $user->id_usuario);
        })
            ->orWhere(function ($query) use ($user) {
                $query
                    ->where('id_bloqueador', $user->id_usuario)
                    ->where('id_bloqueado', $this->id_usuario);
            })
            ->exists();
    }

    public function totalNotificacoesChat(): int
    {
        $solicitacoes = Amizade::where('status_amizade', 'pendente')
            ->where('id_solicitante', '!=', $this->id_usuario)
            ->where(function ($query) {
                $query->where('id_usuario_1', $this->id_usuario)
                    ->orWhere('id_usuario_2', $this->id_usuario);
            })
            ->count();

        $mensagens = $this->mensagensRecebidas()
            ->whereNull('lida_em')
            ->count();

        return $solicitacoes + $mensagens;
    }

    public function sincronizarStatusBanimento(): bool
    {
        if ($this->status_conta === 'excluido') {
            return false;
        }

        $agora = now();

        return DB::transaction(function () use ($agora) {
            $this->banimentos()
                ->where('status_banimento', 'ativo')
                ->where('data_fim', '<=', $agora)
                ->update([
                    'status_banimento' => 'encerrado',
                ]);

            $banido = $this->banimentos()
                ->where('status_banimento', 'ativo')
                ->where('data_fim', '>', $agora)
                ->exists();

            if ($banido && $this->status_conta !== 'banido') {
                $this->status_conta = 'banido';
                $this->saveQuietly();
            }

            if (!$banido && $this->status_conta === 'banido') {
                $this->status_conta = 'ativo';
                $this->saveQuietly();
            }

            return $banido;
        });
    }

    public function excluirConta(): void
    {
        $posts = $this->posts()
            ->where('status_post', 'ativo')
            ->get(['id_post', 'foto']);

        foreach ($posts as $post) {
            CloudinaryMedia::delete($post->foto);
        }

        if ($posts->isNotEmpty()) {
            DB::table('tb_post_reacao')
                ->whereIn('id_post', $posts->pluck('id_post'))
                ->delete();

            $this->posts()
                ->where('status_post', 'ativo')
                ->update(['status_post' => 'removido']);
        }

        $this->nickname = 'Usuário excluído ' . $this->id_usuario;

        $this->email = 'excluido_'
            . $this->id_usuario
            . '_'
            . time()
            . '@squadmaker.local';

        CloudinaryMedia::delete($this->avatar);

        $this->senha = Hash::make(Str::random(40));
        $this->google_id = null;
        $this->avatar = null;
        $this->bio = null;
        $this->email_verified_at = null;
        $this->remember_token = null;
        $this->ultima_atividade = null;
        $this->status_conta = 'excluido';
        $this->data_exclusao = now();

        $this->save();
    }

    public function presenca(): array
    {
        if ($this->status_conta !== 'ativo' || !$this->ultima_atividade) {
            return [
                'status' => 'offline',
                'texto' => 'Offline',
            ];
        }

        $segundos = max(
            0,
            now()->timestamp - $this->ultima_atividade->timestamp
        );

        if ($segundos <= 5 * 60) {
            return [
                'status' => 'online',
                'texto' => 'Online agora',
            ];
        }

        if ($segundos > 3 * 24 * 60 * 60) {
            return [
                'status' => 'offline',
                'texto' => 'Offline',
            ];
        }

        if ($segundos < 60 * 60) {
            $minutos = max(
                1,
                (int)floor($segundos / 60)
            );

            return [
                'status' => 'recente',
                'texto' => 'Ativo há ' . $minutos . ' min',
            ];
        }

        if ($segundos < 24 * 60 * 60) {
            $horas = max(
                1,
                (int)floor($segundos / 3600)
            );

            return [
                'status' => 'recente',
                'texto' => 'Ativo há '
                    . $horas
                    . ($horas === 1 ? ' hora' : ' horas'),
            ];
        }

        $dias = max(
            1,
            (int)floor($segundos / 86400)
        );

        return [
            'status' => 'recente',
            'texto' => 'Ativo há '
                . $dias
                . ($dias === 1 ? ' dia' : ' dias'),
        ];
    }
}
