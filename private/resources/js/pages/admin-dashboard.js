import { Chart, LineController, LineElement, PointElement, LinearScale, TimeScale, CategoryScale, Filler, Tooltip } from 'chart.js'

Chart.register(LineController, LineElement, PointElement, LinearScale, TimeScale, CategoryScale, Filler, Tooltip)

/**
 * Инициализирует график динамики использования инструментов на дашборде.
 */
export function initAdminDashboard() {
  const canvas = document.getElementById('analytics-usage-chart')
  if (!canvas) return

  const raw = canvas.getAttribute('data-chart') || '{}'
  let data
  try {
    data = JSON.parse(raw)
  } catch {
    return
  }

  const labels = Object.keys(data)
  const values = Object.values(data)

  const style = getComputedStyle(document.body)
  const primaryColor = '#0d6efd'

  new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Использований',
          data: values,
          borderColor: primaryColor,
          backgroundColor: 'rgba(13, 110, 253, 0.08)',
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: true,
          tension: 0.3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: (items) => items[0]?.label ?? '',
            label: (item) => ` ${item.formattedValue} использований`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            maxTicksLimit: 10,
            font: { size: 11 },
            color: '#6c757d',
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            font: { size: 11 },
            color: '#6c757d',
          },
          grid: {
            color: 'rgba(0,0,0,0.05)',
          },
        },
      },
    },
  })
}
