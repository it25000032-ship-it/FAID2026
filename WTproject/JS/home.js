
const registerButton = document.getElementById("registerButton");
const loginButton = document.getElementById("loginButton");

const registerModal = document.getElementById("registerM");
const loginModal = document.getElementById("loginM");

registerButton.addEventListener("click", function () {
    registerModal.style.display = "flex";
});


loginButton.addEventListener("click", function () {
    loginModal.style.display = "flex";
});


const closeButtons = document.querySelectorAll(".close-modal");

closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
        registerModal.style.display = "none";
        loginModal.style.display = "none";
    });
});

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


document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        registerModal.style.display = "none";
        loginModal.style.display = "none";
    }
});



const searchForm = document.getElementById("searchForm");
const searchInput=document.getElementById("searchInput");

searchForm.addEventListener("submit", function (event) {
  event.preventDefault();

 const searchText = searchInput.value.trim();

  if (searchText === "") {
        alert("Please enter a product name.");
        return;
    }

  console.log("Search for:", searchText);
});


