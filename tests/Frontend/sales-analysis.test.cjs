const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

class Element {
    constructor() { this.hidden = false; this.textContent = ''; this.children = []; this.style = {}; this.attributes = {}; this.listeners = {}; this.elements = {}; }
    querySelector(selector) { return this.elements[selector] || null; }
    setAttribute(name, value) { this.attributes[name] = value; }
    appendChild(child) { this.children.push(child); }
    replaceChildren() { this.children = []; }
    addEventListener(event, handler) { this.listeners[event] = handler; }
    dispatch(event) { this.listeners[event]?.(); }
}

function setup() {
    const keys = ['trend', 'channels', 'accounts-trend', 'retention'];
    const cards = {};
    for (const key of keys) {
        const card = new Element(); card.dataset = { chart: key };
        for (const selector of ['.chart-content', '.chart-state', '.chart-retry', '.chart-updated', '.chart-period', '.chart-frame', '.chart-note', 'thead', 'tbody', 'canvas']) {
            card.elements[selector] = new Element();
        }
        if (key === 'trend' || key === 'accounts-trend') { card.elements['.chart-channel'] = new Element(); card.elements['.chart-channel'].value = 'all'; }
        if (key === 'retention') { delete card.elements['canvas']; delete card.elements['.chart-frame']; }
        cards[key] = card;
    }
    const root = new Element(); root.dataset = { url: '/sales-analysis/data' };
    root.querySelectorAll = () => Object.values(cards);
    const elements = { salesAnalysis: root, analysisMonth: new Element(), monthError: new Element() };
    elements.analysisMonth.value = '2026-09'; elements.analysisMonth.checkValidity = () => true;
    for (const name of ['total', 'growth', 'accounts']) elements['metric-' + name] = new Element();
    const pending = []; const charts = []; const timers = new Map(); let timerId = 0;
    const context = {
        document: { getElementById: id => elements[id], createElement: () => new Element() },
        Intl, URLSearchParams, AbortController,
        setTimeout: callback => { timers.set(++timerId, callback); return timerId; },
        clearTimeout: id => timers.delete(id),
        fetch: (url, options) => new Promise((resolve, reject) => pending.push({ url, options, resolve, reject })),
        Chart: class { constructor(canvas, config) { this.config = config; charts.push(this); } destroy() { this.destroyed = true; } },
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/sales-analysis.js'), 'utf8'), context);
    return { cards, elements, pending, charts, timers };
}

const flush = () => new Promise(resolve => setImmediate(resolve));
const period = { current: '01 Sep – 09 Sep 2026', previous: '01 Aug – 09 Aug 2026' };
function payload(key) {
    const base = { period, updated_at: '09 Sep 2026 12:00' };
    if (key === 'accounts-trend') return { ...base, labels: [1, 2], current: [1, 2], previous: [1, 1] };
    if (key === 'retention') return { ...base, range_label: 'Januari 2026 - 09 Feb 2026', offsets: [0, 1], rows: [
        { label: 'Januari 2026', baseline: 4, cells: [
            { month: '2026-01', count: 4, rate: 100, is_future: false, is_partial: false },
            { month: '2026-02', count: 2, rate: 50, is_future: false, is_partial: true },
        ] },
        { label: 'Februari 2026', baseline: 0, cells: [
            { month: '2026-02', count: 0, rate: null, is_future: false, is_partial: true },
            { month: '2026-03', count: null, rate: null, is_future: true, is_partial: false },
        ] },
    ] };
    if (key === 'trend') return { ...base, labels: [1, 2], current: [100, 200], previous: [50, 80] };
    if (key === 'channels') return { ...base, rows: [{ name: 'AM', current: 200, previous: 80, share: 100, growth: 150, accounts: 2 }], summary: { total: 200, previous: 80, growth: 150, accounts: 2 } };
}
function resolve(request, data) { request.resolve({ ok: true, redirected: false, json: async () => data }); }

test('starts four independent requests and renders a finished chart while others load', async () => {
    const app = setup();
    assert.equal(app.pending.length, 4);
    assert.deepEqual(app.pending.map(request => request.url.split('/').pop().split('?')[0]), ['trend', 'channels', 'accounts-trend', 'retention']);
    resolve(app.pending[0], payload('trend')); await flush();
    assert.equal(app.cards.trend.attributes['aria-busy'], 'false');
    assert.equal(app.cards.trend.querySelector('.chart-content').hidden, false);
    assert.equal(app.cards.channels.attributes['aria-busy'], 'true');
    assert.equal(app.charts[0].config.type, 'line');
    for (let index = 1; index < 4; index++) resolve(app.pending[index], payload(['trend', 'channels', 'accounts-trend', 'retention'][index]));
    await flush();
    assert.equal(app.charts.length, 3);
    assert.equal(app.elements['metric-accounts'].textContent, '2');
    assert.equal(app.cards.channels.attributes['aria-busy'], 'false');
    assert.equal(app.timers.size, 0);
});

test('one failed chart has its own retry and does not hide successful charts', async () => {
    const app = setup();
    resolve(app.pending[0], payload('trend'));
    app.pending[1].reject(new Error('Server failed')); await flush();
    assert.equal(app.cards.channels.querySelector('.chart-retry').hidden, false);
    assert.equal(app.cards.trend.querySelector('.chart-content').hidden, false);
    app.cards.channels.querySelector('.chart-retry').dispatch('click');
    assert.equal(app.pending.length, 5);
    assert.match(app.pending[4].url, /\/channels\?/);
    resolve(app.pending[4], payload('channels')); await flush();
    assert.equal(app.cards.channels.querySelector('.chart-retry').hidden, true);
    assert.equal(app.cards.channels.querySelector('.chart-content').hidden, false);
});

test('month changes abort old requests and ignore stale responses', async () => {
    const app = setup();
    app.elements.analysisMonth.value = '2026-08';
    app.elements.analysisMonth.dispatch('change');
    assert.equal(app.pending.length, 8);
    assert.ok(app.pending.slice(0, 4).every(request => request.options.signal.aborted));
    assert.ok(app.pending.slice(4).every(request => request.url.includes('month=2026-08')));
    const newData = payload('trend'); newData.current = [1, 2];
    resolve(app.pending[4], newData); await flush();
    resolve(app.pending[0], payload('trend')); await flush();
    assert.equal(app.charts.length, 1);
    assert.deepEqual(app.charts[0].config.data.datasets[0].data, [1, 2]);
});

test('channel reloads only trend and empty states work', async () => {
    const app = setup();
    app.cards.trend.querySelector('.chart-channel').value = 'am'; app.cards.trend.querySelector('.chart-channel').dispatch('change');
    assert.equal(app.pending.length, 5);
    assert.match(app.pending[4].url, /channel=am/);
    resolve(app.pending[4], { ...payload('trend'), current: [0, 0], previous: [0, 0] }); await flush();
    assert.equal(app.cards.trend.querySelector('.chart-content').hidden, true);
    assert.match(app.cards.trend.querySelector('.chart-state').textContent, /Belum ada data/);
});

test('failed or redirected responses do not get rendered as report data', async () => {
    const app = setup();
    app.pending[0].resolve({ ok: true, redirected: true });
    app.pending[1].resolve({ ok: false, redirected: false });
    await flush();
    assert.equal(app.cards.trend.querySelector('.chart-retry').hidden, false);
    assert.equal(app.cards.channels.querySelector('.chart-retry').hidden, false);
    assert.equal(app.elements['metric-total'].textContent, '—');
    assert.equal(app.charts.length, 0);
});

test('account trend uses account units and reloads only its own channel selection', async () => {
    const app = setup();
    resolve(app.pending[2], payload('accounts-trend')); await flush();
    const config = app.charts[0].config;
    assert.equal(config.type, 'line');
    assert.equal(config.options.scales.y.ticks.precision, 0);
    const tooltip = config.options.plugins.tooltip.callbacks.label({ dataset: { label: 'September' }, parsed: { y: 2 } });
    assert.equal(tooltip, 'September: 2 akun');
    const select = app.cards['accounts-trend'].querySelector('.chart-channel');
    select.value = 'am'; select.dispatch('change');
    assert.equal(app.pending.length, 5);
    assert.match(app.pending[4].url, /\/accounts-trend\?.*channel=am/);
    assert.equal(app.pending[0].options.signal.aborted, false);
    assert.equal(app.pending[1].options.signal.aborted, false);
});

test('retention renders a visible cohort table without a chart, including future and partial cells', async () => {
    const app = setup();
    resolve(app.pending[3], payload('retention')); await flush();
    assert.equal(app.charts.length, 0);
    const card = app.cards.retention;
    assert.equal(card.querySelector('.chart-content').hidden, false);
    const headers = card.querySelector('thead').children[0].children.map(cell => cell.textContent);
    assert.deepEqual(headers, ['Bulan cohort', 'Akun dasar', 'N+0', 'N+1']);
    const rows = card.querySelector('tbody').children;
    assert.equal(rows[0].children[0].textContent, 'Januari 2026');
    assert.equal(rows[0].children[2].textContent, '100% (4 akun)');
    assert.equal(rows[0].children[3].textContent, '50% (2 akun) *');
    assert.equal(rows[1].children[2].textContent, '—');
    assert.match(rows[1].children[2].title, /tidak ada akun dasar/);
    assert.match(rows[1].children[3].title, /belum masuk periode/);
    assert.equal(app.cards.trend.attributes['aria-busy'], 'true');
    assert.equal(card.querySelector('.chart-channel'), null);
    assert.equal(app.pending.length, 4);
    assert.doesNotMatch(app.pending[3].url, /channel=/);
});
