@extends('layouts.internal')

@section('content')

    @php
        $user = auth()->user();
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="profile-page">

        <div class="profile-top">

            <div class="profile-avatar-area">

                <form
                    method="POST"
                    action="{{ route('perfil.avatar.update') }}"
                    enctype="multipart/form-data"
                    id="avatarForm"
                >
                    @csrf
                    @method('PATCH')

                    <label for="avatarInput" class="profile-avatar-wrapper">

                        <img
                            src="{{ $user->avatar ?: asset('images/icone.png') }}"
                            alt="{{ $user->nickname }}"
                            class="profile-avatar"
                        >

                        <span class="profile-avatar-overlay">
                <i class="bi bi-camera-fill"></i>
                Alterar foto
            </span>

                    </label>

                    <input
                        type="file"
                        name="avatar"
                        id="avatarInput"
                        accept="image/png,image/jpeg,image/webp"
                        hidden
                    >

                </form>

            </div>

            <div class="profile-main-info">

                <section class="profile-info-card">

                    <form
                        method="POST"
                        action="{{ route('perfil.update') }}"
                        id="profileForm"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="text"
                            name="nickname"
                            id="nicknameInput"
                            class="profile-name-input"
                            value="{{ old('nickname', $user->nickname) }}"
                            maxlength="80"
                            readonly
                        >

                        <button
                            type="button"
                            class="profile-edit-button"
                            id="profileEditButton"
                            title="Editar perfil"
                        >
                            <i class="bi bi-pencil-fill" id="profileEditIcon"></i>
                        </button>

                        <textarea
                            name="bio"
                            id="bioInput"
                            class="profile-bio"
                            readonly
                            placeholder="Você ainda não adicionou uma bio."
                        >{{ old('bio', $user->bio) }}</textarea>

                    </form>

                </section>

                <section class="profile-genres-card">

                    <strong>Meus Gêneros:</strong>

                    <span class="profile-empty-inline">
                    Nenhum gênero configurado.
                </span>

                    <button type="button" class="profile-small-add">
                        +
                    </button>

                </section>

            </div>

        </div>

        <section class="profile-section profile-feed">

            <h2>Feed do Usuário</h2>

            <div class="profile-empty-content">
                Você ainda não possui publicações.
            </div>

        </section>

        <section class="profile-section">

            <h2>Meus Jogos</h2>

            <div class="profile-items">

                <div class="profile-empty-text">
                    Nenhum jogo adicionado.
                </div>

                <button type="button" class="profile-add-card">
                    +
                </button>

            </div>

        </section>

        <section class="profile-section">

            <h2>Minhas Plataformas</h2>

            <div class="profile-items">

                <div class="profile-empty-text">
                    Nenhuma plataforma adicionada.
                </div>

                <button type="button" class="profile-add-card">
                    +
                </button>

            </div>

        </section>

    </div>

    <script>
        const profileForm = document.getElementById('profileForm');
        const nicknameInput = document.getElementById('nicknameInput');
        const bioInput = document.getElementById('bioInput');
        const profileEditButton = document.getElementById('profileEditButton');
        const profileEditIcon = document.getElementById('profileEditIcon');

        const avatarForm = document.getElementById('avatarForm');
        const avatarInput = document.getElementById('avatarInput');

        let editingProfile = false;

        profileEditButton.addEventListener('click', function () {
            if (!editingProfile) {
                editingProfile = true;

                nicknameInput.removeAttribute('readonly');
                bioInput.removeAttribute('readonly');

                nicknameInput.focus();

                profileEditIcon.classList.remove('bi-pencil-fill');
                profileEditIcon.classList.add('bi-check-lg');

                profileEditButton.title = 'Salvar alterações';
            } else {
                profileForm.requestSubmit();
            }
        });

        avatarInput.addEventListener('change', function () {
            if (avatarInput.files.length > 0) {
                avatarForm.requestSubmit();
            }
        });
    </script>

@endsection
