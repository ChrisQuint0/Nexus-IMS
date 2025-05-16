document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("login-form");
  const emailInput = document.getElementById("login-email");
  const passwordInput = document.getElementById("login-pass");
  const errorMessageDiv = document.getElementById("error-message");
  const loginBtn = document.getElementById("login-btn"); // Assuming you have a button with this ID

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  const showError = (message) => {
    if (errorMessageDiv) {
      errorMessageDiv.textContent = message;
      errorMessageDiv.style.display = "block";
    } else {
      console.error("Error element not found:", message);
    }
  };

  const handleLogin = (event) => {
    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();

    if (!email || !emailRegex.test(email)) {
      showError("Please enter a valid email address.");
      return;
    }

    if (!password) {
      showError("Password is required.");
      return;
    }

    if (loginBtn) {
      loginBtn.disabled = true;
    }

    fetch("../php/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        email: email,
        password: password,
      }),
      credentials: "include",
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((err) => {
            throw err;
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          window.location.href = data.redirect || "../pages/dashboard.html"; // Use redirect from backend if provided
        } else {
          showError(data.error || data.message || "Invalid login credentials.");
        }
      })
      .catch((error) => {
        console.error("Login error:", error);
        showError(error.error || "An unexpected error occurred.");
      })
      .finally(() => {
        if (loginBtn) {
          loginBtn.disabled = false;
        }
      });
  };

  loginForm.addEventListener("submit", handleLogin);
});

function showPass() {
  const passwordField = document.getElementById("login-pass");
  passwordField.type = passwordField.type === "password" ? "text" : "password";
}
