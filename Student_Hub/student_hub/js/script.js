/* =========================================================
   STUDENT HUB - GLOBAL SCRIPT (cleaned)

   1.  Theme (saved + toggle button)
   2.  Change Heading button
   3.  Announcement modal (Open Announcement button)
   4.  Notification banner
   5.  Hamburger menu
   6.  Hero image slider
   7.  FAQ accordion
   8.  Login: create-account redirect
   9.  Registration form validation
   10. Contact form
   11. Feedback form

   Every section checks that its elements exist first,
   so this one file is safe to load on every page.
   ========================================================= */

"use strict";


/* =========================================================
   1. THEME
   ========================================================= */

const themeBtn = document.getElementById("themeBtn");

// Apply the saved theme on every page
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-theme");
}

function updateThemeButtonText() {
    if (!themeBtn) {
        return;
    }

    themeBtn.textContent = document.body.classList.contains("dark-theme")
        ? "Light Theme"
        : "Dark Theme";
}

updateThemeButtonText();

if (themeBtn) {
    themeBtn.addEventListener("click", function () {
        const isDark = document.body.classList.toggle("dark-theme");

        localStorage.setItem("theme", isDark ? "dark" : "light");
        updateThemeButtonText();
    });
}


/* =========================================================
   2. CHANGE HEADING BUTTON
   Each click switches the hero heading between the original
   text and a slightly different version. The highlighted
   <span> keeps its styling in both versions.
   ========================================================= */

const changeHeadingBtn = document.getElementById("changeHeadingBtn");
const heroHeading = document.getElementById("heroHeading");

if (changeHeadingBtn && heroHeading) {
    const originalHeading = heroHeading.innerHTML;
    const changedHeading = "You're Welcome to <span>Student Hub!</span>";

    let isHeadingChanged = false;

    changeHeadingBtn.addEventListener("click", function () {
        isHeadingChanged = !isHeadingChanged;

        heroHeading.innerHTML = isHeadingChanged
            ? changedHeading
            : originalHeading;
    });
}


/* =========================================================
   3. ANNOUNCEMENT MODAL
   Open: Open Announcement button
   Close: X button, click outside the box, or Escape key
   ========================================================= */

const openAnnouncementBtn = document.getElementById("openAnnouncementBtn");
const announcementModal = document.getElementById("announcementModal");
const closeModalBtn = document.getElementById("closeModalBtn");

function openAnnouncement() {
    if (announcementModal) {
        announcementModal.classList.add("active");
    }
}

function closeAnnouncement() {
    if (announcementModal) {
        announcementModal.classList.remove("active");
    }
}

if (openAnnouncementBtn) {
    openAnnouncementBtn.addEventListener("click", openAnnouncement);
}

if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeAnnouncement);
}

if (announcementModal) {
    announcementModal.addEventListener("click", function (event) {
        // Only the dark background, not the box itself
        if (event.target === announcementModal) {
            closeAnnouncement();
        }
    });
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeAnnouncement();
    }
});


/* =========================================================
   4. NOTIFICATION BANNER
   ========================================================= */

const notificationBanner = document.getElementById("notificationBanner");
const notificationClose = document.getElementById("notificationClose");

if (notificationBanner && notificationClose) {
    notificationClose.addEventListener("click", function () {
        notificationBanner.style.display = "none";
    });
}


/* =========================================================
   5. HAMBURGER MENU
   ========================================================= */

const hamburger = document.getElementById("hamburger");
const mainNav = document.getElementById("mainNav");

function closeMenu() {
    hamburger.classList.remove("active");
    mainNav.classList.remove("active");
    hamburger.setAttribute("aria-expanded", "false");
}

if (hamburger && mainNav) {
    hamburger.setAttribute("aria-expanded", "false");

    hamburger.addEventListener("click", function () {
        const isOpen = mainNav.classList.toggle("active");

        hamburger.classList.toggle("active", isOpen);
        hamburger.setAttribute("aria-expanded", String(isOpen));
    });

    // Close the menu after choosing a link
    mainNav.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", closeMenu);
    });
}


/* =========================================================
   6. HERO IMAGE SLIDER
   ========================================================= */

const heroSlides = document.querySelectorAll(".hero-slide");
const heroDots = document.querySelectorAll(".hero-dot");
const heroPrev = document.getElementById("heroPrev");
const heroNext = document.getElementById("heroNext");

const HERO_SLIDE_DELAY = 4000; // milliseconds

let heroCurrentSlide = 0;
let heroTimer = null;

