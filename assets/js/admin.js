/**
 * Gloy Skincare - Executive Admin Analytics & Interactivity Script
 */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Chart === 'undefined') return;

  const data = window.gloyAnalytics || {
    months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    salesData: [142000, 168000, 185000, 215000, 238000, 256000, 289000, 312000, 345000, 388000, 420000, 465000],
    profitData: [82600, 97700, 107600, 125100, 138500, 148900, 168200, 181500, 200700, 225800, 244400, 270600],
    qtyData: [68, 79, 87, 98, 108, 119, 134, 146, 162, 178, 194, 214],
    ordersData: [42, 49, 54, 61, 68, 74, 82, 89, 98, 107, 116, 128],
    sparklineSales: [14, 18, 16, 22, 26, 24, 30, 34, 38, 42, 45, 48],
    sparklineProfit: [8, 10, 9, 13, 15, 14, 17, 19, 21, 24, 26, 28],
    sparklineMargin: [56, 57, 56, 58, 58, 57, 59, 58, 58, 59, 58, 58.2],
    sparklineQty: [6, 8, 7, 10, 11, 12, 14, 15, 17, 18, 20, 22],
    sparklineDiscount: [2, 3, 2, 4, 3, 4, 5, 4, 4, 5, 4, 3]
  };

  // 1. Helper to render sparkline mini-curves
  function createSparkline(canvasId, points, color, fillColor) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: points.map((_, i) => i + 1),
        datasets: [{
          data: points,
          borderColor: color || '#7C8B6F',
          backgroundColor: fillColor || 'rgba(124, 139, 111, 0.12)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false }
        },
        scales: {
          x: { display: false },
          y: { display: false }
        }
      }
    });
  }

  // Initialize Sparklines
  createSparkline('sparklineSales', data.sparklineSales, '#7C8B6F', 'rgba(124, 139, 111, 0.15)');
  createSparkline('sparklineProfit', data.sparklineProfit, '#C97C5D', 'rgba(201, 124, 93, 0.15)');
  createSparkline('sparklineMargin', data.sparklineMargin, '#D97706', 'rgba(217, 119, 6, 0.15)');
  createSparkline('sparklineQty', data.sparklineQty, '#7C8B6F', 'rgba(124, 139, 111, 0.15)');
  createSparkline('sparklineDiscount', data.sparklineDiscount, '#C97C5D', 'rgba(201, 124, 93, 0.15)');

  // 2. Main Analytics Trend Chart (Dual Bar + Line)
  const mainCanvas = document.getElementById('adminSalesTrendChart');
  let mainChart = null;

  let currentMetric = 'sales';
  let currentChartType = 'bar';

  function getMainChartDataset(metric) {
    let dataset = {
      label: 'Monthly Sales (Rs.)',
      data: data.salesData,
      barColor: '#2B2420',
      lineColor: '#7C8B6F'
    };

    if (metric === 'profit') {
      dataset = {
        label: 'Gross Profit (Rs.)',
        data: data.profitData,
        barColor: '#C97C5D',
        lineColor: '#7C8B6F'
      };
    } else if (metric === 'qty') {
      dataset = {
        label: 'Quantity Sold (Units)',
        data: data.qtyData,
        barColor: '#7C8B6F',
        lineColor: '#C97C5D'
      };
    } else if (metric === 'orders') {
      dataset = {
        label: 'Completed Orders',
        data: data.ordersData,
        barColor: '#4A403A',
        lineColor: '#7C8B6F'
      };
    }

    return dataset;
  }

  function renderMainChart() {
    if (!mainCanvas) return;
    const ctx = mainCanvas.getContext('2d');
    const set = getMainChartDataset(currentMetric);

    if (mainChart) {
      mainChart.destroy();
    }

    const datasets = [];

    if (currentChartType === 'bar') {
      datasets.push({
        type: 'bar',
        label: set.label,
        data: set.data,
        backgroundColor: set.barColor,
        borderRadius: 4,
        barThickness: 22,
        order: 2
      });
      datasets.push({
        type: 'line',
        label: 'Target / Trend',
        data: set.data.map(v => Math.round(v * 1.05)),
        borderColor: set.lineColor,
        borderWidth: 2.5,
        borderDash: [4, 4],
        pointBackgroundColor: set.lineColor,
        pointRadius: 4,
        fill: false,
        tension: 0.35,
        order: 1
      });
    } else {
      datasets.push({
        type: 'line',
        label: set.label,
        data: set.data,
        borderColor: set.lineColor,
        backgroundColor: 'rgba(124, 139, 111, 0.12)',
        borderWidth: 3,
        fill: true,
        pointBackgroundColor: set.lineColor,
        pointRadius: 5,
        pointHoverRadius: 7,
        tension: 0.4
      });
    }

    mainChart = new Chart(ctx, {
      data: {
        labels: data.months,
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              font: { family: 'Plus Jakarta Sans', size: 12 },
              color: '#4A403A'
            }
          },
          tooltip: {
            padding: 12,
            backgroundColor: 'rgba(43, 36, 32, 0.92)',
            titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: '700' },
            bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                let val = context.raw;
                if (currentMetric === 'sales' || currentMetric === 'profit') {
                  return `${label}: Rs. ${val.toLocaleString()}`;
                }
                return `${label}: ${val.toLocaleString()}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(232, 226, 217, 0.7)' },
            ticks: {
              color: '#786C64',
              font: { family: 'Plus Jakarta Sans', size: 11 },
              callback: function(val) {
                if (currentMetric === 'sales' || currentMetric === 'profit') {
                  return (val >= 1000) ? (val / 1000) + 'k' : val;
                }
                return val;
              }
            }
          },
          x: {
            grid: { display: false },
            ticks: {
              color: '#786C64',
              font: { family: 'Plus Jakarta Sans', size: 11 }
            }
          }
        }
      }
    });
  }

  // Initial render of main chart
  renderMainChart();

  // 3. Tab Buttons Click Handlers (Sales / Profit / Qty / Orders)
  const tabBtns = document.querySelectorAll('#analyticsViewFilter .analytics-tab-btn');
  const heading = document.getElementById('mainChartHeading');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentMetric = btn.dataset.metric;

      if (heading) {
        if (currentMetric === 'sales') heading.textContent = 'Sales Trend by Month (Rs.)';
        else if (currentMetric === 'profit') heading.textContent = 'Gross Profit Trend by Month (Rs.)';
        else if (currentMetric === 'qty') heading.textContent = 'Units Sold by Month (Volume)';
        else if (currentMetric === 'orders') heading.textContent = 'Order Volume by Month';
      }

      renderMainChart();
    });
  });

  // 4. Toggle Chart Type (Bars vs Line)
  document.getElementById('btnChartModeBars')?.addEventListener('click', () => {
    currentChartType = 'bar';
    renderMainChart();
  });

  document.getElementById('btnChartModeLine')?.addEventListener('click', () => {
    currentChartType = 'line';
    renderMainChart();
  });

  // 5. AJAX Order Status Changer
  document.querySelectorAll('.js-order-status-change').forEach(select => {
    select.addEventListener('change', () => {
      const orderId = select.dataset.orderId;
      const newStatus = select.value;

      fetch('orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
          action: 'update_status',
          order_id: orderId,
          order_status: newStatus
        })
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          if (typeof window.showToast === 'function') {
            window.showToast('Order status updated successfully', 'success');
          } else {
            alert('Order status updated successfully');
          }
        } else {
          alert(res.message || 'Update failed');
        }
      })
      .catch(err => console.error(err));
    });
  });
});
