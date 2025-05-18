document.addEventListener("DOMContentLoaded", function () {
  loadInitialData();

  const welcomeMessage = document.getElementById("welcome-message");

  fetch("../php/get_user_info.php", {
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        window.location.href = "../pages/login.html";
        return;
      }
      if (data.userType && data.username) {
        welcomeMessage.textContent = `Welcome ${data.username}!`;
      }
    })
    .catch((error) => {
      console.error("Error checking session:", error);
      window.location.href = "../pages/login.html";
    });
});

function loadInitialData(filter = "all") {
  fetch("../php/statistics.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=loadInitialData&filter=${filter}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error loading initial data:", data.error);
        // Handle error
        return;
      }
      populateDashboard(data);
      setupFilterListener();
    })
    .catch((error) => {
      console.error("Fetch error:", error);
      // Handle fetch error
    });
}

function populateDashboard(data) {
  document.getElementById("available-stat").textContent = data.available;
  document.getElementById("claimed-stat").textContent = data.claimed;
  document.getElementById("brand-new-stat").textContent = data.brand_new;
  document.getElementById("repair-stat").textContent = data.for_repair;
  document.getElementById("defective-stat").textContent = data.defective;
  document.getElementById("unrecoverable-stat").textContent =
    data.unrecoverable; // Set unrecoverable stat

  const itemSelection = document.getElementById("item-selection");
  itemSelection.innerHTML = data.dropdownOptions;

  const pieContainer = document.getElementById("pie");
  if (pieContainer) {
    createD3PieChart(pieContainer, data.statusCounts); // Pass data here
  }
  const barContainer = document.getElementById("bar");
  if (barContainer) {
    createBarChart(barContainer, data.statusCounts); // Pass data here
  }

  // Adjust sidebar height
  const sidebar = document.querySelector(".sidenav");
  const mainContent = document.querySelector(".main-content");
  if (sidebar && mainContent) {
    sidebar.style.minHeight = `${mainContent.offsetHeight}px`;
    sidebar.style.height = "auto";
  }
}

function setupFilterListener() {
  const selectElement = document.getElementById("item-selection");
  selectElement.addEventListener("change", function (event) {
    const selectedValue = event.target.value;
    loadInitialData(selectedValue); // Reload data with the new filter
  });
}

// Implementation using D3 (more customizable)
function createD3PieChart(container, statusCounts) {
  // Accept statusCounts as a parameter
  // Data for the pie chart
  const pieData = [
    { category: "Available", value: parseInt(statusCounts[1] || 0) },
    { category: "Claimed", value: parseInt(statusCounts[3] || 0) },
    { category: "Brand New", value: parseInt(statusCounts[2] || 0) },
    { category: "For Repair", value: parseInt(statusCounts[4] || 0) },
    { category: "Defective", value: parseInt(statusCounts[5] || 0) },
    { category: "Unrecoverable", value: parseInt(statusCounts[9] || 0) }, // Add unrecoverable
    { category: "Lost", value: parseInt(statusCounts[11] || 0) }, // Add lost
  ];

  // Clear any existing content
  container.innerHTML = "";

  // Set up dimensions
  const width = 700;
  const height = 400;
  const radius = Math.min(width, height) / 2;

  // Create SVG
  const svg = d3
    .select(container)
    .append("svg")
    .attr("width", width)
    .attr("height", height)
    .append("g")
    .attr("transform", `translate(${width / 3},${height / 2})`);

  // Set up colors
  const color = d3
    .scaleOrdinal()
    .domain(pieData.map((d) => d.category)) // Use pieData here
    .range([
      "#003f5c",
      "#2f4b7c",
      "#665191",
      "#a05195",
      "#d45087",
      "#f95d6a",
      "#ff7c43", // Add color for unrecoverable
      "#ffa600", // Add color for lost
    ]);

  // Create pie chart
  const pie = d3.pie().value((d) => d.value);

  const arc = d3
    .arc()
    .innerRadius(0)
    .outerRadius(radius * 0.8);

  // For label positioning
  const labelArc = d3
    .arc()
    .innerRadius(radius * 0.4)
    .outerRadius(radius * 1.7);

  // Add arcs
  const arcs = svg.selectAll("arc").data(pie(pieData)).enter().append("g"); // Use pieData

  arcs
    .append("path")
    .attr("d", arc)
    .attr("fill", (d) => color(d.data.category))
    .attr("stroke", "white")
    .style("stroke-width", "2px")
    .style("opacity", 0.8)
    .on("mouseover", function () {
      d3.select(this).style("opacity", 1);
    })
    .on("mouseout", function () {
      d3.select(this).style("opacity", 0.8);
    });

  // Add labels
  arcs
    .append("text")
    .attr("transform", (d) => `translate(${labelArc.centroid(d)})`)
    .attr("text-anchor", "middle")
    .text((d) => (d.data.value > 20 ? d.data.category : ""))
    .style("font-size", "12px")
    .style("fill", "black")
    .style("font-weight", "bold");

  // Add title with filter information (assuming currentFilter is still globally available or you pass it)
  svg
    .append("text")
    .attr("x", -120)
    .attr("y", -180)
    .attr("text-anchor", "start")
    .style("font-size", "18px")
    .style("font-weight", "bold")
    .text(() => {
      if (typeof currentFilter !== "undefined") {
        return currentFilter === "all"
          ? "All Items"
          : `Filtered by: ${
              currentFilter.charAt(0).toUpperCase() + currentFilter.slice(1)
            }`;
      }
      return "Inventory Status";
    });

  // Add legend
  const legend = svg
    .selectAll(".legend")
    .data(pieData) // Use pieData
    .enter()
    .append("g")
    .attr("class", "legend")
    .attr("transform", (d, i) => `translate(250,${i * 20 - 100})`);

  legend
    .append("rect")
    .attr("width", 18)
    .attr("height", 18)
    .attr("fill", (d) => color(d.category));

  legend
    .append("text")
    .attr("x", 20)
    .attr("y", 10)
    .text((d) => `${d.category}: ${d.value}`)
    .style("font-size", "17px");
}

