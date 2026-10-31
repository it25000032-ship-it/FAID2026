
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
const materialsContainer = document.querySelector(".product-container");
console.log(materialsContainer);
function displayMaterials(materials) {
       materialsContainer.querySelectorAll(".dynamic-material-card").forEach(card => card.remove());
       materials.forEach(material => {
       const card=document.createElement("div");
       card.classList.add("product-card","dynamic-material-card");
           card.innerHTML=`
           <h3>${material.material_name}</h3>
           <p>${material.description}</p>
           <p>Category: ${material.category}</p>
           <p>Price: $${material.price}</p>
           <p>Quantity: ${material.quantity}</p>
           <button class="buy-now">Request Material</button>`;
           materialsContainer.appendChild(card); });
}
function getMaterials() {
     fetch("FaidProject/getMaterials.php")
       .then(response => response.json())
       .then(data =>{
               console.log(" materials:", data);
               displayMaterials(data); })
       .catch(error => {
           console.error("Error fetching materials:", error);
           });
}
 getMaterials()
const addMaterialButton = document.getElementById("addMaterialBtn");
const addModal = document.getElementById("addMaterialModal");
const addCloseButton = document.getElementById("closeAddModel");
addMaterialButton.addEventListener("click", function () {
    addModal.style.display = "flex";
});
addCloseButton.addEventListener("click", function () {
    addModal.style.display = "none";
});
const addMaterialForm = document.getElementById("addMaterialForm");
addMaterialForm.addEventListener("submit", function (e) {
          e.preventDefault();
          const formData = new FormData(addMaterialForm);
          fetch("FaidProject/addMaterial.php", {
                method: "POST",
                body:formData 
          })
               .then(response => response.json())
               .then(data =>{
                    alert(data.message);
                    if(data.success) {
                      addMaterialForm.reset();
                      addModal.style.display = "none";
                      getMaterials();
                    }
         })
        .catch(error => {
        console.error("Error adding material:", error);
        alert("Error adding material. Please try again.");
        });
});