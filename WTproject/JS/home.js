const registerButton = document.getElementById("registerButton");
const loginButton = document.getElementById("loginButton");
const logoutButton = document.getElementById("logoutButton");
const focusSearchButton = document.getElementById("focusSearchButton");
const companyProfileButton = document.getElementById("companyProfileButton");
const mobileNavToggle = document.getElementById("mobileNavToggle");
const siteNav = document.getElementById("siteNav");
const registerModal = document.getElementById("registerM");
const loginModal = document.getElementById("loginM");
const requestModal = document.getElementById("requestModal");
const addMaterialButton = document.getElementById("addMaterialBtn");
const addModal = document.getElementById("addMaterialModal");
const requestForm = document.getElementById("requestForm");
const addMaterialForm = document.getElementById("addMaterialForm");
const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const productContainer = document.querySelector(".product-container");
const featuredOffers = document.getElementById("featuredOffers");
const offerSuggestions = document.getElementById("offerSuggestions");

const FAID_BASE = "FaidProject/";
let csrfToken = "";
let companyAuthenticated = false;
let currentCompanyId = null;
let allMaterials = [];
let currentCategory = "all";

function initializeHomeAtmosphere() {
    const atmosphere = document.getElementById("homeAtmosphere");
    const sparkles = document.getElementById("homeSparkles");
    for (let index = 0; index < 56; index++) {
        const sparkle = document.createElement("span");
        sparkle.className = "home-sparkle";
        sparkle.style.left = ((index * 37 + 11) % 100) + "%";
        sparkle.style.top = ((index * 61 + 7) % 100) + "%";
        sparkle.style.setProperty("--sparkle-size", (8 + (index * 13) % 9) + "px");
        sparkle.style.setProperty("--sparkle-delay", "-" + ((index * 7) % 12) + "s");
        sparkles.appendChild(sparkle);
    }

    if (!window.matchMedia("(pointer: fine)").matches ||
        window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    let frame = 0;
    document.addEventListener("pointermove", event => {
        if (frame) return;
        const x = event.clientX / window.innerWidth * 100;
        const y = event.clientY / window.innerHeight * 100;
        frame = window.requestAnimationFrame(() => {
            atmosphere.style.setProperty("--pointer-x", x + "%");
            atmosphere.style.setProperty("--pointer-y", y + "%");
            frame = 0;
        });
    }, { passive: true });

    const heroImage = document.querySelector(".hero-image");
    if (heroImage) {
        heroImage.addEventListener("pointermove", event => {
            const bounds = heroImage.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            heroImage.style.setProperty("--hero-tilt-x", (-y * 3).toFixed(2) + "deg");
            heroImage.style.setProperty("--hero-tilt-y", (x * 3).toFixed(2) + "deg");
        });
        heroImage.addEventListener("pointerleave", () => {
            heroImage.style.removeProperty("--hero-tilt-x");
            heroImage.style.removeProperty("--hero-tilt-y");
        });
    }
}

const csrfReady = fetch(FAID_BASE + "csrf_token.php")
    .then(response => {
        if (!response.ok) throw new Error("Could not start a secure session.");
        return response.json();
    })
    .then(data => {
        csrfToken = data.csrf_token || "";
        if (!csrfToken) throw new Error("Could not start a secure session.");
    })
    .catch(error => {
        console.error("Session initialization error:", error);
        showToast(error.message || "Could not start a secure session.", "error");
    });

function openModal(modal) {
    if (!modal) return;
    modal.style.display = "flex";
    modal.setAttribute("aria-hidden", "false");
    const firstField = modal.querySelector("input:not([type=hidden]):not([type=file]), select, textarea");
    if (firstField) firstField.focus();
}

function closeModal(modal) {
    if (!modal) return;
    modal.style.display = "none";
    modal.setAttribute("aria-hidden", "true");
}

registerButton.addEventListener("click", () => openModal(registerModal));
loginButton.addEventListener("click", () => openModal(loginModal));
focusSearchButton.addEventListener("click", focusMarketplaceSearch);
document.getElementById("marketplaceSearchButton").addEventListener("click", focusMarketplaceSearch);
document.getElementById("footerSignIn").addEventListener("click", () => openModal(loginModal));
document.getElementById("footerRegister").addEventListener("click", () => openModal(registerModal));
companyProfileButton.addEventListener("click", function (event) {
    if (currentCompanyId) return;
    event.preventDefault();
    if (window.matchMedia("(max-width: 760px)").matches) {
        mobileNavToggle.setAttribute("aria-expanded", "false");
        mobileNavToggle.setAttribute("aria-label", "Open navigation");
        siteNav.classList.remove("is-open");
    }
    openModal(loginModal);
});

function focusMarketplaceSearch() {
    if (window.matchMedia("(max-width: 760px)").matches) {
        mobileNavToggle.setAttribute("aria-expanded", "false");
        mobileNavToggle.setAttribute("aria-label", "Open navigation");
        siteNav.classList.remove("is-open");
    }
    const searchTop = searchInput.getBoundingClientRect().top + window.scrollY;
    const targetScroll = Math.max(0, searchTop - 150);
    window.scrollTo({ top: targetScroll, behavior: "instant" });
    searchInput.focus({ preventScroll: true });
}

mobileNavToggle.addEventListener("click", function () {
    const expanded = mobileNavToggle.getAttribute("aria-expanded") !== "true";
    mobileNavToggle.setAttribute("aria-expanded", String(expanded));
    mobileNavToggle.setAttribute("aria-label", expanded ? "Close navigation" : "Open navigation");
    siteNav.classList.toggle("is-open", expanded);
});

siteNav.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", function () {
        mobileNavToggle.setAttribute("aria-expanded", "false");
        mobileNavToggle.setAttribute("aria-label", "Open navigation");
        siteNav.classList.remove("is-open");
    });
});

