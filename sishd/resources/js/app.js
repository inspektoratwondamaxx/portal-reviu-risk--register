import './bootstrap';
import * as bootstrap from 'bootstrap';
import { Chart } from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
});
