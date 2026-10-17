// ================================
// GLOBAL DARK THEME
// ================================

// Get saved theme
const savedTheme = localStorage.getItem("theme");

// Apply saved theme to EVERY page
if (savedTheme === "dark") {
    document.body.classList.add("dark-theme");
}


// ================================
// THEME BUTTON
// ================================

const themeBtn = document.getElementById("themeBtn");

if (themeBtn) {

    // Set correct button text
    if (document.body.classList.contains("dark-theme")) {
        themeBtn.textContent = "Light Theme";
    } else {
        themeBtn.textContent = "Dark Theme";
    }


    // Toggle theme
    themeBtn.addEventListener("click", function () {

        document.body.classList.toggle("dark-theme");

        if (document.body.classList.contains("dark-theme")) {

            // Save dark theme
            localStorage.setItem("theme", "dark");

            themeBtn.textContent = "Light Theme";

        } else {

            // Save light theme
            localStorage.setItem("theme", "light");

            themeBtn.textContent = "Dark Theme";

        }

    });

}



// ================================
// CHANGE HEADING
// ================================

const changeBtn =
    document.getElementById("changeBtn");

const welcomeHeading =
    document.getElementById("welcomeHeading");


if (changeBtn && welcomeHeading) {

    changeBtn.addEventListener("click", function () {

        welcomeHeading.textContent =
            "You're Welcome to Student Hub!";

    });

}



// ================================
// FAQ ACCORDION
// ================================

const faqQuestions = document.querySelectorAll(".faq-question");

faqQuestions.forEach(function (question) {

    question.addEventListener("click", function () {

        const currentItem = question.parentElement;

        // Close all other FAQ items
        document.querySelectorAll(".faq-item").forEach(function (item) {

            if (item !== currentItem) {
                item.classList.remove("active");
            }

        });

        // Toggle clicked question
        currentItem.classList.toggle("active");

    });

});


// ================================
// HAMBURGER MENU
// ================================

const hamburger = document.getElementById("hamburger");
const mainNav = document.getElementById("mainNav");

if (hamburger && mainNav) {

    hamburger.addEventListener("click", function () {

        hamburger.classList.toggle("active");
        mainNav.classList.toggle("active");

    });


    // Close menu after clicking a navigation link

    const navLinks = mainNav.querySelectorAll("a");

    navLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            hamburger.classList.remove("active");
            mainNav.classList.remove("active");

        });

    });

}

// ================================
// HERO IMAGE SLIDER
// ================================

const heroSlides = document.querySelectorAll(".hero-slide");
const heroDots = document.querySelectorAll(".hero-dot");

const heroPrev = document.getElementById("heroPrev");
const heroNext = document.getElementById("heroNext");

let heroCurrentSlide = 0;


// Show slide

function showHeroSlide(index) {

    if (heroSlides.length === 0) {
        return;
    }

    if (index >= heroSlides.length) {
        heroCurrentSlide = 0;
    }

    if (index < 0) {
        heroCurrentSlide = heroSlides.length - 1;
    }

    heroSlides.forEach(function (slide) {
        slide.classList.remove("active");
    });

    heroDots.forEach(function (dot) {
        dot.classList.remove("active");
    });

    heroSlides[heroCurrentSlide].classList.add("active");

    if (heroDots[heroCurrentSlide]) {
        heroDots[heroCurrentSlide].classList.add("active");
    }
}


// Next button

if (heroNext) {

    heroNext.addEventListener("click", function () {

        heroCurrentSlide++;

        showHeroSlide(heroCurrentSlide);

    });

}


// Previous button

if (heroPrev) {

    heroPrev.addEventListener("click", function () {

        heroCurrentSlide--;

        showHeroSlide(heroCurrentSlide);

    });

}


// Dots

heroDots.forEach(function (dot, index) {

    dot.addEventListener("click", function () {

        heroCurrentSlide = index;

        showHeroSlide(heroCurrentSlide);

    });

});


