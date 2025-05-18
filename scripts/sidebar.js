document.addEventListener("DOMContentLoaded", function () {
  loadSidebarContent().then(() => {
    getUserRole().then((userRole) => {
      initializeSidebar(userRole);
      applyRoleBasedSidebar(userRole);
    });
  });
});

async function getUserRole() {
  try {
    const response = await fetch("../php/get_user_role.php");
    if (!response.ok) {
      console.error("Failed to fetch user role:", response.status);
      return null;
    }
    const data = await response.json();
    return data.role; // Assuming the PHP returns {"role": "admin"} or {"role": "dept_head"}
  } catch (error) {
    console.error("Error fetching user role:", error);
    return null;
  }
}

async function loadSidebarContent() {
  const sidebarContainer = document.getElementById("sidebar-container");
  if (!sidebarContainer) return;

  try {
    if (!sidebarContainer.innerHTML.trim()) {
      const response = await fetch("../pages/side_bar_nav.html");
      if (!response.ok) {
        throw new Error("Failed to load sidebar");
      }
      const html = await response.text();
      sidebarContainer.innerHTML = html;
    }
  } catch (error) {
    console.error("Error loading sidebar:", error);
    sidebarContainer.innerHTML = `
            <button class="show-sidebar-btn"><b>></b></button>
            <div class="sidenav sidenav-hidden" id="sidenav">
                <div class="hide">
                    <button class="hide-btn">&times;</button>
                </div>
                <div class="logo-div">
                    <img src="../assets/images/nexus_logo.png" alt="Nexus Logo" />
                    <p>Nexus NIMS</p>
                </div>
                <div class="dashboard-div">
                    <img src="../assets/images/dashboard_icon.png" alt="" />
                    <div class="borrowers-dashboard"></div>
                    <p>Dashboard</p>
                </div>
                <div class="inventory-div">
                    <img src="../assets/images/inventory_logo.png" alt="" />
                    <p>Inventory</p>
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="inventory-dropdown">
                    <div class="inventory-claimed-option">
                        <p>Inventory - Claimed</p>
                    </div>
                    <div class="inventory-available-option">
                        <p>Inventory - Available</p>
                    </div>
                    <div class="inventory-brand-new-option">
                        <p>Inventory - Brand New</p>
                    </div>
                </div>
                <div class="repairables-div">
                    <img src="../assets/images/repair_icon.png" alt="" />
                    <div class="repairables-option"></div>
                    <p>Repairables</p>
                </div>
                <div class="logs-div">
                    <img src="../assets/images/reports_icon.png" alt="" />
                    <p>Reports</p>
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="logs-dropdown">
                    <div class="activity-logs-option">
                        <p>Activity Logs</p>
                    </div>
                    <div class="claims-option">
                        <p>Claims</p>
                    </div>
                    <div class="returns-option">
                        <p>Returns</p>
                    </div>
                    <div class="repairs-option">
                        <p>Repairs</p>
                    </div>
                    <div class="unrecoverables-option">
                        <p>Unrecoverables</p>
                    </div>
                </div>
                <div class="settings-div">
                    <img src="../assets/images/settings_icon.png" alt="" />
                    <p>Settings</p>
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="settings-dropdown">
                    <div class="settings-data_table">
                        <img src="../assets/images/data_table_icon.png" alt="" />
                        <p style="display: inline-block; margin-left: 10px">Data Tables</p>
                    </div>
                    <div class="settings-add_new_item">
                        <img src="../assets/images/add-new-item-img.png" alt="" />
                        <p style="display: inline-block; margin-left: 10px">Add New Item</p>
                    </div>
                    <div class="settings-add_info">
                        <img src="../assets/images/add-info-img.png" alt="" />
                        <p style="display: inline-block; margin-left: 10px">Add Info</p>
                    </div>
                    <div class="settings-claim_an_item">
                        <img src="../assets/images/claim_icon.png" alt="" />
                        <p style="display: inline-block; margin-left: 10px">Claim an Item</p>
                    </div>
                    <div class="settings-add_users">
                        <img src="../assets/images/add_user_icon.png" alt="" />
                        <p style="display: inline-block; margin-left: 10px">Add Users</p>
                    </div>
                    <div class="settings-upload_csv">
                        <img src="../assets/images/upload.png" alt="" />
                        <p style="display: inline-block; margin-left: 10px">Upload CSV</p>
                    </div>
                </div>
                <div class="sign-out-div">
                    <img src="../assets/images/sign_out.png" alt="" />
                    <div class="sign-out-option"></div>
                    <p>Sign Out</p>
                </div>
            </div>
        `;
  }
}

