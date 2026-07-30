(function () {
  'use strict';

  function activateFacade(facade) {
    if (!facade || facade.dataset.kingyActivated === '1') {
      return;
    }

    var videoId = facade.getAttribute('data-kingy-youtube-id') || '';
    if (!/^[A-Za-z0-9_-]{11}$/.test(videoId)) {
      return;
    }

    var button = facade.querySelector('button');
    var title = button ? button.getAttribute('aria-label') : 'Kingy AI video';
    var iframe = document.createElement('iframe');
    iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0';
    iframe.title = title;
    iframe.width = '1280';
    iframe.height = '720';
    iframe.loading = 'eager';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    iframe.allowFullscreen = true;
    iframe.setAttribute('tabindex', '0');

    facade.dataset.kingyActivated = '1';
    facade.replaceChildren(iframe);
    iframe.focus({ preventScroll: true });
  }

  function useThumbnailFallback(image) {
    var fallback = image ? image.getAttribute('data-fallback-src') : '';
    if (!fallback || image.dataset.kingyFallbackUsed === '1') {
      return;
    }

    image.dataset.kingyFallbackUsed = '1';
    image.src = fallback;
  }

  document.querySelectorAll('[data-kingy-youtube-thumbnail]').forEach(function (image) {
    image.addEventListener('error', function () {
      useThumbnailFallback(image);
    });
    image.addEventListener('load', function () {
      if (image.naturalWidth > 0 && image.naturalWidth < 640) {
        useThumbnailFallback(image);
      }
    });

    if (image.complete && (image.naturalWidth === 0 || image.naturalWidth < 640)) {
      useThumbnailFallback(image);
    }
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.kingy-ali-youtube-facade button');
    if (button) {
      activateFacade(button.closest('.kingy-ali-youtube-facade'));
    }
  });
})();
