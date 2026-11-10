// Self-hosted fonts (no external CDN, so CSP can stay 'self').
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/jetbrains-mono/400.css';

// Bootstrap's interactive components (modals, dropdowns, offcanvas) and Chart.js.
import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;