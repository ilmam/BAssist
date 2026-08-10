{{-- Auto-dismiss success flash banners (data-bassist-auto-dismiss). Errors stay. --}}
<style>
    [data-bassist-auto-dismiss] {
        transition: opacity 0.35s ease, margin 0.35s ease, padding 0.35s ease, max-height 0.35s ease;
    }

    [data-bassist-auto-dismiss].is-dismissing {
        opacity: 0;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        max-height: 0 !important;
        overflow: hidden;
        pointer-events: none;
        border-width: 0 !important;
    }

    @media (prefers-reduced-motion: reduce) {
        [data-bassist-auto-dismiss] {
            transition: none;
        }
    }
</style>
<script>
    (function () {
        const DEFAULT_MS = 4000;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function dismiss(el) {
            if (!el || el.dataset.bassistDismissed === '1') {
                return;
            }

            el.dataset.bassistDismissed = '1';

            if (reduceMotion) {
                el.remove();
                return;
            }

            el.style.maxHeight = el.offsetHeight + 'px';
            // Force layout so max-height transition runs from measured height.
            void el.offsetHeight;
            el.classList.add('is-dismissing');

            const cleanup = () => {
                if (el.isConnected) {
                    el.remove();
                }
            };

            el.addEventListener('transitionend', cleanup, { once: true });
            window.setTimeout(cleanup, 500);
        }

        function schedule(el) {
            const raw = el.getAttribute('data-bassist-auto-dismiss');
            if (raw === 'off' || raw === 'false' || raw === '0') {
                return;
            }

            const ms = Number.parseInt(raw, 10);
            const delay = Number.isFinite(ms) && ms > 0 ? ms : DEFAULT_MS;

            window.setTimeout(() => dismiss(el), delay);
        }

        document.querySelectorAll('[data-bassist-auto-dismiss]').forEach(schedule);
    })();
</script>
