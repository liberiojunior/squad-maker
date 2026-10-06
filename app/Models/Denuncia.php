<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denuncia extends Model
{
    public const MOTIVOS_CONVERSA = [
        'assedio_ofensa' => 'Assédio ou ofensa',
        'discriminacao' => 'Discriminação',
        'ameaca' => 'Ameaça',
        'conteudo_inadequado' => 'Conteúdo sexual ou inadequado',
        'spam_abuso' => 'Spam ou comportamento abusivo',
        'outro' => 'Outro',
    ];

    public const MOTIVOS_PERFIL = [
        'foto_inadequada' => 'Foto inadequada',
        'nome_bio_ofensivo' => 'Nickname ou bio ofensiva',
        'falsa_identidade' => 'Falsidade de identidade',
        'assedio' => 'Assédio',
        'outro' => 'Outro',
    ];

    protected $table = 'tb_denuncia';

    protected $primaryKey = 'id_denuncia';

    public $timestamps = false;

    protected $fillable = [
        'tipo_denuncia',
        'motivo',
        'descricao',
        'anexo',
        'contexto',
        'data_denuncia',
        'status_denuncia',
        'data_resolucao',
        'justificativa',
        'id_denunciante',
        'id_denunciado',
        'id_conversa',
        'id_administrador',
    ];

    protected $casts = [
        'contexto' => 'array',
        'data_denuncia' => 'datetime',
        'data_resolucao' => 'datetime',
    ];

    public function denunciante()
    {
        return $this->belongsTo(User::class, 'id_denunciante', 'id_usuario');
    }

    public function denunciado()
    {
        return $this->belongsTo(User::class, 'id_denunciado', 'id_usuario');
    }

    public function conversa()
    {
        return $this->belongsTo(Conversa::class, 'id_conversa', 'id_conversa');
    }

    public function motivoTexto(): string
    {
        $motivos = self::MOTIVOS_CONVERSA + self::MOTIVOS_PERFIL + [
            'registro_anterior' => 'Registro anterior',
        ];

        return $motivos[$this->motivo] ?? 'Outro';
    }

    public function tipoTexto(): string
    {
        return match ($this->tipo_denuncia) {
            'conversa' => 'Conversa',
            'perfil' => 'Perfil',
            'mensagem' => 'Mensagem antiga',
            'post' => 'Publicação antiga',
            default => ucfirst((string) $this->tipo_denuncia),
        };
    }
}
