<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.plugin.autotable.min.js"></script>
  <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: "#4F46E5",
              secondary: "#10B981",
            },
            borderRadius: {
              none: "0px",
              sm: "4px",
              DEFAULT: "8px",
              md: "12px",
              lg: "16px",
              xl: "20px",
              "2xl": "24px",
              "3xl": "32px",
              full: "9999px",
              button: "8px",
            },
          },
        },
      };
  </script>
  <script>
    // Global variable to store chart instance
    let membershipChartInstance = null;

    // Function to export chart and table as PDF
    function exportToPDF() {
      console.log("Starting PDF export...");
      
      // Check if jsPDF is loaded
      if (!window.jspdf || !window.jspdf.jsPDF) {
        console.error("jsPDF library not loaded!");
        return;
      }
      console.log("jsPDF library confirmed loaded.");

      const tableContainer = document.querySelector('table');
      if (!tableContainer) {
        console.error("Table container not found!");
        return;
      }
      console.log("Table container found.");

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF('p', 'mm', 'a4');
      console.log("jsPDF instance created.");

      try {
        // Add table
        console.log("Adding table to PDF...");
        doc.autoTable({
          html: tableContainer,
          startY: 20,
          theme: 'grid',
          headStyles: { fillColor: '#4F46E5' },
          bodyStyles: { lineColor: '#E5E7EB' },
        });
        console.log("Table added successfully.");

        // Add chart image
        if (membershipChartInstance) {
          console.log("Retrieving chart image...");
          const chartImage = membershipChartInstance.getDataURL({
            type: 'png',
            pixelRatio: 2,
            backgroundColor: '#fff'
          });
          if (chartImage) {
            doc.addImage(chartImage, 'PNG', 10, doc.lastAutoTable.finalY + 10, 180, 100);
            console.log("Chart image added to PDF.");
          } else {
            console.warn("Chart image data is empty.");
          }
        } else {
          console.warn("Chart instance not available.");
        }

        // Save the PDF
        console.log("Saving PDF...");
        doc.save('report.pdf');
        console.log("PDF saved successfully.");
      } catch (error) {
        console.error("Error during PDF generation:", error);
      }
    }

    // Original event listener for PDF export
    document.addEventListener("DOMContentLoaded", function () {
      console.log("Setting up PDF button listener...");
      const pdfBtn = document.getElementById('exportPDFBtn');
      if (pdfBtn) {
        pdfBtn.addEventListener('click', exportToPDF);
        console.log("PDF button listener set up.");
      } else {
        console.error("PDF button not found!");
      }
    });
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
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php
  // Consolidated database queries for chart and table data
  require_once '../libs/db-connection.php';

  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  $query = "
      SELECT 
          DATE(u.created_at) AS date,
          COUNT(u.id) AS new_members,
          (SELECT COUNT(*) FROM users u2 WHERE DATE(u2.created_at) <= DATE(u.created_at)) AS total_users,
          COALESCE(SUM(t.amount), 0) AS revenue,
          CASE 
              WHEN SUM(t.amount) > 0 THEN 'Successful'
              WHEN SUM(t.amount) IS NULL THEN 'No Transactions'
              ELSE 'Pending'
          END AS status
      FROM users u
      LEFT JOIN transactions t ON DATE(t.created_at) = DATE(u.created_at) AND t.transaction_type = 'Subscription'
      GROUP BY DATE(u.created_at)
      ORDER BY DATE(u.created_at)
  ";
  $result = $conn->query($query);

  $dates = [];
  $newMembers = [];
  $totalUsers = [];
  $revenue = [];
  $tableData = [];

  if ($result) {
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $dates[] = $row['date'];
              $newMembers[] = (int)$row['new_members'];
              $totalUsers[] = (int)$row['total_users'];
              $revenue[] = (float)$row['revenue'];
              $tableData[] = $row;
          }
      } else {
          $dates = [date('Y-m-d')];
          $newMembers = [0];
          $totalUsers = [0];
          $revenue = [0];
          $tableData[] = ['date' => date('Y-m-d'), 'new_members' => 0, 'total_users' => 0, 'revenue' => 0, 'status' => 'No Transactions'];
      }
  } else {
      echo "Query failed: " . $conn->error;
      $dates = [date('Y-m-d')];
      $newMembers = [0];
      $totalUsers = [0];
      $revenue = [0];
      $tableData[] = ['date' => date('Y-m-d'), 'new_members' => 0, 'total_users' => 0, 'revenue' => 0, 'status' => 'Error'];
  }

  $conn->close();
  ?>

  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-white border-r border-gray-200 flex-shrink-0">
      <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900">Report Parameters</h2>
      </div>
      <div class="px-4">
        <!-- Date Range -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
          <div class="relative">
            <input type="date" class="w-full border border-gray-300 rounded-button px-3 py-2 text-sm date-picker" id="startDate">
          </div>
          <div class="relative mt-2">
            <input type="date" class="w-full border border-gray-300 rounded-button px-3 py-2 text-sm date-picker" id="endDate">
          </div>
        </div>

        <!-- Group By -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Group By</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="radio" name="groupBy" value="day" class="text-primary">
              <span class="ml-2 text-sm text-gray-700">Day</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="groupBy" value="week" class="text-primary">
              <span class="ml-2 text-sm text-gray-700">Week</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="groupBy" value="month" class="text-primary" checked>
              <span class="ml-2 text-sm text-gray-700">Month</span>
            </label>
          </div>
        </div>

        <!-- Data Filters -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Data Filters</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded" checked>
              <span class="ml-2 text-sm text-gray-700">Active Members</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded" checked>
              <span class="ml-2 text-sm text-gray-700">Pending Payments</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="text-primary rounded">
              <span class="ml-2 text-sm text-gray-700">Expired Members</span>
            </label>
          </div>
        </div>

        <!-- Generate Report Button -->
        <button class="w-full bg-primary text-white rounded-button px-4 py-2 text-sm font-medium hover:bg-primary/90 transition-colors" onclick="generateReport()">
          Generate Report
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1">
      <header class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-6 py-4">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Reports Dashboard</h1>
            <div class="flex items-center text-sm text-gray-500 mt-1">
              <span>Dashboard</span>
              <i class="ri-arrow-right-s-line mx-1"></i>
              <span>Reports</span>
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
                  <i class="ri-team-line mr-1"></i>
                  Membership
              </button>
              <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full" onclick="window.location.href='subscription.php'">
                  <i class="ri-money-dollar-circle-line mr-1"></i>
                  Subscription
              </button>
              <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full" onclick="window.location.href='fundraising.php'">
                  <i class="ri-hand-heart-line mr-1"></i>
                  Fundraising
              </button>
              <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full" onclick="window.location.href='payments.php'">
                  <i class="ri-bank-line mr-1"></i>
                  Payments
              </button>
          </div>
        </div>
      </header>

      <!-- Report Content -->
      <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <div class="relative">
              <input type="text" placeholder="Search reports..." class="pl-8 pr-4 py-2 border border-gray-300 rounded-button text-sm">
              <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-button hover:bg-gray-50" onclick="exportReport('excel')">
                <i class="ri-file-excel-2-line mr-1"></i>
                Excel
            </button>
            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-button hover:bg-gray-50" id="exportPDFBtn" onclick="exportToPDF()">
                <i class="ri-file-pdf-line mr-1"></i>
                PDF
            </button>
            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-button hover:bg-gray-50" onclick="exportReport('print')">
                <i class="ri-printer-line mr-1"></i>
                Print
            </button>
          </div>
        </div>

        <!-- Chart and Table -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-6">
            <div id="membershipChart" class="h-80 mb-8"></div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="text-left border-b border-gray-200">
                    <th class="pb-3 font-medium text-gray-900">Date</th>
                    <th class="pb-3 font-medium text-gray-900">New Members</th>
                    <th class="pb-3 font-medium text-gray-900">Total Number of Users</th>
                    <th class="pb-3 font-medium text-gray-900">Revenue</th>
                    <th class="pb-3 font-medium text-gray-900">Status</th>
                  </tr>
                </thead>
                <tbody id="reportTable">
                  <?php
                  foreach ($tableData as $row) {
                      echo "<tr class='border-b border-gray-200'>";
                      echo "<td class='py-3 text-sm font-medium text-gray-900'>" . htmlspecialchars($row['date']) . "</td>";
                      echo "<td class='py-3 text-sm font-medium text-gray-900'>" . htmlspecialchars($row['new_members']) . "</td>";
                      echo "<td class='py-3 text-sm font-medium text-gray-900'>" . htmlspecialchars($row['total_users']) . "</td>";
                      echo "<td class='py-3 text-sm font-medium text-gray-900'>" . htmlspecialchars($row['revenue']) . "</td>";
                      echo "<td class='py-3 text-sm font-medium text-gray-900'>" . htmlspecialchars($row['status']) . "</td>";
                      echo "</tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Notification -->
        <div id="notification" class="fixed top-4 right-4 bg-white rounded-lg shadow-lg p-4 hidden">
          <div class="flex items-center">
            <i class="ri-checkbox-circle-line text-green-500 text-xl mr-2"></i>
            <span class="text-sm text-gray-700">Report exported successfully!</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  // JavaScript code to initialize the chart and store instance
  function initChart() {
    console.log("Initializing chart...");
    membershipChartInstance = echarts.init(document.getElementById("membershipChart"));
    const option = {
      animation: false,
      tooltip: {
        trigger: "axis",
        backgroundColor: "rgba(255, 255, 255, 0.9)",
        borderColor: "#e5e7eb",
        borderWidth: 1,
        textStyle: { color: "#1f2937" },
      },
      legend: {
        data: ["New Members", "Total Users", "Revenue"],
        bottom: 0,
      },
      grid: {
        top: "5%",
        left: "3%",
        right: "4%",
        bottom: "15%",
        containLabel: true,
      },
      xAxis: {
        type: "category",
        data: <?php echo json_encode($dates); ?>,
      },
      yAxis: [
        {
          type: "value",
          name: "Members",
          position: "left",
        },
        {
          type: "value",
          name: "Revenue",
          position: "right",
        },
      ],
      series: [
        {
          name: "New Members",
          type: "line",
          smooth: true,
          data: <?php echo json_encode($newMembers); ?>,
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
                { offset: 1, color: "rgba(87, 181, 231, 0)" },
              ],
            },
          },
        },
        {
          name: "Total Users",
          type: "line",
          smooth: true,
          data: <?php echo json_encode($totalUsers); ?>,
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
                { offset: 1, color: "rgba(16, 185, 129, 0)" },
              ],
            },
          },
        },
        {
          name: "Revenue",
          type: "line",
          smooth: true,
          yAxisIndex: 1,
          data: <?php echo json_encode($revenue); ?>,
          itemStyle: { color: "rgba(244, 114, 182, 1)" },
          areaStyle: {
            color: {
              type: "linear",
              x: 0,
              y: 0,
              x2: 0,
              y2: 1,
              colorStops: [
                { offset: 0, color: "rgba(244, 114, 182, 0.2)" },
                { offset: 1, color: "rgba(244, 114, 182, 0)" },
              ],
            },
          },
        },
      ],
    };

    try {
      membershipChartInstance.setOption(option);
      console.log("Chart initialized successfully.");
    } catch (error) {
      console.error("Error setting chart options:", error);
    }
  }

  // Initialize chart when the page loads with error checking
  document.addEventListener("DOMContentLoaded", () => {
    if (typeof echarts === 'undefined') {
      console.error("ECharts library not loaded!");
    } else {
      initChart();
    }
  });

  // Export report function (enhanced for Excel and Print)
  function exportReport(format) {
    const notification = document.getElementById("notification");
    notification.classList.remove("hidden");
    setTimeout(() => {
      notification.classList.add("hidden");
    }, 3000);

    if (format === 'excel') {
      const table = document.querySelector('table');
      let csv = [];
      const rows = table.querySelectorAll('tr');
      for (const row of rows) {
        const cols = row.querySelectorAll('th, td');
        const rowData = Array.from(cols).map(col => `"${col.textContent.trim()}"`).join(',');
        csv.push(rowData);
      }
      const csvContent = csv.join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'report.csv';
      link.click();
    } else if (format === 'print') {
      window.print();
    }
  }
  </script>
</body>
</html>