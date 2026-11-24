

import Alpine from 'alpinejs';
import Toastify from 'toastify-js';
import 'font-awesome/css/font-awesome.min.css';
import 'toastify-js/src/toastify.css';

window.Alpine = Alpine;
window.CityCareToast = (message, type = 'success') => Toastify({
    text: String(message),
    duration: 5000,
    close: true,
    gravity: 'top',
    position: 'right',
    stopOnFocus: true,
    className: `citycare-toast citycare-toast-${type}`,
}).showToast();

// Replace browser-native confirmation prompts with the shared, accessible modal.
const confirmDialog = document.getElementById('app-confirm-dialog');
if (confirmDialog) {
    const confirmMessage = document.getElementById('app-confirm-message');
    const confirmAccept = document.getElementById('app-confirm-accept');
    const confirmCancel = document.getElementById('app-confirm-cancel');
    let pendingForm = null;
    let pendingSubmitter = null;

    document.addEventListener('submit', event => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form?.hasAttribute('data-confirm') || form.dataset.confirmApproved === 'true') return;

        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter;
        confirmMessage.textContent = form.dataset.confirm || 'Are you sure you want to continue?';
        confirmAccept.textContent = form.dataset.confirmOk || 'OK';
        confirmDialog.showModal();
        confirmCancel.focus();
    });

    confirmAccept.addEventListener('click', () => {
        const form = pendingForm;
        const submitter = pendingSubmitter;
        pendingForm = null;
        pendingSubmitter = null;
        confirmDialog.close();

        if (!form) return;
        form.dataset.confirmApproved = 'true';
        if (submitter && submitter.form === form) form.requestSubmit(submitter);
        else form.requestSubmit();
        delete form.dataset.confirmApproved;
    });

    const discardPendingSubmission = () => {
        pendingForm = null;
        pendingSubmitter = null;
    };
    confirmCancel.addEventListener('click', () => confirmDialog.close());
    confirmDialog.addEventListener('cancel', discardPendingSubmission);
    confirmDialog.addEventListener('close', discardPendingSubmission);
}

// Give immediate, action-specific feedback during full-page navigation and form posts.
const navigationProgress = document.getElementById('app-navigation-progress');
const showNavigationProgress = () => {
    if (navigationProgress) navigationProgress.hidden = false;
};
const actionLabel = (text, fallback = 'Working…', isCreate = false) => {
    const action = String(text || '').trim().toLowerCase();
    if (/\b(clear|clearing)\b/.test(action) && /patient/.test(action)) return 'Clearing patient…';
    if (/\b(dispense|dispensing)\b/.test(action)) return 'Dispensing medicine…';
    if (/\b(pay|payment|checkout)\b/.test(action)) return 'Processing payment…';
    if (/\b(book|booking|reserve)\b/.test(action)) return 'Booking appointment…';
    if (isCreate && /doctor/.test(action)) return 'Adding doctor…';
    if (isCreate && /patient/.test(action)) return 'Adding patient…';
    if (isCreate && /department/.test(action)) return 'Adding department…';
    if (isCreate && /medicine|drug|stock/.test(action)) return 'Adding medicine…';
    if (isCreate && /test|laboratory/.test(action)) return 'Adding laboratory test…';
    if (isCreate && /user|account/.test(action)) return 'Creating account…';
    if (/\b(add|create|new)\b/.test(action) && /doctor/.test(action)) return 'Adding doctor…';
    if (/\b(save|update|edit)\b/.test(action) && /doctor/.test(action)) return 'Saving doctor changes…';
    if (/\b(add|create|new)\b/.test(action) && /patient/.test(action)) return 'Adding patient…';
    if (/\b(save|update|edit)\b/.test(action) && /patient/.test(action)) return 'Saving patient record…';
    if (/\b(add|create|new)\b/.test(action) && /department/.test(action)) return 'Adding department…';
    if (/\b(save|update|edit)\b/.test(action) && /department/.test(action)) return 'Saving department…';
    if (/\b(add|create|new)\b/.test(action) && /medicine|drug/.test(action)) return 'Adding medicine…';
    if (/\b(save|update|edit)\b/.test(action) && /medicine|drug|stock/.test(action)) return 'Updating stock…';
    if (/\b(add|create|new)\b/.test(action) && /test/.test(action)) return 'Adding laboratory test…';
    if (/\b(save|update|edit)\b/.test(action) && /test|result/.test(action)) return 'Saving laboratory results…';
    if (/\b(add|create|new)\b/.test(action) && /user|account/.test(action)) return 'Creating account…';
    if (/\b(add|create|new)\b/.test(action)) return 'Saving new record…';
    if (/\b(schedule|reschedule)\b/.test(action)) return 'Saving schedule…';
    if (/\b(clear|release)\b/.test(action)) return 'Clearing patient…';
    if (/\b(delete|remove|archive|deactivate)\b/.test(action)) return 'Updating record…';
    if (/\b(save|update|edit|submit|confirm)\b/.test(action)) return 'Saving changes…';
    if (/\b(search|filter|apply)\b/.test(action)) return 'Searching…';
    if (/\b(export|download)\b/.test(action)) return 'Preparing download…';
    return fallback;
};
const showPendingLabel = (element, label) => {
    if (!element || element.dataset.pendingShown === 'true') return;
    if (element instanceof HTMLInputElement) {
        element.dataset.originalValue = element.value;
        element.value = label;
    } else {
        element.dataset.originalMarkup = element.innerHTML;
        const spinner = document.createElement('span');
        spinner.className = 'app-button-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.textContent = label;
        element.replaceChildren(spinner, text);
    }
    element.dataset.pendingShown = 'true';
    element.setAttribute('aria-busy', 'true');
    element.setAttribute('aria-disabled', 'true');
    element.classList.add('pointer-events-none', 'opacity-80');
};

