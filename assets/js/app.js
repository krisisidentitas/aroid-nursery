/* ==========================================================
   Aroid Plant Nursery - Dashboard
   ========================================================== */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toastEl = document.getElementById('appToast');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Notifikasi singkat ---------- */
    function showToast(message) {
        document.getElementById('toastMsg').textContent = message;
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2800 }).show();
    }

    /* ---------- Sidebar (mobile) ---------- */
    function toggleSidebar(open) {
        sidebar.classList.toggle('open', open);
        overlay.classList.toggle('show', open);
    }
    document.getElementById('btnMenu').addEventListener('click', () => toggleSidebar(true));
    overlay.addEventListener('click', () => toggleSidebar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggleSidebar(false); });

    /* ---------- Navigasi visual: pindahkan penanda menu aktif ---------- */
    const links = document.querySelectorAll('.nav-item-link');
    links.forEach((link) => {
        link.addEventListener('click', (e) => {

            const url = link.getAttribute('href');

            if (url === '#') {
                e.preventDefault();

                const nama = link.querySelector('span').textContent;

                showToast(
                    'Halaman "' + nama + '" akan dibuat pada tahap berikutnya.'
                );

                return;
            }

            toggleSidebar(false);
        });
    });

    /* ---------- Tombol keluar (login belum ada) ---------- */
    document.getElementById('btnLogout').addEventListener('click', () => {
        showToast('Fitur login dan keluar akan dibuat pada tahap berikutnya.');
    });

    /* ---------- Animasi angka statistik (sekali saat halaman dibuka) ---------- */
    if (!reduceMotion) {
        document.querySelectorAll('[data-count]').forEach((el) => {
            const target = Number(el.dataset.count);
            const start = performance.now();
            const duration = 800;
            (function tick(now) {
                const p = Math.min((now - start) / duration, 1);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(tick);
            })(start);
        });
    }

    /* ---------- Grafik batang SVG sederhana ---------- */
    const chartEl = document.getElementById('salesChart');

    function drawChart() {
        const data = JSON.parse(chartEl.dataset.chart);
        const W = 600, H = 190, padTop = 24, padBottom = 28;
        const max = Math.max(...data.map((d) => d.nilai));
        const peak = data.findIndex((d) => d.nilai === max);
        const slot = W / data.length;
        const barW = slot * 0.5;
        const plotH = H - padTop - padBottom;
        const NS = 'http://www.w3.org/2000/svg';

        const svg = document.createElementNS(NS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Grafik penjualan 7 hari terakhir');

        function add(tag, attrs, text) {
            const node = document.createElementNS(NS, tag);
            for (const k in attrs) node.setAttribute(k, attrs[k]);
            if (text !== undefined) node.textContent = text;
            svg.appendChild(node);
            return node;
        }

        // Garis bantu
        [0, 0.5, 1].forEach((f) => {
            const y = padTop + plotH * f;
            add('line', { x1: 0, x2: W, y1: y, y2: y, class: 'grid' });
        });

        data.forEach((d, i) => {
            const h = (d.nilai / max) * plotH;
            const x = i * slot + (slot - barW) / 2;
            const y = padTop + plotH - h;
            const bar = add('rect', {
                x: x, y: y, width: barW, height: h, rx: 8,
                class: 'bar' + (i === peak ? ' peak' : '')
            });
            bar.appendChild(Object.assign(document.createElementNS(NS, 'title'),
                { textContent: d.label + ': Rp ' + d.nilai.toFixed(1).replace('.', ',') + ' juta' }));
            add('text', { x: x + barW / 2, y: y - 7, 'text-anchor': 'middle', class: 'val' },
                d.nilai.toFixed(1).replace('.', ','));
            add('text', { x: x + barW / 2, y: H - 6, 'text-anchor': 'middle', class: 'axis' }, d.label);
        });

        chartEl.replaceChildren(svg);
    }
    drawChart();
});