function showHeroSlide(index) {
    if (heroSlides.length === 0) {
        return;
    }

    // Wrap around at both ends
    heroCurrentSlide = (index + heroSlides.length) % heroSlides.length;

    heroSlides.forEach(function (slide, i) {
        slide.classList.toggle("active", i === heroCurrentSlide);
    });

    heroDots.forEach(function (dot, i) {
        dot.classList.toggle("active", i === heroCurrentSlide);
    });
}

function startHeroTimer() {
    if (heroSlides.length < 2) {
        return;
    }

    clearInterval(heroTimer);

    heroTimer = setInterval(function () {
        showHeroSlide(heroCurrentSlide + 1);
    }, HERO_SLIDE_DELAY);
}

// Manual controls also restart the timer, so a slide never flips right after a click
function goToHeroSlide(index) {
    showHeroSlide(index);
    startHeroTimer();
}

if (heroNext) {
    heroNext.addEventListener("click", function () {
        goToHeroSlide(heroCurrentSlide + 1);
    });
}

if (heroPrev) {
    heroPrev.addEventListener("click", function () {
        goToHeroSlide(heroCurrentSlide - 1);
    });
}

heroDots.forEach(function (dot, index) {
    dot.addEventListener("click", function () {
        goToHeroSlide(index);
    });
});

startHeroTimer();


/* =========================================================
   7. FAQ ACCORDION (one answer open at a time)
   ========================================================= */

const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach(function (item) {
    const question = item.querySelector(".faq-question");

    if (!question) {
        return;
    }

    question.addEventListener("click", function () {
        faqItems.forEach(function (otherItem) {
            if (otherItem !== item) {
                otherItem.classList.remove("active");
            }
        });

        item.classList.toggle("active");
    });
});


/* =========================================================
   8. LOGIN PAGE: CREATE ACCOUNT REDIRECT
   ========================================================= */

const createAccountBtn = document.getElementById("createAccountBtn");

if (createAccountBtn) {
    createAccountBtn.addEventListener("click", function (event) {
        event.preventDefault();
        window.location.href = "registration.html";
    });
}


/* =========================================================
   9. REGISTRATION FORM VALIDATION
   ========================================================= */

const registrationForm = document.getElementById("registrationForm");

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const MIN_PASSWORD_LENGTH = 6;

// Where an error message goes: checkboxes use .form-options, other fields their own group
function getErrorContainer(input) {
    if (input.type === "checkbox") {
        return input.closest(".form-options");
    }

    // Radio groups (star rating) keep their error under the whole group
    if (input.type === "radio") {
        return input.closest(".form-group");
    }

    return input.parentElement;
}

function showError(input, message) {
    input.classList.add("input-error");

    const error = document.createElement("span");
    error.className = "error-message";
    error.textContent = message;

    const container = getErrorContainer(input);

    if (container) {
        container.appendChild(error);
    }
}

function clearError(input) {
    input.classList.remove("input-error");

    const container = getErrorContainer(input);
    const error = container && container.querySelector(".error-message");

    if (error) {
        error.remove();
    }
}

function clearAllErrors(form) {
    form.querySelectorAll(".error-message").forEach(function (error) {
        error.remove();
    });

    form.querySelectorAll(".input-error").forEach(function (input) {
        input.classList.remove("input-error");
    });
}

// Remove a field's error as soon as the user edits it (used by all forms)
function clearErrorsOnEdit(form) {
    form.querySelectorAll("input, textarea, select").forEach(function (field) {
        const usesChangeEvent =
            field.type === "checkbox" ||
            field.type === "radio" ||
            field.tagName === "SELECT";

        field.addEventListener(usesChangeEvent ? "change" : "input", function () {
            clearError(field);
        });
    });
}

function validateRegistration() {
    const fullname = document.getElementById("fullname");
    const email = document.getElementById("registerEmail");
    const studentId = document.getElementById("studentId");
    const password = document.getElementById("registerPassword");
    const confirmPassword = document.getElementById("confirmPassword");
    const terms = document.getElementById("terms");

    let isValid = true;

    // Full name
    if (fullname.value.trim() === "") {
        showError(fullname, "Please enter your full name.");
        isValid = false;
    }

    // Email
    if (email.value.trim() === "") {
        showError(email, "Please enter your email.");
        isValid = false;
    } else if (!EMAIL_PATTERN.test(email.value.trim())) {
        showError(email, "Please enter a valid email address.");
        isValid = false;
    }

    // Student ID
    if (studentId.value.trim() === "") {
        showError(studentId, "Please enter your student ID.");
        isValid = false;
    }

    // Password
    if (password.value.trim() === "") {
        showError(password, "Please enter a password.");
        isValid = false;
    } else if (password.value.length < MIN_PASSWORD_LENGTH) {
        showError(
            password,
            "Password must be at least " + MIN_PASSWORD_LENGTH + " characters."
        );
        isValid = false;
    }

    // Confirm password
    if (confirmPassword.value.trim() === "") {
        showError(confirmPassword, "Please confirm your password.");
        isValid = false;
    } else if (password.value !== confirmPassword.value) {
        showError(confirmPassword, "Passwords do not match.");
        isValid = false;
    }

    // Terms and conditions
    if (!terms.checked) {
        showError(terms, "Please accept the Terms & Conditions.");
        isValid = false;
    }

    return isValid;
}

