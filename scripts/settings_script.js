document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const darkToggle = document.getElementById("darkToggle");
  const themeSelect = document.getElementById("themeSelect");
  const fontSizeSlider = document.querySelector(
    '.font-size-slider input[type="range"]'
  );
  const keyboardToggle = document.querySelectorAll('input[type="checkbox"]')[1]; // second checkbox

  // Dark mode
  if (localStorage.getItem("darkMode") === "enabled") {
    body.classList.add("dark");
    if (darkToggle) darkToggle.checked = true;
  }

  // Theme
  const savedTheme = localStorage.getItem("theme");
  if (savedTheme) {
    body.classList.add(savedTheme);
    if (themeSelect) themeSelect.value = savedTheme;
  }

  // Dark mode
  if (darkToggle) {
    darkToggle.addEventListener("change", () => {
      const enabled = darkToggle.checked;
      body.classList.toggle("dark", enabled);
      localStorage.setItem("darkMode", enabled ? "enabled" : "disabled");
    });
  }

  // Theme dropdown
  if (themeSelect) {
    themeSelect.addEventListener("change", () => {
      body.classList.remove("forest", "lava", "corporate");

      const selected = themeSelect.value;
      if (selected !== "default") {
        body.classList.add(selected);
        localStorage.setItem("theme", selected);
      } else {
        localStorage.removeItem("theme");
      }
    });
  }

  // Font size
  if (fontSizeSlider) {
    fontSizeSlider.addEventListener("input", (e) => {
      document.body.style.fontSize = `${e.target.value}px`;
    });
  }

  // Keyboard navigation
  if (keyboardToggle) {
    keyboardToggle.addEventListener("change", () => {
      body.classList.toggle("keyboard-nav", keyboardToggle.checked);
    });
  }
});