// Automatic sliding

if (heroSlides.length > 1) {

    setInterval(function () {

        heroCurrentSlide++;

        showHeroSlide(heroCurrentSlide);

    }, 4000);

}

// ================================
// NOTIFICATION BANNER
// ================================

const notificationBanner =
    document.getElementById("notificationBanner");

const notificationClose =
    document.getElementById("notificationClose");


if (notificationClose && notificationBanner) {

    notificationClose.addEventListener("click", function () {

        notificationBanner.style.display = "none";

    });

}

// ================================
// ANNOUNCEMENT MODAL
// ================================

const openModalBtn =
    document.getElementById("openModalBtn");

const announcementModal =
    document.getElementById("announcementModal");

const closeModalBtn =
    document.getElementById("closeModalBtn");


// Open modal

if (openModalBtn && announcementModal) {

    openModalBtn.addEventListener("click", function () {

        announcementModal.classList.add("active");

    });

}


// Close modal

if (closeModalBtn && announcementModal) {

    closeModalBtn.addEventListener("click", function () {

        announcementModal.classList.remove("active");

    });

}


// Close when clicking outside modal

if (announcementModal) {

    announcementModal.addEventListener("click", function (event) {

        if (event.target === announcementModal) {

            announcementModal.classList.remove("active");

        }

    });

}


// Close with Escape key

document.addEventListener("keydown", function (event) {

    if (
        event.key === "Escape" &&
        announcementModal
    ) {

        announcementModal.classList.remove("active");

    }

});


//registration

// ================================
// CREATE ACCOUNT REDIRECT
// ================================

const createAccountBtn =
    document.getElementById("createAccountBtn");

if (createAccountBtn) {

    createAccountBtn.addEventListener("click", function (event) {

        event.preventDefault();

        window.location.href = "registration.html";

    });

}
// ================================
// REGISTRATION FORM VALIDATION
// ================================

// const registrationForm =
//     document.getElementById("registrationForm");

// if (registrationForm) {

//     registrationForm.addEventListener("submit", function (event) {

//         event.preventDefault();

//         let isValid = true;


//         // Remove old error messages

//         document.querySelectorAll(".error-message").forEach(function (error) {
//             error.remove();
//         });


//         // Get all required inputs

//         const requiredInputs =
//             registrationForm.querySelectorAll("input[required]");


//         requiredInputs.forEach(function (input) {

//             // Check normal inputs

//             if (
//                 input.type !== "checkbox" &&
//                 input.value.trim() === ""
//             ) {

//                 showError(input, "This field is required.");

//                 isValid = false;
//             }


//             // Check checkbox

//             if (
//                 input.type === "checkbox" &&
//                 !input.checked
//             ) {

//                 showError(
//                     input,
//                     "Please accept the Terms & Conditions."
//                 );

//                 isValid = false;
//             }

//         });


//         // Check password matching

//         const password =
//             document.getElementById("registerPassword");

//         const confirmPassword =
//             document.getElementById("confirmPassword");


//         if (
//             password.value !== "" &&
//             confirmPassword.value !== "" &&
//             password.value !== confirmPassword.value
//         ) {

//             showError(
//                 confirmPassword,
//                 "Passwords do not match."
//             );

//             isValid = false;
//         }


//         // If everything is valid

//         if (isValid) {

//             alert("Registration successful!");

//             registrationForm.reset();

//             window.location.href = "login.html";
//         }

//     });


//     // Function to show error

//     function showError(input, message) {

//         const error =
//             document.createElement("span");

//         error.className = "error-message";

//         error.textContent = message;

//         input.parentElement.appendChild(error);
//     }

// }

// ============================================
// REGISTRATION FORM VALIDATION
// ============================================

const registrationForm =
    document.getElementById("registrationForm");


