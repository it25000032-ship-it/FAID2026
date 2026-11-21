<?php
require_once __DIR__ . "/FaidProject/security.php";

$requestedId = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
$companyId = $requestedId && $requestedId > 0 ? (int)$requestedId : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
    <meta name="theme-color" content="#0b2559">
    <title>Company Profile | FAID</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/homeStyle.css?v=20261117-profile-starlight-2">
</head>
<body class="company-profile-page">
    <div class="profile-atmosphere" id="profileAtmosphere" aria-hidden="true">
        <div class="profile-sparkles" id="profileSparkles"></div>
    </div>
    <header class="company-profile-header">
        <a class="company-profile-brand" href="home.html" aria-label="FAID marketplace">FAID</a>
        <a class="company-profile-marketplace" href="home.html#available-offers">Marketplace</a>
    </header>

    <main class="company-profile-main" data-company-id="<?php echo $companyId; ?>">
        <p id="profileStatus" class="company-profile-status" role="status" aria-live="polite">Loading company profile...</p>

        <section class="company-profile-summary" aria-labelledby="companyName">
            <div class="profile-hero-art" aria-hidden="true">
                <span class="profile-art-orbit profile-art-orbit-one"></span>
                <span class="profile-art-orbit profile-art-orbit-two"></span>
                <span class="profile-art-core"></span>
                <span class="profile-art-star profile-art-star-one">✦</span>
                <span class="profile-art-star profile-art-star-two">✧</span>
            </div>
            <div class="company-profile-heading">
                <div>
                    <p class="eyebrow"><span class="profile-live-dot" aria-hidden="true"></span> COMPANY PROFILE</p>
                    <h1 id="companyName">Company</h1>
                </div>
                <button type="button" id="editProfileButton" hidden>Edit profile</button>
            </div>
            <p id="companyDescription" class="company-profile-description"></p>
            <div class="profile-offer-total">
                <strong id="companyOfferCount">—</strong>
                <span>published offers</span>
            </div>
            <dl class="company-profile-details">
                <div><dt>Address</dt><dd id="companyAddress">—</dd></div>
                <div><dt>Phone</dt><dd id="companyPhone">—</dd></div>
                <div><dt>Joined FAID</dt><dd id="companyJoined">—</dd></div>
            </dl>
        </section>

        <section id="profileEditor" class="company-profile-editor" aria-labelledby="profileEditorTitle" hidden>
            <h2 id="profileEditorTitle">Edit company profile</h2>
            <form id="companyProfileForm">
                <label for="profileCompanyName">Company name</label>
                <input id="profileCompanyName" name="company_name" type="text" maxlength="150" required>
                <label for="profilePhone">Phone number</label>
                <input id="profilePhone" name="phone" type="tel" maxlength="30" required>
                <label for="profileAddress">Address</label>
                <input id="profileAddress" name="address" type="text" maxlength="255" required>
                <label for="profileDescriptionInput">Short description</label>
                <textarea id="profileDescriptionInput" name="description" maxlength="500" rows="4"></textarea>
                <div class="company-profile-form-actions">
                    <button type="submit">Save profile</button>
                    <button type="button" id="cancelProfileEdit" class="button-quiet">Cancel</button>
                </div>
            </form>
        </section>

        <section class="company-profile-offers" aria-labelledby="companyOffersTitle">
            <div class="company-profile-offers-heading">
                <div>
                    <p class="eyebrow">MARKETPLACE OFFERS</p>
                    <h2 id="companyOffersTitle">Offers from this company</h2>
                </div>
            </div>
            <div id="companyMaterials" class="product-container" aria-live="polite"></div>
        </section>
    </main>

    <div class="modal" id="profileRequestModal" role="dialog" aria-modal="true" aria-labelledby="profileRequestTitle" aria-hidden="true">
        <div class="modal-content">
            <button type="button" class="close-modal" id="closeProfileRequest" aria-label="Close">&times;</button>
            <h2 id="profileRequestTitle">Request Material</h2>
            <p id="profileRequestMaterialName"></p>
            <p>Your quantity is reserved while the request is pending or approved.</p>
            <form id="profileRequestForm" action="FaidProject/request_material.php" method="post">
                <input type="hidden" name="material_id" id="profileRequestMaterialId">
                <label for="profileRequestQuantity">Quantity</label>
                <input type="number" id="profileRequestQuantity" name="quantity" min="1" step="1" value="1" required>
                <button type="submit">Submit Request</button>
            </form>
        </div>
    </div>

    <div id="toastRegion" class="toast-region" aria-live="polite" aria-atomic="true"></div>
    <script src="JS/ui.js?v=20261117-company-profile"></script>
    <script src="JS/company_profile.js?v=20261117-profile-starlight-3"></script>
</body>
</html>
