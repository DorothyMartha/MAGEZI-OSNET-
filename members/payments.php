<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Report</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.3/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { primary: "#4F46E5", secondary: "#10B981" },
          borderRadius: { none: "0px", sm: "4px", DEFAULT: "8px", md: "12px", lg: "16px", xl: "20px", "2xl": "24px", "3xl": "32px", full: "9999px", button: "8px" }
        }
      }
    };
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
  <style>
    body { font-family: 'Inter', sans-serif; }
    .date-picker::-webkit-calendar-picker-indicator {
      background: transparent;
      bottom: 0;
      color: transparent;
      cursor: pointer;
      height: auto;
      left: 0;
      position: absolute;
      right: 0;
      top: 0;
      width: auto;
    }
    table { border-collapse: collapse; }
    #membershipChart { 
      min-width: 300px; 
      min-height: 300px; 
      width: 100%; 
      height: 320px;
    }
    @media print {
      .flex, .flex-1, .w-64, header, .mb-6 { display: none; }
      .bg-white.rounded-lg.shadow { box-shadow: none; border: none; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
      th { background-color: #4F46E5; color: white; }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex min-h-screen">
    <div class="w-64 bg-white border-r border-gray-200 flex-shrink-0">
      <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900">Report Parameters</h2>
      </div>
      <div class="px-4">
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
          <div class="relative">
            <input type="date" class="w-full border border-gray-300 rounded-button px-3 py-2 text-sm date-picker" id="startDate" value="2025-02-19">
          </div>
          <div class="relative mt-2">
            <input type="date" class="w-full border border-gray-300 rounded-button px-3 py-2 text-sm date-picker" id="endDate" value="2025-02-28">
          </div>
        </div>
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Group By</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="radio" name="groupBy" value="day" class="text-primary" checked>
              <span class="ml-2 text-sm text-gray-700">Day</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="groupBy" value="week" class="text-primary">
              <span class="ml-2 text-sm text-gray-700">Week</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="groupBy" value="month" class="text-primary">
              <span class="ml-2 text-sm text-gray-700">Month</span>
            </label>
          </div>
        </div>
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Data Filters</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded" checked>
              <span class="ml-2 text-sm text-gray-700">Active Subscriptions</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded" checked>
              <span class="ml-2 text-sm text-gray-700">Pending Payments</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded" checked>
              <span class="ml-2 text-sm text-gray-700">Active Donations</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded">
              <span class="ml-2 text-sm text-gray-700">Expired Subscriptions/Donations</span>
            </label>
          </div>
        </div>
        <button class="w-full bg-primary text-white rounded-button px-4 py-2 text-sm font-medium hover:bg-primary/90 transition-colors" onclick="generateReport()">
          Generate Report
        </button>
      </div>
    </div>

    <div class="flex-1">
      <header class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-6 py-4">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Payment Report</h1>
            <div class="flex items-center text-sm text-gray-500 mt-1">
              <span>Dashboard</span>
              <i class="ri-arrow-right-s-line mx-1"></i>
              <span>Payment Reports</span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <button class="flex items-center text-gray-700 hover:text-gray-900" id="notificationBtn">
              <i class="ri-notification-3-line text-xl"></i>
            </button>
            <button class="flex items-center text-gray-700 hover:text-gray-900" id="profileBtn">
              <i class="ri-user-3-line text-xl"></i>
            </button>
          </div>
        </div>
        <div class="px-6 py-2 border-b border-gray-200">
          <div class="flex space-x-1">
            <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full" onclick="window.location.href='membership.php'">
              <i class="ri-team-line mr-1"></i> Membership
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full" onclick="window.location.href='subscription.php'">
              <i class="ri-money-dollar-circle-line mr-1"></i> Subscription
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full" onclick="window.location.href='fundraising.php'">
              <i class="ri-hand-heart-line mr-1"></i> Fundraising
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-900 bg-gray-100 rounded-full active-tab" onclick="window.location.href='payments.php'">
              <i class="ri-bank-line mr-1"></i> Payments
            </button>
          </div>
        </div>
      </header>

      <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <div class="relative">
              <input type="text" placeholder="Search reports..." class="pl-8 pr-4 py-2 border border-gray-300 rounded-button text-sm">
              <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-button hover:bg-gray-50" data-format="excel">
              <i class="ri-file-excel-2-line mr-1"></i> Excel
            </button>
            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-button hover:bg-gray-50" id="exportPDFBtn" data-format="pdf">
              <i class="ri-file-pdf-line mr-1"></i> PDF
            </button>
            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-button hover:bg-gray-50" data-format="print">
              <i class="ri-printer-line mr-1"></i> Print
            </button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="p-6">
            <div id="membershipChart" class="mb-8"></div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="text-left border-b border-gray-200">
                    <th class="pb-3 font-medium text-gray-900">Name</th>
                    <th class="pb-3 font-medium text-gray-900">Amount</th>
                    <th class="pb-3 font-medium text-gray-900">Payment Status</th>
                    <th class="pb-3 font-medium text-gray-900">Date</th>
                    <th class="pb-3 font-medium text-gray-900">Membership Category</th>
                    <th class="pb-3 font-medium text-gray-900">Type</th>
                  </tr>
                </thead>
                <tbody id="reportTable">
                  <?php
                  error_reporting(E_ALL);
                  ini_set('display_errors', 1);
                  require_once '../libs/db-connection.php';
                  if ($conn->connect_error) {
                    echo "<tr><td colspan='6'>Connection failed: " . $conn->connect_error . "</td></tr>";
                    echo "<script>console.error('Database connection failed: " . $conn->connect_error . "');</script>";
                  } else {
                    $query = "
                      (SELECT u.full_name AS name, s.amount, s.payment_status, DATE(s.created_at) AS date, mc.category_name AS membership_category, 'Subscription' AS type
                      FROM subscriptions s LEFT JOIN users u ON s.user_id = u.id LEFT JOIN membership_categories mc ON u.membership_category_id = mc.id)
                      UNION
                      (SELECT u.full_name AS name, d.amount, d.payment_status, DATE(d.donation_date) AS date, mc.category_name AS membership_category, 'Donation' AS type
                      FROM donations d LEFT JOIN users u ON d.user_id = u.id LEFT JOIN membership_categories mc ON u.membership_category_id = mc.id)
                      ORDER BY date DESC
                    ";
                    $result = $conn->query($query);
                    if ($result === false) {
                      echo "<tr><td colspan='6'>Query failed: " . $conn->error . "</td></tr>";
                      echo "<script>console.error('Query failed: " . $conn->error . "');</script>";
                    } elseif ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        echo "<tr class='border-b border-gray-200'>";
                        echo "<td class='py-3 text-sm font-medium text-gray-900'>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>";
                        echo "<td class='py-3 text-sm font-medium text-gray-900'>UGX " . number_format($row['amount'] ?? 0, 2) . "</td>";
                        echo "<td class='py-3 text-sm font-medium text-gray-900'>" . ($row['payment_status'] ?? 'N/A') . "</td>";
                        echo "<td class='py-3 text-sm font-medium text-gray-900'>" . ($row['date'] ?? 'N/A') . "</td>";
                        echo "<td class='py-3 text-sm font-medium text-gray-900'>" . ($row['membership_category'] ? htmlspecialchars($row['membership_category']) : 'N/A') . "</td>";
                        echo "<td class='py-3 text-sm font-medium text-gray-900'>" . $row['type'] . "</td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='6' class='py-3 text-sm text-gray-900 text-center'>No data found</td></tr>";
                    }
                    $conn->close();
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div id="notification" class="fixed top-4 right-4 bg-white rounded-lg shadow-lg p-4 hidden">
        <div class="flex items-center">
          <i class="ri-checkbox-circle-line text-green-500 text-xl mr-2"></i>
          <span class="text-sm text-gray-700" id="notificationMessage"></span>
        </div>
      </div>
  </div>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { primary: "#4F46E5", secondary: "#10B981" },
          borderRadius: { none: "0px", sm: "4px", DEFAULT: "8px", md: "12px", lg: "16px", xl: "20px", "2xl": "24px", "3xl": "32px", full: "9999px", button: "8px" }
        }
      }
    };

    let chart;
    function initChart() {
      const chartContainer = document.getElementById("membershipChart");
      if (!chartContainer) {
        console.error("Chart container not found");
        alert("Chart failed: Container not found");
        return;
      }
      if (!window.echarts) {
        console.error("ECharts library not loaded");
        alert("Chart failed: ECharts library not loaded");
        return;
      }
      console.log("Chart container size:", chartContainer.offsetWidth, chartContainer.offsetHeight);
      chart = echarts.init(chartContainer);
      
      // Generate daily dates from 2025-02-19 to 2025-02-28
      const startDate = new Date("2025-02-19");
      const endDate = new Date("2025-02-28");
      const phpDates = [];
      for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        phpDates.push(d.toISOString().split('T')[0]);
      }
      
      const subTransactions = <?php
         require_once '../libs/db-connection.php';
        $query = "SELECT DATE(created_at) AS date, amount FROM subscriptions WHERE amount IS NOT NULL AND created_at IS NOT NULL ORDER BY created_at";
        $result = $conn->query($query);
        $transactions = [];
        if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $transactions[] = ['date' => $row['date'], 'amount' => floatval($row['amount'])];
          }
        } else {
          echo "<script>console.warn('No subscription transactions found');</script>";
        }
        echo json_encode($transactions);
        $conn->close();
      ?>;
      
      const donTransactions = <?php
        require_once '../libs/db-connection.php';
        $query = "SELECT DATE(donation_date) AS date, amount FROM donations WHERE amount IS NOT NULL AND donation_date IS NOT NULL ORDER BY donation_date";
        $result = $conn->query($query);
        $transactions = [];
        if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $transactions[] = ['date' => $row['date'], 'amount' => floatval($row['amount'])];
          }
        } else {
          echo "<script>console.warn('No donation transactions found');</script>";
        }
        echo json_encode($transactions);
        $conn->close();
      ?>;
      
      const phpSubAmounts = new Array(phpDates.length).fill(0);
      subTransactions.forEach(tx => {
        const index = phpDates.indexOf(tx.date);
        if (index !== -1) phpSubAmounts[index] = tx.amount;
      });
      
      const phpDonAmounts = new Array(phpDates.length).fill(0);
      donTransactions.forEach(tx => {
        const index = phpDates.indexOf(tx.date);
        if (index !== -1) phpDonAmounts[index] = tx.amount;
      });

      const option = {
        animation: false,
        tooltip: { trigger: "axis" },
        legend: { data: ["Subscription Amount", "Donation Amount"], bottom: 0 },
        grid: { top: "5%", left: "3%", right: "4%", bottom: "15%", containLabel: true },
        xAxis: { type: "category", data: phpDates },
        yAxis: { type: "value", name: "Amount (UGX)" },
        series: [
          { 
            name: "Subscription Amount", 
            type: "line", 
            smooth: true, 
            data: phpSubAmounts, 
            itemStyle: { color: "rgba(87, 181, 231, 1)" },
            areaStyle: {
              color: {
                type: "linear",
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  { offset: 0, color: "rgba(87, 181, 231, 0.2)" },
                  { offset: 1, color: "rgba(87, 181, 231, 0)" }
                ]
              }
            }
          },
          { 
            name: "Donation Amount", 
            type: "line", 
            smooth: true, 
            data: phpDonAmounts, 
            itemStyle: { color: "rgba(16, 185, 129, 1)" },
            areaStyle: {
              color: {
                type: "linear",
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  { offset: 0, color: "rgba(16, 185, 129, 0.2)" },
                  { offset: 1, color: "rgba(16, 185, 129, 0)" }
                ]
              }
            }
          }
        ]
      };
      console.log("Chart Option:", option);
      chart.setOption(option);
      console.log("Chart initialized:", chart);
    }

    function exportToPDF() {
      console.log("Exporting to PDF...");
      const chartContainer = document.getElementById('membershipChart');
      const tableContainer = document.querySelector('table');

      if (!chartContainer || !tableContainer) {
        console.error("Chart or table not found");
        return;
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF('p', 'mm', 'a4');

      // Add table content
      doc.autoTable({
        html: tableContainer,
        startY: 20,
        theme: 'grid',
        headStyles: { fillColor: '#4F46E5' },
        bodyStyles: { lineColor: '#E5E7EB' },
      });

      // Add the chart content (image)
      const chartCanvas = chartContainer.querySelector('canvas');
      if (chartCanvas) {
        const chartImage = chartCanvas.toDataURL('image/png');
        doc.addImage(chartImage, 'PNG', 10, doc.lastAutoTable.finalY + 10, 180, 100);
      } else {
        console.warn("Chart canvas not found, skipping chart in PDF");
      }

      // Save the PDF
      doc.save('payment_report.pdf');
    }

    function exportReport(format) {
      const chartContainer = document.getElementById('membershipChart');
      const table = document.querySelector('table');

      if (!chartContainer || !table) {
        console.error("Chart or table not found");
        return;
      }

      if (format === 'pdf') {
        exportToPDF();
      } else if (format === 'excel') {
        const wb = XLSX.utils.book_new();
        const wsData = [
          Array.from(table.querySelectorAll('thead th')).map(th => th.textContent),
          ...Array.from(table.querySelectorAll('tbody tr')).map(row => Array.from(row.querySelectorAll('td')).map(td => td.textContent))
        ];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        XLSX.utils.book_append_sheet(wb, ws, 'Payment Report');
        XLSX.writeFile(wb, 'payment_report.xlsx');
      } else if (format === 'print') {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
          console.error("Print window blocked");
          alert("Print failed: Please allow pop-ups");
          return;
        }
        printWindow.document.write(`
          <html>
            <head><title>Payment Report</title>
            <style>body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; margin-bottom: 20px; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #4F46E5; color: white; } img { max-width: 100%; height: auto; }</style>
            </head>
            <body><h1>Payment Report</h1>${table.outerHTML}${chartContainer ? `<img src="${chartContainer.querySelector('canvas').toDataURL('image/png')}" alt="Payment Chart">` : ''}</body>
          </html>
        `);
        printWindow.document.close();
        printWindow.print();
      }

      const notification = document.getElementById("notification");
      const notificationMessage = document.getElementById("notificationMessage");
      notificationMessage.textContent = `Report exported as ${format.toUpperCase()}!`;
      notification.classList.remove("hidden");
      setTimeout(() => notification.classList.add("hidden"), 3000);
    }

    function generateReport() {
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;
      console.log(`Generate Report clicked - Date Range: ${startDate} to ${endDate} - Filtering not fully implemented yet`);
      initChart(); // Reinitialize chart with potential new data
    }

    document.addEventListener('DOMContentLoaded', () => {
      try {
        initChart();
      } catch (e) {
        console.error("Chart initialization error:", e);
        alert("Chart initialization failed: " + e.message);
      }

      const exportButtons = document.querySelectorAll('[data-format]');
      exportButtons.forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const format = button.getAttribute('data-format');
          exportReport(format);
        });
      });

      document.getElementById('exportPDFBtn').addEventListener('click', (e) => {
        e.preventDefault();
        exportReport('pdf');
      });
    });
  </script>
</body>
</html>