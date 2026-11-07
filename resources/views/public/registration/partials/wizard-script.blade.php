<script>
(function () {
    var form = document.querySelector('[data-register-wizard]');
    if (!form) return;

    var root = form.closest('.register-shell') || form.parentElement || document;
    var links = Array.prototype.slice.call(root.querySelectorAll('.register-steps__item'));
    var panels = Array.prototype.slice.call(form.querySelectorAll('[data-step-panel]'));
    var nav = form.querySelector('[data-register-nav]');
    var btnPrev = form.querySelector('[data-register-prev]');
    var btnNext = form.querySelector('[data-register-next]');
    var submitWrap = form.querySelector('[data-register-submit]');
    var submitBtn = form.querySelector('[data-register-submit] button[type="submit"]');
    var total = panels.length;
    var current = 1;
    if (!total) return;

    var nextDefaultLabel = btnNext ? (btnNext.getAttribute('data-label-next') || btnNext.textContent.trim() || 'Weiter →') : 'Weiter →';
    var submitLabel = (submitBtn && submitBtn.textContent.trim()) || 'Registrierung einreichen';

    function panelFor(step) {
        return form.querySelector('[data-step-panel="' + step + '"]');
    }

    function setHidden(el, hide) {
        if (!el) return;
        el.hidden = !!hide;
        el.classList.toggle('is-hidden', !!hide);
        if (hide) {
            el.setAttribute('aria-hidden', 'true');
        } else {
            el.removeAttribute('aria-hidden');
        }
    }

    function setStep(step) {
        current = Math.max(1, Math.min(total, step));
        var isLast = current === total;

        panels.forEach(function (panel) {
            var active = Number(panel.getAttribute('data-step-panel')) === current;
            setHidden(panel, !active);
            panel.classList.toggle('is-active-step', active);
        });

        links.forEach(function (link) {
            var s = Number(link.getAttribute('data-step'));
            link.classList.toggle('is-active', s === current);
            link.classList.toggle('is-done', s < current);
            link.setAttribute('aria-current', s === current ? 'step' : 'false');
        });

        setHidden(btnPrev, current === 1);
        // Keep Weiter visible on last step — it submits the form.
        setHidden(btnNext, false);
        setHidden(submitWrap, true); // note + links stay out of the way; primary CTA is nav button
        setHidden(nav, false);

        if (btnNext) {
            btnNext.textContent = isLast ? submitLabel : nextDefaultLabel;
            btnNext.setAttribute('data-mode', isLast ? 'submit' : 'next');
            btnNext.type = 'button';
        }

        // Show legal note under nav on last step only.
        var note = form.querySelector('[data-register-final-note]');
        setHidden(note, !isLast);
        var loginLine = form.querySelector('[data-register-final-links]');
        setHidden(loginLine, !isLast);
    }

    function validateStep(step) {
        var panel = panelFor(step);
        if (!panel) return true;
        var fields = panel.querySelectorAll('input, select, textarea');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (field.disabled || field.type === 'hidden') continue;
            if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                field.reportValidity();
                field.focus();
                return false;
            }
        }
        return true;
    }

    function validateAll() {
        for (var s = 1; s <= total; s++) {
            if (!validateStep(s)) {
                setStep(s);
                return false;
            }
        }
        return true;
    }

    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var target = Number(link.getAttribute('data-step'));
            if (target === current) return;
            if (target > current) {
                for (var s = current; s < target; s++) {
                    if (!validateStep(s)) return;
                }
            }
            setStep(target);
        });
    });

    if (btnNext) {
        btnNext.addEventListener('click', function () {
            if (btnNext.getAttribute('data-mode') === 'submit') {
                if (!validateAll()) return;
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
                return;
            }
            if (!validateStep(current)) return;
            setStep(current + 1);
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function () {
            setStep(current - 1);
        });
    }

    form.addEventListener('submit', function (e) {
        if (!validateAll()) {
            e.preventDefault();
        }
    });

    var errorField = form.querySelector('.is-invalid, .field-error');
    if (errorField) {
        var errPanel = errorField.closest('[data-step-panel]');
        if (errPanel) current = Number(errPanel.getAttribute('data-step-panel')) || 1;
    }

    setStep(current);

    form.querySelectorAll('.register-upload__input').forEach(function (input) {
        input.addEventListener('change', function () {
            var meta = input.parentElement && input.parentElement.querySelector('.register-upload__meta');
            if (meta) {
                meta.textContent = (input.files && input.files[0]) ? input.files[0].name : (meta.getAttribute('data-default') || meta.textContent);
            }
            if (input.closest('.register-upload')) {
                input.closest('.register-upload').classList.toggle('has-file', !!(input.files && input.files[0]));
            }
        });
    });
})();
</script>