function initializeSidebar(userRole) {
  const showBtn = document.querySelector(".show-sidebar-btn");
  const hideBtn = document.querySelector(".hide-btn");

  if (showBtn) {
    showBtn.addEventListener("click", showSideNav);
  }

  if (hideBtn) {
    hideBtn.addEventListener("click", hideSideNav);
  }

  restoreDropdownStates();
  setupDropdowns();
  setupNavigation();
  highlightCurrentPage();
  initSidebarState();
}

function applyRoleBasedSidebar(userRole) {
  const dashboardDiv = document.querySelector(".dashboard-div");
  const inventoryDiv = document.querySelector(".inventory-div");
  const logsDiv = document.querySelector(".logs-div");
  const settingsDiv = document.querySelector(".settings-div");

  const inventoryDropdown = document.querySelector(".inventory-dropdown");
  const activityLogsOption = document.querySelector(".activity-logs-option");
  const claimsOption = document.querySelector(".claims-option");
  const returnsOption = document.querySelector(".returns-option");
  const repairsOption = document.querySelector(".repairs-option");
  const unrecoverablesOption = document.querySelector(".unrecoverables-option");
  const settingsDropdown = document.querySelector(".settings-dropdown");

  if (userRole === "dept_head") {
    // Show Dashboard, Inventory, Repairables
    if (dashboardDiv) dashboardDiv.style.display = "flex";
    if (inventoryDiv) inventoryDiv.style.display = "flex";
    const repairablesDiv = document.querySelector(".repairables-div");
    if (repairablesDiv) repairablesDiv.style.display = "flex";

    // Show Inventory dropdown and its options
    if (inventoryDropdown) inventoryDropdown.style.display = "block";

    // Modify Reports dropdown
    if (logsDiv) {
      const logsDropdownEl = document.querySelector(".logs-dropdown");
      if (logsDropdownEl) {
        if (returnsOption) returnsOption.style.display = "block";
        if (repairsOption) repairsOption.style.display = "block";
        if (unrecoverablesOption) unrecoverablesOption.style.display = "block"; // Show Unrecoverables
        if (activityLogsOption) activityLogsOption.style.display = "none";
        if (claimsOption) claimsOption.style.display = "none"; // Always hide Claims for dept_head
        logsDropdownEl.classList.add("show"); // Initially show the reports dropdown
        const logsToggle = logsDiv.querySelector(".dropdown-arrow");
        if (logsToggle) logsToggle.classList.add("rotate");
        localStorage.setItem("logsMenuOpen", "true"); // Ensure it stays open
      }
    }

    // Hide Settings dropdown
    if (settingsDiv) settingsDiv.style.display = "none";
    if (settingsDropdown) settingsDropdown.style.display = "none";
  } else if (userRole === "admin") {
    // For admin, ensure everything is visible except Claims
    if (dashboardDiv) dashboardDiv.style.display = "flex";
    if (inventoryDiv) inventoryDiv.style.display = "flex";
    const repairablesDiv = document.querySelector(".repairables-div");
    if (repairablesDiv) repairablesDiv.style.display = "flex";
    if (logsDiv) {
      const logsDropdownEl = document.querySelector(".logs-dropdown");
      if (logsDropdownEl) {
        if (returnsOption) returnsOption.style.display = "block";
        if (repairsOption) repairsOption.style.display = "block";
        if (unrecoverablesOption) unrecoverablesOption.style.display = "block";
        if (activityLogsOption) activityLogsOption.style.display = "block";
        if (claimsOption) claimsOption.style.display = "none"; // Always hide Claims for admin as well
      }
    }
    if (settingsDiv) settingsDiv.style.display = "flex";
    if (settingsDropdown) settingsDropdown.style.display = "block";
    if (inventoryDropdown) inventoryDropdown.style.display = "block"; // Ensure inventory dropdown is visible for admin
  }
  // Add a default case or handle other roles if necessary
}

