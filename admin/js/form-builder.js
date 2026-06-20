(function() {
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize State
    const rawFields = (typeof sk_connect_builder_data !== 'undefined' && sk_connect_builder_data.fields) ? sk_connect_builder_data.fields : [];
    const rawSettings = (typeof sk_connect_builder_data !== 'undefined' && sk_connect_builder_data.settings) ? sk_connect_builder_data.settings : {};
    
    let fields = Array.isArray(rawFields) ? rawFields : [];
    let settings = (rawSettings && typeof rawSettings === 'object') ? rawSettings : {
        recipient_email: '',
        success_message: 'Thank you! Your message has been sent successfully.',
        primary_color: '#6366f1',
        submit_label: 'Send Message'
    };


    let selectedFieldIndex = null;
    let hasUnsavedChanges = false;

    const canvas = document.getElementById('sk-connect-builder-canvas');
    const emptyState = document.getElementById('sk-connect-canvas-empty');
    const inspector = document.getElementById('sk-connect-properties-inspector');
    const inspectorContent = document.getElementById('inspector-content');
    const inspectorInstructions = document.getElementById('inspector-instructions');

    // Form settings inputs
    const formTitleInput = document.getElementById('sk-connect-form-title-input');
    const formEmailInput = document.getElementById('sk-connect-form-email-input');
    const formSuccessInput = document.getElementById('sk-connect-form-success-input');
    const formSubmitLblInput = document.getElementById('sk-connect-form-submit-lbl');
    const formAccentColorInput = document.getElementById('sk-connect-form-accent-color');
    const formAccentColorHexInput = document.getElementById('sk-connect-form-accent-color-hex');

    // Unsaved changes warning
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Initialize form settings inputs from loaded state
    if (formEmailInput) formEmailInput.value = settings.recipient_email;
    if (formSuccessInput) formSuccessInput.value = settings.success_message;
    if (formSubmitLblInput) formSubmitLblInput.value = settings.submit_label || 'Send Message';
    if (formAccentColorInput) {
        formAccentColorInput.value = settings.primary_color || '#6366f1';
        formAccentColorHexInput.value = settings.primary_color || '#6366f1';
    }

    // Synchronize Form Color picker
    if (formAccentColorInput && formAccentColorHexInput) {
        formAccentColorInput.addEventListener('input', function() {
            formAccentColorHexInput.value = formAccentColorInput.value;
            settings.primary_color = formAccentColorInput.value;
            hasUnsavedChanges = true;
        });
        formAccentColorHexInput.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(formAccentColorHexInput.value)) {
                formAccentColorInput.value = formAccentColorHexInput.value;
                settings.primary_color = formAccentColorHexInput.value;
                hasUnsavedChanges = true;
            }
        });
    }

    // Debounce utility
    function debounce(fn, delay) {
        let timer;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() { fn.apply(context, args); }, delay);
        };
    }

    const debouncedRenderCanvas = debounce(renderCanvas, 150);

    // 2. Render Canvas Preview Function
    function renderCanvas() {
        canvas.innerHTML = '';
        
        if (fields.length === 0) {
            emptyState.style.display = 'block';
            return;
        }
        emptyState.style.display = 'none';

        fields.forEach((field, index) => {
            const block = document.createElement('div');
            block.className = 'sk-connect-canvas-block';
            if (selectedFieldIndex === index) {
                block.classList.add('active');
            }

            // Click block to select
            block.addEventListener('click', function(e) {
                if (e.target.closest('.block-actions')) return; // ignore move/delete clicks
                selectField(index);
            });

            // Block Layout
            let inputHTML = '';
            if (['text', 'email', 'tel', 'number', 'url', 'date'].includes(field.type)) {
                inputHTML = `<input type="${field.type}" placeholder="${escapeHtml(field.placeholder || '')}" class="sk-connect-canvas-input" disabled>`;
            } else if (field.type === 'textarea') {
                inputHTML = `<textarea placeholder="${escapeHtml(field.placeholder || '')}" class="sk-connect-canvas-input" disabled style="height:60px; resize:none;"></textarea>`;
            } else if (field.type === 'select') {
                const options = Array.isArray(field.options) ? field.options : [];
                inputHTML = `<select class="sk-connect-canvas-input" disabled style="appearance:none;">
                    ${options.map(opt => `<option>${escapeHtml(opt)}</option>`).join('')}
                </select>`;
            } else if (field.type === 'checkbox' || field.type === 'radio') {
                const options = Array.isArray(field.options) ? field.options : [];
                const type = field.type;
                inputHTML = `<div class="sk-connect-canvas-options-wrap">
                    ${options.map(opt => `
                        <label class="sk-connect-canvas-option-lbl">
                            <span class="indicator ${type}"></span> ${escapeHtml(opt)}
                        </label>
                    `).join('')}
                </div>`;
            }

            block.innerHTML = `
                <div class="block-header">
                    <span class="block-label">${escapeHtml(field.label)} ${field.required ? '<span style="color:#ef4444;">*</span>' : ''}</span>
                    <span class="block-type-badge">${field.type.toUpperCase()}</span>
                </div>
                <div class="block-body">
                    ${inputHTML}
                </div>
                <div class="block-actions">
                    <button type="button" class="action-move-up" title="Move Up" data-index="${index}">▲</button>
                    <button type="button" class="action-move-down" title="Move Down" data-index="${index}">▼</button>
                    <button type="button" class="action-delete" title="Delete Field" data-index="${index}">🗑️</button>
                </div>
            `;

            canvas.appendChild(block);
        });

        // Add action triggers
        canvas.querySelectorAll('.action-move-up').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                moveField(idx, -1);
            });
        });

        canvas.querySelectorAll('.action-move-down').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                moveField(idx, 1);
            });
        });

        canvas.querySelectorAll('.action-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                deleteField(idx);
            });
        });
    }

    // 3. Move Fields Function
    function moveField(index, direction) {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= fields.length) return;

        // Swap items
        const temp = fields[index];
        fields[index] = fields[newIndex];
        fields[newIndex] = temp;

        // Retain selection
        if (selectedFieldIndex === index) {
            selectedFieldIndex = newIndex;
        } else if (selectedFieldIndex === newIndex) {
            selectedFieldIndex = index;
        }

        hasUnsavedChanges = true;
        renderCanvas();
    }

    // 4. Delete Field Function
    function deleteField(index) {
        fields.splice(index, 1);
        if (selectedFieldIndex === index) {
            closeInspector();
        } else if (selectedFieldIndex > index) {
            selectedFieldIndex--;
        }
        hasUnsavedChanges = true;
        renderCanvas();
    }

    // 5. Select Field Function
    function selectField(index) {
        selectedFieldIndex = index;
        const field = fields[index];

        // Highlight block
        const blocks = canvas.querySelectorAll('.sk-connect-canvas-block');
        blocks.forEach((block, idx) => {
            if (idx === index) block.classList.add('active');
            else block.classList.remove('active');
        });

        // Populate Inspector
        document.getElementById('field-index-ref').value = index;
        document.getElementById('field-label-input').value = field.label;
        document.getElementById('field-required-input').checked = !!field.required;

        const placeholderGroup = document.getElementById('placeholder-group');
        const placeholderInput = document.getElementById('field-placeholder-input');
        if (['text', 'email', 'textarea', 'tel', 'number', 'url', 'date'].includes(field.type)) {
            placeholderGroup.style.display = 'block';
            placeholderInput.value = field.placeholder || '';
        } else {
            placeholderGroup.style.display = 'none';
        }

        const optionsGroup = document.getElementById('options-group');
        if (field.type === 'select' || field.type === 'checkbox' || field.type === 'radio') {
            optionsGroup.style.display = 'block';
            renderInspectorOptions(field);
        } else {
            optionsGroup.style.display = 'none';
        }

        // Show Inspector Content
        inspectorInstructions.style.display = 'none';
        inspectorContent.style.display = 'block';
    }

    function closeInspector() {
        selectedFieldIndex = null;
        inspectorInstructions.style.display = 'block';
        inspectorContent.style.display = 'none';
    }

    // 6. Manage Option Lists inside Inspector
    function renderInspectorOptions(field) {
        const listContainer = document.getElementById('options-items-list');
        listContainer.innerHTML = '';

        if (!Array.isArray(field.options)) {
            field.options = [];
        }

        field.options.forEach((opt, idx) => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.gap = '6px';
            row.style.alignItems = 'center';

            row.innerHTML = `
                <input type="text" class="opt-value-input" data-index="${idx}" value="${escapeHtml(opt)}" style="flex-grow:1; margin-bottom:0;">
                <button type="button" class="opt-delete-btn" data-index="${idx}" style="background:none; border:none; cursor:pointer; color:#ef4444; font-size:16px;">&times;</button>
            `;

            listContainer.appendChild(row);
        });

        // Add Bindings for option inputs
        listContainer.querySelectorAll('.opt-value-input').forEach(input => {
            input.addEventListener('input', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                field.options[idx] = this.value;
                hasUnsavedChanges = true;
                debouncedRenderCanvas();
            });
        });

        listContainer.querySelectorAll('.opt-delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                field.options.splice(idx, 1);
                hasUnsavedChanges = true;
                renderInspectorOptions(field);
                renderCanvas();
            });
        });
    }

    // Add Options Button Handler
    const addOptionBtn = document.getElementById('sk-connect-add-option-btn');
    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function() {
            if (selectedFieldIndex === null) return;
            const field = fields[selectedFieldIndex];
            if (!Array.isArray(field.options)) field.options = [];

            field.options.push(`Choice ${field.options.length + 1}`);
            hasUnsavedChanges = true;
            renderInspectorOptions(field);
            renderCanvas();
        });
    }

    // 7. Inspector Real-time Sync handlers
    document.getElementById('field-label-input').addEventListener('input', function() {
        if (selectedFieldIndex === null) return;
        fields[selectedFieldIndex].label = this.value;
        hasUnsavedChanges = true;
        debouncedRenderCanvas();
    });

    document.getElementById('field-placeholder-input').addEventListener('input', function() {
        if (selectedFieldIndex === null) return;
        fields[selectedFieldIndex].placeholder = this.value;
        hasUnsavedChanges = true;
        debouncedRenderCanvas();
    });

    document.getElementById('field-required-input').addEventListener('change', function() {
        if (selectedFieldIndex === null) return;
        fields[selectedFieldIndex].required = this.checked;
        hasUnsavedChanges = true;
        renderCanvas();
    });

    // 8. Toolbox Field Additions
    const toolbox = document.querySelector('.toolbox-buttons');
    if (toolbox) {
        toolbox.addEventListener('click', function(e) {
            const btn = e.target.closest('.toolbox-btn');
            if (!btn) return;

            const type = btn.getAttribute('data-type');
            addField(type);
        });
    }

    function addField(type) {
        const uniqueId = 'field_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5);
        let newField = {
            id: uniqueId,
            type: type,
            label: capitalize(type) + ' Field',
            required: false
        };

        if (['text', 'email', 'textarea', 'tel', 'number', 'url', 'date'].includes(type)) {
            newField.placeholder = 'Enter value...';
        } else if (type === 'select' || type === 'checkbox' || type === 'radio') {
            newField.options = ['Option 1', 'Option 2'];
        }

        fields.push(newField);
        hasUnsavedChanges = true;
        renderCanvas();

        // Select the newly created field
        selectField(fields.length - 1);
    }

    // 9. Save Form Configuration Handler
    const saveBtn = document.getElementById('sk-connect-save-form-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const title = formTitleInput.value.trim();
            if (!title) {
                alert('Please enter a Form Name.');
                return;
            }

            if (fields.length === 0) {
                alert('Please add at least one field to your form.');
                return;
            }

            // Assemble settings
            settings.recipient_email = formEmailInput.value.trim() || sk_connect_admin_obj.admin_email;
            settings.success_message = formSuccessInput.value.trim() || 'Thank you! Your message has been sent successfully.';
            settings.submit_label = formSubmitLblInput.value.trim() || 'Send Message';
            settings.primary_color = formAccentColorInput.value;

            saveBtn.disabled = true;
            saveBtn.innerHTML = 'Saving...';

            const builderFormId = (typeof sk_connect_builder_data !== 'undefined') ? sk_connect_builder_data.form_id : 0;
            const payload = new URLSearchParams();
            payload.append('action', 'sk_connect_save_form_config');
            payload.append('id', builderFormId);
            payload.append('title', title);
            payload.append('fields', JSON.stringify(fields));
            payload.append('settings', JSON.stringify(settings));
            payload.append('security', sk_connect_admin_obj.nonce);

            fetch(sk_connect_admin_obj.ajax_url, {
                method: 'POST',
                body: payload,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    hasUnsavedChanges = false;
                    window.location.href = sk_connect_admin_obj.form_list_url;
                } else {
                    alert('Failed to save form config: ' + (res.data || 'Unknown error'));
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '💾 Save Form';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Connection error while saving form.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '💾 Save Form';
            });
        });
    }

    // Helper functions
    function capitalize(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Initial render
    renderCanvas();
});

})();
