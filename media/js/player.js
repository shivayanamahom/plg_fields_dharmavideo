document.addEventListener('DOMContentLoaded', () => {
    if (window.Fancybox) {
        window.Fancybox.bind('[data-dharmavideo-fancybox]', {
            Hash: false,
            mainClass: 'dharmavideo-lightbox',
            Html: {
                iframeAttr: {
                    allow: 'autoplay; encrypted-media; picture-in-picture; fullscreen',
                    allowfullscreen: '',
                    referrerpolicy: 'strict-origin-when-cross-origin',
                },
            },
        });
    }
}, { once: true });

document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const launch = event.target.closest('[data-dharmavideo-embed]');
    if (!launch || (launch.hasAttribute('data-dharmavideo-fancybox') && window.Fancybox)) return;

    const url = new URL(launch.dataset.dharmavideoEmbed);
    url.searchParams.set('autoplay', launch.dataset.dharmavideoProvider === 'vk' ? '1' : 'true');
    event.preventDefault();

    const iframe = document.createElement('iframe');
    iframe.className = 'dharmavideo-player';
    iframe.src = url;
    iframe.title = launch.dataset.dharmavideoTitle;
    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    iframe.allowFullscreen = true;
    launch.replaceWith(iframe);
    iframe.focus({ preventScroll: true });
});
