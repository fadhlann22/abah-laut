document.addEventListener('DOMContentLoaded', function () {
    // Mobile nav toggle
    const navToggle = document.getElementById('navToggle');
    const mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('open');
        });
    }

    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    // Auto hide flash
    const flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(function () {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity 0.4s';
            setTimeout(function () { flash.remove(); }, 400);
        }, 4500);
    }

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm') || 'Yakin ingin menghapus?')) {
                e.preventDefault();
            }
        });
    });

    // Image preview for upload
    const imgInput = document.getElementById('image');
    const imgPreview = document.getElementById('imgPreview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

/* Table utilities for staff/admin screens: print current report or export the visible table. */
document.addEventListener('DOMContentLoaded', function () {
    const workspace = document.querySelector('.dashboard-layout');
    if (!workspace) return;

    const escapeCsv = function (value) {
        const clean = String(value ?? '').replace(/\s+/g, ' ').trim();
        return '"' + clean.replace(/"/g, '""') + '"';
    };
    const cellText = function (cell) {
        const clone = cell.cloneNode(true);
        clone.querySelectorAll('button, .btn').forEach(function (node) { node.remove(); });
        clone.querySelectorAll('input, select, textarea').forEach(function (field) {
            let value = '';
            if (field.tagName === 'SELECT') value = field.options[field.selectedIndex]?.text || '';
            else if (field.type === 'checkbox' || field.type === 'radio') value = field.checked ? (field.value || 'Ya') : '';
            else value = field.value || '';
            field.replaceWith(document.createTextNode(value));
        });
        return clone.innerText || clone.textContent || '';
    };

    document.querySelectorAll('.main-content table').forEach(function (table, index) {
        if (table.dataset.utilitiesReady === '1') return;
        table.dataset.utilitiesReady = '1';
        const card = table.closest('.content-card');
        const toolbar = document.createElement('div');
        toolbar.className = 'print-toolbar no-print';
        toolbar.setAttribute('aria-label', 'Alat laporan');

        const printButton = document.createElement('button');
        printButton.type = 'button';
        printButton.className = 'btn btn-outline btn-sm';
        printButton.textContent = '🖨 Cetak tabel';
        printButton.addEventListener('click', function () {
            const oldTitle = document.title;
            const heading = card?.querySelector('.content-card-header h2')?.textContent?.trim();
            if (heading) document.title = 'Abah Laut - ' + heading;
            window.print();
            window.setTimeout(function () { document.title = oldTitle; }, 800);
        });

        const exportButton = document.createElement('button');
        exportButton.type = 'button';
        exportButton.className = 'btn btn-secondary btn-sm';
        exportButton.textContent = '↓ Ekspor CSV';
        exportButton.addEventListener('click', function () {
            const rows = Array.from(table.querySelectorAll('tr')).map(function (tr) {
                return Array.from(tr.querySelectorAll('th,td')).map(cellText);
            }).filter(function (row) { return row.some(function (cell) { return cell.trim() !== ''; }); });
            if (!rows.length) return;
            const csv = '\uFEFF' + rows.map(function (row) { return row.map(escapeCsv).join(','); }).join('\r\n');
            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            const heading = (card?.querySelector('.content-card-header h2')?.textContent || 'data-abah-laut')
                .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'data-abah-laut';
            link.href = url;
            link.download = heading + '-' + new Date().toISOString().slice(0,10) + '.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        });
        toolbar.append(printButton, exportButton);
        const responsive = table.closest('.table-responsive');
        if (responsive) responsive.parentNode.insertBefore(toolbar, responsive);
        else table.parentNode.insertBefore(toolbar, table);
    });

    // Add a clear report timestamp to printouts, without changing the underlying data.
    const main = document.querySelector('.main-content');
    if (main && !main.querySelector('.print-meta')) {
        const meta = document.createElement('div');
        meta.className = 'print-meta';
        meta.textContent = 'Abah Laut Seafood • Dicetak: ' + new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'long', timeStyle: 'short'
        }).format(new Date());
        const header = main.querySelector('.page-header');
        if (header) header.insertAdjacentElement('afterend', meta);
    }
});
