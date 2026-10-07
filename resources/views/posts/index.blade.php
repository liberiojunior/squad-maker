@extends('layouts.internal')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success profile-public-feedback">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger profile-public-feedback">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <main class="profile-page profile-posts-page">
        <div class="profile-posts-page-heading">
            <div>
                <a href="{{ $isOwner ? route('perfil') : route('usuarios.perfil', $user) }}" class="profile-posts-back">
                    <i class="bi bi-arrow-left"></i>
                    Voltar ao perfil
                </a>

                <h1>Publicações de {{ $user->nickname }}</h1>

                @if ($isOwner)
                    <button
                        type="button"
                        class="profile-posts-create-top"
                        data-bs-toggle="modal"
                        data-bs-target="#createPostModal"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Nova publicação
                    </button>
                @endif
            </div>
        </div>

        @if ($posts->isNotEmpty() || $isOwner)
            <div class="profile-posts-grid profile-posts-grid-page">
                @foreach ($posts as $post)
                    @include('posts.card', [
                        'post' => $post,
                        'user' => $user,
                        'isOwner' => $isOwner,
                        'podeReagir' => $podeReagir,
                        'postsReagidos' => $postsReagidos,
                    ])
                @endforeach

            </div>

            @if ($posts->hasPages())
                <div class="squad-pagination profile-posts-pagination">
                    {{ $posts->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="profile-feed-empty profile-posts-page-empty">
                <i class="bi bi-images"></i>
                <p>Nenhuma publicação ainda.</p>
            </div>
        @endif
    </main>

    @if ($isOwner)
        @include('posts.create-modal')
    @endif
@endsection