if (registrationForm) {

    // ========================================
    // FORM SUBMIT
    // ========================================

    registrationForm.addEventListener("submit", function (event) {

        // Stop the form from submitting normally
        event.preventDefault();

        let isValid = true;


        // ========================================
        // REMOVE OLD ERROR MESSAGES
        // ========================================

        registrationForm
            .querySelectorAll(".error-message")
            .forEach(function (error) {

                error.remove();

            });


        // Remove old red borders
        registrationForm
            .querySelectorAll(".input-error")
            .forEach(function (input) {

                input.classList.remove("input-error");

            });


        // ========================================
        // GET FORM FIELDS
        // ========================================

        const fullname =
            document.getElementById("fullname");

        const email =
            document.getElementById("registerEmail");

        const studentId =
            document.getElementById("studentId");

        const password =
            document.getElementById("registerPassword");

        const confirmPassword =
            document.getElementById("confirmPassword");

        const terms =
            document.getElementById("terms");


        // ========================================
        // FULL NAME
        // ========================================

        if (fullname.value.trim() === "") {

            showError(
                fullname,
                "Please enter your full name."
            );

            isValid = false;
        }


        // ========================================
        // EMAIL
        // ========================================

        if (email.value.trim() === "") {

            showError(
                email,
                "Please enter your email."
            );

            isValid = false;

        } else {

            const emailPattern =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email.value.trim())) {

                showError(
                    email,
                    "Please enter a valid email address."
                );

                isValid = false;
            }
        }


        // ========================================
        // STUDENT ID
        // ========================================

        if (studentId.value.trim() === "") {

            showError(
                studentId,
                "Please enter your student ID."
            );

            isValid = false;
        }


        // ========================================
        // PASSWORD
        // ========================================

        if (password.value.trim() === "") {

            showError(
                password,
                "Please enter a password."
            );

            isValid = false;

        } else if (password.value.length < 6) {

            showError(
                password,
                "Password must be at least 6 characters."
            );

            isValid = false;
        }


        // ========================================
        // CONFIRM PASSWORD
        // ========================================

        if (confirmPassword.value.trim() === "") {

            showError(
                confirmPassword,
                "Please confirm your password."
            );

            isValid = false;

        } else if (
            password.value !== confirmPassword.value
        ) {

            showError(
                confirmPassword,
                "Passwords do not match."
            );

            isValid = false;
        }


        // ========================================
        // TERMS AND CONDITIONS
        // ========================================

        if (!terms.checked) {

            showError(
                terms,
                "Please accept the Terms & Conditions."
            );

            isValid = false;
        }


        // ========================================
        // SUCCESS
        // ========================================

        if (isValid) {

            alert("Registration successful!");

            // Clear form
            registrationForm.reset();

            // Go to login page
            window.location.href = "login.html";
        }

    });


    // ========================================
    // SHOW ERROR FUNCTION
    // ========================================

    function showError(input, message) {

        // Add red border
        input.classList.add("input-error");


        // Create error message
        const error =
            document.createElement("span");

        error.className = "error-message";

        error.textContent = message;


        // ====================================
        // CHECKBOX
        // ====================================

        if (input.type === "checkbox") {

            const formOptions =
                input.closest(".form-options");

            formOptions.appendChild(error);

        }


        // ====================================
        // NORMAL INPUT
        // ====================================

        else {

            input.parentElement.appendChild(error);

        }

    }


    // ========================================
    // REMOVE ERROR WHEN USER STARTS TYPING
    // ========================================

    const allInputs =
        registrationForm.querySelectorAll("input");


    allInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            input.classList.remove("input-error");

            const error =
                input.parentElement.querySelector(
                    ".error-message"
                );

            if (error) {
                error.remove();
            }

        });


        // Checkbox change
        input.addEventListener("change", function () {

            input.classList.remove("input-error");

            const formOptions =
                input.closest(".form-options");

            if (formOptions) {

                const error =
                    formOptions.querySelector(
                        ".error-message"
                    );

                if (error) {
                    error.remove();
                }

            }

        });

    });

}