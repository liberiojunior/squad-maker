<?php

use App\Models\Conversa;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('usuarios.{idUsuario}', function (User $user, int $idUsuario) {
    return $user->id_usuario === $idUsuario;
});

Broadcast::channel('conversas.{idConversa}', function (User $user, int $idConversa) {
    return Conversa::whereKey($idConversa)
        ->where(function ($query) use ($user) {
            $query->where('id_usuario_1', $user->id_usuario)
                ->orWhere('id_usuario_2', $user->id_usuario);
        })
        ->exists();
});