if (registrationForm) {
    registrationForm.addEventListener("submit", function (event) {
        event.preventDefault();

        clearAllErrors(registrationForm);

        if (validateRegistration()) {
            alert("Registration successful!");

            registrationForm.reset();
            window.location.href = "login.html";
        }
    });

    clearErrorsOnEdit(registrationForm);
}


/* =========================================================
   10. CONTACT FORM
   All fields required. Nothing is sent anywhere yet:
   a valid form shows the success message and resets.
   ========================================================= */

const MIN_MESSAGE_LENGTH = 10;

const contactForm = document.getElementById("contactForm");
const contactSuccess = document.getElementById("contactSuccess");

function validateContact() {
    const name = document.getElementById("contactName");
    const email = document.getElementById("contactEmail");
    const subject = document.getElementById("contactSubject");
    const message = document.getElementById("contactMessage");

    let isValid = true;

    if (name.value.trim() === "") {
        showError(name, "Please enter your full name.");
        isValid = false;
    }

    if (email.value.trim() === "") {
        showError(email, "Please enter your email.");
        isValid = false;
    } else if (!EMAIL_PATTERN.test(email.value.trim())) {
        showError(email, "Please enter a valid email address.");
        isValid = false;
    }

    if (subject.value.trim() === "") {
        showError(subject, "Please enter a subject.");
        isValid = false;
    }

    if (message.value.trim() === "") {
        showError(message, "Please write your message.");
        isValid = false;
    } else if (message.value.trim().length < MIN_MESSAGE_LENGTH) {
        showError(
            message,
            "Message must be at least " + MIN_MESSAGE_LENGTH + " characters."
        );
        isValid = false;
    }

    return isValid;
}

if (contactForm) {
    contactForm.addEventListener("submit", function (event) {
        event.preventDefault();

        clearAllErrors(contactForm);

        if (contactSuccess) {
            contactSuccess.hidden = true;
        }

        if (validateContact()) {
            contactForm.reset();

            if (contactSuccess) {
                contactSuccess.hidden = false;
                contactSuccess.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        }
    });

    clearErrorsOnEdit(contactForm);
}


/* =========================================================
   11. FEEDBACK FORM
   Name and email are optional (email is checked if filled).
   Topic, star rating and comments are required.
   ========================================================= */

const feedbackForm = document.getElementById("feedbackForm");
const feedbackSuccess = document.getElementById("feedbackSuccess");

function validateFeedback() {
    const email = document.getElementById("feedbackEmail");
    const category = document.getElementById("feedbackCategory");
    const message = document.getElementById("feedbackMessage");
    const anyRating = document.getElementById("rating1");
    const chosenRating = feedbackForm.querySelector('input[name="rating"]:checked');

    let isValid = true;

    if (
        email.value.trim() !== "" &&
        !EMAIL_PATTERN.test(email.value.trim())
    ) {
        showError(email, "Please enter a valid email address.");
        isValid = false;
    }

    if (category.value === "") {
        showError(category, "Please choose a topic.");
        isValid = false;
    }

    if (!chosenRating) {
        showError(anyRating, "Please choose a star rating.");
        isValid = false;
    }

    if (message.value.trim() === "") {
        showError(message, "Please write your comments.");
        isValid = false;
    } else if (message.value.trim().length < MIN_MESSAGE_LENGTH) {
        showError(
            message,
            "Comments must be at least " + MIN_MESSAGE_LENGTH + " characters."
        );
        isValid = false;
    }

    return isValid;
}

if (feedbackForm) {
    feedbackForm.addEventListener("submit", function (event) {
        event.preventDefault();

        clearAllErrors(feedbackForm);

        if (feedbackSuccess) {
            feedbackSuccess.hidden = true;
        }

        if (validateFeedback()) {
            feedbackForm.reset();

            if (feedbackSuccess) {
                feedbackSuccess.hidden = false;
                feedbackSuccess.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        }
    });

    clearErrorsOnEdit(feedbackForm);
}