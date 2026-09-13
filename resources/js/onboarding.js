const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function showErrors(message, errors) {
    const box = document.getElementById('form-errors');

    document.querySelectorAll('[data-error-for]').forEach((el) => {
        if (!(el instanceof HTMLElement)) {
            return;
        }

        el.textContent = '';
        el.classList.add('hidden');
    });

    if (errors && typeof errors === 'object') {
        Object.entries(errors).forEach(([key, value]) => {
            const field = document.querySelector(`[data-error-for="${key}"]`);
            const text = Array.isArray(value) ? String(value[0] ?? '') : String(value ?? '');

            if (field instanceof HTMLElement && text !== '') {
                field.textContent = text;
                field.classList.remove('hidden');
            }
        });
    }

    if (!box) {
        return;
    }

    if (!message) {
        box.textContent = '';
        box.classList.add('hidden');

        return;
    }

    box.textContent = message;
    box.classList.remove('hidden');
}

function firstValidationMessage(data) {
    const errors = data?.data ?? data?.errors;

    if (errors && typeof errors === 'object') {
        const first = Object.values(errors)[0];

        if (Array.isArray(first) && first[0]) {
            return String(first[0]);
        }
    }

    return data?.message ?? 'Check the form and try again.';
}

function validationErrors(data) {
    const errors = data?.data ?? data?.errors;

    if (errors && typeof errors === 'object') {
        return errors;
    }

    return null;
}

function resetAnalyze(status) {
    if (!(status instanceof HTMLElement)) {
        return;
    }

    status.classList.add('hidden');
    status.querySelectorAll('[data-check]').forEach((line) => {
        line.classList.add('hidden');
        line.classList.remove('text-foreground');
        line.classList.add('text-muted-foreground');
    });
}

function startAnalyze(status) {
    if (!(status instanceof HTMLElement)) {
        return;
    }

    resetAnalyze(status);
    status.classList.remove('hidden');

    const lines = [...status.querySelectorAll('[data-check]')];

    lines.forEach((line, index) => {
        window.setTimeout(() => {
            line.classList.remove('hidden');
            line.classList.remove('text-muted-foreground');
            line.classList.add('text-foreground');
        }, index * 400);
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax')) {
        return;
    }

    event.preventDefault();

    const submit = form.querySelector('[type="submit"]');
    const analyze = form.hasAttribute('data-analyze');
    const status = form.querySelector('[data-analyze-status]');
    const originalLabel = submit instanceof HTMLButtonElement ? submit.textContent : '';

    if (submit instanceof HTMLButtonElement) {
        submit.disabled = true;
        submit.textContent = 'Working…';
    }

    if (analyze) {
        startAnalyze(status);
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });

        const data = await response.json();

        if (!response.ok || data.status !== 'success') {
            showErrors(firstValidationMessage(data), validationErrors(data));
            resetAnalyze(status);

            if (submit instanceof HTMLButtonElement) {
                submit.disabled = false;
                submit.textContent = originalLabel;
            }

            return;
        }

        if (data.data?.redirect) {
            window.location.href = data.data.redirect;
        }
    } catch {
        showErrors('Something went wrong. Try again.');
        resetAnalyze(status);

        if (submit instanceof HTMLButtonElement) {
            submit.disabled = false;
            submit.textContent = originalLabel;
        }
    }
});

document.querySelectorAll('[data-industry-group]').forEach((group) => {
    group.addEventListener('change', () => {
        const boxes = [...group.querySelectorAll('input[type="checkbox"]')];
        const checked = boxes.filter((box) => box.checked);

        if (checked.length > 3) {
            checked.at(-1).checked = false;
        }
    });
});

document.querySelectorAll('[data-offer-form]').forEach((form) => {
    const list = form.querySelector('[data-bundle-list]');
    const template = form.querySelector('[data-bundle-template]');
    const add = form.querySelector('[data-add-bundle]');

    if (!(list instanceof HTMLElement) || !(template instanceof HTMLTemplateElement) || !(add instanceof HTMLElement)) {
        return;
    }

    let index = 0;

    add.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        list.insertAdjacentHTML('beforeend', html);
        index += 1;
    });
});