// Keep ordinary list filters responsive while preserving lookup widgets that use their own APIs.
document.querySelectorAll('form[method="GET"], form[method="get"]').forEach(form => {
    const search = form.querySelector('input[type="search"]');
    if (!search || form.hasAttribute('data-manual-search')) return;

    let timer;
    let lastSubmittedValue = search.value.trim();
    const hint = document.createElement('span');
    hint.className = 'sr-only';
    hint.setAttribute('role', 'status');
    hint.setAttribute('aria-live', 'polite');
    search.insertAdjacentElement('afterend', hint);

    form.addEventListener('submit', event => {
        window.clearTimeout(timer);
        const value = search.value.trim();
        if (value.length === 1) {
            event.preventDefault();
            hint.textContent = 'Type at least 2 characters to filter.';
            return;
        }
        lastSubmittedValue = value;
    });

    search.addEventListener('input', event => {
        if (event.isComposing) return;
        const value = search.value.trim();
        window.clearTimeout(timer);

        if (value.length === 0 && lastSubmittedValue.length >= 2) {
            hint.textContent = 'Clearing search results.';
            timer = window.setTimeout(() => form.requestSubmit(), 250);
        } else if (value.length >= 2) {
            hint.textContent = 'Updating results.';
            timer = window.setTimeout(() => form.requestSubmit(), 350);
        } else if (value.length === 1) {
            hint.textContent = 'Type at least 2 characters to filter.';
        } else {
            hint.textContent = '';
        }
    });
});

// Offer a compact in-page section index on long pages with multiple top-level sections.
document.querySelectorAll('main#main-content, main#policy-content').forEach(main => {
    if (main.hasAttribute('data-no-section-nav')) return;

    const sections = [...main.querySelectorAll('section')].filter(section => {
        const parentSection = section.parentElement?.closest('section');
        return !parentSection && !section.closest('[hidden], [aria-hidden="true"]');
    });
    const items = sections.map((section, index) => {
        const heading = section.querySelector('h2, h3, h4');
        const label = (section.getAttribute('aria-label') || heading?.textContent || '').trim().replace(/\s+/g, ' ');
        return label ? { section, label, index } : null;
    }).filter(Boolean);

    if (items.length < 2) return;

    const nav = document.createElement('nav');
    nav.className = 'sticky top-[4.5rem] z-20 -mx-4 mb-4 border-b border-slate-200 bg-white/95 px-4 py-2 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8';
    nav.setAttribute('aria-label', 'On this page');
    const inner = document.createElement('div');
    inner.className = 'flex items-center gap-2 overflow-x-auto';
    const label = document.createElement('span');
    label.className = 'shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500';
    label.textContent = 'Jump to';
    inner.append(label);

    items.forEach(({ section, label: text, index }) => {
        if (!section.id) section.id = `page-section-${index + 1}`;
        section.style.scrollMarginTop = '8rem';
        const link = document.createElement('a');
        link.href = `#${section.id}`;
        link.className = 'shrink-0 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-700';
        link.textContent = text;
        inner.append(link);
    });
    nav.append(inner);
    main.insertBefore(nav, main.firstChild);
});

