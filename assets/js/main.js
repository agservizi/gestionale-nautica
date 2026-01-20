/**
 * NautikaPro - JavaScript Vanilla
 */

// Inizializzazione al caricamento della pagina
document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initSearch();
    initTooltips();
    initDateInputs();
    initClienteTipoPratica();
    initPatenteScadenzaCalc();
    initDynamicPraticaFields();
    initAgendaDragDrop();
    initConsentBanner();
    initGlobalInputFormatting();
});

// ============================================
// SIDEBAR COLLAPSE
// ============================================
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebarCollapseTop = document.getElementById('sidebarCollapseTop');
    const overlay = document.getElementById('sidebarOverlay');
    const mobileBreakpoint = 992;

    if (!sidebar) {
        return;
    }


    function isMobile() {
        return window.innerWidth < mobileBreakpoint;
    }

    function openMobile() {
        sidebar.classList.add('mobile-open');
        if (overlay) overlay.classList.add('show');
    }

    function closeMobile() {
        sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('show');
    }

    function toggleDesktop() {
        sidebar.classList.toggle('collapsed');
        const collapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        updateSidebarTooltips(collapsed);
    }

    function applyCollapsedFromStorage() {
        const saved = localStorage.getItem('sidebarCollapsed') === 'true';
        if (saved) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
        document.body.classList.toggle('sidebar-collapsed', saved);
        document.documentElement.classList.toggle('sidebar-collapsed', saved);
        updateSidebarTooltips(sidebar.classList.contains('collapsed'));
    }

    function toggleSidebar() {
        if (isMobile()) {
            if (sidebar.classList.contains('mobile-open')) {
                closeMobile();
            } else {
                openMobile();
            }
        } else {
            toggleDesktop();
        }
    }

    // Event delegation per i toggle (capture per evitare stopPropagation)
    document.addEventListener('click', (e) => {
        const btnTop = e.target.closest('#sidebarCollapseTop');
        const btnSide = e.target.closest('#sidebarCollapse');
        if (btnTop || btnSide) {
            console.log('[Sidebar] capture click', { target: e.target });
            e.preventDefault();
            toggleSidebar();
        }
    }, true);

    if (overlay) {
        overlay.addEventListener('click', closeMobile);
    }

    // Ripristina stato sidebar dal localStorage
    if (!isMobile()) {
        applyCollapsedFromStorage();
    }

    if (isMobile()) {
        applyCollapsedFromStorage();
        closeMobile();
        document.body.classList.remove('sidebar-collapsed');
    }

    window.addEventListener('resize', () => {
        if (isMobile()) {
            applyCollapsedFromStorage();
            closeMobile();
            document.body.classList.remove('sidebar-collapsed');
        } else {
            applyCollapsedFromStorage();
            closeMobile();
        }
    });

    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (isMobile()) {
                closeMobile();
            }
        });
    });
}

function updateSidebarTooltips(enable) {
    if (!window.bootstrap || !bootstrap.Tooltip) {
        return;
    }
    const links = document.querySelectorAll('.sidebar-link');
    links.forEach(link => {
        const title = link.getAttribute('data-tooltip') || '';
        const existing = bootstrap.Tooltip.getInstance(link);
        if (existing) {
            existing.dispose();
        }
        if (enable) {
            link.setAttribute('title', title);
            link.setAttribute('data-bs-toggle', 'tooltip');
            link.setAttribute('data-bs-placement', 'right');
            new bootstrap.Tooltip(link);
        } else {
            link.removeAttribute('data-bs-toggle');
            link.removeAttribute('data-bs-placement');
            link.removeAttribute('title');
        }
    });
}

// ============================================
// COOKIE CONSENT BANNER
// ============================================
function initConsentBanner() {
    const banner = document.getElementById('cookieBanner');
    if (!banner) return;

    const cookieName = banner.dataset.consentName;
    if (!cookieName) return;

    const existing = getCookieValue(cookieName);
    if (existing) {
        banner.classList.add('d-none');
        return;
    }

    banner.classList.remove('d-none');

    banner.querySelectorAll('[data-consent-action]').forEach(button => {
        button.addEventListener('click', async () => {
            const value = button.dataset.consentAction;
            try {
                const response = await fetch('/consent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ consent: value })
                });
                if (!response.ok) {
                    throw new Error('Consent failed');
                }
            } catch (e) {
                // ignore errors to avoid blocking UX
            }
            banner.classList.add('d-none');
        });
    });
}