function setupDropdowns() {
  const dropdowns = [
    {
      toggle: ".inventory-div",
      menu: ".inventory-dropdown",
      storageKey: "inventoryMenuOpen",
    },
    {
      toggle: ".logs-div",
      menu: ".logs-dropdown",
      storageKey: "logsMenuOpen",
    },
    {
      toggle: ".settings-div",
      menu: ".settings-dropdown",
      storageKey: "settingsMenuOpen",
    },
  ];

  dropdowns.forEach(({ toggle, menu, storageKey }) => {
    const toggleEl = document.querySelector(toggle);
    const menuEl = document.querySelector(menu);

    if (!toggleEl || !menuEl) return;

    toggleEl.addEventListener("click", (e) => {
      e.stopPropagation();
      const isOpening = !menuEl.classList.contains("show");

      if (isOpening) {
        const openDropdowns = document.querySelectorAll(".show");
        if (openDropdowns.length >= 2) {
          const oldestOpenDropdown = openDropdowns[0];
          oldestOpenDropdown.classList.remove("show");
          const oldestArrow =
            oldestOpenDropdown.previousElementSibling?.querySelector(
              ".dropdown-arrow"
            );
          if (oldestArrow) oldestArrow.classList.remove("rotate");

          const closedDropdown = dropdowns.find(
            ({ menu }) => menu === `.${oldestOpenDropdown.classList[0]}`
          );
          if (closedDropdown) {
            localStorage.setItem(closedDropdown.storageKey, "false");
          }
        }
      }

      menuEl.classList.toggle("show");
      const arrow = toggleEl.querySelector(".dropdown-arrow");
      if (arrow) arrow.classList.toggle("rotate");

      localStorage.setItem(storageKey, menuEl.classList.contains("show"));
    });
  });

  document.addEventListener("click", (e) => {
    if (
      !e.target.closest(".inventory-div, .logs-div, .settings-div") &&
      !e.target.closest(
        ".inventory-dropdown, .logs-dropdown, .settings-dropdown"
      )
    ) {
      saveDropdownStatesBeforeClosing();
      closeAllDropdowns();
    }
  });
}

function saveDropdownStatesBeforeClosing() {
  const dropdowns = [
    { menu: ".inventory-dropdown", storageKey: "inventoryMenuOpen" },
    { menu: ".logs-dropdown", storageKey: "logsMenuOpen" },
    { menu: ".settings-dropdown", storageKey: "settingsMenuOpen" },
  ];

  dropdowns.forEach(({ menu, storageKey }) => {
    const menuEl = document.querySelector(menu);
    if (menuEl) {
      localStorage.setItem(storageKey, menuEl.classList.contains("show"));
    }
  });
}

function restoreDropdownStates() {
  const dropdownStates = [
    {
      key: "inventoryMenuOpen",
      menu: ".inventory-dropdown",
      toggle: ".inventory-div",
    },
    { key: "logsMenuOpen", menu: ".logs-dropdown", toggle: ".logs-div" },
    {
      key: "settingsMenuOpen",
      menu: ".settings-dropdown",
      toggle: ".settings-div",
    },
  ];

  dropdownStates.forEach(({ key, menu, toggle }) => {
    if (localStorage.getItem(key) === "true") {
      const menuEl = document.querySelector(menu);
      const toggleEl = document.querySelector(toggle);

      if (menuEl) menuEl.classList.add("show");
      if (toggleEl) {
        const arrow = toggleEl.querySelector(".dropdown-arrow");
        if (arrow) arrow.classList.add("rotate");
      }
    }
  });
}

