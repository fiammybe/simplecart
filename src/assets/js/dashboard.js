/*
 * SimpleCart admin dashboard: filters the orders in the browser and draws their evolution.
 *
 * The page carries the data as JSON (#simplecart-dashboard-data) and the texts as JSON
 * (#simplecart-dashboard-strings). Everything is written to the page with textContent, never as HTML.
 */
(function (root) {
    'use strict';

    var PAID = ['paid', 'closed'];
    var PERIODS = ['day', 'week', 'month'];
    var MODES = ['period', 'cum'];

    // ---- filter state -------------------------------------------------------------------------

    function emptyFilters() {
        return { c: [], p: [], s: [], q: {} };
    }

    function itemFilterActive(f) {
        return f.c.length > 0 || f.p.length > 0;
    }

    function itemMatches(item, f) {
        return (f.c.length === 0 || f.c.indexOf(item.c) !== -1) && (f.p.length === 0 || f.p.indexOf(item.p) !== -1);
    }

    /** Or inside one filter, and between filters */
    function orderMatches(order, f) {
        if (f.s.length > 0 && f.s.indexOf(order.status) === -1) {
            return false;
        }

        var questionIds = Object.keys(f.q);

        for (var i = 0; i < questionIds.length; i++) {
            var wanted = f.q[questionIds[i]];
            var given = order.answers[questionIds[i]] || [];

            if (wanted.length > 0 && !given.some(function (value) { return wanted.indexOf(value) !== -1; })) {
                return false;
            }
        }

        if (itemFilterActive(f)) {
            return order.items.some(function (item) { return itemMatches(item, f); });
        }

        return true;
    }

    /** What an order adds to the figures: its whole total, or only the items the filters ask for */
    function contribution(order, f) {
        if (!itemFilterActive(f)) {
            return { amount: order.total, items: order.items };
        }

        var items = order.items.filter(function (item) { return itemMatches(item, f); });
        var amount = items.reduce(function (sum, item) { return sum + item.a; }, 0);

        return { amount: amount, items: items };
    }

    /** @return {{order: object, amount: number, items: Array}[]} */
    function select(data, f) {
        var rows = [];

        data.orders.forEach(function (order) {
            if (!orderMatches(order, f)) {
                return;
            }

            var part = contribution(order, f);
            rows.push({ order: order, amount: part.amount, items: part.items });
        });

        return rows;
    }

    function summarize(rows) {
        var sum = { orders: 0, amount: 0, paidOrders: 0, paidAmount: 0, pendingOrders: 0, pendingAmount: 0 };

        rows.forEach(function (row) {
            sum.orders++;
            sum.amount += row.amount;

            if (PAID.indexOf(row.order.status) !== -1) {
                sum.paidOrders++;
                sum.paidAmount += row.amount;
            } else if (row.order.status === 'pending') {
                sum.pendingOrders++;
                sum.pendingAmount += row.amount;
            }
        });

        return sum;
    }

    /** Quantity and revenue per product; lines that are not a product are grouped by their name */
    function productTable(rows, data) {
        var names = {};
        var table = {};

        data.products.forEach(function (product) { names[product.id] = product.name; });

        rows.forEach(function (row) {
            row.items.forEach(function (item) {
                var key = item.p > 0 ? 'p' + item.p : 'n' + item.n;

                if (!table[key]) {
                    table[key] = { name: item.p > 0 ? (names[item.p] || item.n) : item.n, quantity: 0, amount: 0 };
                }

                table[key].quantity += item.q;
                table[key].amount += item.a;
            });
        });

        return Object.keys(table).map(function (key) { return table[key]; }).sort(function (a, b) {
            return b.amount - a.amount || a.name.localeCompare(b.name);
        });
    }

    // ---- periods ------------------------------------------------------------------------------

    function parseDate(text) {
        var part = text.split('-');

        return Date.UTC(parseInt(part[0], 10), parseInt(part[1], 10) - 1, parseInt(part[2], 10));
    }

    function formatDate(time) {
        var d = new Date(time);

        return d.getUTCFullYear() + '-' + String(d.getUTCMonth() + 1).padStart(2, '0') + '-' + String(d.getUTCDate()).padStart(2, '0');
    }

    /** The first day of the period a date falls in: the day itself, the Monday of its week, or the 1st of its month */
    function periodStart(text, period) {
        var time = parseDate(text);

        if (period === 'week') {
            var weekday = (new Date(time).getUTCDay() + 6) % 7;

            return time - weekday * 86400000;
        }

        if (period === 'month') {
            var d = new Date(time);

            return Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), 1);
        }

        return time;
    }

    function nextPeriod(time, period) {
        if (period === 'week') {
            return time + 7 * 86400000;
        }

        if (period === 'month') {
            var d = new Date(time);

            return Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + 1, 1);
        }

        return time + 86400000;
    }

    function periodLabel(time, period) {
        return period === 'month' ? formatDate(time).slice(0, 7) : formatDate(time);
    }

    /**
     * Orders and value per period from the first to the last period with an order, empty periods as 0
     *
     * @return {{labels: string[], orders: number[], amount: number[]}}
     */
    function evolution(rows, period, cumulative) {
        var result = { labels: [], orders: [], amount: [] };

        if (rows.length === 0) {
            return result;
        }

        var buckets = {};
        var first = Infinity;
        var last = -Infinity;

        rows.forEach(function (row) {
            var start = periodStart(row.order.date, period);

            buckets[start] = buckets[start] || { orders: 0, amount: 0 };
            buckets[start].orders++;
            buckets[start].amount += row.amount;
            first = Math.min(first, start);
            last = Math.max(last, start);
        });

        var orders = 0;
        var amount = 0;

        for (var time = first; time <= last; time = nextPeriod(time, period)) {
            var bucket = buckets[time] || { orders: 0, amount: 0 };

            orders = cumulative ? orders + bucket.orders : bucket.orders;
            amount = cumulative ? amount + bucket.amount : bucket.amount;
            result.labels.push(periodLabel(time, period));
            result.orders.push(orders);
            result.amount.push(Math.round(amount * 100) / 100);
        }

        return result;
    }

    // ---- the state in the address -------------------------------------------------------------

    function serialize(state) {
        var params = new URLSearchParams();

        state.filters.c.forEach(function (id) { params.append('c', String(id)); });
        state.filters.p.forEach(function (id) { params.append('p', String(id)); });
        state.filters.s.forEach(function (status) { params.append('s', status); });
        Object.keys(state.filters.q).forEach(function (id) {
            state.filters.q[id].forEach(function (option) { params.append('q' + id, option); });
        });

        if (state.period !== 'day') {
            params.set('g', state.period);
        }

        if (state.mode !== 'period') {
            params.set('m', state.mode);
        }

        return params.toString();
    }

    /** Values that are not in the data are dropped, so an old or edited address never breaks the page */
    function parse(hash, data) {
        var params = new URLSearchParams(String(hash).replace(/^#/, ''));
        var state = { filters: emptyFilters(), period: 'day', mode: 'period' };
        var campaigns = data.campaigns.map(function (campaign) { return campaign.id; });
        var products = data.products.map(function (product) { return product.id; });
        var statuses = data.statuses.map(function (status) { return status.key; });

        params.getAll('c').forEach(function (value) {
            var id = parseInt(value, 10);

            if (campaigns.indexOf(id) !== -1 && state.filters.c.indexOf(id) === -1) { state.filters.c.push(id); }
        });
        params.getAll('p').forEach(function (value) {
            var id = parseInt(value, 10);

            if (products.indexOf(id) !== -1 && state.filters.p.indexOf(id) === -1) { state.filters.p.push(id); }
        });
        params.getAll('s').forEach(function (value) {
            if (statuses.indexOf(value) !== -1 && state.filters.s.indexOf(value) === -1) { state.filters.s.push(value); }
        });
        data.questions.forEach(function (question) {
            var chosen = params.getAll('q' + question.id).filter(function (option, index, all) {
                return question.options.indexOf(option) !== -1 && all.indexOf(option) === index;
            });

            if (chosen.length > 0) { state.filters.q[question.id] = chosen; }
        });

        if (PERIODS.indexOf(params.get('g')) !== -1) { state.period = params.get('g'); }
        if (MODES.indexOf(params.get('m')) !== -1) { state.mode = params.get('m'); }

        return state;
    }

    var logic = {
        emptyFilters: emptyFilters,
        orderMatches: orderMatches,
        select: select,
        summarize: summarize,
        productTable: productTable,
        evolution: evolution,
        serialize: serialize,
        parse: parse
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = logic;
    }

    if (typeof document === 'undefined') {
        return;
    }

    // ---- page ---------------------------------------------------------------------------------

    function readJson(id) {
        var node = document.getElementById(id);

        return node ? JSON.parse(node.textContent) : null;
    }

    function el(tag, attributes, children) {
        var node = document.createElement(tag);

        Object.keys(attributes || {}).forEach(function (name) {
            if (name === 'text') {
                node.textContent = attributes[name];
            } else if (name === 'class') {
                node.className = attributes[name];
            } else {
                node.setAttribute(name, attributes[name]);
            }
        });
        (children || []).forEach(function (child) { node.appendChild(child); });

        return node;
    }

    function start() {
        var host = document.getElementById('simplecart-dashboard');
        var data = readJson('simplecart-dashboard-data');
        var text = readJson('simplecart-dashboard-strings');

        if (!host || !data || !text) {
            return;
        }

        var number = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var whole = new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 });
        var state = parse(location.hash, data);
        var charts = {};
        var filterBox = el('div', { class: 'sc-dash-filters' });
        var summaryBox = el('div', { class: 'sc-dash-summary' });
        var emptyBox = el('p', { class: 'sc-dash-empty', text: text.noOrders, hidden: '' });
        var viewBox = el('div', { class: 'sc-dash-view' });
        var chartBox = el('div', { class: 'sc-dash-charts' });
        var productBox = el('div', { class: 'sc-dash-products' });

        host.textContent = '';
        [filterBox, summaryBox, emptyBox, viewBox, chartBox, productBox].forEach(function (node) { host.appendChild(node); });

        function writeAddress() {
            var hash = serialize(state);

            history.replaceState(null, '', location.pathname + location.search + (hash ? '#' + hash : ''));
        }

        function update() {
            writeAddress();
            render();
        }

        function toggle(list, value, on) {
            var index = list.indexOf(value);

            if (on && index === -1) { list.push(value); }
            if (!on && index !== -1) { list.splice(index, 1); }
        }

        function facet(title, options, selected, onChange) {
            var set = el('fieldset', { class: 'sc-dash-facet' }, [el('legend', { text: title })]);

            options.forEach(function (option) {
                var box = el('input', { type: 'checkbox' });

                box.checked = selected.indexOf(option.value) !== -1;
                box.addEventListener('change', function () { onChange(option.value, box.checked); update(); });
                set.appendChild(el('label', { class: 'sc-dash-option' }, [box, el('span', { text: ' ' + option.label })]));
            });

            return set;
        }

        function buildFilters() {
            filterBox.textContent = '';

            var row = el('div', { class: 'sc-dash-facets' });

            row.appendChild(facet(text.campaign, data.campaigns.map(function (c) { return { value: c.id, label: c.name }; }), state.filters.c, function (value, on) { toggle(state.filters.c, value, on); }));
            row.appendChild(facet(text.product, data.products.map(function (p) { return { value: p.id, label: p.name }; }), state.filters.p, function (value, on) { toggle(state.filters.p, value, on); }));
            row.appendChild(facet(text.status, data.statuses.map(function (s) { return { value: s.key, label: s.label }; }), state.filters.s, function (value, on) { toggle(state.filters.s, value, on); }));
            filterBox.appendChild(row);

            if (data.questions.length > 0) {
                var details = el('details', { class: 'sc-dash-questions' }, [el('summary', { text: text.answers })]);
                var inner = el('div', { class: 'sc-dash-facets' });

                if (Object.keys(state.filters.q).length > 0) {
                    details.setAttribute('open', '');
                }

                data.questions.forEach(function (question) {
                    var selected = state.filters.q[question.id] || [];

                    inner.appendChild(facet(question.label, question.options.map(function (o) { return { value: o, label: o }; }), selected, function (value, on) {
                        var list = (state.filters.q[question.id] = state.filters.q[question.id] || []);

                        toggle(list, value, on);

                        if (list.length === 0) { delete state.filters.q[question.id]; }
                    }));
                });
                details.appendChild(inner);
                filterBox.appendChild(details);
            }

            var clear = el('button', { type: 'button', class: 'sc-dash-clear', text: text.clear });

            clear.addEventListener('click', function () { state.filters = emptyFilters(); buildFilters(); update(); });
            filterBox.appendChild(clear);
        }

        function stat(label, value, extra) {
            return el('div', { class: 'sc-dash-stat' }, [
                el('dt', { text: label }),
                el('dd', { text: value }),
                el('dd', { class: 'sc-dash-stat-sub', text: extra })
            ]);
        }

        function renderSummary(sum) {
            var list = el('dl', { class: 'sc-dash-stats' });

            list.appendChild(stat(text.total, number.format(sum.amount), whole.format(sum.orders) + ' ' + text.orders));
            list.appendChild(stat(text.paid, number.format(sum.paidAmount), whole.format(sum.paidOrders) + ' ' + text.orders));
            list.appendChild(stat(text.pending, number.format(sum.pendingAmount), whole.format(sum.pendingOrders) + ' ' + text.orders));
            summaryBox.textContent = '';
            summaryBox.appendChild(list);
        }

        function radioGroup(legend, name, options, current, onChange) {
            var set = el('fieldset', { class: 'sc-dash-choice' }, [el('legend', { text: legend })]);

            options.forEach(function (option) {
                var input = el('input', { type: 'radio', name: name, value: option.value });

                input.checked = option.value === current;
                input.addEventListener('change', function () { onChange(option.value); update(); });
                set.appendChild(el('label', { class: 'sc-dash-option' }, [input, el('span', { text: ' ' + option.label })]));
            });

            return set;
        }

        function buildView() {
            viewBox.textContent = '';
            viewBox.appendChild(radioGroup(text.period, 'sc-period', [
                { value: 'day', label: text.day }, { value: 'week', label: text.week }, { value: 'month', label: text.month }
            ], state.period, function (value) { state.period = value; }));
            viewBox.appendChild(radioGroup(text.mode, 'sc-mode', [
                { value: 'period', label: text.perPeriod }, { value: 'cum', label: text.cumulative }
            ], state.mode, function (value) { state.mode = value; }));
            viewBox.appendChild(el('p', { class: 'sc-dash-note', text: text.timezone + ': ' + data.timezone }));
        }

        function drawChart(key, title, series, color) {
            var section = chartBox.querySelector('[data-chart="' + key + '"]');

            if (!section) {
                section = el('section', { class: 'sc-dash-chart', 'data-chart': key });
                section.appendChild(el('h3', { text: title }));
                section.appendChild(el('div', { class: 'sc-dash-canvas' }, [el('canvas', { role: 'img', 'aria-label': title })]));
                section.appendChild(el('details', { class: 'sc-dash-table' }));
                chartBox.appendChild(section);
            }

            var cumulative = state.mode === 'cum';
            var type = cumulative ? 'line' : 'bar';
            var canvas = section.querySelector('canvas');
            var dataset = { label: title, data: series.values, borderColor: color, backgroundColor: color, fill: false, tension: 0 };

            if (typeof root.Chart !== 'undefined') {
                if (charts[key] && charts[key].config.type !== type) {
                    charts[key].destroy();
                    charts[key] = null;
                }

                if (charts[key]) {
                    charts[key].data.labels = series.labels;
                    charts[key].data.datasets[0] = dataset;
                    charts[key].update();
                } else {
                    charts[key] = new root.Chart(canvas, {
                        type: type,
                        data: { labels: series.labels, datasets: [dataset] },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: series.integer ? 0 : undefined } } }
                        }
                    });
                }
            }

            return section;
        }

        function fillTable(section, evo) {
            var details = section.querySelector('details');
            var table = el('table', { class: 'table' });
            var head = el('tr', {}, [
                el('th', { scope: 'col', text: text.periodHeader }),
                el('th', { scope: 'col', text: text.orders }),
                el('th', { scope: 'col', text: text.value })
            ]);

            table.appendChild(el('thead', {}, [head]));

            var body = el('tbody');

            evo.labels.forEach(function (label, index) {
                body.appendChild(el('tr', {}, [
                    el('th', { scope: 'row', text: label }),
                    el('td', { text: whole.format(evo.orders[index]) }),
                    el('td', { text: number.format(evo.amount[index]) })
                ]));
            });
            table.appendChild(body);
            details.textContent = '';
            details.appendChild(el('summary', { text: text.showTable }));
            details.appendChild(table);
        }

        function renderCharts(rows) {
            var evo = evolution(rows, state.period, state.mode === 'cum');
            var counts = drawChart('orders', text.ordersChart, { labels: evo.labels, values: evo.orders, integer: true }, '#2563eb');
            var values = drawChart('amount', text.amountChart, { labels: evo.labels, values: evo.amount, integer: false }, '#0f766e');

            fillTable(counts, evo);
            fillTable(values, evo);
            chartBox.hidden = rows.length === 0;
        }

        function renderProducts(rows) {
            var products = productTable(rows, data);

            productBox.textContent = '';

            if (products.length === 0) {
                return;
            }

            var body = el('tbody');

            products.forEach(function (product) {
                body.appendChild(el('tr', {}, [
                    el('td', { text: product.name }),
                    el('td', { class: 'sc-dash-num', text: whole.format(product.quantity) }),
                    el('td', { class: 'sc-dash-num', text: number.format(product.amount) })
                ]));
            });
            productBox.appendChild(el('h3', { text: text.productsTitle }));
            productBox.appendChild(el('table', { class: 'table' }, [
                el('thead', {}, [el('tr', {}, [
                    el('th', { scope: 'col', text: text.product }),
                    el('th', { scope: 'col', class: 'sc-dash-num', text: text.quantity }),
                    el('th', { scope: 'col', class: 'sc-dash-num', text: text.value })
                ])]),
                body
            ]));
        }

        function render() {
            var rows = select(data, state.filters);

            renderSummary(summarize(rows));
            emptyBox.hidden = rows.length > 0;
            renderCharts(rows);
            renderProducts(rows);
        }

        window.addEventListener('hashchange', function () {
            state = parse(location.hash, data);
            buildFilters();
            buildView();
            render();
        });

        buildFilters();
        buildView();
        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}(typeof window !== 'undefined' ? window : globalThis));
