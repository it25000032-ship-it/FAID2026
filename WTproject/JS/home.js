
const registerButton = document.getElementById("registerButton");
const loginButton = document.getElementById("loginButton");

const registerModal = document.getElementById("registerM");
const loginModal = document.getElementById("loginM");
console.log("Register button:", registerButton);
console.log("Login button:", loginButton);
console.log("Register modal:", registerModal);
console.log("Login modal:", loginModal);
console.log("JavaScript connected!");
// Open Register window
registerButton.addEventListener("click", function () {
    registerModal.style.display = "flex";
});

// Open Sign in window
loginButton.addEventListener("click", function () {
    loginModal.style.display = "flex";
});

// Close windows using the X buttons
const closeButtons = document.querySelectorAll(".close-modal");

closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
        registerModal.style.display = "none";
        loginModal.style.display = "none";
    });
});

// Close window by clicking the dark background
registerModal.addEventListener("click", function (event) {
    if (event.target === registerModal) {
        registerModal.style.display = "none";
    }
});

loginModal.addEventListener("click", function (event) {
    if (event.target === loginModal) {
        loginModal.style.display = "none";
    }
});

// Close window by pressing Escape
document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        registerModal.style.display = "none";
        loginModal.style.display = "none";
    }
});
console.log("JavaScript connected!");