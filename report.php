<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reports Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: "#4F46E5",
              secondary: "#6366F1",
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
    <style>
      :where([class^="ri-"])::before { content: "\f3c2"; }
      body { font-family: 'Inter', sans-serif; }
      .chart-container { height: 300px; }
      input[type="number"]::-webkit-inner-spin-button,
      input[type="number"]::-webkit-outer-spin-button {
          -webkit-appearance: none;
          margin: 0;
      }
    </style>
  </head>
  <body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen">
      <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold">Report Parameters</h2>
        </div>
        <div class="p-4 flex-1 overflow-y-auto">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Date Range</label
              >
              <div class="flex space-x-2">
                <input
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-button text-sm"
                  id="startDate"
                />
                <input
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-button text-sm"
                  id="endDate"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Status</label
              >
              <div class="relative">
                <button
                  id="statusFilter"
                  class="w-full px-3 py-2 border border-gray-300 rounded-button text-sm text-left flex items-center justify-between"
                >
                  <span>All Status</span>
                  <i class="ri-arrow-down-s-line"></i>
                </button>
                <div
                  id="statusDropdown"
                  class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-10"
                >
                  <div class="p-2">
                    <div
                      class="cursor-pointer hover:bg-gray-100 px-3 py-2 rounded text-sm"
                    >
                      Active
                    </div>
                    <div
                      class="cursor-pointer hover:bg-gray-100 px-3 py-2 rounded text-sm"
                    >
                      Pending
                    </div>
                    <div
                      class="cursor-pointer hover:bg-gray-100 px-3 py-2 rounded text-sm"
                    >
                      Expired
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Group By</label
              >
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    type="radio"
                    name="groupBy"
                    class="text-primary"
                    checked
                  />
                  <span class="ml-2 text-sm">Month</span>
                </label>
                <label class="flex items-center">
                  <input type="radio" name="groupBy" class="text-primary" />
                  <span class="ml-2 text-sm">Quarter</span>
                </label>
                <label class="flex items-center">
                  <input type="radio" name="groupBy" class="text-primary" />
                  <span class="ml-2 text-sm">Year</span>
                </label>
              </div>
            </div>
            <button
              class="w-full bg-primary text-white px-4 py-2 rounded-button text-sm font-medium hover:bg-primary/90"
              id="generateReport"
            >
              Generate Report
            </button>
          </div>
        </div>
      </div>

      <div class="flex-1">
        <header class="bg-white border-b border-gray-200">
          <div class="flex items-center justify-between px-6 py-4">
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">
                Reports Dashboard
              </h1>
              <div
                class="flex items-center space-x-2 text-sm text-gray-500 mt-1"
              >
                <span>Dashboard</span>
                <i class="ri-arrow-right-s-line"></i>
                <span>Reports</span>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <button class="text-gray-500 hover:text-gray-700">
                <i class="ri-notification-3-line text-xl"></i>
              </button>
              <button class="flex items-center space-x-2">
                <div
                  class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center"
                >
                  <i class="ri-user-line text-gray-600"></i>
                </div>
              </button>
            </div>
          </div>
          <div class="px-6 py-2">
            <div class="flex space-x-1 bg-gray-100 p-1 rounded-full w-fit">
              <button
                class="px-4 py-2 rounded-full text-sm font-medium bg-white shadow"
                id="membershipTab"
              >
                Membership
              </button>
              <button
                class="px-4 py-2 rounded-full text-sm font-medium text-gray-600 hover:text-gray-900"
                id="paymentsTab"
              >
                Payments
              </button>
              <button
                class="px-4 py-2 rounded-full text-sm font-medium text-gray-600 hover:text-gray-900"
                id="fundraisingTab"
              >
                Fundraising
              </button>
            </div>
          </div>
        </header>

        <main class="p-6">
          <div class="bg-white rounded-lg shadow">
            <div
              class="p-4 border-b border-gray-200 flex items-center justify-between"
            >
              <div class="flex items-center space-x-4">
                <div class="relative">
                  <input
                    type="text"
                    placeholder="Search reports..."
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-button text-sm w-64"
                  />
                  <i
                    class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                  ></i>
                </div>
                <button
                  class="px-3 py-2 border border-gray-300 rounded-button text-sm flex items-center space-x-2"
                >
                  <i class="ri-filter-3-line"></i>
                  <span>Filters</span>
                </button>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  class="px-3 py-2 border border-gray-300 rounded-button text-sm flex items-center space-x-2"
                >
                  <i class="ri-download-line"></i>
                  <span>Export</span>
                </button>
                <button
                  class="px-3 py-2 border border-gray-300 rounded-button text-sm flex items-center space-x-2"
                >
                  <i class="ri-save-line"></i>
                  <span>Save</span>
                </button>
              </div>
            </div>

            <div class="p-4">
              <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600">Total Members</div>
                  <div class="text-2xl font-semibold mt-1">2,847</div>
                  <div class="text-sm text-green-600 flex items-center mt-2">
                    <i class="ri-arrow-up-line mr-1"></i>
                    <span>12.5% vs last month</span>
                  </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600">Active Subscriptions</div>
                  <div class="text-2xl font-semibold mt-1">2,156</div>
                  <div class="text-sm text-green-600 flex items-center mt-2">
                    <i class="ri-arrow-up-line mr-1"></i>
                    <span>8.3% vs last month</span>
                  </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600">Monthly Revenue</div>
                  <div class="text-2xl font-semibold mt-1">$48,592</div>
                  <div class="text-sm text-green-600 flex items-center mt-2">
                    <i class="ri-arrow-up-line mr-1"></i>
                    <span>15.2% vs last month</span>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-lg border border-gray-200">
                  <div class="p-4 border-b border-gray-200">
                    <h3 class="font-medium">Membership Growth</h3>
                  </div>
                  <div class="p-4">
                    <div id="membershipChart" class="chart-container"></div>
                  </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200">
                  <div class="p-4 border-b border-gray-200">
                    <h3 class="font-medium">Revenue Distribution</h3>
                  </div>
                  <div class="p-4">
                    <div id="revenueChart" class="chart-container"></div>
                  </div>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="bg-gray-50">
                      <th
                        class="px-4 py-3 text-left text-sm font-medium text-gray-600"
                      >
                        Member Name
                      </th>
                      <th
                        class="px-4 py-3 text-left text-sm font-medium text-gray-600"
                      >
                        Status
                      </th>
                      <th
                        class="px-4 py-3 text-left text-sm font-medium text-gray-600"
                      >
                        Join Date
                      </th>
                      <th
                        class="px-4 py-3 text-left text-sm font-medium text-gray-600"
                      >
                        Last Payment
                      </th>
                      <th
                        class="px-4 py-3 text-left text-sm font-medium text-gray-600"
                      >
                        Amount
                      </th>
                    </tr>
                  </thead>
                  <tbody
                    class="divide-y divide-gray-200"
                    id="reportTable"
                  ></tbody>
                </table>
              </div>

              <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-600">
                  Showing 1 to 10 of 100 entries
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    class="px-3 py-2 border border-gray-300 rounded-button text-sm disabled:opacity-50"
                    disabled
                  >
                    Previous
                  </button>
                  <button
                    class="px-3 py-2 bg-primary text-white rounded-button text-sm"
                  >
                    1
                  </button>
                  <button
                    class="px-3 py-2 border border-gray-300 rounded-button text-sm"
                  >
                    2
                  </button>
                  <button
                    class="px-3 py-2 border border-gray-300 rounded-button text-sm"
                  >
                    3
                  </button>
                  <button
                    class="px-3 py-2 border border-gray-300 rounded-button text-sm"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>

    <script>
      const mockData = {
        members: [
          {
            name: "Emily Thompson",
            status: "Active",
            joinDate: "2024-12-15",
            lastPayment: "2025-02-01",
            amount: "$99.00",
          },
          {
            name: "Michael Roberts",
            status: "Active",
            joinDate: "2024-11-20",
            lastPayment: "2025-01-20",
            amount: "$99.00",
          },
          {
            name: "Sarah Anderson",
            status: "Pending",
            joinDate: "2025-01-05",
            lastPayment: "2025-02-05",
            amount: "$79.00",
          },
          {
            name: "James Wilson",
            status: "Active",
            joinDate: "2024-10-30",
            lastPayment: "2025-01-30",
            amount: "$129.00",
          },
          {
            name: "Alexandra Davis",
            status: "Expired",
            joinDate: "2024-09-15",
            lastPayment: "2024-12-15",
            amount: "$99.00",
          },
        ],
        membershipGrowth: {
          months: ["Sep", "Oct", "Nov", "Dec", "Jan", "Feb"],
          data: [1850, 2100, 2300, 2500, 2700, 2847],
        },
        revenueDistribution: {
          categories: ["Memberships", "Events", "Donations", "Other"],
          data: [65, 20, 10, 5],
        },
      };

      function initCharts() {
        const membershipChart = echarts.init(
          document.getElementById("membershipChart"),
        );
        const revenueChart = echarts.init(document.getElementById("revenueChart"));

        membershipChart.setOption({
          animation: false,
          tooltip: {
            trigger: "axis",
            backgroundColor: "rgba(255, 255, 255, 0.9)",
            borderColor: "#e5e7eb",
            textStyle: { color: "#1f2937" },
          },
          grid: { top: 10, right: 10, bottom: 20, left: 40 },
          xAxis: {
            type: "category",
            data: mockData.membershipGrowth.months,
            axisLine: { lineStyle: { color: "#e5e7eb" } },
          },
          yAxis: {
            type: "value",
            axisLine: { show: false },
            splitLine: { lineStyle: { color: "#e5e7eb" } },
          },
          series: [
            {
              data: mockData.membershipGrowth.data,
              type: "line",
              smooth: true,
              symbol: "none",
              lineStyle: { color: "rgba(87, 181, 231, 1)" },
              areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  {
                    offset: 0,
                    color: "rgba(87, 181, 231, 0.1)",
                  },
                  {
                    offset: 1,
                    color: "rgba(87, 181, 231, 0)",
                  },
                ]),
              },
            },
          ],
        });
        revenueChart.setOption({
          animation: false,
          tooltip: {
            trigger: "item",
            backgroundColor: "rgba(255, 255, 255, 0.9)",
            borderColor: "#e5e7eb",
            textStyle: { color: "#1f2937" },
          },
          series: [
            {
              type: "pie",
              radius: "70%",
              data: mockData.revenueDistribution.categories.map(
                (category, index) => ({
                  value: mockData.revenueDistribution.data[index],
                  name: category,
                }),
              ),
              label: {
                formatter: "{b}: {d}%",
              },
              itemStyle: {
                borderRadius: 4,
              },
              color: [
                "rgba(87, 181, 231, 1)",
                "rgba(141, 211, 199, 1)",
                "rgba(251, 191, 114, 1)",
                "rgba(252, 141, 98, 1)",
              ],
            },
          ],
        });

        window.addEventListener("resize", () => {
          membershipChart.resize();
          revenueChart.resize();
        });
      }

      function populateTable() {
        const tableBody = document.getElementById("reportTable");
        tableBody.innerHTML = mockData.members
          .map(
            (member) => `
                      <tr class="hover:bg-gray-50">
                          <td class="px-4 py-3 text-sm">${member.name}</td>
                          <td class="px-4 py-3 text-sm">
                              <span class="px-2 py-1 rounded-full text-xs font-medium ${
                                member.status === "Active"
                                  ? "bg-green-100 text-green-800"
                                  : member.status === "Pending"
                                    ? "bg-yellow-100 text-yellow-800"
                                    : "bg-red-100 text-red-800"
                              }">${member.status}</span>
                          </td>
                          <td class="px-4 py-3 text-sm">${member.joinDate}</td>
                          <td class="px-4 py-3 text-sm">${member.lastPayment}</td>
                          <td class="px-4 py-3 text-sm">${member.amount}</td>
                      </tr>
                  `,
          )
          .join("");
      }
      document.addEventListener("DOMContentLoaded", () => {
        initCharts();
        populateTable();

        const statusFilter = document.getElementById("statusFilter");
        const statusDropdown = document.getElementById("statusDropdown");

        statusFilter.addEventListener("click", () => {
          statusDropdown.classList.toggle("hidden");
        });

        document.addEventListener("click", (e) => {
          if (!statusFilter.contains(e.target)) {
            statusDropdown.classList.add("hidden");
          }
        });

        const tabs = ["membershipTab", "paymentsTab", "fundraisingTab"];
        tabs.forEach((tabId) => {
          document.getElementById(tabId).addEventListener("click", (e) => {
            tabs.forEach((id) => {
              const tab = document.getElementById(id);
              if (id === tabId) {
                tab.classList.add("bg-white", "shadow");
                tab.classList.remove("text-gray-600");
              } else {
                tab.classList.remove("bg-white", "shadow");
                tab.classList.add("text-gray-600");
              }
            });
          });
        });

        document.getElementById("generateReport").addEventListener("click", () => {
          const notification = document.createElement("div");
          notification.className =
            "fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2";
          notification.innerHTML = `
                          <i class="ri-checkbox-circle-line"></i>
                          <span>Report generated successfully!</span>
                      `;
          document.body.appendChild(notification);
          setTimeout(() => notification.remove(), 3000);
        });
      });
    </script>
  </body>
</html>
