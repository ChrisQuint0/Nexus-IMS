document
  .getElementById("reset-pass-form")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    console.log(
      "Submitting with token:",
      document.getElementById("token-field").value
    );

    const formData = new FormData(this);

    fetch("../php/reset_pass.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          window.location.href =
            data.redirect || "login.html?success=password_updated";
        } else if (data.error) {
          showError(data.error);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showError(error.message || "An error occurred. Please try again.");
      });
  });

function showError(message) {
  const errorElement = document.getElementById("error-message");
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = "block";
  }
}

window.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search);
  const token = urlParams.get("token");
  if (token) {
    document.getElementById("token-field").value = token;
  } else {
    showError("Missing or invalid token.");
  }
});