document.querySelectorAll("[data-open-modal]").forEach(button => {
    button.addEventListener("click", function () {
        closeModal(button.closest(".modal"));
        openModal(document.getElementById(button.dataset.openModal));
    });
});

document.querySelectorAll(".close-modal").forEach(function (button) {
    button.addEventListener("click", function () {
        closeModal(button.closest(".modal"));
    });
});

document.querySelectorAll(".modal").forEach(function (modal) {
    modal.addEventListener("click", function (event) {
        if (event.target === modal) closeModal(modal);
    });
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        document.querySelectorAll(".modal").forEach(modal => {
            closeModal(modal);
        });
    }
});

function setCompanySession(isAuthenticated, companyId = null) {
    companyAuthenticated = isAuthenticated;
    currentCompanyId = isAuthenticated && companyId ? Number(companyId) : null;
    logoutButton.hidden = !isAuthenticated;
    loginButton.hidden = isAuthenticated;
    registerButton.hidden = isAuthenticated;
    companyProfileButton.hidden = false;
    companyProfileButton.href = currentCompanyId
        ? "company_profile.php?id=" + encodeURIComponent(currentCompanyId)
        : "#";
}

async function loadCompanySession() {
    try {
        const response = await fetch(FAID_BASE + "session_info.php");
        if (!response.ok) throw new Error("Could not check the current session.");
        const data = await response.json();
        setCompanySession(Boolean(data.company_authenticated), data.company_id);
    } catch (error) {
        console.error("Session status error:", error);
    }
}

function handleJsonForm(form, modal, onSuccess) {
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (form.dataset.submitting === "true") return;
        if (!form.reportValidity()) return;
        form.dataset.submitting = "true";
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add("is-loading");
            submitButton.textContent = "Please wait...";
        }
        try {
            await csrfReady;
            if (!csrfToken) throw new Error("Secure session unavailable. Refresh the page and try again.");
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: { "X-CSRF-Token": csrfToken }
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                showToast(data.message || "The request could not be completed.", "error");
                return;
            }
            if (data.csrf_token) csrfToken = data.csrf_token;
            showToast(data.message || "Your request was completed.");
            form.reset();
            form.querySelectorAll(".field-error").forEach(error => error.textContent = "");
            form.querySelectorAll("[aria-invalid]").forEach(field => field.removeAttribute("aria-invalid"));
            closeModal(modal);
            if (onSuccess) onSuccess(data);
        } catch (error) {
            console.error("Form submission error:", error);
            showToast(error.message || "The request could not be completed. Please try again.", "error");
        } finally {
            form.dataset.submitting = "false";
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove("is-loading");
                submitButton.textContent = originalText;
            }
        }
    });
}

