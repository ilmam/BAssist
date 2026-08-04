<script>
(function () {
    var formId = @json($formId);
    var optionsUrl = @json($affectedOptionsUrl);

    function findControl(form, name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function selectedValue(control) {
        if (!control) {
            return '';
        }
        return (control.value || '').toString();
    }

    function setSelectOptions(select, options, selectedId) {
        if (!select) {
            return;
        }

        var keep = selectedId ? String(selectedId) : '';
        select.innerHTML = '';

        var blank = document.createElement('option');
        blank.value = '';
        blank.textContent = '—';
        select.appendChild(blank);

        (options || []).forEach(function (opt) {
            var option = document.createElement('option');
            option.value = String(opt.id);
            option.textContent = opt.label;
            if (keep !== '' && String(opt.id) === keep) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function bindAffectedOptions(form, url) {
        if (!form || form.dataset.affectedOptionsBound === '1') {
            return;
        }
        form.dataset.affectedOptionsBound = '1';

        var typeControl = findControl(form, 'affected_type');
        var projectControl = findControl(form, 'project_id');
        var itemControl = findControl(form, 'affected_id');
        if (!typeControl || !itemControl) {
            return;
        }

        var reload = function () {
            var type = selectedValue(typeControl);
            var projectId = selectedValue(projectControl);
            var current = selectedValue(itemControl);

            if (!type || !projectId || projectId === '0') {
                setSelectOptions(itemControl, [], '');
                return;
            }

            var endpoint = url + '?type=' + encodeURIComponent(type) + '&project_id=' + encodeURIComponent(projectId);
            fetch(endpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('affected options failed');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    setSelectOptions(itemControl, payload.options || [], current);
                })
                .catch(function () {
                    setSelectOptions(itemControl, [], '');
                });
        };

        form.addEventListener('change', function (event) {
            var name = event.target && event.target.getAttribute('name');
            if (name === 'affected_type' || name === 'project_id') {
                reload();
            }
        });

        reload();
    }

    function boot(root) {
        var scope = root && root.querySelector ? root : document;
        var form = null;

        if (scope.id === formId) {
            form = scope;
        } else if (scope.querySelector) {
            form = scope.querySelector('#' + formId);
        }

        if (!form) {
            form = document.getElementById(formId);
        }

        if (form) {
            bindAffectedOptions(form, optionsUrl);
        }
    }

    boot(document);

    if (!window.__bassistAffectedOptionsListening) {
        window.__bassistAffectedOptionsListening = true;
        document.addEventListener('DOMContentLoaded', function () { boot(document); });
        document.addEventListener('bassist:modal-loaded', function (event) {
            boot((event.detail && event.detail.container) || document);
        });
    }
})();
</script>
