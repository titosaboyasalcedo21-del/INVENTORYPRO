

import 'bootstrap';
import Chart from 'chart.js/auto';

const chartData = document.getElementById('sales-chart-data');
const chartCanvas = document.getElementById('sales-chart');
const reportData = document.getElementById('report-chart-data');
const reportCanvas = document.getElementById('report-chart');

const currency = document.documentElement.dataset.currency || 'PEN';
const formatCurrency = amount => new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(amount);
const lineForm = document.querySelector('[data-line-form]');

if (lineForm instanceof HTMLFormElement) {
    const lines = lineForm.querySelector('[data-line-items]');
    const template = lineForm.querySelector('[data-line-template]');
    const subtotalOutput = lineForm.querySelector('[data-subtotal]');
    const discountOutput = lineForm.querySelector('[data-discount]');
    const taxOutput = lineForm.querySelector('[data-tax]');
    const totalOutput = lineForm.querySelector('[data-grand-total]');
    const checkoutConfig = document.getElementById('checkout-config');
    const taxRate = checkoutConfig ? Number(JSON.parse(checkoutConfig.textContent || '{}').taxRate || 0) : 0;

    const renumber = () => {
        [...lines.querySelectorAll('[data-line-row]')].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
        });
    };

    const recalculate = () => {
        let subtotal = 0;
        lines.querySelectorAll('[data-line-row]').forEach(row => {
            const select = row.querySelector('[data-product-select]');
            const option = select?.selectedOptions[0];
            const quantity = Number(row.querySelector('[data-quantity]')?.value || 0);
            const price = Number(option?.dataset.price || 0);
            const stockOutput = row.querySelector('[data-stock-display]');
            const priceOutput = row.querySelector('[data-price-display]');
            const amountOutput = row.querySelector('[data-line-total]');

            if (stockOutput) stockOutput.textContent = option?.dataset.stock || '—';
            if (priceOutput) priceOutput.textContent = option?.value ? formatCurrency(price) : '—';
            if (amountOutput) amountOutput.textContent = option?.value ? formatCurrency(price * quantity) : '—';
            if (option?.value && quantity > 0) subtotal += price * quantity;
        });

        if (lineForm.hasAttribute('data-sale-form')) {
            const discount = Math.min(Number(lineForm.querySelector('[name="discount_amount"]')?.value || 0), subtotal);
            const tax = Math.round(Math.max(subtotal - discount, 0) * taxRate) / 100;
            if (subtotalOutput) subtotalOutput.textContent = formatCurrency(subtotal);
            if (discountOutput) discountOutput.textContent = formatCurrency(discount);
            if (taxOutput) taxOutput.textContent = formatCurrency(tax);
            if (totalOutput) totalOutput.textContent = formatCurrency(subtotal - discount + tax);
        }
    };

    lineForm.addEventListener('click', event => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        if (target.closest('[data-add-line]') && template instanceof HTMLTemplateElement) {
            lines.append(template.content.cloneNode(true));
            renumber();
            lines.querySelector('[data-line-row]:last-child select')?.focus();
            recalculate();
        }

        if (target.closest('[data-remove-line]')) {
            if (lines.querySelectorAll('[data-line-row]').length > 1) {
                target.closest('[data-line-row]')?.remove();
                renumber();
                recalculate();
            }
        }
    });

    lineForm.addEventListener('change', event => {
        const target = event.target;
        if (target instanceof HTMLSelectElement && target.matches('[data-product-select], [data-purchase-product-select]')) {
            const costInput = target.closest('[data-line-row]')?.querySelector('[name$="[unit_cost]"]');
            const cost = target.selectedOptions[0]?.dataset.cost;
            if (costInput instanceof HTMLInputElement && !costInput.dataset.userEdited && cost !== undefined) {
                costInput.value = cost;
            }
        }
        recalculate();
    });
    lineForm.addEventListener('input', event => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.name.endsWith('[unit_cost]')) {
            target.dataset.userEdited = 'true';
        }
        recalculate();
    });
    recalculate();
}

if (chartData instanceof HTMLScriptElement && chartCanvas instanceof HTMLCanvasElement) {
    const { labels, amounts } = JSON.parse(chartData.textContent || '{}');

    new Chart(chartCanvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Ventas',
                data: amounts,
                borderColor: '#356ee6',
                backgroundColor: 'rgba(53, 110, 230, .1)',
                pointBackgroundColor: '#356ee6',
                pointRadius: 3,
                pointHoverRadius: 5,
                borderWidth: 2,
                fill: true,
                tension: .35,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: context => formatCurrency(context.parsed.y) } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#8492a6', maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
                y: { beginAtZero: true, grid: { color: '#eef1f5' }, border: { display: false }, ticks: { color: '#8492a6', precision: 0 } },
            },
        },
    });
}

if (reportData instanceof HTMLScriptElement && reportCanvas instanceof HTMLCanvasElement) {
    const { labels, amounts } = JSON.parse(reportData.textContent || '{}');

    new Chart(reportCanvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Ventas',
                data: amounts,
                backgroundColor: 'rgba(53, 110, 230, .76)',
                borderRadius: 4,
                maxBarThickness: 28,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => formatCurrency(context.parsed.y) } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#8492a6', maxRotation: 0, autoSkip: true, maxTicksLimit: 14 } },
                y: { beginAtZero: true, grid: { color: '#eef1f5' }, border: { display: false }, ticks: { color: '#8492a6', precision: 0 } },
            },
        },
    });
}
