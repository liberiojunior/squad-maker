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

    <main class="profile-post-feed-page">
        <header class="profile-post-feed-heading">
            <div>
                <h1>Publicações</h1>
                <p>Veja as publicações mais recentes suas e dos seus parceiros.</p>
            </div>

            <div class="profile-post-feed-actions">
                <a
                    href="{{ route('posts.index', ['user' => auth()->user()->nickname]) }}"
                    class="profile-post-feed-own"
                >
                    <i class="bi bi-grid-3x3-gap"></i>
                    Minhas publicações
                </a>

                <button
                    type="button"
                    class="profile-posts-create-top"
                    data-bs-toggle="modal"
                    data-bs-target="#createPostModal"
                >
                    <i class="bi bi-plus-lg"></i>
                    Nova publicação
                </button>
            </div>
        </header>

        @if ($posts->isNotEmpty())
            <div class="profile-post-feed-list">
                @foreach ($posts as $post)
                    @include('posts.card', [
                        'post' => $post,
                        'user' => $post->usuario,
                        'isOwner' => $post->id_usuario === auth()->id(),
                        'podeReagir' => true,
                        'postsReagidos' => $postsReagidos,
                        'feedMode' => true,
                    ])
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div class="squad-pagination profile-post-feed-pagination">
                    {{ $posts->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="profile-feed-empty profile-post-feed-empty">
                <i class="bi bi-images"></i>
                <strong>Nenhuma publicação por enquanto.</strong>
                <p>Quando você ou seus parceiros publicarem algo, aparecerá aqui.</p>
            </div>
        @endif
    </main>

    @include('posts.create-modal')
@endsection
