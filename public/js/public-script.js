(function() {
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wrappers = document.querySelectorAll('.sk-connect-form-wrapper');

    wrappers.forEach(wrapper => {
        const form = wrapper.querySelector('.sk-connect-contact-form');
        if (!form) return;

        const submitBtn = wrapper.querySelector('.sk-connect-submit-btn');
        const spinner = wrapper.querySelector('.sk-connect-spinner');
        const feedbackGeneral = wrapper.querySelector('.sk-connect-feedback-general');
        const successContainer = wrapper.querySelector('.sk-connect-success-container');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Clear previous states
            feedbackGeneral.textContent = '';
            feedbackGeneral.className = 'sk-connect-feedback-general';
            feedbackGeneral.style.display = 'none';

            let hasError = false;

            // Simple HTML5 validation fallback
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.classList.remove('sk-connect-invalid');
                const fb = input.parentElement.querySelector('.sk-connect-field-feedback');
                if (fb) fb.style.display = 'none';

                if (input.required && !input.value.trim()) {
                    hasError = true;
                    input.classList.add('sk-connect-invalid');
                    if (fb) {
                        fb.textContent = 'This field is required';
                        fb.style.display = 'block';
                    }
                } else if (input.type === 'email' && input.value) {
                    const re = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
                    if (!re.test(input.value)) {
                        hasError = true;
                        input.classList.add('sk-connect-invalid');
                        if (fb) {
                            fb.textContent = 'Invalid email address';
                            fb.style.display = 'block';
                        }
                    }
                } else if (input.type === 'url' && input.value) {
                    try {
                        new URL(input.value);
                    } catch (_) {
                        hasError = true;
                        input.classList.add('sk-connect-invalid');
                        if (fb) {
                            fb.textContent = 'Please enter a valid URL (e.g., https://example.com)';
                            fb.style.display = 'block';
                        }
                    }
                }
            });

            // Special handling for required radio groups
            const radioGroups = {};
            form.querySelectorAll('input[type="radio"][required]').forEach(radio => {
                const name = radio.name;
                if (!radioGroups[name]) {
                    radioGroups[name] = { checked: false, elements: [] };
                }
                radioGroups[name].elements.push(radio);
                if (radio.checked) {
                    radioGroups[name].checked = true;
                }
            });

            for (const name in radioGroups) {
                if (!radioGroups[name].checked) {
                    hasError = true;
                    const firstEl = radioGroups[name].elements[0];
                    const wrap = firstEl.closest('.sk-connect-form-group');
                    if (wrap) {
                        const fb = wrap.querySelector('.sk-connect-field-feedback');
                        if (fb) {
                            fb.textContent = 'Please select an option';
                            fb.style.display = 'block';
                        }
                        // Add invalid class to first element for scrolling
                        firstEl.classList.add('sk-connect-invalid');
                    }
                }
            }

            // Special handling for required checkbox groups
            const checkboxGroups = {};
            form.querySelectorAll('input[type="checkbox"][required]').forEach(cb => {
                const name = cb.name.replace('[]', '');
                if (!checkboxGroups[name]) {
                    checkboxGroups[name] = { checked: false, elements: [] };
                }
                checkboxGroups[name].elements.push(cb);
                if (cb.checked) {
                    checkboxGroups[name].checked = true;
                }
            });

            for (const name in checkboxGroups) {
                if (!checkboxGroups[name].checked) {
                    hasError = true;
                    const firstEl = checkboxGroups[name].elements[0];
                    const wrap = firstEl.closest('.sk-connect-form-group');
                    if (wrap) {
                        const fb = wrap.querySelector('.sk-connect-field-feedback');
                        if (fb) {
                            fb.textContent = 'Please select at least one option';
                            fb.style.display = 'block';
                        }
                        // Add invalid class to first element for scrolling
                        firstEl.classList.add('sk-connect-invalid');
                    }
                }
            }


            if (hasError) {
                const firstError = form.querySelector('.sk-connect-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            // UI Loading state
            submitBtn.classList.add('loading');
            spinner.style.display = 'block';

            // Gather Data
            const formData = new FormData(form);
            formData.append('action', 'sk_connect_submit_custom_form');
            formData.append('security', sk_connect_form_obj.nonce);

            fetch(sk_connect_form_obj.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(res => {
                submitBtn.classList.remove('loading');
                spinner.style.display = 'none';

                if (res.success) {
                    form.style.display = 'none';
                    successContainer.style.display = 'block';
                } else {
                    feedbackGeneral.style.display = 'block';
                    feedbackGeneral.textContent = res.data || 'An error occurred. Please try again.';
                    feedbackGeneral.classList.add('sk-connect-error');
                }
            })
            .catch(err => {
                console.error(err);
                submitBtn.classList.remove('loading');
                spinner.style.display = 'none';
                feedbackGeneral.style.display = 'block';
                feedbackGeneral.textContent = 'Network error. Please check your connection and try again.';
                feedbackGeneral.classList.add('sk-connect-error');
            });
        });
    });
});

})();
