document
  .getElementById("request-email-form")
  .addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("../php/request_email.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (response.redirected) {
          window.location.href = response.url;
        } else {
          return response.text().then((text) => {
            showError("An error occurred: " + text);
          });
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showError("An error occurred. Please try again.");
      });
  });

function showError(message) {
  const errorElement = document.getElementById("error-message");
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = "block";
  }
}

// request_email.js
window.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  if (params.has("error")) {
    showError(decodeURIComponent(params.get("error")));
  }
  if (params.has("success")) {
    alert("Password reset email sent! Please check your inbox.");
  }
});
