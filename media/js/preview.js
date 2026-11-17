(() => {
    const config = Joomla.getOptions('plg_fields_dharmavideo.preview');
    if (!config) return;

    const pending = new WeakMap();

    const showPlayer = (editor, embed) => {
        const src = new URL(embed);
        src.searchParams.set('autoplay', src.hostname === 'rutube.ru' ? 'false' : '0');

        const iframe = document.createElement('iframe');
        iframe.className = 'dharmavideo-player';
        iframe.src = src;
        iframe.title = config.title;
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.allow = 'encrypted-media; picture-in-picture; fullscreen';
        iframe.allowFullscreen = true;
        editor.querySelector('[data-dharmavideo-player]').replaceChildren(iframe);
    };

    document.querySelectorAll('[data-dharmavideo-initial-embed]').forEach(editor => {
        showPlayer(editor, editor.dataset.dharmavideoInitialEmbed);
    });

    document.addEventListener('subform-row-add', ({ detail: { row } }) => {
        const editor = row.querySelector('[data-dharmavideo-editor]');
        if (!editor) return;

        const input = editor.querySelector('input');
        const help = editor.querySelector('[data-dharmavideo-help]');
        help.id = input.id + '-desc';
        input.setAttribute('aria-describedby', help.id);
    });

    document.addEventListener('input', (event) => {
        const input = event.target;
        if (!input.matches('[data-dharmavideo-editor] input')) return;

        const editor = input.closest('[data-dharmavideo-editor]');
        const status = editor.querySelector('[data-dharmavideo-status]');
        const previous = pending.get(editor);

        if (previous) {
            clearTimeout(previous.timer);
            previous.controller.abort();
            pending.delete(editor);
        }

        editor.querySelector('[data-dharmavideo-player]').replaceChildren();
        status.textContent = '';
        const url = input.value.trim();
        if (!url) return;

        const controller = new AbortController();
        const timer = setTimeout(async () => {
            if (!editor.isConnected) return;

            try {
                const response = await fetch(config.url, {
                    method: 'POST',
                    body: new URLSearchParams({ url, [config.token]: '1' }),
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                const result = await response.json();
                if (controller.signal.aborted || !editor.isConnected) return;

                if (result.success && typeof result.data?.[0] === 'string') {
                    showPlayer(editor, result.data[0]);
                } else {
                    status.textContent = result.message || config.error;
                }
            } catch {
                if (!controller.signal.aborted && editor.isConnected) status.textContent = config.error;
            } finally {
                if (pending.get(editor)?.controller === controller) pending.delete(editor);
            }
        }, 450);

        pending.set(editor, { controller, timer });
    });
})();