document.addEventListener('submit', event => {
    const form = event.target instanceof HTMLFormElement ? event.target : null;
    if (!form || event.defaultPrevented || form.hasAttribute('data-no-pending') || form.target) return;
    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';
    showNavigationProgress();
    const submitter = event.submitter instanceof HTMLElement ? event.submitter : form.querySelector('button[type="submit"], input[type="submit"]');
    if (submitter) {
        const source = submitter.dataset.pendingLabel || form.dataset.pendingLabel || submitter.innerText || submitter.value;
        const methodOverride = form.querySelector('input[name="_method"]')?.value;
        const isCreate = form.method.toLowerCase() === 'post' && !methodOverride;
        showPendingLabel(submitter, actionLabel(source, 'Working…', isCreate));
    }
});

document.addEventListener('click', event => {
    const link = event.target instanceof Element ? event.target.closest('a[data-page-loading], a[role="button"], a[class*="bg-"]') : null;
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target || link.hasAttribute('download') || link.dataset.noPending !== undefined) return;

    const destination = new URL(link.href, window.location.href);
    if (destination.origin !== window.location.origin || (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash)) return;
    showNavigationProgress();
    showPendingLabel(link, link.dataset.pendingLabel || 'Opening…');
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-pending-shown="true"]').forEach(element => {
        if (element.dataset.originalMarkup !== undefined) element.innerHTML = element.dataset.originalMarkup;
        if (element.dataset.originalValue !== undefined) element.value = element.dataset.originalValue;
        delete element.dataset.originalMarkup;
        delete element.dataset.originalValue;
        delete element.dataset.pendingShown;
        element.removeAttribute('aria-busy');
        element.removeAttribute('aria-disabled');
        element.classList.remove('pointer-events-none', 'opacity-80');
    });
    if (navigationProgress) navigationProgress.hidden = true;
});

Alpine.start();

if (window.CityCareDashboard) {
    import('chart.js/auto').then(({ default: Chart }) => {
        Chart.defaults.font.family = 'Figtree, sans-serif';
        Chart.defaults.color = '#64748b';

        const dashboard = window.CityCareDashboard;
        const palette = ['#0f766e', '#0891b2', '#10b981', '#f59e0b', '#e11d48'];
        const axisOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' }, border: { display: false } },
            },
        };

        const appointmentTrend = document.getElementById('appointment-trend-chart');
        if (appointmentTrend) {
            new Chart(appointmentTrend, {
                type: 'line',
                data: {
                    labels: dashboard.appointmentTrend.labels,
                    datasets: [{
                        label: 'Appointments',
                        data: dashboard.appointmentTrend.values,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.12)',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#0f766e',
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: axisOptions,
            });
        }

        const appointmentStatuses = document.getElementById('appointment-status-chart');
        if (appointmentStatuses) {
            new Chart(appointmentStatuses, {
                type: 'doughnut',
                data: {
                    labels: dashboard.appointmentStatuses.labels,
                    datasets: [{
                        data: dashboard.appointmentStatuses.values,
                        backgroundColor: palette,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                    },
                },
            });
        }

        const monthlyCollections = document.getElementById('monthly-collections-chart');
        if (monthlyCollections && dashboard.monthlyCollections) {
            new Chart(monthlyCollections, {
                type: 'bar',
                data: {
                    labels: dashboard.monthlyCollections.labels,
                    datasets: [{
                        label: 'Received (UGX)',
                        data: dashboard.monthlyCollections.values,
                        backgroundColor: '#14b8a6',
                        borderRadius: 7,
                        maxBarThickness: 34,
                    }],
                },
                options: {
                    ...axisOptions,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `UGX ${new Intl.NumberFormat('en-UG').format(context.parsed.y)}`,
                            },
                        },
                    },
                    scales: {
                        ...axisOptions.scales,
                        y: {
                            ...axisOptions.scales.y,
                            ticks: { callback: value => new Intl.NumberFormat('en-UG', { notation: 'compact' }).format(value) },
                        },
                    },
                },
            });
        }
    }).catch(() => window.CityCareToast('Dashboard charts could not be loaded.', 'error'));
}
