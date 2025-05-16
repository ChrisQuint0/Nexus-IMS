document.addEventListener("DOMContentLoaded", function () {
  loadSidebarContent().then(() => {
    initializeSidebar();
  });
});

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
        <!-- Your sidebar content here (copy from side_bar_nav.html) -->
        <div class="hide">
          <button class="hide-btn">&times;</button>
        </div>
        <!-- Rest of your sidebar structure -->
      </div>
    `;
  }
}

function initializeSidebar() {
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
          const oldestArrow = oldestOpenDropdown
            .previousElementSibling?.querySelector(".dropdown-arrow");
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