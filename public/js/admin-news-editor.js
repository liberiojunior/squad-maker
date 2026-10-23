document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-news-editor]');

    if (!shell) {
        return;
    }

    const editor = shell.querySelector('[contenteditable="true"]');
    document.execCommand('defaultParagraphSeparator', false, 'p');
    const input = shell.querySelector('[data-news-editor-input]');
    const toolbar = shell.querySelector('[data-news-editor-toolbar]');
    const toggle = shell.querySelector('[data-news-editor-toggle]');
    const linkButton = shell.querySelector('[data-news-link]');
    const linkPanel = shell.querySelector('[data-news-link-panel]');
    const linkUrl = shell.querySelector('[data-news-link-url]');
    const linkApply = shell.querySelector('[data-news-link-apply]');
    const linkRemove = shell.querySelector('[data-news-link-remove]');
    const linkCancel = shell.querySelector('[data-news-link-cancel]');
    const imageButton = shell.querySelector('[data-news-image]');
    const imageInput = shell.querySelector('[data-news-image-input]');
    const textSize = shell.querySelector('[data-news-size]');
    const imageSize = shell.querySelector('[data-news-image-size]');
    const videoButton = shell.querySelector('[data-news-video]');
    const videoRemoveButton = shell.querySelector('[data-news-video-remove]');
    const videoPanel = shell.querySelector('[data-news-video-panel]');
    const videoUrl = shell.querySelector('[data-news-video-url]');
    const videoInsert = shell.querySelector('[data-news-video-insert]');
    const videoCancel = shell.querySelector('[data-news-video-cancel]');
    const status = shell.querySelector('[data-news-editor-status]');
    const form = shell.closest('form');
    let savedRange = null;
    let selectedImage = null;
    let selectedVideo = null;
    let draggedMedia = null;
    let dropMarker = null;
    let insertionBookmark = null;
    let linkRange = null;
    let linkTargetImage = null;
    let linkTargetAnchor = null;

    const directChildOfEditor = (node) => {
        if (!node) {
            return null;
        }

        let element = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;

        while (element && element.parentElement !== editor) {
            element = element.parentElement;
        }

        return element && element.parentElement === editor ? element : null;
    };

    const ensureDropMarker = () => {
        if (!dropMarker) {
            dropMarker = document.createElement('div');
            dropMarker.className = 'news-media-drop-marker';
            dropMarker.setAttribute('contenteditable', 'false');
        }

        return dropMarker;
    };

    const selectionInsideEditor = () => {
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0) {
            return false;
        }

        const node = selection.anchorNode;
        return node && (node === editor || editor.contains(node));
    };

    const saveSelection = () => {
        if (!selectionInsideEditor()) {
            return;
        }

        savedRange = window.getSelection().getRangeAt(0).cloneRange();
    };

    const removeInsertionBookmark = () => {
        if (insertionBookmark && insertionBookmark.parentNode) {
            insertionBookmark.remove();
        }

        insertionBookmark = null;
    };

    const placeInsertionBookmark = () => {
        removeInsertionBookmark();
        saveSelection();

        const selection = window.getSelection();
        let range = savedRange ? savedRange.cloneRange() : null;

        if (!range || !editor.contains(range.commonAncestorContainer)) {
            range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
        }

        range.collapse(true);

        insertionBookmark = document.createElement('span');
        insertionBookmark.className = 'news-editor-bookmark';
        insertionBookmark.dataset.newsInsertionBookmark = '1';
        insertionBookmark.setAttribute('contenteditable', 'false');
        insertionBookmark.textContent = '\u200b';
        range.insertNode(insertionBookmark);

        const afterBookmark = document.createRange();
        afterBookmark.setStartAfter(insertionBookmark);
        afterBookmark.collapse(true);
        savedRange = afterBookmark.cloneRange();
    };

    const restoreSelection = () => {
        editor.focus();

        const selection = window.getSelection();
        selection.removeAllRanges();

        if (savedRange && editor.contains(savedRange.commonAncestorContainer)) {
            selection.addRange(savedRange);
            return;
        }

        const range = document.createRange();
        range.selectNodeContents(editor);
        range.collapse(false);
        selection.addRange(range);
        savedRange = range.cloneRange();
    };

    const setCaretAtStart = (element) => {
        const range = document.createRange();
        range.selectNodeContents(element);
        range.collapse(true);

        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        savedRange = range.cloneRange();
    };

    const insertMediaAtSelection = (media) => {
        if (insertionBookmark && editor.contains(insertionBookmark)) {
            const block = directChildOfEditor(insertionBookmark);
            const splittable = block && ['P', 'DIV', 'H2', 'H3', 'BLOCKQUOTE'].includes(block.tagName)
                && !block.classList.contains('news-video-embed');

            if (splittable) {
                const trailingRange = document.createRange();
                trailingRange.setStartAfter(insertionBookmark);
                trailingRange.setEnd(block, block.childNodes.length);

                const trailing = trailingRange.extractContents();
                const after = block.cloneNode(false);
                after.append(trailing);
                insertionBookmark.remove();
                insertionBookmark = null;

                if (!block.textContent.trim() && !block.querySelector('img, .news-video-embed, br')) {
                    block.innerHTML = '<br>';
                }

                if (!after.textContent.trim() && !after.querySelector('img, .news-video-embed, br')) {
                    after.innerHTML = '<br>';
                }

                block.after(media, after);
                setCaretAtStart(after);
                return;
            }

            insertionBookmark.replaceWith(media);
            insertionBookmark = null;

            const paragraph = document.createElement('p');
            paragraph.innerHTML = '<br>';
            media.after(paragraph);
            setCaretAtStart(paragraph);
            return;
        }

        restoreSelection();

        const selection = window.getSelection();
        const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

        if (!range || !editor.contains(range.commonAncestorContainer)) {
            editor.append(media);
            const paragraph = document.createElement('p');
            paragraph.innerHTML = '<br>';
            editor.append(paragraph);
            setCaretAtStart(paragraph);
            return;
        }

        range.deleteContents();
        range.collapse(true);
        range.insertNode(media);

        const paragraph = document.createElement('p');
        paragraph.innerHTML = '<br>';
        media.after(paragraph);
        setCaretAtStart(paragraph);
    };

    const imageLinkOf = (image) => {
        const anchor = image?.parentElement;
        return anchor?.matches('a.news-image-link') ? anchor : null;
    };

    const mediaDragNode = (media) => {
        if (media?.matches('img')) {
            return imageLinkOf(media) || media;
        }

        return media;
    };

    const makeMediaDraggable = (media) => {
        const draggable = mediaDragNode(media);

        if (!draggable) {
            return;
        }

        if (draggable !== media) {
            media.removeAttribute('draggable');
        }

        draggable.setAttribute('draggable', 'true');

        if (media.classList.contains('news-video-embed')) {
            media.setAttribute('contenteditable', 'false');
        }
    };

    const syncInput = () => {
        const clone = editor.cloneNode(true);
        clone.querySelectorAll('.is-selected, .is-dragging').forEach((element) => {
            element.classList.remove('is-selected', 'is-dragging');
        });
        clone.querySelectorAll('.news-media-drop-marker, [data-news-insertion-bookmark]').forEach((element) => element.remove());
        clone.querySelectorAll('[contenteditable], [draggable]').forEach((element) => {
            element.removeAttribute('contenteditable');
            element.removeAttribute('draggable');
        });

        const text = clone.textContent.replace(/\u00a0/g, ' ').trim();
        const hasMedia = Boolean(clone.querySelector('img, .news-video-embed'));
        input.value = text || hasMedia ? clone.innerHTML.trim() : '';
    };

    const openToolbar = () => {
        if (!toolbar.hidden) {
            return;
        }

        toolbar.hidden = false;
        shell.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
    };

    const youtubeIdFromUrl = (value) => {
        try {
            const url = new URL(value.trim());
            const host = url.hostname.toLowerCase().replace(/^www\./, '');
            let id = '';

            if (host === 'youtu.be') {
                id = url.pathname.split('/').filter(Boolean)[0] || '';
            } else if (host === 'youtube.com' || host === 'm.youtube.com') {
                if (url.pathname === '/watch') {
                    id = url.searchParams.get('v') || '';
                } else {
                    const parts = url.pathname.split('/').filter(Boolean);

                    if (['embed', 'shorts', 'live'].includes(parts[0])) {
                        id = parts[1] || '';
                    }
                }
            }

            return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : null;
        } catch (error) {
            return null;
        }
    };

    const normalizeLinkUrl = (value) => {
        let candidate = value.trim();

        if (!candidate) {
            return null;
        }

        if (!/^[a-z][a-z0-9+.-]*:\/\//i.test(candidate)) {
            candidate = `https://${candidate}`;
        }

        try {
            const url = new URL(candidate);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
        } catch (error) {
            return null;
        }
    };

    const anchorFromRange = (range) => {
        if (!range) {
            return null;
        }

        let node = range.commonAncestorContainer;

        if (node.nodeType !== Node.ELEMENT_NODE) {
            node = node.parentElement;
        }

        const anchor = node?.closest?.('a');
        return anchor && editor.contains(anchor) ? anchor : null;
    };

    const closeLinkPanel = () => {
        linkPanel.hidden = true;
        linkUrl.value = '';
        linkRange = null;
        linkTargetImage = null;
        linkTargetAnchor = null;
        linkRemove.disabled = true;
    };

    const openLinkPanel = () => {
        saveSelection();
        linkTargetImage = selectedImage;
        linkTargetAnchor = null;
        linkRange = null;

        if (linkTargetImage) {
            linkTargetAnchor = imageLinkOf(linkTargetImage);
        } else if (savedRange && editor.contains(savedRange.commonAncestorContainer) && !savedRange.collapsed) {
            linkRange = savedRange.cloneRange();
            linkTargetAnchor = anchorFromRange(linkRange);
        } else {
            status.textContent = 'Selecione um trecho de texto ou uma imagem para adicionar o hyperlink.';
            return;
        }

        linkUrl.value = linkTargetAnchor?.getAttribute('href') || '';
        linkRemove.disabled = !linkTargetAnchor;
        linkPanel.hidden = false;
        openToolbar();
        linkUrl.focus();
    };

    const restoreLinkRange = () => {
        if (!linkRange || !editor.contains(linkRange.commonAncestorContainer)) {
            return false;
        }

        editor.focus();
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(linkRange);
        savedRange = linkRange.cloneRange();
        return true;
    };

    const deselectVideo = () => {
        if (selectedVideo) {
            selectedVideo.classList.remove('is-selected');
        }

        selectedVideo = null;
        videoRemoveButton.disabled = true;
    };

    const selectVideo = (video) => {
        deselectVideo();

        if (selectedImage) {
            selectedImage.classList.remove('is-selected');
            selectedImage = null;
            imageSize.value = '';
            imageSize.disabled = true;
        }

        deselectVideo();
        selectedVideo = video;

        if (selectedVideo) {
            selectedVideo.classList.add('is-selected');
            videoRemoveButton.disabled = false;
            openToolbar();
        }
    };

    const selectImage = (image) => {
        deselectVideo();

        if (selectedImage && selectedImage !== image) {
            selectedImage.classList.remove('is-selected');
        }

        selectedImage = image;

        if (!selectedImage) {
            imageSize.value = '';
            imageSize.disabled = true;
            return;
        }

        selectedImage.classList.add('is-selected');
        imageSize.disabled = false;
        imageSize.value = selectedImage.dataset.size || 'medium';
        openToolbar();
    };

    const runCommand = (command, value = null) => {
        restoreSelection();
        document.execCommand(command, false, value);
        saveSelection();
        syncInput();
    };

    toggle.addEventListener('click', () => {
        const opening = toolbar.hidden;
        toolbar.hidden = !opening;
        shell.classList.toggle('is-open', opening);
        toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
    });

    toolbar.querySelectorAll('[data-news-command]').forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => runCommand(button.dataset.newsCommand));
    });

    toolbar.querySelector('[data-news-format]').addEventListener('change', (event) => {
        runCommand('formatBlock', event.target.value);
        event.target.value = 'p';
    });

    toolbar.querySelector('[data-news-font]').addEventListener('change', (event) => {
        if (event.target.value) {
            runCommand('fontName', event.target.value);
        }

        event.target.value = '';
    });

    const applyTextSize = () => {
        const size = Number.parseInt(textSize.value, 10);

        if (!Number.isInteger(size) || size < 10 || size > 48) {
            status.textContent = 'Escolha um tamanho entre 10 e 48 px.';
            textSize.focus();
            return;
        }

        restoreSelection();
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0 || selection.getRangeAt(0).collapsed) {
            status.textContent = 'Selecione o texto que deseja redimensionar.';
            return;
        }

        document.execCommand('fontSize', false, '7');
        editor.querySelectorAll('font[size="7"]').forEach((font) => {
            const span = document.createElement('span');
            span.style.fontSize = `${size}px`;

            while (font.firstChild) {
                span.append(font.firstChild);
            }

            font.replaceWith(span);
        });

        saveSelection();
        syncInput();
        status.textContent = `Texto ajustado para ${size} px.`;
    };

    textSize.addEventListener('mousedown', saveSelection);
    textSize.addEventListener('change', applyTextSize);
    textSize.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyTextSize();
        }
    });

    imageSize.addEventListener('change', () => {
        if (!selectedImage || !imageSize.value) {
            return;
        }

        selectedImage.dataset.size = imageSize.value;

        const imageLink = imageLinkOf(selectedImage);
        if (imageLink) {
            imageLink.dataset.size = imageSize.value;
        }

        syncInput();
        status.textContent = 'Tamanho da imagem atualizado.';
    });

    linkButton.addEventListener('mousedown', (event) => event.preventDefault());
    linkButton.addEventListener('click', openLinkPanel);

    linkCancel.addEventListener('click', () => {
        closeLinkPanel();
        status.textContent = '';
        editor.focus();
    });

    linkApply.addEventListener('click', () => {
        const href = normalizeLinkUrl(linkUrl.value);

        if (!href) {
            status.textContent = 'Informe um link válido começando com http:// ou https://.';
            linkUrl.focus();
            return;
        }

        if (linkTargetImage) {
            let anchor = imageLinkOf(linkTargetImage);

            if (!anchor) {
                anchor = document.createElement('a');
                anchor.className = 'news-image-link';
                linkTargetImage.before(anchor);
                anchor.append(linkTargetImage);
            }

            anchor.href = href;
            anchor.target = '_blank';
            anchor.rel = 'noopener noreferrer';
            anchor.dataset.size = linkTargetImage.dataset.size || 'medium';
            makeMediaDraggable(linkTargetImage);
            selectImage(linkTargetImage);
        } else if (restoreLinkRange()) {
            document.execCommand('createLink', false, href);
            const anchor = anchorFromRange(window.getSelection().getRangeAt(0));

            if (anchor) {
                anchor.target = '_blank';
                anchor.rel = 'noopener noreferrer';
            }

            saveSelection();
        }

        closeLinkPanel();
        syncInput();
        status.textContent = 'Hyperlink aplicado.';
    });

    linkRemove.addEventListener('click', () => {
        if (linkTargetImage) {
            const anchor = imageLinkOf(linkTargetImage);

            if (anchor) {
                anchor.replaceWith(linkTargetImage);
                makeMediaDraggable(linkTargetImage);
                selectImage(linkTargetImage);
            }
        } else if (restoreLinkRange()) {
            document.execCommand('unlink', false, null);
            saveSelection();
        }

        closeLinkPanel();
        syncInput();
        status.textContent = 'Hyperlink removido.';
    });

    linkUrl.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            linkApply.click();
        }
    });

    imageButton.addEventListener('mousedown', (event) => event.preventDefault());
    imageButton.addEventListener('click', () => {
        placeInsertionBookmark();
        imageInput.click();
    });

    imageInput.addEventListener('change', async () => {
        const file = imageInput.files[0];

        if (!file) {
            removeInsertionBookmark();
            return;
        }

        const data = new FormData();
        data.append('imagem', file);

        status.textContent = 'Enviando imagem...';
        imageButton.disabled = true;

        try {
            const response = await fetch(shell.dataset.imageUploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: data,
            });

            const payload = await response.json();

            if (!response.ok || !payload.url) {
                throw new Error(payload.message || 'Não foi possível enviar a imagem.');
            }

            const inserted = document.createElement('img');
            inserted.src = payload.url;
            inserted.alt = file.name.replace(/\.[^.]+$/, '');
            inserted.dataset.size = 'medium';
            inserted.loading = 'lazy';
            makeMediaDraggable(inserted);
            insertMediaAtSelection(inserted);
            selectImage(inserted);

            syncInput();
            status.textContent = 'Imagem adicionada na posição do cursor. Arraste para reposicionar.';
        } catch (error) {
            removeInsertionBookmark();
            status.textContent = error.message || 'Não foi possível enviar a imagem.';
        } finally {
            imageButton.disabled = false;
            imageInput.value = '';
        }
    });

    videoButton.addEventListener('mousedown', (event) => event.preventDefault());
    videoButton.addEventListener('click', () => {
        placeInsertionBookmark();
        videoPanel.hidden = false;
        videoUrl.focus();
    });

    videoCancel.addEventListener('click', () => {
        removeInsertionBookmark();
        videoPanel.hidden = true;
        videoUrl.value = '';
        status.textContent = '';
        editor.focus();
    });

    videoInsert.addEventListener('click', () => {
        const id = youtubeIdFromUrl(videoUrl.value);

        if (!id) {
            status.textContent = 'Informe um link válido do YouTube.';
            videoUrl.focus();
            return;
        }

        const inserted = document.createElement('div');
        inserted.className = 'news-video-embed';
        inserted.innerHTML = `<iframe src="https://www.youtube.com/embed/${id}" title="Vídeo do YouTube" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
        makeMediaDraggable(inserted);
        insertMediaAtSelection(inserted);
        selectVideo(inserted);

        videoPanel.hidden = true;
        videoUrl.value = '';
        syncInput();
        status.textContent = 'Vídeo adicionado na posição do cursor. Arraste para reposicionar.';
    });

    videoUrl.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            videoInsert.click();
        }
    });

    const removeSelectedMedia = () => {
        if (selectedVideo) {
            const video = selectedVideo;
            deselectVideo();
            video.remove();
            syncInput();
            status.textContent = 'Vídeo removido da matéria.';
            editor.focus();
            return true;
        }

        if (selectedImage) {
            const image = selectedImage;
            image.classList.remove('is-selected');
            selectedImage = null;
            imageSize.value = '';
            imageSize.disabled = true;
            const imageLink = imageLinkOf(image);

            if (imageLink) {
                imageLink.remove();
            } else {
                image.remove();
            }

            syncInput();
            status.textContent = 'Imagem removida da matéria.';
            editor.focus();
            return true;
        }

        return false;
    };

    videoRemoveButton.addEventListener('click', () => {
        removeSelectedMedia();
    });

    editor.addEventListener('click', (event) => {
        const video = event.target.closest('.news-video-embed');

        if (video && editor.contains(video)) {
            selectVideo(video);
            status.textContent = 'Vídeo selecionado. Use a lixeira da barra para removê-lo.';
            return;
        }

        const image = event.target.closest('img');

        if (image && editor.contains(image)) {
            if (imageLinkOf(image)) {
                event.preventDefault();
            }

            selectImage(image);
            status.textContent = imageLinkOf(image)
                ? 'Imagem selecionada e com hyperlink. Use o botão de link para editar ou remover.'
                : 'Imagem selecionada. Escolha o tamanho na barra de formatação.';
            return;
        }

        deselectVideo();

        if (selectedImage) {
            selectedImage.classList.remove('is-selected');
            selectedImage = null;
            imageSize.value = '';
            imageSize.disabled = true;
        }
    });

    editor.querySelectorAll('.news-video-embed').forEach((video) => {
        makeMediaDraggable(video);
    });

    editor.querySelectorAll('img').forEach((image) => {
        if (!['small', 'medium', 'large', 'full'].includes(image.dataset.size)) {
            image.dataset.size = 'medium';
        }

        const imageLink = imageLinkOf(image);
        if (imageLink) {
            imageLink.classList.add('news-image-link');
            imageLink.dataset.size = image.dataset.size;
            imageLink.target = '_blank';
            imageLink.rel = 'noopener noreferrer';
        }

        makeMediaDraggable(image);
    });

    editor.addEventListener('dragstart', (event) => {
        const media = event.target.closest('img, .news-video-embed');

        if (!media || !editor.contains(media)) {
            return;
        }

        draggedMedia = mediaDragNode(media);
        draggedMedia.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', 'news-media');
    });

    editor.addEventListener('dragover', (event) => {
        if (!draggedMedia) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';

        const marker = ensureDropMarker();
        const target = directChildOfEditor(event.target);

        if (!target) {
            editor.append(marker);
            return;
        }

        const rect = target.getBoundingClientRect();
        const after = event.clientY > rect.top + (rect.height / 2);
        target.parentNode.insertBefore(marker, after ? target.nextSibling : target);
    });

    editor.addEventListener('drop', (event) => {
        if (!draggedMedia) {
            return;
        }

        event.preventDefault();

        if (dropMarker && dropMarker.parentNode) {
            dropMarker.replaceWith(draggedMedia);
        }

        draggedMedia.classList.remove('is-dragging');
        draggedMedia = null;
        dropMarker = null;
        syncInput();
        status.textContent = 'Mídia reposicionada na matéria.';
    });

    editor.addEventListener('dragend', () => {
        if (draggedMedia) {
            draggedMedia.classList.remove('is-dragging');
        }

        draggedMedia = null;

        if (dropMarker) {
            dropMarker.remove();
            dropMarker = null;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!['Delete', 'Backspace'].includes(event.key) || (!selectedImage && !selectedVideo)) {
            return;
        }

        const target = event.target;
        const typingField = target instanceof HTMLInputElement
            || target instanceof HTMLTextAreaElement
            || target instanceof HTMLSelectElement
            || (target instanceof HTMLElement && target.isContentEditable && target !== editor);

        if (typingField) {
            return;
        }

        event.preventDefault();
        removeSelectedMedia();
    });

    editor.addEventListener('input', syncInput);
    editor.addEventListener('keyup', saveSelection);
    editor.addEventListener('mouseup', saveSelection);
    editor.addEventListener('focus', saveSelection);
    document.addEventListener('selectionchange', saveSelection);

    form.addEventListener('submit', (event) => {
        removeInsertionBookmark();
        syncInput();

        if (!editor.textContent.replace(/\u00a0/g, ' ').trim()) {
            event.preventDefault();
            status.textContent = 'Escreva o conteúdo da notícia antes de publicar.';
            editor.focus();
        }
    });

    syncInput();
});
