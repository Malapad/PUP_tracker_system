document.addEventListener("DOMContentLoaded", () => {
  const courseFilter = document.getElementById("courseFilter");
  const yearFilter = document.getElementById("yearFilter");
  const periodFilter = document.getElementById("datePeriod");
  const loadingOverlay = document.getElementById("loadingOverlay");

  let violationChartInstance = null;

  const renderEmptyState = (containerId, message) => {
    const container = document.getElementById(containerId);
    if (container)
      container.innerHTML = `<div class="empty-state">${message}</div>`;
  };

  const populateFilters = async () => {
    try {
      const [coursesRes, yearsRes] = await Promise.all([
        fetch("security_dashboard.php?action=get_courses"),
        fetch("security_dashboard.php?action=get_years"),
      ]);
      const coursesData = await coursesRes.json();
      const yearsData = await yearsRes.json();

      if (coursesData && coursesData.data) {
        coursesData.data.forEach((c) =>
          courseFilter.add(new Option(c.course_name, c.course_id))
        );
      }
      if (yearsData && yearsData.data) {
        yearsData.data.forEach((y) => {
          const suffix =
            new Map([
              [1, "st"],
              [2, "nd"],
              [3, "rd"],
            ]).get(parseInt(y.year)) || "th";
          yearFilter.add(new Option(`${y.year}${suffix} Year`, y.year_id));
        });
      }
    } catch (error) {
      console.error("Error populating filters:", error);
    }
  };

  const updateDashboard = async () => {
    loadingOverlay.style.display = "flex";
    if (violationChartInstance) violationChartInstance.destroy();
    document.getElementById("violationInsight").textContent = "";

    const url = `security_dashboard.php?action=get_dashboard_data&course=${courseFilter.value}&year=${yearFilter.value}&period=${periodFilter.value}`;

    try {
      const response = await fetch(url);
      const result = await response.json();
      if (result.error || !result.data)
        throw new Error(result.error || "No data received");
      const data = result.data;

      if (data.violation && data.violation.labels.length > 0) {
        document.getElementById("violationChartContainer").innerHTML =
          '<canvas id="violationChart"></canvas>';

        const topViolationLabel = data.violation.labels[0];
        const topViolationCount = data.violation.data[0];
        const totalViolations = data.violation.data.reduce((a, b) => a + b, 0);
        const topPercentage =
          totalViolations > 0
            ? ((topViolationCount / totalViolations) * 100).toFixed(0)
            : 0;
        document.getElementById(
          "violationInsight"
        ).textContent = `'${topViolationLabel}' is the most common issue, making up ${topPercentage}% of cases.`;

        violationChartInstance = new Chart(
          document.getElementById("violationChart"),
          {
            type: "bar",
            data: {
              labels: data.violation.labels,
              datasets: [
                {
                  label: "Violations",
                  data: data.violation.data,
                  backgroundColor: (c) =>
                    c.dataIndex === 0 ? "#FFC425" : "#BE123C",
                  borderRadius: 4,
                  hoverBackgroundColor: (c) =>
                    c.dataIndex === 0 ? "#FBBF24" : "#D63353",
                },
              ],
            },
            options: {
              indexAxis: "y",
              responsive: true,
              maintainAspectRatio: false,
              animation: { duration: 1200, easing: "easeInOutQuart" },
              onHover: (e, el) => {
                e.native.target.style.cursor = el[0] ? "pointer" : "default";
              },
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: "#600000",
                  padding: 10,
                  cornerRadius: 5,
                  displayColors: false,
                  callbacks: {
                    title: () => null,
                    label: (c) => `${c.label}: ${c.raw}`,
                  },
                },
              },
              scales: {
                x: { grid: { display: false }, ticks: { precision: 0 } },
                y: { grid: { display: false } },
              },
            },
          }
        );
      } else {
        renderEmptyState(
          "violationChartContainer",
          "No Violation Data Available"
        );
      }
    } catch (error) {
      console.error("Error updating dashboard:", error);
      renderEmptyState("violationChartContainer", "Could not load data.");
    } finally {
      loadingOverlay.style.display = "none";
    }
  };

  [courseFilter, yearFilter, periodFilter].forEach((filter) => {
    filter.addEventListener("change", updateDashboard);
  });

  populateFilters().then(updateDashboard);
});