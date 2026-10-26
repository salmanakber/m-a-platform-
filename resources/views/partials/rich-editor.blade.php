{{--
  Shared TinyMCE init.
  @param string $selector CSS selector, e.g. #body or #description
  @param string|null $formId optional form to sync on submit
  @param int $height editor height
--}}
@php
    $selector = $selector ?? '#body';
    $formId = $formId ?? null;
    $height = (int) ($height ?? 420);
@endphp
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    if (!window.tinymce) return;
    var selector = @json($selector);
    var formId = @json($formId);
    var height = {{ $height }};

    if (tinymce.get(selector.replace(/^#/, ''))) {
        try { tinymce.get(selector.replace(/^#/, '')).remove(); } catch (e) {}
    }

    tinymce.init({
        selector: selector,
        height: height,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'lists link autolink code table autoresize',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat',
        block_formats: 'Absatz=p; Überschrift 2=h2; Überschrift 3=h3',
        content_style:
            'body{font-family:Manrope,system-ui,sans-serif;font-size:15.5px;line-height:1.7;color:#132033;padding:12px 16px;}' +
            'h2{font-family:Cormorant Garamond,Georgia,serif;font-size:1.55rem;color:#0E4971;margin:1.35em 0 .45em;font-weight:600;}' +
            'h3{font-size:1.08rem;color:#0E4971;margin:1.15em 0 .35em;}' +
            'p{margin:0 0 .95em;}' +
            'ul,ol{margin:0 0 1em;padding-left:1.35em;}' +
            'li{margin:0.25em 0;}' +
            'a{color:#0E4971;}' +
            'strong{color:#0a3554;}',
        skin: 'oxide',
        content_css: 'default',
        convert_urls: false,
        setup: function (editor) {
            editor.on('change keyup SetContent', function () { editor.save(); });
        },
        init_instance_callback: function (editor) {
            window.__richEditors = window.__richEditors || {};
            window.__richEditors[editor.id] = editor;
        }
    });

    window.setRichContent = function (id, html) {
        var ed = tinymce.get(id) || (window.__richEditors && window.__richEditors[id]);
        if (ed) {
            ed.setContent(html || '');
            ed.save();
            var host = ed.getContainer();
            if (host) {
                host.classList.add('xp-editor-flash');
                setTimeout(function () { host.classList.remove('xp-editor-flash'); }, 1200);
            }
            return true;
        }
        var el = document.getElementById(id);
        if (el) { el.value = html || ''; return true; }
        return false;
    };

    if (formId) {
        var form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function () {
                var id = selector.replace(/^#/, '');
                if (tinymce.get(id)) tinymce.get(id).save();
            });
        }
    }
})();
</script>
