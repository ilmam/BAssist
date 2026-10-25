/**
 * Bridge between the classic form controls and the EntityForm Livewire component.
 *
 * Field controls (native inputs, KTSelect, the code editor) stay plain form
 * elements; this script reads the form like a browser would and talks to the
 * component:
 *
 *  - leaving / changing a field -> component.validateField(name, value)  (live validation)
 *  - submit             -> component.save(values)               (save in place)
 *
 * The form keeps its `action`, so without JavaScript it still posts normally.
 */
const FORM = 'form[data-livewire-form]';

function componentFor(form) {
    const root = form.closest('[wire\\:id]');

    return root ? window.Livewire?.find(root.getAttribute('wire:id')) ?? null : null;
}

/** Form values as an object; repeated / `name[]` keys become arrays. */
function formValues(form) {
    const values = {};
    new FormData(form).forEach((value, key) => {
        if (value instanceof File) {
            return;
        }
        if (key.endsWith('[]')) {
            const name = key.slice(0, -2);
            (values[name] ||= []).push(value);
        } else if (key in values) {
            values[key] = [].concat(values[key], value);
        } else {
            values[key] = value;
        }
    });

    return values;
}

function fieldValue(form, name) {
    const values = formValues(form);

    return name in values ? values[name] : '';
}

/**
 * Validate a field when the user leaves it (focusout) or changes it (selects,
 * checkboxes, widgets). The last checked value is remembered per field so the
 * focusout that follows a change does not send the same value twice.
 */
function checkField(event) {
    const field = event.target;
    const form = field instanceof Element ? field.closest(FORM) : null;
    if (!form || !field.name || field.name === '_token' || field.name === '_method' || field.type === 'hidden') {
        return;
    }

    const value = fieldValue(form, field.name);
    const signature = JSON.stringify(value);
    if (field.dataset.lwChecked === signature) {
        return;
    }
    field.dataset.lwChecked = signature;

    componentFor(form)?.validateField(field.name, value);
}

document.addEventListener('change', checkField);
document.addEventListener('focusout', checkField);

document.addEventListener('submit', (event) => {
    const form = event.target instanceof Element ? event.target.closest(FORM) : null;
    const component = form ? componentFor(form) : null;
    if (!form || !component) {
        return;
    }

    event.preventDefault();

    const submit = form.querySelector('[type="submit"]');
    if (submit) {
        submit.disabled = true;
    }

    component.save(formValues(form)).finally(() => {
        // The component may have been replaced (redirect, pop-up closed); re-query.
        if (submit?.isConnected) {
            submit.disabled = false;
        }
    });
});

// Saved on the page: the unsaved-changes guard starts again from the saved state.
document.addEventListener('livewire:init', () => {
    window.Livewire.on('entity-form-saved', () => {
        window.setTimeout(() => {
            document.querySelectorAll(FORM).forEach((form) => window.bassistCaptureFormBaseline?.(form));
        }, 50);
    });
});
