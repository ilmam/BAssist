@php
    $mockupId = 'salt-'.uniqid();
@endphp

<section class="space-y-3">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-foreground">{{ __('ui.screen_mockup') }}</h3>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" id="{{ $mockupId }}-popup">{{ __('ui.screen_preview_popup') }}</button>
    </div>
    <p class="text-sm text-muted-foreground">{{ __('ui.screen_mockup_help') }}</p>

    <div id="{{ $mockupId }}" class="p-4 bg-white rounded border overflow-auto" style="min-height: 8rem;"></div>

    <details>
        <summary class="text-sm cursor-pointer">{{ __('ui.screen_salt_source') }}</summary>
        <pre id="{{ $mockupId }}-src" class="mt-2 p-3 text-xs border rounded overflow-auto">{{ $salt }}</pre>
    </details>
</section>

{{-- The browser build of PlantUML has no Salt, so the wireframe is drawn by our own small renderer. --}}
<link rel="stylesheet" href="{{ asset('css/salt-wireframe.css').'?v='.@filemtime(public_path('css/salt-wireframe.css')) }}">
<script>
    (function () {
        var out = document.getElementById(@json($mockupId));
        var src = document.getElementById(@json($mockupId.'-src'));

        function draw() {
            document.getElementById(@json($mockupId.'-popup')).addEventListener('click', function () {
                window.SaltWireframe.popup(src.textContent, @json($title ?? __('ui.screen_mockup')));
            });
            try {
                window.SaltWireframe.render(src.textContent, out);
            } catch (e) {
                out.textContent = 'Could not render the mockup: ' + e.message;
            }
        }

        if (window.SaltWireframe) {
            draw();
            return;
        }

        // Load the renderer first; an inline script can run before an external one is ready.
        var tag = document.createElement('script');
        tag.src = @json(asset('js/salt-wireframe.js').'?v='.@filemtime(public_path('js/salt-wireframe.js')));
        tag.onload = draw;
        tag.onerror = function () { out.textContent = 'Could not load the mockup renderer.'; };
        document.head.appendChild(tag);
    })();
</script>