// Implementation using D3 bar chart
function createBarChart(container, statusCounts) {
  // Accept statusCounts as a parameter
  // Data for the bar chart
  const barData = [
    { category: "Available", value: parseInt(statusCounts[1] || 0) },
    { category: "Claimed", value: parseInt(statusCounts[3] || 0) },
    { category: "Brand New", value: parseInt(statusCounts[2] || 0) },
    { category: "For Repair", value: parseInt(statusCounts[4] || 0) },
    { category: "Defective", value: parseInt(statusCounts[5] || 0) },
    { category: "Unrecoverable", value: parseInt(statusCounts[9] || 0) }, // Add unrecoverable
    { category: "Lost", value: parseInt(statusCounts[11] || 0) }, // Add lost
  ];

  // Sort data from highest to lowest
  barData.sort((a, b) => b.value - a.value);

  // Clear any existing content
  container.innerHTML = "";

  // Set up dimensions
  const margin = { top: 30, right: 30, bottom: 70, left: 80 };
  const width = 600 - margin.left - margin.right;
  const height = 400 - margin.top - margin.bottom;

  // Create SVG
  const svg = d3
    .select(container)
    .append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
    .append("g")
    .attr("transform", `translate(${margin.left},${margin.top})`);

  // Add title with filter information (assuming currentFilter is still globally available or you pass it)
  svg
    .append("text")
    .attr("x", width / 2)
    .attr("y", -10)
    .attr("text-anchor", "middle")
    .style("font-size", "16px")
    .style("font-weight", "bold")
    .text(() => {
      if (typeof currentFilter !== "undefined") {
        return currentFilter === "all"
          ? "All Items"
          : `Filtered by: ${
              currentFilter.charAt(0).toUpperCase() + currentFilter.slice(1)
            }`;
      }
      return "";
    });

  // X axis
  const x = d3
    .scaleBand()
    .range([0, width])
    .domain(barData.map((d) => d.category))
    .padding(0.2);

  svg
    .append("g")
    .attr("transform", `translate(0,${height})`)
    .call(d3.axisBottom(x))
    .style("font-size", "14px")
    .selectAll("text")
    .attr("transform", "translate(-10,0)rotate(-45)")
    .style("text-anchor", "end");

  // Y axis
  const y = d3
    .scaleLinear()
    .domain([0, d3.max(barData, (d) => d.value) * 1.1])
    .range([height, 0]);

  svg.append("g").call(d3.axisLeft(y));

  // Add Y axis label
  svg
    .append("text")
    .attr("text-anchor", "end")
    .attr("transform", "rotate(-90)")
    .attr("y", -margin.left + 30)
    .attr("x", -height / 2)
    .style("font-size", "20px")
    .text("Quantity");

  // Set bar colors
  const color = d3
    .scaleOrdinal()
    .domain(barData.map((d) => d.category))
    .range([
      "#FF6B6B",
      "#4ECDC4",
      "#1A535C",
      "#FF9F1C",
      "#2EC4B6",
      "#E71D36",
      "#c77dff", // Add color for unrecoverable
      "#7400b8", // Add color for lost
    ]);

  // Add bars
  svg
    .selectAll("mybar")
    .data(barData)
    .enter()
    .append("rect")
    .attr("x", (d) => x(d.category))
    .attr("y", (d) => y(d.value))
    .attr("width", x.bandwidth())
    .attr("height", (d) => height - y(d.value))
    .attr("fill", (d) => color(d.category))
    .attr("stroke", "white")
    .attr("stroke-width", 1)
    .style("opacity", 0.8)
    .on("mouseover", function (event, d) {
      d3.select(this).style("opacity", 1);

      // Show tooltip with value
      svg
        .append("text")
        .attr("id", "tooltip")
        .attr("x", x(d.category) + x.bandwidth() / 2)
        .attr("y", y(d.value) - 5)
        .attr("text-anchor", "middle")
        .text(d.value)
        .style("font-size", "28px")
        .style("font-weight", "500");
    })
    .on("mouseout", function () {
      d3.select(this).style("opacity", 0.8);

      // Remove tooltip
      svg.select("#tooltip").remove();
    });
}
