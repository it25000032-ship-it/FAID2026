(function () {
    document.documentElement.classList.add('js');

    // 1) Header shadow + yellow scroll-progress bar
    var header = document.querySelector('header');
    var bar = document.querySelector('.progress');
    function onScroll() {
        if (header) header.classList.toggle('scrolled', window.scrollY > 10);
        var max = document.documentElement.scrollHeight - window.innerHeight;
        if (bar) bar.style.transform = 'scaleX(' + (max > 0 ? window.scrollY / max : 0) + ')';
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    // 2) Feature cards fade in when they enter the screen
    var items = document.querySelectorAll('.feature, .reveal');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('in', 'is-visible');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.2 });
        items.forEach(function (el) { io.observe(el); });
    } else {
        items.forEach(function (el) { el.classList.add('in', 'is-visible'); });
    }

    // 3) Hero image tilts with the mouse
    var wrap = document.querySelector('.hero-image');
    if (wrap && window.matchMedia('(hover: hover) and (prefers-reduced-motion: no-preference)').matches) {
        wrap.addEventListener('mousemove', function (e) {
            var r = wrap.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            wrap.style.setProperty('--ry', (x * 8) + 'deg');
            wrap.style.setProperty('--rx', (-y * 8) + 'deg');
        });
        wrap.addEventListener('mouseleave', function () {
            wrap.style.setProperty('--ry', '0deg');
            wrap.style.setProperty('--rx', '0deg');
        });
    }

    // 4) Ripple on every button click
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var r = btn.getBoundingClientRect();
        var d = Math.max(r.width, r.height);
        var s = document.createElement('span');
        s.className = 'ripple';
        s.style.width = s.style.height = d + 'px';
        s.style.left = (e.clientX - r.left - d / 2) + 'px';
        s.style.top = (e.clientY - r.top - d / 2) + 'px';
        btn.appendChild(s);
        setTimeout(function () { s.remove(); }, 600);
    });
    }

    // 5) Close any modal with Escape or by clicking the dark background
    function closeAll() {
        document.querySelectorAll('.modal').forEach(function (m) { m.style.display = 'none'; });
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
    document.querySelectorAll('.modal').forEach(function (m) {
        m.addEventListener('click', function (e) { if (e.target === m) m.style.display = 'none'; });
    });

    window.showToast = function (message, type) {
        var region = document.getElementById('toastRegion');
        if (!region) return;
        var toast = document.createElement('div');
        toast.className = 'toast ' + (type === 'error' ? 'error' : 'success');
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.textContent = String(message || '');
        region.appendChild(toast);
        window.setTimeout(function () { toast.remove(); }, 5000);
    };

    document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var field = toggle.closest('.password-field');
            var input = field && field.querySelector('input');
            if (!input) return;
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.textContent = visible ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        });
    });

    function passwordStrength(value) {
        var score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/\d/.test(value) && /[^A-Za-z0-9]/.test(value)) score++;
        return Math.min(4, score);
    }

    document.querySelectorAll('[data-password-strength-for]').forEach(function (widget) {
        var input = document.getElementById(widget.dataset.passwordStrengthFor);
        var label = widget.querySelector('strong');
        if (!input || !label) return;
        input.addEventListener('input', function () {
            var score = input.value ? passwordStrength(input.value) : 0;
            var labels = ['Use at least 8 characters', 'Weak', 'Fair', 'Good', 'Strong'];
            widget.dataset.strength = String(score);
            label.textContent = labels[score];
        });
    });

    function errorFor(field) {
        var wrapper = field.parentElement;
        if (wrapper && wrapper.classList.contains('password-field')) {
            wrapper = wrapper.nextElementSibling;
        } else {
            wrapper = field.nextElementSibling;
        }
        return wrapper && wrapper.classList.contains('field-error') ? wrapper : null;
    }

    function validateField(field) {
        if (field.id === 'confirmPassword') {
            var newPassword = document.getElementById('newPassword');
            field.setCustomValidity(
                newPassword && field.value !== newPassword.value
                    ? 'Passwords do not match.'
                    : ''
            );
        }
        var valid = field.validity.valid;
        field.setAttribute('aria-invalid', valid ? 'false' : 'true');
        var error = errorFor(field);
        if (error) error.textContent = valid ? '' : field.validationMessage;
        return valid;
    }

    document.querySelectorAll('form').forEach(function (form) {
        var fields = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea'));
        var touched = new WeakSet();
        fields.forEach(function (field) {
            field.addEventListener('blur', function () {
                touched.add(field);
                validateField(field);
            });
            field.addEventListener('input', function () {
                if (touched.has(field)) validateField(field);
                if (field.id === 'newPassword') {
                    var confirmation = document.getElementById('confirmPassword');
                    if (confirmation && touched.has(confirmation)) validateField(confirmation);
                }
            });
            field.addEventListener('change', function () {
                if (touched.has(field)) validateField(field);
            });
        });

        if (form.hasAttribute('data-loading-form')) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    fields.forEach(function (field) {
                        touched.add(field);
                        validateField(field);
                    });
                    var firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) firstInvalid.focus();
                    return;
                }
                var button = form.querySelector('button[type="submit"]');
                if (button && !form.dataset.submitting) {
                    form.dataset.submitting = 'true';
                    button.dataset.originalText = button.textContent;
                    button.textContent = 'Signing in...';
                    button.classList.add('is-loading');
                    button.disabled = true;
                } else if (form.dataset.submitting) {
                    event.preventDefault();
                }
            });
        }
    });
})();