function getCookieValue(name) {
    const safeName = name.replace(/([.$?*|{}\[\]\\\/\+^])/g, '\\$1');
    const match = document.cookie.match(new RegExp('(?:^|; )' + safeName + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

// ============================================
// RICERCA VELOCE CLIENTE
// ============================================
function initSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchCliente');
    const resultsPanel = document.getElementById('searchResults');

    if (searchForm && searchInput) {
        const apiBase = searchForm.getAttribute('data-api-base') || '/pages';
        let debounceTimer;

        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = '/pages/clienti.php?search=' + encodeURIComponent(query);
            }
        });

        const hideResults = () => {
            if (!resultsPanel) return;
            resultsPanel.classList.remove('show');
            resultsPanel.innerHTML = '';
        };

        const renderResults = (results) => {
            if (!resultsPanel) return;
            if (!results || results.length === 0) {
                resultsPanel.innerHTML = '<div class="list-group-item text-muted">Nessun risultato</div>';
                resultsPanel.classList.add('show');
                return;
            }
            resultsPanel.innerHTML = results.map(item => {
                const label = `${item.cognome} ${item.nome}`.trim();
                const meta = [item.email, item.codice_fiscale].filter(Boolean).join(' · ');
                return `
                    <a href="${apiBase}/cliente_dettaglio.php?id=${item.id}" class="list-group-item list-group-item-action">
                        <div class="fw-semibold">${label}</div>
                        ${meta ? `<div class=\"small text-muted\">${meta}</div>` : ''}
                    </a>
                `;
            }).join('');
            resultsPanel.classList.add('show');
        };

        const runSearch = async () => {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                hideResults();
                return;
            }
            try {
                const res = await fetch(`${apiBase}/api/clienti_search.php?q=${encodeURIComponent(query)}&limit=8`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                renderResults(data.results || []);
            } catch (e) {
                hideResults();
            }
        };

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runSearch, 250);
        });

        document.addEventListener('click', (e) => {
            if (!resultsPanel) return;
            if (e.target !== searchInput && !resultsPanel.contains(e.target)) {
                hideResults();
            }
        });
    }
}

// ============================================
// TOOLTIPS BOOTSTRAP
// ============================================
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ============================================
// DATE INPUTS - Imposta data odierna di default
// ============================================
function initDateInputs() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');

    dateInputs.forEach(input => {
        if (!input.value && input.hasAttribute('data-default-today')) {
            input.value = today;
        }
    });
}

// ============================================
// FORMAT MONEY
// ============================================
function formatMoney(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// ============================================
// FORMAT DATE
// ============================================
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT');
}

// ============================================
// ALERT AUTO-DISMISS
// ============================================
function showAlert(message, type = 'success', duration = 3000) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
    }, duration);
}

// ============================================
// LOADING OVERLAY
// ============================================
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'spinner-overlay';
    overlay.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// ============================================
// CONFIRM DELETE
// ============================================
function confirmDelete(message = 'Sei sicuro di voler eliminare questo elemento?') {
    return confirm(message);
}

// ============================================
// CALCOLO RESIDUO PRATICA
// ============================================
function calcolaResiduo() {
    const totaleInput = document.getElementById('totale_previsto');
    const pagatoInput = document.getElementById('totale_pagato');
    const residuoDisplay = document.getElementById('residuo_display');

    if (totaleInput && pagatoInput && residuoDisplay) {
        const totale = parseFloat(totaleInput.value) || 0;
        const pagato = parseFloat(pagatoInput.value) || 0;
        const residuo = totale - pagato;

        residuoDisplay.textContent = formatMoney(residuo);
        residuoDisplay.className = residuo > 0 ? 'text-danger' : 'text-success';
    }
}

// ============================================
// CAMPI DINAMICI TIPO PRATICA
// ============================================
function toggleCampiPratica() {
    const tipoPratica = document.getElementById('tipoPratica');
    if (!tipoPratica) return;

    const tipo = tipoPratica.value;

    // Nascondi tutti i campi opzionali
    const campiConseguimento = document.getElementById('campi_conseguimento');
    const campiRinnovo = document.getElementById('campi_rinnovo');
    const campiDuplicato = document.getElementById('campi_duplicato');
    const campiAltro = document.getElementById('campi_altro');

    // Nascondi tutto
    [campiConseguimento, campiRinnovo, campiDuplicato, campiAltro].forEach(el => {
        if (el) el.style.display = 'none';
    });

    // Mostra i campi appropriati
    if (tipo.includes('Patente') && !tipo.includes('Rinnovo')) {
        if (campiConseguimento) campiConseguimento.style.display = 'block';
    } else if (tipo === 'Rinnovo') {
        if (campiRinnovo) campiRinnovo.style.display = 'block';
    } else if (tipo === 'Duplicato') {
        if (campiDuplicato) campiDuplicato.style.display = 'block';
    } else if (tipo === 'Altro') {
        if (campiAltro) campiAltro.style.display = 'block';
    }
}

function initDynamicPraticaFields() {
    const tipoPratica = document.getElementById('tipoPratica');
    if (tipoPratica) {
        tipoPratica.addEventListener('change', toggleCampiPratica);
        toggleCampiPratica();
    }
}

// ============================================
// FORMATTazione INPUT TESTO (Sentence case)
// ============================================
function initGlobalInputFormatting() {
    const selector = 'input[type="text"], textarea';
    document.querySelectorAll(selector).forEach((el) => {
        if (el.hasAttribute('data-preserve-case')) {
            return;
        }
        el.addEventListener('blur', () => {
            const value = el.value;
            if (!value) return;
            const trimmed = value.trim();
            if (trimmed.length === 0) return;
            const lower = trimmed.toLocaleLowerCase('it-IT');
            const formatted = lower.charAt(0).toLocaleUpperCase('it-IT') + lower.slice(1);
            el.value = formatted;
        });
    });
}

function initClienteTipoPratica() {
    const selectCliente = document.getElementById('selectCliente');
    const tipoPratica = document.getElementById('tipoPratica');
    const tipoPraticaDisplay = document.getElementById('tipoPraticaDisplay');
    if (!selectCliente || !tipoPratica || !tipoPraticaDisplay) return;

    const updateTipo = () => {
        const selectedOption = selectCliente.options[selectCliente.selectedIndex];
        const tipo = selectedOption ? selectedOption.getAttribute('data-tipo-pratica') : '';
        tipoPratica.value = tipo || '';
        tipoPraticaDisplay.value = tipo || '';
        toggleCampiPratica();
    };

    selectCliente.addEventListener('change', updateTipo);
    updateTipo();
}

function initPatenteScadenzaCalc() {
    const clienteCf = document.getElementById('clienteCodiceFiscale');
    const clienteConseguimento = document.getElementById('clienteDataConseguimento');
    const clienteScadenza = document.getElementById('clienteDataScadenza');
    bindPatenteCalc(clienteCf, clienteConseguimento, clienteScadenza);

    const quickCf = document.getElementById('clienteQuickCodiceFiscale');
    const quickConseguimento = document.getElementById('clienteQuickDataConseguimento');
    const quickScadenza = document.getElementById('clienteQuickDataScadenza');
    bindPatenteCalc(quickCf, quickConseguimento, quickScadenza);
}

function bindPatenteCalc(cfInput, conseguimentoInput, scadenzaInput) {
    if (!cfInput || !conseguimentoInput || !scadenzaInput) return;

    const updateScadenza = () => {
        const cf = (cfInput.value || '').trim().toUpperCase();
        const conseguimento = conseguimentoInput.value;
        if (!cf || !conseguimento) return;

        const birthDate = getBirthDateFromCF(cf);
        if (!birthDate) return;

        const consegDate = new Date(conseguimento + 'T00:00:00');
        if (Number.isNaN(consegDate.getTime())) return;

        let age = consegDate.getFullYear() - birthDate.getFullYear();
        const m = consegDate.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && consegDate.getDate() < birthDate.getDate())) {
            age--;
        }

        const validYears = age >= 60 ? 5 : 10;
        const scadenza = new Date(consegDate);
        scadenza.setFullYear(scadenza.getFullYear() + validYears);
        scadenzaInput.value = scadenza.toISOString().slice(0, 10);
    };

    cfInput.addEventListener('input', updateScadenza);
    conseguimentoInput.addEventListener('change', updateScadenza);
}

function getBirthDateFromCF(cf) {
    if (!/^[A-Z0-9]{16}$/.test(cf)) return null;

    const year = parseInt(cf.substr(6, 2), 10);
    const monthChar = cf.substr(8, 1);
    const dayRaw = parseInt(cf.substr(9, 2), 10);

    const monthMap = {
        A: 1,
        B: 2,
        C: 3,
        D: 4,
        E: 5,
        H: 6,
        L: 7,
        M: 8,
        P: 9,
        R: 10,
        S: 11,
        T: 12
    };

    if (!monthMap[monthChar]) return null;
    const day = dayRaw > 40 ? dayRaw - 40 : dayRaw;
    if (day < 1 || day > 31) return null;

    const currentYear = new Date().getFullYear();
    const currentYY = currentYear % 100;
    const fullYear = year <= currentYY ? 2000 + year : 1900 + year;

    const date = new Date(fullYear, monthMap[monthChar] - 1, day);
    if (Number.isNaN(date.getTime())) return null;
    return date;
}

function initAgendaDragDrop() {
    const items = document.querySelectorAll('.agenda-item');
    const days = document.querySelectorAll('.agenda-day');
    if (!items.length || !days.length) return;

    items.forEach(item => {
        item.addEventListener('dragstart', e => {
            e.dataTransfer.setData('text/plain', item.dataset.id);
        });
    });

    days.forEach(day => {
        day.addEventListener('dragover', e => {
            e.preventDefault();
            day.classList.add('bg-light');
        });
        day.addEventListener('dragleave', () => {
            day.classList.remove('bg-light');
        });
        day.addEventListener('drop', async e => {
            e.preventDefault();
            day.classList.remove('bg-light');
            const id = e.dataTransfer.getData('text/plain');
            const date = day.dataset.date;
            if (!id || !date) return;

            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('date', date);
                formData.append('csrf_token', window.CSRF_TOKEN || '');

                const res = await fetch('/pages/agenda_move.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.ok) {
                    showAlert('Guida spostata al ' + date, 'success');
                } else {
                    showAlert(data.message || 'Errore spostamento', 'danger');
                }
            } catch (err) {
                showAlert('Errore di rete', 'danger');
            }
        });
    });
}

// ============================================
// EXPORT TABLE TO CSV
// ============================================
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            let text = col.textContent.trim();
            text = text.replace(/"/g, '""'); // Escape quotes
            return `"${text}"`;
        });
        csv.push(rowData.join(','));
    });

    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ============================================
// CALENDAR HELPER
// ============================================
function renderCalendar(year, month, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    let html = '<table class="table table-bordered calendar-table">';
    html += '<thead><tr>';
    ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'].forEach(day => {
        html += `<th>${day}</th>`;
    });
    html += '</tr></thead><tbody><tr>';

    // Giorni vuoti prima del primo giorno del mese
    for (let i = 0; i < startingDayOfWeek; i++) {
        html += '<td class="calendar-day-empty"></td>';
    }

    // Giorni del mese
    for (let day = 1; day <= daysInMonth; day++) {
        if ((startingDayOfWeek + day - 1) % 7 === 0 && day !== 1) {
            html += '</tr><tr>';
        }

        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        html += `<td class="calendar-day" data-date="${dateStr}">${day}</td>`;
    }

    html += '</tr></tbody></table>';
    container.innerHTML = html;

    // Event listeners per i giorni
    container.querySelectorAll('.calendar-day').forEach(dayEl => {
        dayEl.addEventListener('click', function () {
            const date = this.getAttribute('data-date');
            onDateClick(date);
        });
    });
}

function onDateClick(date) {
    // Implementare azione al click sulla data
    console.log('Data selezionata:', date);
}

// ============================================
// VALIDATION HELPERS
// ============================================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\+\-\(\)]+$/;
    return re.test(phone);
}

// ============================================
// DEBOUNCE UTILITY
// ============================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