function closeAllDropdowns() {
  document
    .querySelectorAll(".inventory-dropdown, .logs-dropdown, .settings-dropdown")
    .forEach((menu) => {
      menu.classList.remove("show");
    });

  document.querySelectorAll(".dropdown-arrow").forEach((arrow) => {
    arrow.classList.remove("rotate");
  });
}

function setupNavigation() {
  const navItems = {
    ".dashboard-div": "../pages/dashboard.html",
    ".inventory-claimed-option": "../pages/claimed.html",
    ".inventory-available-option": "../pages/available.html",
    ".inventory-brand-new-option": "../pages/brand_new.html",
    ".repairables-div": "../pages/repairables.html",
    ".activity-logs-option": "../pages/activity_logs.html",
    ".claims-option": "../pages/claims.html",
    ".returns-option": "../pages/returns.html",
    ".repairs-option": "../pages/repairs.html",
    ".unrecoverables-option": "../pages/unrecoverables.html",
    ".settings-data_table": "../pages/data_tables.html",
    ".settings-add_new_item": "../pages/add_item_equipment.html",
    ".settings-add_info": "../pages/add_info.html",
    ".settings-claim_an_item": "../pages/add_borrower.html",
    ".settings-add_users": "../pages/new_user.html",
    ".settings-upload_csv": "../pages/upload_csv.html",
    ".sign-out-div": "../pages/login.html",
  };

  Object.entries(navItems).forEach(([selector, url]) => {
    const element = document.querySelector(selector);
    if (element) {
      element.addEventListener("click", (e) => {
        saveDropdownStatesBeforeClosing();
        window.location.href = url;
      });
    }
  });
}

function showSideNav() {
  const sidenav = document.getElementById("sidenav");
  const showBtn = document.querySelector(".show-sidebar-btn");

  if (sidenav) {
    sidenav.classList.remove("sidenav-hidden");
  }

  if (showBtn) {
    showBtn.style.display = "none";
  }

  document.body.classList.add("sidebar-visible");
  localStorage.setItem("sidebarVisible", "true");
}

function hideSideNav() {
  const sidenav = document.getElementById("sidenav");
  const showBtn = document.querySelector(".show-sidebar-btn");

  if (sidenav) {
    sidenav.classList.add("sidenav-hidden");
  }

  if (showBtn) {
    showBtn.style.display = "block";
  }

  document.body.classList.remove("sidebar-visible");
  localStorage.setItem("sidebarVisible", "false");
}

function highlightCurrentPage() {
  const currentPage = window.location.pathname.split("/").pop();
  if (!currentPage) return;

  const pageMap = {
    "dashboard.html": ".dashboard-div",
    "claimed.html": ".inventory-claimed-option",
    "available.html": ".inventory-available-option",
    "brand_new.html": ".inventory-brand-new-option",
    "repairables.html": ".repairables-div",
    "activity_logs.html": ".activity-logs-option",
    "claims.html": ".claims-option",
    "returns.html": ".returns-option",
    "repairs.html": ".repairs-option",
    "unrecoverables.html": ".unrecoverables-option",
    "data_tables.html": ".settings-data_table",
    "add_item_equipment.html": ".settings-add_new_item",
    "add_info.html": ".settings-add_info",
    "add_borrower.html": ".settings-claim_an_item",
    "new_user.html": ".settings-add_users",
    "upload_csv.html": ".settings-upload_csv",
  };

  const selector = pageMap[currentPage];
  if (!selector) return;

  const activeItem = document.querySelector(selector);
  if (!activeItem) return;

  activeItem.classList.add("active-menu-item");

  const parentDropdown = activeItem.closest(
    ".inventory-dropdown, .logs-dropdown, .settings-dropdown"
  );
  if (parentDropdown) {
    parentDropdown.classList.add("show");
    const parentToggle = parentDropdown.previousElementSibling;
    const arrow = parentToggle?.querySelector(".dropdown-arrow");
    if (arrow) arrow.classList.add("rotate");
  }
}

function initSidebarState() {
  if (localStorage.getItem("sidebarVisible") === "false") {
    hideSideNav();
  } else {
    showSideNav();
  }
}
