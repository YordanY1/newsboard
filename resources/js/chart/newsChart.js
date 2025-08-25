// resources/js/chart/newsChart.js
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";

// Register only what we use (tree-shaking friendly)
Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
);

// Keep one chart instance per canvas element to avoid leaks on Livewire rerenders
function destroyIfAny(el) {
    if (el && el._chart) {
        el._chart.destroy();
        el._chart = null;
    }
}

/**
 * Mount a bar chart for "articles per day".
 * @param {HTMLCanvasElement} el
 * @param {{date:string,count:number}[]} series
 */
export function mount(el, series) {
    if (!el) return;
    destroyIfAny(el);

    const labels = (series || []).map((s) => s.date);
    const counts = (series || []).map((s) => s.count);

    const ctx = el.getContext("2d");
    el._chart = new Chart(ctx, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: "Articles",
                    data: counts,
                    borderWidth: 1,
                    backgroundColor: "rgba(99, 102, 241, 0.7)", // indigo-ish
                    borderColor: "rgba(99, 102, 241, 1)",
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 800,
                easing: "easeOutQuart", // smooth deceleration
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
            plugins: {
                legend: { display: false },
                tooltip: { mode: "index", intersect: false },
            },
        },
    });
}

export function unmount(el) {
    destroyIfAny(el);
}
