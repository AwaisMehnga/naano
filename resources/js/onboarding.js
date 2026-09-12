const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function showErrors(message) {
    const box = document.getElementById('form-errors');

    if (!box) {
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

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax')) {
        return;
    }

    event.preventDefault();

    const submit = form.querySelector('[type="submit"]');
    const analyze = form.hasAttribute('data-analyze');
    const status = form.querySelector('[data-analyze-status]');

    if (submit instanceof HTMLButtonElement) {
        submit.disabled = true;
    }

    if (analyze && status instanceof HTMLElement) {
        status.classList.remove('hidden');
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
            showErrors(firstValidationMessage(data));

            if (submit instanceof HTMLButtonElement) {
                submit.disabled = false;
            }

            return;
        }

        if (data.data?.redirect) {
            window.location.href = data.data.redirect;
        }
    } catch {
        showErrors('Something went wrong. Try again.');

        if (submit instanceof HTMLButtonElement) {
            submit.disabled = false;
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
