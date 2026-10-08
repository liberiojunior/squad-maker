<div class="modal fade" id="createPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profile-post-create-dialog">
        <div class="modal-content squad-modal profile-post-create-modal">
            <form
                method="POST"
                action="{{ route('posts.store') }}"
                enctype="multipart/form-data"
                id="createPostForm"
            >
                @csrf

                <div class="modal-header">
                    <h2 class="modal-title" id="createPostModalTitle">
                        Nova publicação
                    </h2>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div
                    class="modal-body profile-post-create-body"
                    id="postCreateMainPanel"
                >
                    <div class="profile-post-caption-field">
                        <textarea
                            id="postDescription"
                            name="descricao"
                            class="form-control squad-scrollbar profile-post-caption"
                            rows="2"
                            maxlength="280"
                            placeholder="O que você quer compartilhar?"
                        >{{ old('descricao') }}</textarea>

                        <div
                            class="squad-emoji-wrap profile-post-emoji-wrap"
                            id="postDescriptionEmojiWrap"
                        >
                            <button
                                type="button"
                                class="squad-emoji-button"
                                id="postDescriptionEmojiButton"
                                aria-expanded="false"
                                aria-controls="postDescriptionEmojiPicker"
                                aria-label="Adicionar emoji à publicação"
                                title="Adicionar emoji"
                            >
                                <i class="bi bi-emoji-smile"></i>
                            </button>

                            <div
                                class="squad-emoji-picker"
                                id="postDescriptionEmojiPicker"
                                hidden
                            >
                                @foreach (['😀', '😂', '😊', '😍', '😎', '😭', '🔥', '❤️', '💜', '👍', '👏', '🎮', '🏆', '🤝', '🎉', '👀'] as $emoji)
                                    <button type="button" data-emoji="{{ $emoji }}" aria-label="Adicionar {{ $emoji }}">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </div>

                        <span
                            class="profile-post-caption-counter"
                            id="postDescriptionCounter"
                        >
                            0 / 280
                        </span>
                    </div>

                    <div class="profile-post-image-area">
                        <input
                            type="file"
                            id="postImage"
                            name="foto"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >

                        <div
                            class="profile-post-image-preview"
                            id="postImagePreview"
                        >
                            <label
                                for="postImage"
                                class="profile-post-image-empty"
                                id="postImageEmpty"
                            >
                                <i class="bi bi-images"></i>
                                <strong>Adicionar imagem</strong>
                                <span>Opcional • JPG, PNG ou WebP de até 5 MB.</span>
                            </label>

                            <div
                                class="profile-post-image-selected"
                                id="postImageSelected"
                                hidden
                            >
                                <img
                                    id="postImagePreviewImage"
                                    src=""
                                    alt="Pré-visualização da publicação"
                                >

                                <button
                                    type="button"
                                    class="profile-post-image-edit"
                                    id="postImageEdit"
                                >
                                    <i class="bi bi-pencil-fill"></i>
                                    Editar
                                </button>
                            </div>

                            <button
                                type="button"
                                class="profile-post-image-remove"
                                id="postImageRemove"
                                title="Remover imagem"
                                aria-label="Remover imagem"
                                hidden
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="profile-post-crop-panel"
                    id="postCropPanel"
                    hidden
                >
                    <div class="profile-post-crop-heading">
                        <strong>Ajustar imagem</strong>
                        <span>
                            Arraste a imagem para definir o enquadramento 3:4.
                        </span>
                    </div>

                    <div class="profile-post-crop-stage">
                        <canvas
                            id="postCropCanvas"
                            width="540"
                            height="720"
                        ></canvas>

                        <div
                            class="profile-post-crop-safe-area"
                            aria-hidden="true"
                        >
                            <span>Área de ajuste</span>
                        </div>
                    </div>

                    <div
                        class="profile-post-crop-controls"
                        aria-label="Controles de enquadramento"
                    >
                        <button
                            type="button"
                            id="postCropZoomOut"
                            title="Diminuir zoom"
                            aria-label="Diminuir zoom"
                        >
                            <i class="bi bi-dash-lg"></i>
                        </button>

                        <button
                            type="button"
                            id="postCropRotate"
                            title="Girar imagem"
                            aria-label="Girar imagem"
                        >
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>

                        <button
                            type="button"
                            id="postCropCenter"
                            title="Centralizar imagem"
                            aria-label="Centralizar imagem"
                        >
                            <i class="bi bi-bullseye"></i>
                        </button>

                        <button
                            type="button"
                            id="postCropZoomIn"
                            title="Aumentar zoom"
                            aria-label="Aumentar zoom"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    <div
                        class="profile-post-crop-error"
                        id="postCropError"
                        hidden
                    ></div>

                    <div class="profile-post-crop-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="postCropCancel"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="btn profile-post-submit"
                            id="postCropApply"
                        >
                            Aplicar
                        </button>
                    </div>
                </div>

                <div
                    class="modal-footer profile-post-create-footer"
                    id="postCreateFooter"
                >
                    <button
                        type="submit"
                        class="btn profile-post-submit"
                        id="postPublishButton"
                    >
                        Publicar
                    </button>
                </div>
            </form>

            <div
                class="profile-post-discard-confirm"
                id="postDiscardConfirm"
                hidden
            >
                <div
                    class="profile-post-discard-card"
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="postDiscardConfirmTitle"
                    aria-describedby="postDiscardConfirmText"
                >
                    <div class="profile-post-discard-icon" aria-hidden="true">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>

                    <h3 id="postDiscardConfirmTitle">Cancelar edição?</h3>

                    <p id="postDiscardConfirmText">
                        Tem certeza que deseja cancelar sua edição? O conteúdo desta publicação será descartado.
                    </p>

                    <div class="profile-post-discard-actions">
                        <button
                            type="button"
                            class="btn profile-continue-editing"
                            id="postDiscardKeep"
                        >
                            Continuar editando
                        </button>

                        <button
                            type="button"
                            class="btn btn-danger"
                            id="postDiscardButton"
                        >
                            Descartar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script src="{{ asset('js/post.js') }}"></script>
@endonce
