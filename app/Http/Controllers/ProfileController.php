<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'nickname' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $user->nickname = $request->nickname;
        $user->bio = $request->bio;

        $user->save();

        return redirect()->route('perfil');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('avatar');

        $folder = public_path('uploads/avatars');

        $fileName = 'avatar_' . $request->user()->id_usuario . '_' . time()
            . '.' . $file->getClientOriginalExtension();

        $file->move($folder, $fileName);

        $user = $request->user();
        $user->avatar = '/uploads/avatars/' . $fileName;
        $user->save();

        return redirect()->route('perfil');
    }
}
