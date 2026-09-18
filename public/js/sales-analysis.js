(() => {
    'use strict';
    const root = document.getElementById('salesAnalysis');
    if (!root) return;
    const month = document.getElementById('analysisMonth');
    const cards = new Map([...root.querySelectorAll('[data-chart]')].map(card => [card.dataset.chart, {
        card, chart: null, request: null, version: 0, payload: null,
    }]));
    const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
    const money = value => value == null ? '—' : 'Rp ' + number.format(value);
    const accountCount = value => value == null ? '—' : number.format(value) + ' akun';
    const compact = value => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value);
    const percent = value => value == null ? '—' : number.format(value) + '%';
    const growth = (value, previous, current) => value == null
        ? (current > 0 && previous === 0 ? 'Belum ada pembanding' : '—')
        : (value > 0 ? '+' : '') + percent(value);
    const metricGroups = { channels: ['total', 'growth', 'accounts'] };

    function setMetrics(key, text) {
        (metricGroups[key] || []).forEach(name => { document.getElementById('metric-' + name).textContent = text; });
    }

    function updateMetrics(key, data) {
        const summary = data.summary;
        if (!summary) return;
        const values = { total: money(summary.total), growth: growth(summary.growth, summary.previous, summary.total), accounts: number.format(summary.accounts) };
        Object.entries(values).forEach(([name, value]) => { document.getElementById('metric-' + name).textContent = value; });
    }

    function detailTable(card, headers, rows) {
        const head = card.querySelector('thead');
        const body = card.querySelector('tbody');
        head.replaceChildren();
        body.replaceChildren();
        const headerRow = document.createElement('tr');
        headers.forEach(label => {
            const cell = document.createElement('th');
            cell.scope = 'col'; cell.textContent = label; headerRow.appendChild(cell);
        });
        head.appendChild(headerRow);
        rows.forEach(values => {
            const row = document.createElement('tr');
            values.forEach(value => {
                const cell = document.createElement('td');
                cell.textContent = value; row.appendChild(cell);
            });
            body.appendChild(row);
        });
    }

    function render(key) {
        const state = cards.get(key);
        const { card, payload: data } = state;
        if (!data) return;
        const content = card.querySelector('.chart-content');
        const status = card.querySelector('.chart-state');
        const rows = data.rows;
        const isTrend = key === 'trend' || key === 'accounts-trend';
        const isAccounts = key === 'accounts-trend';
        const isRetention = key === 'retention';
        const formatValue = isRetention ? percent : isAccounts ? accountCount : money;
        const isEmpty = isRetention ? !rows.length : isTrend
            ? !data.current.some(value => value !== 0) && !data.previous.some(value => value !== 0)
            : !rows.length || !rows.some(row => row.current !== 0 || row.previous !== 0);
        state.chart?.destroy(); state.chart = null;
        card.querySelector('.chart-updated').textContent = 'Data: ' + data.updated_at;
        status.hidden = !isEmpty;
        status.textContent = isEmpty ? (isRetention ? 'Belum ada akun pada bulan dasar untuk menghitung retention.' : 'Belum ada data untuk periode ini.') : '';
        content.hidden = isEmpty;
        if (isEmpty) return;
        card.querySelector('.chart-period').textContent = data.period.current + ' · Pembanding: ' + data.period.previous;

        if (isRetention) {
            card.querySelector('.chart-period').textContent = data.range_label;
            detailTable(card, ['Bulan cohort', 'Akun dasar', ...data.offsets.map(offset => 'N+' + offset)], rows.map(row => [
                row.label, accountCount(row.baseline), ...row.cells.map(cell => cell.is_future || cell.rate == null ? '—'
                    : percent(cell.rate) + ' (' + number.format(cell.count) + ' akun)' + (cell.is_partial ? ' *' : '')),
            ]));
            rows.forEach((row, rowIndex) => row.cells.forEach((cell, index) => {
                const element = card.querySelector('tbody').children[rowIndex].children[index + 2];
                element.title = cell.month + (cell.is_future ? ': belum masuk periode laporan' : row.baseline === 0 ? ': tidak ada akun dasar'
                    : ': ' + accountCount(cell.count) + ' dari ' + accountCount(row.baseline) + (cell.is_partial ? ' (sementara)' : ''));
                element.style.backgroundColor = cell.rate == null ? '#f1f5f9' : 'rgba(5, 150, 105, ' + (0.05 + cell.rate / 100 * 0.22) + ')';
            }));
            card.querySelector('.chart-note').textContent = 'Setiap baris berisi seluruh akun unik yang topup pada bulan dasar, bukan hanya akun baru. N+0 = 100% akun dasar; N+1 = bulan berikutnya, N+2 = dua bulan setelahnya, dan seterusnya. ' +
                'Persentase selalu dibagi jumlah akun dasar pada baris yang sama. Akun boleh kembali setelah melewati bulan tanpa topup. Email kosong dan Voucher Bonus dikecualikan. ' +
                '— berarti bulan belum masuk periode laporan atau akun dasar kosong. * Angka bulan berjalan masih sementara.';
            return;
        }

        let config;
        const options = {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: {} } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: compact } } },
        };
        card.querySelector('.chart-frame').style.height = isTrend || isRetention ? '330px' : Math.max(330, rows.length * 48) + 'px';

        if (isTrend) {
            config = { type: 'line', data: { labels: data.labels, datasets: [
                { label: data.period.current, data: data.current, borderColor: '#2563eb', backgroundColor: '#2563eb16', fill: true, tension: 0.15 },
                { label: data.period.previous, data: data.previous, borderColor: '#94a3b8', borderDash: [6, 4], tension: 0.15 },
            ] }, options };
            options.scales.x.title = { display: true, text: 'Tanggal dalam bulan' };
            options.plugins.tooltip.callbacks.label = ctx => ctx.dataset.label + ': ' + formatValue(ctx.parsed.y);
            if (isAccounts) {
                options.scales.y.title = { display: true, text: 'Jumlah akun unik' };
                options.scales.y.ticks = { precision: 0, callback: value => number.format(value) };
            }
            card.querySelector('.chart-note').textContent = isAccounts
                ? 'Jumlah email unik sejak awal bulan. Akun dihitung sekali pada topup pertamanya dalam periode tersebut, meskipun topup berulang. Email kosong dan Voucher Bonus tidak dihitung.'
                : 'Akumulasi settlement sejak awal bulan. Hari tanpa topup tetap ditampilkan; tanggal yang tidak ada pada bulan pembanding tidak diisi.';
            detailTable(card, ['Tanggal', 'Bulan terpilih', 'Bulan sebelumnya'], data.labels.map((day, i) => [day, formatValue(data.current[i]), formatValue(data.previous[i])]));

        } else {
            options.indexAxis = 'y';
            options.scales = { x: { beginAtZero: true, ticks: { callback: compact } }, y: { grid: { display: false } } };
            const datasets = [];
            datasets.push({ label: 'Periode terpilih', data: rows.map(row => row.current), backgroundColor: '#2563eb' },
                { label: 'Periode sebelumnya', data: rows.map(row => row.previous), backgroundColor: '#cbd5e1' });
            options.plugins.tooltip.callbacks.afterBody = items => {
                const row = rows[items[0].dataIndex];
                return ['Kontribusi: ' + percent(row.share), 'Pertumbuhan: ' + growth(row.growth, row.previous, row.current), 'Akun topup: ' + number.format(row.accounts)];
            };
            card.querySelector('.chart-note').textContent = 'Urutan berdasarkan settlement periode terpilih. Persentase kontribusi dan pertumbuhan tersedia pada tooltip dan angka detail.';
            detailTable(card, ['Channel', 'Topup', 'Sebelumnya', 'Kontribusi', 'Pertumbuhan', 'Akun'], rows.map(row => [row.name, money(row.current), money(row.previous), percent(row.share), growth(row.growth, row.previous, row.current), number.format(row.accounts)]));
            options.plugins.tooltip.callbacks.label = ctx => ctx.dataset.label + ': ' + money(ctx.parsed.x);
            config = { type: 'bar', data: { labels: rows.map(row => row.name), datasets }, options };
        }
        if (typeof Chart === 'undefined') throw new Error('Chart library unavailable');
        state.chart = new Chart(card.querySelector('canvas'), config);
    }

    async function load(key) {
        const state = cards.get(key);
        state.request?.abort();
        const request = new AbortController();
        const version = ++state.version;
        state.request = request; state.payload = null;
        const { card } = state;
        card.setAttribute('aria-busy', 'true');
        card.querySelector('.chart-content').hidden = true;
        card.querySelector('.chart-state').hidden = false;
        card.querySelector('.chart-state').textContent = 'Memuat grafik…';
        card.querySelector('.chart-retry').hidden = true;
        card.querySelector('.chart-updated').textContent = '';
        setMetrics(key, 'Memuat…');
        const timeout = setTimeout(() => request.abort(), 60000);
        try {
            const params = new URLSearchParams({ month: month.value });
            const channel = card.querySelector('.chart-channel');
            if (channel) params.set('channel', channel.value);
            const response = await fetch(root.dataset.url + '/' + key + '?' + params, {
                signal: request.signal, headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            if (!response.ok || response.redirected) throw new Error('Request failed');
            const data = await response.json();
            if (version !== state.version) return;
            state.payload = data;
            updateMetrics(key, data);
            render(key);
        } catch (error) {
            if (version !== state.version) return;
            state.payload = null;
            card.querySelector('.chart-content').hidden = true;
            card.querySelector('.chart-state').hidden = false;
            card.querySelector('.chart-state').textContent = 'Data belum berhasil dimuat. Silakan coba lagi.';
            card.querySelector('.chart-retry').hidden = false;
            setMetrics(key, '—');
        } finally {
            clearTimeout(timeout);
            if (version === state.version) card.setAttribute('aria-busy', 'false');
        }
    }

    function loadAll() {
        const valid = month.checkValidity() && /^\d{4}-\d{2}$/.test(month.value);
        document.getElementById('monthError').hidden = valid;
        if (!valid) return;
        // Each card owns its request and render cycle; no shared loading barrier.
        cards.forEach((_, key) => load(key));
    }
    month.addEventListener('change', loadAll);
    cards.forEach(({ card }, key) => {
        card.querySelector('.chart-retry').addEventListener('click', () => { if (month.checkValidity()) load(key); });
        card.querySelector('.chart-channel')?.addEventListener('change', () => { if (month.checkValidity()) load(key); });
    });
    loadAll();
})();
