document.addEventListener("DOMContentLoaded", function () {
    const profileMain = document.querySelector(".company-profile-main");
    const companyId = Number(profileMain.dataset.companyId);
    const profileStatus = document.getElementById("profileStatus");
    const materialsContainer = document.getElementById("companyMaterials");
    const editProfileButton = document.getElementById("editProfileButton");
    const profileEditor = document.getElementById("profileEditor");
    const profileForm = document.getElementById("companyProfileForm");
    const requestModal = document.getElementById("profileRequestModal");
    const requestForm = document.getElementById("profileRequestForm");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let company = null;
    let companySession = null;

    function setProfileStatus(message, isError) {
        profileStatus.textContent = message;
        profileStatus.classList.toggle("is-error", Boolean(isError));
    }

    function setModalVisibility(modal, visible) {
        modal.style.display = visible ? "flex" : "none";
        modal.setAttribute("aria-hidden", String(!visible));
    }

    function initializeProfileAtmosphere() {
        const atmosphere = document.getElementById("profileAtmosphere");
        const sparkles = document.getElementById("profileSparkles");
        const sparkleCount = 56;
        for (let index = 0; index < sparkleCount; index++) {
            const sparkle = document.createElement("span");
            sparkle.className = "profile-sparkle";
            sparkle.style.left = ((index * 37 + 11) % 100) + "%";
            sparkle.style.top = ((index * 61 + 7) % 100) + "%";
            sparkle.style.setProperty("--sparkle-size", (2 + (index * 13) % 5) + "px");
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
            frame = window.requestAnimationFrame(() => {
                atmosphere.style.setProperty("--pointer-x", (event.clientX / window.innerWidth * 100) + "%");
                atmosphere.style.setProperty("--pointer-y", (event.clientY / window.innerHeight * 100) + "%");
                frame = 0;
            });
        }, { passive: true });
    }

    function formatDate(value) {
        const dateText = String(value || "").slice(0, 10);
        const date = new Date(dateText + "T00:00:00");
        return Number.isNaN(date.getTime())
            ? dateText
            : new Intl.DateTimeFormat(undefined, {
                year: "numeric",
                month: "long",
                day: "numeric"
            }).format(date);
    }

    function makeImagePlaceholder() {
        const placeholder = document.createElement("div");
        placeholder.className = "card-placeholder";
        placeholder.textContent = "Offer image unavailable";
        return placeholder;
    }

    function makeCompanyCard(material) {
        const card = document.createElement("article");
        card.className = "product-card material-card";

        const savedPath = String(material.image_path || "").replace(/^FaidProject\//i, "");
        if (/^uploads\/[A-Za-z0-9_.-]+\.(jpg|jpeg|png|gif|webp)$/i.test(savedPath)) {
            const image = document.createElement("img");
            image.src = "FaidProject/" + savedPath;
            image.alt = material.material_name || "Offer";
            image.loading = "lazy";
            image.onerror = function () {
                image.replaceWith(makeImagePlaceholder());
            };
            card.appendChild(image);
        } else {
            card.appendChild(makeImagePlaceholder());
        }

        const title = document.createElement("h3");
        title.textContent = material.material_name || "";

        const description = document.createElement("p");
        description.className = "card-desc";
        description.textContent = material.description || "";

        const companyInfo = document.createElement("p");
        companyInfo.className = "card-meta company-meta";
        companyInfo.appendChild(document.createTextNode("Company: "));
        const companyProfileUrl = "company_profile.php?id=" + encodeURIComponent(company.company_id);
        const companyName = document.createElement("a");
        companyName.className = "company-profile-name";
        companyName.href = companyProfileUrl;
        companyName.textContent = company.company_name;
        const profileLink = document.createElement("a");
        profileLink.className = "company-profile-link";
        profileLink.href = companyProfileUrl;
        profileLink.textContent = "View Company Profile";
        companyInfo.append(companyName, document.createTextNode(" · "), profileLink);

        const categoryNames = {
            wood: "Wooden Materials",
            metal: "Iron and Metals",
            packaging: "Packaging Materials and Cartons",
            plastic: "Plastic Materials",
            electrical: "Electrical Materials"
        };
        const category = document.createElement("p");
        category.className = "card-meta";
        category.textContent = "Category: " + (categoryNames[material.category] || material.category || "");

        const availableQuantity = Number(material.available_quantity ?? material.quantity);
        const quantity = document.createElement("p");
        quantity.className = "card-meta";
        quantity.textContent = "Available: " + Math.max(0, availableQuantity);

        const price = document.createElement("p");
        price.className = "card-price";
        price.textContent = Number(material.price).toFixed(2) + " IQD";

        const requestButton = document.createElement("button");
        const isOwnListing = companySession &&
            Number(companySession.company_id) === Number(material.company_id);
        requestButton.type = "button";
        requestButton.className = "buy-now";
        requestButton.textContent = isOwnListing
            ? "My Offers"
            : (availableQuantity > 0 ? "Request Material" : "Unavailable");
        requestButton.disabled = Boolean(isOwnListing) || availableQuantity <= 0;
        requestButton.addEventListener("click", function () {
            if (requestButton.disabled) return;
            document.getElementById("profileRequestMaterialId").value = material.material_id;
            document.getElementById("profileRequestMaterialName").textContent = material.material_name || "";
            const quantityInput = document.getElementById("profileRequestQuantity");
            quantityInput.max = String(availableQuantity);
            quantityInput.value = "1";
            setModalVisibility(requestModal, true);
            quantityInput.focus();
        });

        card.append(title, description, companyInfo, category, quantity, price, requestButton);
        return card;
    }

    function renderMaterials(materials) {
        materialsContainer.replaceChildren();
        document.getElementById("companyOfferCount").textContent = materials.length.toLocaleString();
        if (!materials.length) {
            const empty = document.createElement("p");
            empty.className = "company-profile-empty";
            empty.textContent = "This company has no offers yet.";
            materialsContainer.appendChild(empty);
            return;
        }
        materials.forEach(material => {
            const card = makeCompanyCard(material);
            if (window.matchMedia("(hover: hover) and (pointer: fine)").matches &&
                !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                card.addEventListener("pointermove", event => {
                    const bounds = card.getBoundingClientRect();
                    const x = (event.clientX - bounds.left) / bounds.width - 0.5;
                    const y = (event.clientY - bounds.top) / bounds.height - 0.5;
                    card.style.setProperty("--profile-tilt-x", (x * 4).toFixed(2) + "deg");
                    card.style.setProperty("--profile-tilt-y", (-y * 4).toFixed(2) + "deg");
                });
                card.addEventListener("pointerleave", () => {
                    card.style.removeProperty("--profile-tilt-x");
                    card.style.removeProperty("--profile-tilt-y");
                });
            }
            materialsContainer.appendChild(card);
        });
    }

    function populateProfile(companyData) {
        company = companyData;
        document.title = company.company_name + " | FAID Company Profile";
        document.getElementById("companyName").textContent = company.company_name;
        document.getElementById("companyDescription").textContent =
            company.description || "No company description has been added.";
        document.getElementById("companyAddress").textContent = company.address || "Not provided";
        document.getElementById("companyPhone").textContent = company.phone || "Not provided";
        document.getElementById("companyJoined").textContent = formatDate(company.created_at);

        const canEdit = companySession &&
            Number(companySession.company_id) === Number(company.company_id);
        editProfileButton.hidden = !canEdit;
        renderMaterials(companyData.materials);
        setProfileStatus("", false);
    }

    async function loadProfile() {
        if (!Number.isInteger(companyId) || companyId < 1) {
            setProfileStatus("A valid company profile link is required.", true);
            return;
        }

        setProfileStatus("Loading company profile...", false);
        try {
            const profileResponse = await fetch(
                "FaidProject/company_profile_data.php?id=" + encodeURIComponent(companyId)
            );
            const result = await profileResponse.json();
            if (!profileResponse.ok || !result.success) {
                throw new Error(result.message || "Could not load this company profile.");
            }

            try {
                const sessionResponse = await fetch("FaidProject/session_info.php");
                if (!sessionResponse.ok) {
                    throw new Error("Session check returned HTTP " + sessionResponse.status);
                }
                companySession = await sessionResponse.json();
            } catch (error) {
                console.error("Company session check failed:", error);
                companySession = null;
                showToast("Profile loaded, but editing access could not be verified.", "error");
            }
            populateProfile({
                ...result.company,
                materials: Array.isArray(result.materials) ? result.materials : []
            });
        } catch (error) {
            console.error("Company profile load error:", error);
            setProfileStatus(error.message || "Could not load this company profile.", true);
            materialsContainer.replaceChildren();
        }
    }

    function fillProfileForm() {
        document.getElementById("profileCompanyName").value = company.company_name || "";
        document.getElementById("profilePhone").value = company.phone || "";
        document.getElementById("profileAddress").value = company.address || "";
        document.getElementById("profileDescriptionInput").value = company.description || "";
    }

    editProfileButton.addEventListener("click", function () {
        fillProfileForm();
        profileEditor.hidden = false;
        document.getElementById("profileCompanyName").focus();
    });

    document.getElementById("cancelProfileEdit").addEventListener("click", function () {
        profileForm.reset();
        profileEditor.hidden = true;
    });

    profileForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (profileForm.dataset.submitting === "true" || !profileForm.reportValidity()) return;

        profileForm.dataset.submitting = "true";
        const submitButton = profileForm.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = "Saving...";
        try {
            const response = await fetch("FaidProject/update_company_profile.php", {
                method: "POST",
                body: new FormData(profileForm),
                headers: { "X-CSRF-Token": csrfToken }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                showToast(result.message || "Could not update the company profile.", "error");
                return;
            }
            profileEditor.hidden = true;
            showToast(result.message || "Company profile updated.");
            await loadProfile();
        } catch (error) {
            console.error("Company profile update error:", error);
            showToast("Could not update the company profile. Please try again.", "error");
        } finally {
            profileForm.dataset.submitting = "false";
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });

    document.querySelectorAll(".close-modal").forEach(button => {
        button.addEventListener("click", () => setModalVisibility(requestModal, false));
    });
    requestModal.addEventListener("click", event => {
        if (event.target === requestModal) setModalVisibility(requestModal, false);
    });

    requestForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (requestForm.dataset.submitting === "true" || !requestForm.reportValidity()) return;

        requestForm.dataset.submitting = "true";
        const submitButton = requestForm.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = "Sending request...";
        try {
            const response = await fetch(requestForm.action, {
                method: "POST",
                body: new FormData(requestForm),
                headers: { "X-CSRF-Token": csrfToken }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                showToast(result.message || "Could not submit the request.", "error");
                return;
            }
            showToast(result.message || "Your material request was submitted.");
            requestForm.reset();
            setModalVisibility(requestModal, false);
            await loadProfile();
        } catch (error) {
            console.error("Material request error:", error);
            showToast("Could not submit the request. Please try again.", "error");
        } finally {
            requestForm.dataset.submitting = "false";
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") setModalVisibility(requestModal, false);
    });

    initializeProfileAtmosphere();
    loadProfile();
});
