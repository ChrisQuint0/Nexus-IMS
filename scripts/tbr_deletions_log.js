const showPopupBtn = document.getElementById("scan-btn");
const overlayContainer = document.getElementById("overlayContainer");
const closePopupBtn = document.getElementById("closePopupBtn");
const popup = document.querySelector(".popup");

// Scanner Module
let html5QrcodeScanner = null;

function startScanner() {
  const config = {
    fps: 10,
    qrbox: { width: 250, height: 250 },
  };

  html5QrcodeScanner = new Html5QrcodeScanner(
    "reader",
    config,
    /* verbose= */ false
  );

  html5QrcodeScanner.render(function (decodedText, decodedResult) {
    console.log(`Scan result: ${decodedText}`, decodedResult);
    document.getElementById("result").innerHTML = `Scanned: ${decodedText}`;
  });
}

// Function to show the popup
function showPopup() {
  overlayContainer.classList.add("active");
  startScanner();
}

// Function to hide the popup
function hidePopup() {
  overlayContainer.classList.remove("active");
  stopScanner();
}

// Event listeners for main popup
showPopupBtn.addEventListener("click", function (event) {
  event.preventDefault(); // Prevent form submission
  event.stopPropagation(); // Stop event bubbling
  showPopup();
});

closePopupBtn.addEventListener("click", function (event) {
  event.preventDefault(); // Prevent form submission
  event.stopPropagation(); // Stop event bubbling
  hidePopup();
});

// Close popup when clicking outside the popup content
overlayContainer.addEventListener("click", (event) => {
  if (event.target === overlayContainer) {
    hidePopup();
  }
});
