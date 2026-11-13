// ===================== النوافذ المنبثقة =====================
const registerButton = document.getElementById("registerButton");
const loginButton = document.getElementById("loginButton");
const registerModal = document.getElementById("registerM");
const loginModal = document.getElementById("loginM");
const addMaterialButton = document.getElementById("addMaterialBtn");
const addModal = document.getElementById("addMaterialModal");

registerButton.addEventListener("click", () => registerModal.style.display = "flex");
loginButton.addEventListener("click", () => loginModal.style.display = "flex");
addMaterialButton.addEventListener("click", () => addModal.style.display = "flex");

// زر الإغلاق يسكّر النافذة اللي هو بيها
document.querySelectorAll(".close-modal").forEach(function (btn) {
    btn.addEventListener("click", function () {
        btn.closest(".modal").style.display = "none";
    });
});

// الضغط خارج النافذة يسكّرها
document.querySelectorAll(".modal").forEach(function (modal) {
    modal.addEventListener("click", function (event) {
        if (event.target === modal) modal.style.display = "none";
    });
});

// زر Escape يسكّر كل النوافذ
document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        document.querySelectorAll(".modal").forEach(m => m.style.display = "none");
    }
});

// ===================== إرسال الفورمات بـ fetch =====================
function handleForm(form, modal, onSuccess) {
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        fetch(form.action, { method: "POST", body: new FormData(form) })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    form.reset();
                    modal.style.display = "none";
                    if (onSuccess) onSuccess();
                }
            })
            .catch(error => {
                console.error("Form error:", error);
                alert("Something went wrong. Please try again.");
            });
    });
}

handleForm(document.getElementById("registerF"), registerModal);
handleForm(document.getElementById("loginForm"), loginModal);

// ===================== إضافة مادة =====================
const addMaterialForm = document.getElementById("addMaterialForm");

addMaterialForm.addEventListener("submit", function (e) {
    e.preventDefault();

    fetch("FaidProject/addMaterialUser.php", {      // <-- هذا هو التصحيح المهم
        method: "POST",
        body: new FormData(addMaterialForm)
    })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
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

// ===================== عرض المواد (مع الصور) =====================
const productContainer = document.querySelector(".product-container");
const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const UPLOADS_PATH = "FaidProject/uploads/";

let allMaterials = [];
let currentCategory = "all";

function makePlaceholder() {
    const ph = document.createElement("div");
    ph.className = "card-placeholder";
    ph.textContent = "📦";
    return ph;
}

function createCard(m) {
    const card = document.createElement("div");
    card.className = "product-card material-card";

    // ---- الصورة ----
    if (m.image) {
        const img = document.createElement("img");
        img.src = UPLOADS_PATH + encodeURIComponent(m.image);
        img.alt = m.material_name || "Material";
        img.onerror = () => img.replaceWith(makePlaceholder());
        card.appendChild(img);
    } else {
        card.appendChild(makePlaceholder());
    }

    // ---- المعلومات ----
    const title = document.createElement("h3");
    title.textContent = m.material_name;

    const desc = document.createElement("p");
    desc.textContent = m.description;

    const category = document.createElement("p");
    category.textContent = "Category: " + m.category;

    const price = document.createElement("p");
    price.textContent = "Price: $" + Number(m.price).toFixed(2);

    const qty = document.createElement("p");
    qty.textContent = "Quantity: " + m.quantity;

    const btn = document.createElement("button");
    btn.className = "buy-now";
    btn.textContent = "Request Material";

    card.append(title, desc, category, price, qty, btn);
    return card;
}

function renderMaterials() {
    productContainer.querySelectorAll(".material-card, .empty-msg").forEach(c => c.remove());

    const term = searchInput.value.trim().toLowerCase();

    const list = allMaterials.filter(m => {
        const okCategory = currentCategory === "all" || m.category === currentCategory;
        const okSearch = term === "" ||
            (m.material_name || "").toLowerCase().includes(term) ||
            (m.description || "").toLowerCase().includes(term);
        return okCategory && okSearch;
    });

    if (list.length === 0) {
        const msg = document.createElement("p");
        msg.className = "empty-msg";
        msg.textContent = "No materials found.";
        productContainer.appendChild(msg);
        return;
    }

    list.forEach(m => productContainer.appendChild(createCard(m)));
}

function getMaterials() {
    fetch("FaidProject/getMaterials.php")
        .then(response => response.json())
        .then(data => {
            allMaterials = Array.isArray(data) ? data : (data.materials || []);
            renderMaterials();
        })
        .catch(error => console.error("Error loading materials:", error));
}

// ===================== الأقسام والبحث =====================
document.querySelectorAll(".category_material .category").forEach(function (btn) {
    btn.addEventListener("click", function () {
        document.querySelectorAll(".category_material .category")
            .forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        currentCategory = btn.dataset.category;
        renderMaterials();
    });
});

searchInput.addEventListener("input", renderMaterials);
searchForm.addEventListener("submit", function (e) {
    e.preventDefault();
    renderMaterials();
});

// تحميل المواد أول ما تنفتح الصفحة
getMaterials();