handleJsonForm(document.getElementById("registerF"), registerModal);
handleJsonForm(document.getElementById("loginForm"), loginModal, function (data) {
    setCompanySession(true, data.company_id);
});

logoutButton.addEventListener("click", async function () {
    try {
        await csrfReady;
        const response = await fetch(FAID_BASE + "logout.php", {
            method: "POST",
            body: new URLSearchParams(),
            headers: {
                "X-CSRF-Token": csrfToken,
                "X-Requested-With": "XMLHttpRequest"
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            showToast(data.message || "Could not sign out.", "error");
            return;
        }
        window.location.reload();
    } catch (error) {
        console.error("Sign-out error:", error);
        showToast("Could not sign out. Please try again.", "error");
    }
});

addMaterialButton.addEventListener("click", function () {
    if (!companyAuthenticated) {
        showToast("Please sign in with your company account to publish an offer.", "error");
        openModal(loginModal);
        return;
    }
    openModal(addModal);
});

handleJsonForm(addMaterialForm, addModal, getMaterials);

function makePlaceholder() {
    const placeholder = document.createElement("div");
    placeholder.className = "card-placeholder";
    placeholder.textContent = "Offer image unavailable";
    return placeholder;
}

function createCard(material) {
    const card = document.createElement("article");
    card.className = "product-card material-card";
    if (window.matchMedia("(hover: hover) and (pointer: fine)").matches &&
        !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        card.classList.add("home-pointer-card");
        card.addEventListener("pointermove", event => {
            const bounds = card.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            card.style.setProperty("--home-tilt-x", (x * 3).toFixed(2) + "deg");
            card.style.setProperty("--home-tilt-y", (-y * 3).toFixed(2) + "deg");
        });
        card.addEventListener("pointerleave", () => {
            card.style.removeProperty("--home-tilt-x");
            card.style.removeProperty("--home-tilt-y");
        });
    }

    const savedPath = String(material.image_path || "").replace(/^FaidProject\//i, "");
    if (/^uploads\/[A-Za-z0-9_.-]+\.(jpg|jpeg|png|gif|webp)$/i.test(savedPath)) {
        const image = document.createElement("img");
        image.src = FAID_BASE + savedPath;
        image.alt = material.material_name || "Offer";
        image.loading = "lazy";
        image.onerror = () => image.replaceWith(makePlaceholder());
        card.appendChild(image);
    } else {
        card.appendChild(makePlaceholder());
    }

    const title = document.createElement("h3");
    title.textContent = material.material_name || "";

    const description = document.createElement("p");
    description.className = "card-desc";
    description.textContent = material.description || "";

    const companyInfo = document.createElement("p");
    companyInfo.className = "card-meta company-meta";
    companyInfo.appendChild(document.createTextNode("Company: "));
    const companyProfileUrl = "company_profile.php?id=" + encodeURIComponent(material.company_id);
    const companyName = document.createElement("a");
    companyName.className = "company-profile-name";
    companyName.href = companyProfileUrl;
    companyName.textContent = material.company_name || "Company";
    const profileLink = document.createElement("a");
    profileLink.className = "company-profile-link";
    profileLink.href = companyProfileUrl;
    profileLink.textContent = "View Company Profile";
    companyInfo.append(companyName, document.createTextNode(" · "), profileLink);

    const category = document.createElement("p");
    category.className = "card-meta";
    const categoryNames = {
        wood: "Wooden Materials",
        metal: "Iron and Metals",
        packaging: "Packaging Materials and Cartons",
        plastic: "Plastic Materials",
        electrical: "Electrical Materials"
    };
    category.textContent = "Category: " + (categoryNames[material.category] || material.category || "");

    const availableQuantity = Number(material.available_quantity ?? material.quantity);
    const quantity = document.createElement("p");
    quantity.className = "card-meta";
    quantity.textContent = "Available: " + Math.max(0, availableQuantity);

    const price = document.createElement("p");
    price.className = "card-price";
    price.textContent = Number(material.price).toFixed(2) + " IQD";

    const button = document.createElement("button");
    button.type = "button";
    button.className = "buy-now";
    const isOwnListing = companyAuthenticated &&
        Number(material.company_id) === currentCompanyId;
    button.textContent = isOwnListing
        ? "My Offers"
        : (availableQuantity > 0 ? "Request Material" : "Unavailable");
    button.disabled = isOwnListing || availableQuantity <= 0;
    button.addEventListener("click", function () {
        if (isOwnListing || availableQuantity <= 0) return;
        document.getElementById("requestMaterialId").value = material.material_id;
        document.getElementById("requestMaterialName").textContent = material.material_name || "";
        document.getElementById("requestQuantity").max = String(availableQuantity);
        document.getElementById("requestQuantity").value = "1";
        openModal(requestModal);
    });

    card.append(title, description, companyInfo, category, quantity, price, button);
    return card;
}

function getFilteredMaterials() {
    const term = searchInput.value.trim().toLowerCase();
    return allMaterials.filter(material => {
        const matchesCategory = currentCategory === "all" ||
            String(material.category || "").toLowerCase() === currentCategory;
        const searchableText = [
            material.material_name,
            material.description,
            material.category,
            material.price
        ].map(value => String(value ?? "").toLowerCase()).join(" ");
        const matchesSearch = term === "" || searchableText.includes(term);
        return matchesCategory && matchesSearch;
    });
}

function renderMaterials() {
    productContainer.querySelectorAll(".material-card, .empty-msg, .loading-skeleton").forEach(card => card.remove());
    productContainer.classList.add("is-filtering");
    window.setTimeout(() => productContainer.classList.remove("is-filtering"), 180);
    const materials = getFilteredMaterials();
    document.getElementById("offerCount").textContent =
        "Showing " + materials.length + (materials.length === 1 ? " offer" : " offers");
    if (materials.length === 0) {
        const message = document.createElement("p");
        message.className = "empty-msg marketplace-empty";
        message.textContent = "No offers found for your search and selected category. Try another category or clear your search.";
        productContainer.appendChild(message);
        renderFeatured();
        return;
    }
    materials.forEach(material => productContainer.appendChild(createCard(material)));
    renderFeatured();
}

function animateCounter(element, value) {
    if (!element) return;
    const target = Math.max(0, Number(value) || 0);
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        element.textContent = target.toLocaleString();
        return;
    }
    const duration = 850;
    const start = performance.now();
    function frame(now) {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        element.textContent = Math.round(target * eased).toLocaleString();
        if (progress < 1) window.requestAnimationFrame(frame);
    }
    window.requestAnimationFrame(frame);
}

function updateMarketplaceSummary() {
    const companies = new Set(allMaterials.map(material => Number(material.company_id)).filter(Boolean));
    const availableUnits = allMaterials.reduce((sum, material) =>
        sum + Math.max(0, Number(material.available_quantity ?? material.quantity) || 0), 0);
    animateCounter(document.getElementById("heroOfferCount"), allMaterials.length);
    animateCounter(document.getElementById("platformOffersCount"), allMaterials.length);
    animateCounter(document.getElementById("platformCompaniesCount"), companies.size);
    animateCounter(document.getElementById("platformUnitsCount"), availableUnits);

    document.querySelectorAll("[data-category-count]").forEach(element => {
        const category = element.dataset.categoryCount;
        const count = allMaterials.filter(material => material.category === category).length;
        element.textContent = count.toLocaleString() + (count === 1 ? " offer" : " offers");
    });
    updateOfferSuggestions();
}

function updateOfferSuggestions() {
    const term = searchInput.value.trim().toLowerCase();
    offerSuggestions.replaceChildren();
    allMaterials
        .filter(material => !term || String(material.material_name || "").toLowerCase().includes(term))
        .slice(0, 8)
        .forEach(material => {
            const option = document.createElement("option");
            option.value = material.material_name || "";
            offerSuggestions.appendChild(option);
        });
}

function renderFeatured() {
    if (!featuredOffers) return;
    featuredOffers.replaceChildren();
    const recent = getFilteredMaterials().slice(0, 3);
    if (!recent.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = allMaterials.length === 0 && currentCategory === "all" && !searchInput.value.trim()
            ? "New offers will appear here as companies publish them."
            : "No recent offers match your search and selected category.";
        featuredOffers.appendChild(empty);
        return;
    }
    recent.forEach(material => {
        const card = createCard(material);
        card.classList.add("featured-card");
        featuredOffers.appendChild(card);
    });
}

async function getMaterials() {
    try {
        const response = await fetch(FAID_BASE + "getMaterials.php");
        const data = await response.json();
        if (!response.ok || !Array.isArray(data)) {
            throw new Error(data.message || "Could not load materials.");
        }
        allMaterials = data;
        updateMarketplaceSummary();
        renderMaterials();
    } catch (error) {
        console.error("Materials load error:", error);
        productContainer.querySelectorAll(".material-card, .empty-msg, .loading-skeleton").forEach(card => card.remove());
        document.getElementById("offerCount").textContent = "Offers could not be loaded.";
        const message = document.createElement("p");
        message.className = "empty-msg marketplace-empty";
        message.textContent = error.message || "Could not load offers. Please refresh the page.";
        productContainer.appendChild(message);
        featuredOffers.replaceChildren();
        const featuredError = document.createElement("p");
        featuredError.className = "empty-state";
        featuredError.textContent = "Recent offers could not be loaded.";
        featuredOffers.appendChild(featuredError);
    }
}

requestForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    if (requestForm.dataset.submitting === "true" || !requestForm.reportValidity()) return;
    requestForm.dataset.submitting = "true";
    const submitButton = requestForm.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.classList.add("is-loading");
    submitButton.textContent = "Sending request...";
    try {
        await csrfReady;
        if (!csrfToken) throw new Error("Secure session unavailable. Refresh the page and try again.");
        const response = await fetch(requestForm.action, {
            method: "POST",
            body: new FormData(requestForm),
            headers: { "X-CSRF-Token": csrfToken }
        });
        const data = await response.json();
        if (response.status === 401) {
            closeModal(requestModal);
            showToast(data.message || "Please sign in to request material.", "error");
            openModal(loginModal);
            return;
        }
        if (!response.ok || !data.success) {
            showToast(data.message || "Could not submit the request.", "error");
            return;
        }
        showToast(data.message || "Your material request was submitted.");
        requestForm.reset();
        closeModal(requestModal);
        await getMaterials();
    } catch (error) {
        console.error("Material request error:", error);
        showToast(error.message || "Could not submit the request. Please try again.", "error");
    } finally {
        requestForm.dataset.submitting = "false";
        submitButton.disabled = false;
        submitButton.classList.remove("is-loading");
        submitButton.textContent = originalText;
    }
});

