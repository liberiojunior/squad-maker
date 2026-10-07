document.addEventListener('DOMContentLoaded', function() {
    iniciarNovaPublicacao();
});

function iniciarNovaPublicacao() {
    const modal = document.getElementById('createPostModal');
    const form = document.getElementById('createPostForm');
    const title = document.getElementById('createPostModalTitle');
    const mainPanel = document.getElementById('postCreateMainPanel');
    const footer = document.getElementById('postCreateFooter');

    const description = document.getElementById('postDescription');
    const counter = document.getElementById('postDescriptionCounter');

    const imageInput = document.getElementById('postImage');
    const imagePreview = document.getElementById('postImagePreviewImage');
    const imageEmpty = document.getElementById('postImageEmpty');
    const imageSelected = document.getElementById('postImageSelected');
    const imageEdit = document.getElementById('postImageEdit');
    const imageRemove = document.getElementById('postImageRemove');

    const cropPanel = document.getElementById('postCropPanel');
    const cropCanvas = document.getElementById('postCropCanvas');
    const cropZoomOut = document.getElementById('postCropZoomOut');
    const cropZoomIn = document.getElementById('postCropZoomIn');
    const cropRotate = document.getElementById('postCropRotate');
    const cropCenter = document.getElementById('postCropCenter');
    const cropCancel = document.getElementById('postCropCancel');
    const cropApply = document.getElementById('postCropApply');
    const cropError = document.getElementById('postCropError');
    const publishButton = document.getElementById('postPublishButton');
    const discardConfirm = document.getElementById('postDiscardConfirm');
    const discardKeep = document.getElementById('postDiscardKeep');
    const discardButton = document.getElementById('postDiscardButton');

    if (
        !modal
        || !form
        || !title
        || !mainPanel
        || !footer
        || !description
        || !counter
        || !imageInput
        || !imagePreview
        || !imageEmpty
        || !imageSelected
        || !imageEdit
        || !imageRemove
        || !cropPanel
        || !cropCanvas
        || !cropZoomOut
        || !cropZoomIn
        || !cropRotate
        || !cropCenter
        || !cropCancel
        || !cropApply
        || !publishButton
        || !discardConfirm
        || !discardKeep
        || !discardButton
    ) {
        return;
    }

    if (modal.dataset.postCreateReady === '1') {
        return;
    }

    modal.dataset.postCreateReady = '1';

    const TIPOS_PERMITIDOS = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    const TAMANHO_MAXIMO = 5 * 1024 * 1024;

    const EDITOR_WIDTH = 540;
    const EDITOR_HEIGHT = 720;

    const OUTPUT_WIDTH = 1080;
    const OUTPUT_HEIGHT = 1440;

    const ZOOM_MIN = 1;
    const ZOOM_MAX = 3;
    const ZOOM_STEP = 0.12;

    const context = cropCanvas.getContext('2d');

    let sourceImage = null;
    let sourceObjectUrl = null;
    let previewObjectUrl = null;

    let baseScale = 1;
    let zoom = 1;
    let offsetX = 0;
    let offsetY = 0;
    let rotation = 0;

    let appliedState = null;
    let stateBeforeEdit = null;

    let dragging = false;
    let pointerStartX = 0;
    let pointerStartY = 0;
    let offsetStartX = 0;
    let offsetStartY = 0;

    let generationToken = 0;
    let preparingImage = false;
    let submitting = false;
    let discardConfirmed = false;
    let discardPreviousFocus = null;

    function mostrarAviso(tipo, mensagem) {
        if (
            window.SquadAviso
            && typeof window.SquadAviso.mostrar === 'function'
        ) {
            window.SquadAviso.mostrar(tipo, mensagem);
            return;
        }

        console.error(mensagem);
    }

    function atualizarContador() {
        const limite = Number(description.maxLength) || 280;
        const atual = description.value.length;

        counter.textContent =
            atual + ' / ' + limite;

        counter.classList.toggle(
            'is-limit',
            atual >= limite
        );
    }

    function atualizarErroCrop(mensagem = '') {
        if (!cropError) {
            return;
        }

        cropError.textContent = mensagem;
        cropError.hidden = mensagem === '';
    }

    function liberarSourceUrl() {
        if (!sourceObjectUrl) {
            return;
        }

        URL.revokeObjectURL(sourceObjectUrl);
        sourceObjectUrl = null;
    }

    function liberarPreviewUrl() {
        if (!previewObjectUrl) {
            return;
        }

        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
    }

    function normalizarRotacao(value) {
        return (
            (value % 360) + 360
        ) % 360;
    }

    function dimensoesRotacionadas() {
        if (!sourceImage) {
            return {
                width: 0,
                height: 0
            };
        }

        const trocaEixos =
            rotation === 90
            || rotation === 270;

        return {
            width: trocaEixos
                ? sourceImage.naturalHeight
                : sourceImage.naturalWidth,

            height: trocaEixos
                ? sourceImage.naturalWidth
                : sourceImage.naturalHeight
        };
    }

    function calcularEscalaBase() {
        const dimensoes =
            dimensoesRotacionadas();

        if (
            !dimensoes.width
            || !dimensoes.height
        ) {
            return 1;
        }

        return Math.max(
            EDITOR_WIDTH / dimensoes.width,
            EDITOR_HEIGHT / dimensoes.height
        );
    }

    function copiarEstado() {
        return {
            zoom: zoom,
            offsetX: offsetX,
            offsetY: offsetY,
            rotation: rotation
        };
    }

    function aplicarEstado(estado) {
        if (!estado || !sourceImage) {
            return;
        }

        rotation = normalizarRotacao(
            estado.rotation || 0
        );

        baseScale = calcularEscalaBase();

        zoom = Math.max(
            ZOOM_MIN,
            Math.min(
                ZOOM_MAX,
                Number(estado.zoom) || 1
            )
        );

        offsetX =
            Number(estado.offsetX) || 0;

        offsetY =
            Number(estado.offsetY) || 0;

        limitarOffset();
        renderizarCrop();
        atualizarControlesZoom();
    }

    function dimensoesRenderizadas() {
        if (!sourceImage) {
            return {
                width: 0,
                height: 0
            };
        }

        const dimensoes =
            dimensoesRotacionadas();

        return {
            width:
                dimensoes.width
                * baseScale
                * zoom,

            height:
                dimensoes.height
                * baseScale
                * zoom
        };
    }

    function limitarOffset() {
        const dimensoes =
            dimensoesRenderizadas();

        const limitX = Math.max(
            0,
            (
                dimensoes.width
                - EDITOR_WIDTH
            ) / 2
        );

        const limitY = Math.max(
            0,
            (
                dimensoes.height
                - EDITOR_HEIGHT
            ) / 2
        );

        offsetX = Math.max(
            -limitX,
            Math.min(limitX, offsetX)
        );

        offsetY = Math.max(
            -limitY,
            Math.min(limitY, offsetY)
        );
    }

    function desenharImagem(
        targetContext,
        targetWidth,
        targetHeight
    ) {
        if (!sourceImage) {
            return;
        }

        const factorX =
            targetWidth / EDITOR_WIDTH;

        const factorY =
            targetHeight / EDITOR_HEIGHT;

        const imageWidth =
            sourceImage.naturalWidth
            * baseScale
            * zoom
            * factorX;

        const imageHeight =
            sourceImage.naturalHeight
            * baseScale
            * zoom
            * factorY;

        targetContext.save();

        targetContext.translate(
            targetWidth / 2
                + offsetX * factorX,
            targetHeight / 2
                + offsetY * factorY
        );

        targetContext.rotate(
            rotation * Math.PI / 180
        );

        targetContext.drawImage(
            sourceImage,
            -imageWidth / 2,
            -imageHeight / 2,
            imageWidth,
            imageHeight
        );

        targetContext.restore();
    }

    function renderizarCrop() {
        if (!sourceImage || !context) {
            return;
        }

        limitarOffset();

        context.clearRect(
            0,
            0,
            EDITOR_WIDTH,
            EDITOR_HEIGHT
        );

        context.fillStyle = '#12001f';

        context.fillRect(
            0,
            0,
            EDITOR_WIDTH,
            EDITOR_HEIGHT
        );

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        desenharImagem(
            context,
            EDITOR_WIDTH,
            EDITOR_HEIGHT
        );
    }

    function atualizarControlesZoom() {
        cropZoomOut.disabled =
            zoom <= ZOOM_MIN + 0.001;

        cropZoomIn.disabled =
            zoom >= ZOOM_MAX - 0.001;
    }

    function ajustarZoom(novoZoom) {
        zoom = Math.max(
            ZOOM_MIN,
            Math.min(
                ZOOM_MAX,
                novoZoom
            )
        );

        limitarOffset();
        renderizarCrop();
        atualizarControlesZoom();
    }

    function centralizarCrop() {
        zoom = 1;
        offsetX = 0;
        offsetY = 0;

        baseScale =
            calcularEscalaBase();

        renderizarCrop();
        atualizarControlesZoom();
    }

    function girarCrop() {
        rotation =
            normalizarRotacao(
                rotation + 90
            );

        /*
         * Ao girar, recalculamos a escala mínima para continuar
         * preenchendo 100% do quadro 3:4 sem áreas vazias.
         */
        baseScale =
            calcularEscalaBase();

        zoom = 1;
        offsetX = 0;
        offsetY = 0;

        renderizarCrop();
        atualizarControlesZoom();
    }

    function criarArquivoCrop(token) {
        return new Promise(function(resolve, reject) {
            if (!sourceImage) {
                reject(
                    new Error(
                        'Não foi possível preparar a imagem.'
                    )
                );

                return;
            }

            const canvas =
                document.createElement('canvas');

            canvas.width = OUTPUT_WIDTH;
            canvas.height = OUTPUT_HEIGHT;

            const outputContext =
                canvas.getContext('2d');

            if (!outputContext) {
                reject(
                    new Error(
                        'Não foi possível preparar a imagem.'
                    )
                );

                return;
            }

            outputContext.clearRect(
                0,
                0,
                OUTPUT_WIDTH,
                OUTPUT_HEIGHT
            );

            outputContext.imageSmoothingEnabled = true;
            outputContext.imageSmoothingQuality = 'high';

            desenharImagem(
                outputContext,
                OUTPUT_WIDTH,
                OUTPUT_HEIGHT
            );

            canvas.toBlob(
                function(blob) {
                    if (
                        token
                        !== generationToken
                    ) {
                        resolve(null);
                        return;
                    }

                    if (!blob) {
                        reject(
                            new Error(
                                'Não foi possível preparar a imagem.'
                            )
                        );

                        return;
                    }

                    resolve(
                        new File(
                            [blob],
                            'publicacao.webp',
                            {
                                type: 'image/webp'
                            }
                        )
                    );
                },
                'image/webp',
                0.92
            );
        });
    }

    function definirArquivoNoInput(file) {
        const transfer =
            new DataTransfer();

        transfer.items.add(file);

        imageInput.files =
            transfer.files;
    }

    function atualizarPreview(file) {
        liberarPreviewUrl();

        previewObjectUrl =
            URL.createObjectURL(file);

        imagePreview.src =
            previewObjectUrl;

        imageEmpty.hidden = true;
        imageSelected.hidden = false;
        imageRemove.hidden = false;
    }

    async function aplicarCrop() {
        if (!sourceImage) {
            return false;
        }

        const token =
            ++generationToken;

        preparingImage = true;
        publishButton.disabled = true;
        cropApply.disabled = true;

        atualizarErroCrop();

        try {
            const file =
                await criarArquivoCrop(
                    token
                );

            if (
                !file
                || token !== generationToken
            ) {
                return false;
            }

            definirArquivoNoInput(file);
            atualizarPreview(file);

            appliedState =
                copiarEstado();

            return true;

        } catch (error) {
            atualizarErroCrop(
                error.message
            );

            mostrarAviso(
                'danger',
                error.message
            );

            return false;

        } finally {
            if (
                token
                === generationToken
            ) {
                preparingImage = false;
                publishButton.disabled = false;
                cropApply.disabled = false;
            }
        }
    }

    function abrirEditor() {
        if (
            !sourceImage
            || preparingImage
        ) {
            return;
        }

        stateBeforeEdit =
            appliedState
                ? { ...appliedState }
                : copiarEstado();

        mainPanel.hidden = true;
        footer.hidden = true;
        cropPanel.hidden = false;

        title.textContent =
            'Ajustar imagem';

        atualizarErroCrop();

        renderizarCrop();
        atualizarControlesZoom();
    }

    function fecharEditor() {
        cropPanel.hidden = true;
        mainPanel.hidden = false;
        footer.hidden = false;

        title.textContent =
            'Nova publicação';

        atualizarErroCrop();
    }

    function cancelarEditor() {
        if (stateBeforeEdit) {
            aplicarEstado(
                stateBeforeEdit
            );
        }

        fecharEditor();
    }

    function limparImagem() {
        generationToken += 1;

        liberarSourceUrl();
        liberarPreviewUrl();

        sourceImage = null;
        baseScale = 1;
        zoom = 1;
        offsetX = 0;
        offsetY = 0;
        rotation = 0;

        appliedState = null;
        stateBeforeEdit = null;

        dragging = false;
        preparingImage = false;

        imageInput.value = '';
        imagePreview.removeAttribute(
            'src'
        );

        imageSelected.hidden = true;
        imageRemove.hidden = true;
        imageEmpty.hidden = false;

        publishButton.disabled = false;
        cropApply.disabled = false;

        if (context) {
            context.clearRect(
                0,
                0,
                EDITOR_WIDTH,
                EDITOR_HEIGHT
            );
        }

        fecharEditor();
    }

    function temConteudoRascunho() {
        return (
            description.value.trim() !== ''
            || sourceImage !== null
            || imageInput.files.length > 0
        );
    }

    function abrirConfirmacaoDescarte() {
        if (!discardConfirm.hidden) {
            return;
        }

        discardPreviousFocus = document.activeElement;
        discardConfirm.hidden = false;
        discardKeep.focus();
    }

    function fecharConfirmacaoDescarte() {
        discardConfirm.hidden = true;

        if (discardPreviousFocus && modal.contains(discardPreviousFocus)) {
            discardPreviousFocus.focus();
        }

        discardPreviousFocus = null;
    }

    function limparFormulario() {
        form.reset();

        description.value = '';
        description.style.height = '';

        limparImagem();
        atualizarContador();

        form
            .querySelectorAll(
                '.is-valid, .is-invalid'
            )
            .forEach(function(field) {
                field.classList.remove(
                    'is-valid',
                    'is-invalid'
                );
            });
    }

    function validarArquivo(file) {
        if (
            !TIPOS_PERMITIDOS.includes(
                file.type
            )
        ) {
            mostrarAviso(
                'danger',
                'Escolha uma imagem JPG, PNG ou WebP.'
            );

            return false;
        }

        if (
            file.size > TAMANHO_MAXIMO
        ) {
            mostrarAviso(
                'danger',
                'A imagem deve ter no máximo 5 MB.'
            );

            return false;
        }

        return true;
    }

    function carregarImagem(file) {
        if (!validarArquivo(file)) {
            limparImagem();
            return;
        }

        generationToken += 1;

        liberarSourceUrl();
        liberarPreviewUrl();

        sourceObjectUrl =
            URL.createObjectURL(file);

        const image = new Image();

        image.onload =
            async function() {
                sourceImage = image;

                rotation = 0;

                baseScale =
                    calcularEscalaBase();

                zoom = 1;
                offsetX = 0;
                offsetY = 0;

                renderizarCrop();
                atualizarControlesZoom();

                const aplicado =
                    await aplicarCrop();

                if (!aplicado) {
                    return;
                }

                imageEdit.focus();
            };

        image.onerror = function() {
            mostrarAviso(
                'danger',
                'Não foi possível carregar essa imagem.'
            );

            limparImagem();
        };

        image.src = sourceObjectUrl;
    }

    description.addEventListener(
        'input',
        atualizarContador
    );

    imageInput.addEventListener(
        'change',
        function() {
            const file =
                imageInput.files?.[0];

            if (!file) {
                limparImagem();
                return;
            }

            carregarImagem(file);
        }
    );

    imageEdit.addEventListener(
        'click',
        abrirEditor
    );

    imageRemove.addEventListener(
        'click',
        limparImagem
    );

    cropZoomOut.addEventListener(
        'click',
        function() {
            ajustarZoom(
                zoom - ZOOM_STEP
            );
        }
    );

    cropZoomIn.addEventListener(
        'click',
        function() {
            ajustarZoom(
                zoom + ZOOM_STEP
            );
        }
    );

    cropRotate.addEventListener(
        'click',
        girarCrop
    );

    cropCenter.addEventListener(
        'click',
        centralizarCrop
    );

    cropCancel.addEventListener(
        'click',
        cancelarEditor
    );

    cropApply.addEventListener(
        'click',
        async function() {
            const aplicado =
                await aplicarCrop();

            if (aplicado) {
                fecharEditor();
            }
        }
    );

    cropCanvas.addEventListener(
        'pointerdown',
        function(event) {
            if (
                !sourceImage
                || (
                    event.pointerType
                        === 'mouse'
                    && event.button !== 0
                )
            ) {
                return;
            }

            dragging = true;

            pointerStartX =
                event.clientX;

            pointerStartY =
                event.clientY;

            offsetStartX =
                offsetX;

            offsetStartY =
                offsetY;

            cropCanvas.classList.add(
                'dragging'
            );

            cropCanvas.setPointerCapture(
                event.pointerId
            );
        }
    );

    cropCanvas.addEventListener(
        'pointermove',
        function(event) {
            if (!dragging) {
                return;
            }

            const rect =
                cropCanvas
                    .getBoundingClientRect();

            const scaleX =
                EDITOR_WIDTH
                / rect.width;

            const scaleY =
                EDITOR_HEIGHT
                / rect.height;

            offsetX =
                offsetStartX
                + (
                    event.clientX
                    - pointerStartX
                ) * scaleX;

            offsetY =
                offsetStartY
                + (
                    event.clientY
                    - pointerStartY
                ) * scaleY;

            limitarOffset();
            renderizarCrop();
        }
    );

    cropCanvas.addEventListener(
        'pointerup',
        function(event) {
            dragging = false;

            cropCanvas.classList.remove(
                'dragging'
            );

            if (
                cropCanvas.hasPointerCapture(
                    event.pointerId
                )
            ) {
                cropCanvas.releasePointerCapture(
                    event.pointerId
                );
            }
        }
    );

    cropCanvas.addEventListener(
        'pointercancel',
        function() {
            dragging = false;

            cropCanvas.classList.remove(
                'dragging'
            );
        }
    );

    discardKeep.addEventListener('click', fecharConfirmacaoDescarte);

    discardButton.addEventListener('click', function() {
        discardConfirmed = true;
        discardConfirm.hidden = true;
        bootstrap.Modal.getOrCreateInstance(modal).hide();
    });

    modal.addEventListener('hide.bs.modal', function(event) {
        if (submitting || discardConfirmed || !temConteudoRascunho()) {
            return;
        }

        event.preventDefault();
        abrirConfirmacaoDescarte();
    });

    modal.addEventListener('hidden.bs.modal', function() {
        discardConfirm.hidden = true;
        discardPreviousFocus = null;
        discardConfirmed = false;
        limparFormulario();
        submitting = false;
    });

    form.addEventListener(
        'submit',
        function(event) {
            if (
                preparingImage
                || !imageInput.files?.length
            ) {
                event.preventDefault();
                return;
            }

            submitting = true;

            liberarSourceUrl();
            liberarPreviewUrl();
        }
    );

    atualizarContador();
    atualizarControlesZoom();
}
