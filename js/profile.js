
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profileForm');
    const status = document.getElementById('profileAutosaveStatus');

    if (!form || !status) return;

    const statusText = status.querySelector('span');

    let saveTimer = null;
    let saving = false;
    let saveAgain = false;

    function setStatus(state, message, icon) {
        status.classList.remove('saving', 'saved', 'error');

        if (state) {
            status.classList.add(state);
        }

        const iconElement = status.querySelector('i');

        if (iconElement) {
            iconElement.className = `fa-solid ${icon}`;
        }

        if (statusText) {
            statusText.textContent = message;
        }
    }

    async function saveProfile() {
        clearTimeout(saveTimer);

        if (!form.checkValidity()) {
            form.reportValidity();
            setStatus(
                'error',
                'Please correct the highlighted fields.',
                'fa-circle-exclamation'
            );
            return;
        }

        if (saving) {
            saveAgain = true;
            return;
        }

        saving = true;
        saveAgain = false;

        const formData = new FormData(form);

        setStatus(
            'saving',
            'Saving changes...',
            'fa-spinner fa-spin'
        );

        try {
            const response = await fetch('save-profile.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Unable to save changes.'
                );
            }

            const accountName = document.getElementById('accountName');

            if (accountName && result.full_name !== undefined) {
                accountName.value = result.full_name;
            }

            setStatus(
                'saved',
                'All changes saved.',
                'fa-cloud-check'
            );
        } catch (error) {
            setStatus(
                'error',
                error.message || 'Save failed. Please try again.',
                'fa-circle-exclamation'
            );
        } finally {
            saving = false;

            if (saveAgain) {
                saveAgain = false;
                saveProfile();
            }
        }
    }

    function scheduleSave() {
        clearTimeout(saveTimer);

        setStatus(
            'saving',
            'Unsaved changes...',
            'fa-cloud-arrow-up'
        );

        saveTimer = setTimeout(saveProfile, 600);
    }

    // Save automatically when a field changes.
    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

    // Pressing Enter submits the form and saves immediately.
    form.addEventListener('submit', event => {
        event.preventDefault();
        saveProfile();
    });
});