function setActiveCategory(category) {
    currentCategory = category;
    document.querySelectorAll(".category[data-category]").forEach(button => {
        const active = button.dataset.category === currentCategory;
        button.classList.toggle("active", active);
        button.setAttribute("aria-pressed", String(active));
    });
}

function resetMarketplaceFilters() {
    searchInput.value = "";
    setActiveCategory("all");
    updateOfferSuggestions();
    renderMaterials();
}

searchInput.value = "";
setActiveCategory("all");

document.querySelectorAll(".category[data-category]").forEach(function (button) {
    button.addEventListener("click", function () {
        setActiveCategory(button.dataset.category || "all");
        renderMaterials();
        if (button.closest(".category-explorer")) {
            document.getElementById("available-offers").scrollIntoView({ behavior: "smooth" });
        }
    });
});

document.querySelectorAll('a[href="#available-offers"]').forEach(link => {
    link.addEventListener("click", function () {
        if (window.location.hash === "#available-offers") {
            resetMarketplaceFilters();
        }
    });
});

window.addEventListener("hashchange", function () {
    if (window.location.hash === "#available-offers") {
        resetMarketplaceFilters();
    }
});

searchInput.addEventListener("input", function () {
    updateOfferSuggestions();
    renderMaterials();
});
searchForm.addEventListener("submit", function (event) {
    event.preventDefault();
    renderMaterials();
    document.getElementById("available-offers").scrollIntoView({ behavior: "smooth" });
});

csrfReady.then(loadCompanySession);
initializeHomeAtmosphere();
getMaterials();
