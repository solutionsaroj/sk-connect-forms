(function() {
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart.js Dashboard Render (with error boundary)
    const chartCtx = document.getElementById('sk-connect-submissions-chart');
    if (chartCtx && typeof skConnectChartData !== 'undefined') {
        try {
            const labels = skConnectChartData.map(item => item.label);
            const data = skConnectChartData.map(item => item.count);

            const ctx = chartCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

            new Chart(chartCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Submissions',
                        data: data,
                        borderColor: '#6366f1',
                        borderWidth: 3,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#6366f1',
                        pointHoverRadius: 6,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { family: "'Outfit', sans-serif", size: 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#64748b',
                                font: { family: "'Outfit', sans-serif", size: 11 }
                            },
                            grid: { color: '#f1f5f9' }
                        }
                    }
                }
            });
        } catch (err) {
            console.error('SK Connect Forms: Chart initialization failed', err);
        }
    }

    // 2. Settings Color Picker Linkage
    const colorPicker = document.getElementById('sk_connect_forms_primary_color');
    const colorTextInput = document.getElementById('sk_connect_forms_primary_color_text');
    if (colorPicker && colorTextInput) {
        colorPicker.addEventListener('input', function() {
            colorTextInput.value = colorPicker.value;
        });
        colorTextInput.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(colorTextInput.value)) {
                colorPicker.value = colorTextInput.value;
            }
        });
    }

    // 3. Submissions Modal & Table Handlers
    const modal = document.getElementById('sk-connect-details-modal');
    const closeModalBtn = document.getElementById('sk-connect-close-modal');

    if (closeModalBtn && modal) {
        closeModalBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('active');
        });
    }

    // Keyboard accessibility: close modal with Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            modal.classList.remove('active');
        }
    });

    const table = document.querySelector('.sk-connect-table');
    if (table) {
        table.addEventListener('click', function (e) {
            const viewBtn = e.target.closest('.sk-connect-js-view-sub');
            const toggleBtn = e.target.closest('.sk-connect-js-toggle-read');
            const deleteBtn = e.target.closest('.sk-connect-js-delete');
            const deleteFormBtn = e.target.closest('.sk-connect-js-delete-form');

            if (viewBtn) {
                e.preventDefault();
                openSubDetailsModal(viewBtn);
            } else if (toggleBtn) {
                e.preventDefault();
                toggleReadStatus(toggleBtn);
            } else if (deleteBtn) {
                e.preventDefault();
                deleteSubmission(deleteBtn);
            } else if (deleteFormBtn) {
                e.preventDefault();
                deleteForm(deleteFormBtn);
            }
        });
    }

    // View Submission Payload Details inside Modal
    function openSubDetailsModal(btn) {
        const id = btn.getAttribute('data-id');
        const date = btn.getAttribute('data-date');
        const payloadStr = btn.getAttribute('data-payload');
        const isRead = btn.getAttribute('data-read') === '1';

        let payload = {};
        try {
            payload = JSON.parse(payloadStr);
        } catch (e) {
            console.error('Failed to parse payload', e);
        }

        document.getElementById('sk-connect-modal-date').textContent = date;

        // Build dynamic details table
        const listContainer = document.getElementById('sk-connect-modal-payload-list');
        listContainer.innerHTML = '';

        if (typeof skConnectFormFieldsConfig !== 'undefined' && Array.isArray(skConnectFormFieldsConfig)) {
            const detailTable = document.createElement('table');
            detailTable.className = 'sk-connect-sub-detail-table';

            skConnectFormFieldsConfig.forEach(field => {
                let val = payload[field.id] !== undefined ? payload[field.id] : '-';
                if (Array.isArray(val)) {
                    val = val.join(', ');
                }

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="label-col">${escapeHtml(field.label)}</td>
                    <td class="value-col">${escapeHtml(val)}</td>
                `;
                detailTable.appendChild(row);
            });

            listContainer.appendChild(detailTable);
        } else {
            listContainer.textContent = 'No field configurations loaded.';
        }

        // Dynamically update the Single PDF Export button link
        const pdfExportBtn = document.getElementById('sk-connect-modal-export-pdf');
        if (pdfExportBtn && typeof sk_connect_admin_obj !== 'undefined') {
            pdfExportBtn.href = sk_connect_admin_obj.ajax_url + '?action=sk_connect_export_single_pdf&sub_id=' + id + '&security=' + sk_connect_admin_obj.nonce;
        }

        modal.classList.add('active');

        // Automatically mark as read if unread
        if (!isRead) {
            const row = document.getElementById(`sk-connect-row-${id}`);
            const toggleBtn = row.querySelector('.sk-connect-js-toggle-read');
            markAJAXStatus(id, 1, row, toggleBtn, false); // silent
            btn.setAttribute('data-read', '1');
        }
    }

    // Toggle Read Status Action
    function toggleReadStatus(btn) {
        if (btn.disabled) return;
        btn.disabled = true;
        btn.style.opacity = '0.5';

        const id = btn.getAttribute('data-id');
        const row = document.getElementById(`sk-connect-row-${id}`);
        const isUnread = row.classList.contains('sk-connect-row-unread');
        const newStatus = isUnread ? 1 : 0;

        markAJAXStatus(id, newStatus, row, btn, true);
    }

    function markAJAXStatus(id, status, row, btn, showToastFlag) {
        const data = new URLSearchParams();
        data.append('action', 'sk_connect_toggle_submission_read');
        data.append('id', id);
        data.append('status', status);
        data.append('security', sk_connect_admin_obj.nonce);

        fetch(sk_connect_admin_obj.ajax_url, {
            method: 'POST',
            body: data,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(res => {
            btn.disabled = false;
            btn.style.opacity = '1';

            if (res.success) {
                const badge = row.querySelector('.sk-connect-badge');
                const viewBtn = row.querySelector('.sk-connect-js-view-sub');

                if (status === 1) {
                    row.classList.remove('sk-connect-row-unread');
                    badge.classList.remove('unread');
                    badge.classList.add('read');
                    badge.textContent = 'Read';
                    btn.title = 'Mark as Unread';
                    btn.innerHTML = '📬';
                    if (viewBtn) viewBtn.setAttribute('data-read', '1');
                } else {
                    row.classList.add('sk-connect-row-unread');
                    badge.classList.remove('read');
                    badge.classList.add('unread');
                    badge.textContent = 'Unread';
                    btn.title = 'Mark as Read';
                    btn.innerHTML = '📖';
                    if (viewBtn) viewBtn.setAttribute('data-read', '0');
                }

                // Update unread count indicator card if present
                const statUnread = document.getElementById('sk-connect-stat-unread');
                if (statUnread) {
                    statUnread.textContent = res.data.total_unread;
                }

                if (showToastFlag) {
                    showToast('Submission status updated successfully');
                }
            } else {
                showToast('Failed to update status', true);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.style.opacity = '1';
            console.error(err);
            showToast('Connection error', true);
        });
    }

    // Delete submission action
    function deleteSubmission(btn) {
        if (btn.disabled) return;
        const id = btn.getAttribute('data-id');
        if (!confirm('Are you sure you want to permanently delete this submission?')) {
            return;
        }

        btn.disabled = true;
        btn.style.opacity = '0.5';

        const data = new URLSearchParams();
        data.append('action', 'sk_connect_delete_submission');
        data.append('id', id);
        data.append('security', sk_connect_admin_obj.nonce);

        fetch(sk_connect_admin_obj.ajax_url, {
            method: 'POST',
            body: data,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById(`sk-connect-row-${id}`);
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    row.remove();
                    const tbody = table.querySelector('tbody');
                    if (tbody.querySelectorAll('tr').length === 0) {
                        location.reload();
                    }
                }, 300);

                const statTotal = document.getElementById('sk-connect-stat-total');
                const statUnread = document.getElementById('sk-connect-stat-unread');
                if (statTotal) statTotal.textContent = res.data.total_all_time;
                if (statUnread) statUnread.textContent = res.data.total_unread;

                showToast('Submission deleted successfully');
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                showToast('Failed to delete submission', true);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.style.opacity = '1';
            console.error(err);
            showToast('Connection error', true);
        });
    }

    // Delete whole custom Form template
    function deleteForm(btn) {
        if (btn.disabled) return;
        const id = btn.getAttribute('data-id');
        if (!confirm('Are you sure you want to delete this form and ALL of its associated submissions? This action is irreversible.')) {
            return;
        }

        btn.disabled = true;
        btn.style.opacity = '0.5';

        const data = new URLSearchParams();
        data.append('action', 'sk_connect_delete_form_template');
        data.append('id', id);
        data.append('security', sk_connect_admin_obj.nonce);

        fetch(sk_connect_admin_obj.ajax_url, {
            method: 'POST',
            body: data,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById(`sk-connect-form-row-${id}`);
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    row.remove();
                    const tbody = table.querySelector('tbody');
                    if (tbody.querySelectorAll('tr').length === 0) {
                        location.reload();
                    }
                }, 300);

                showToast('Form template deleted successfully');
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                showToast(res.data || 'Failed to delete form', true);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.style.opacity = '1';
            console.error(err);
            showToast('Connection error', true);
        });
    }

    // Helper Toast notice
    function showToast(message, isError) {
        isError = isError || false;
        const toast = document.getElementById('sk-connect-toast');
        if (!toast) return;

        const icon = toast.querySelector('.sk-connect-toast-icon');
        const text = toast.querySelector('.sk-connect-toast-message');

        text.textContent = message;
        icon.textContent = isError ? '❌' : '✓';
        toast.style.background = isError ? '#ef4444' : '#1e293b';

        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
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
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});

})();
