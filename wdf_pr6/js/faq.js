// ==========================================
// FAQ DATA
// ==========================================

let faqs = [];


// ==========================================
// PAGINATION SETTINGS
// ==========================================

let currentPage = 1;

const faqsPerPage = 5;


// ==========================================
// DOM ELEMENTS
// ==========================================

const faqContainer =
    document.getElementById("faqContainer");

const faqSearch =
    document.getElementById("faqSearch");

const faqCategory =
    document.getElementById("faqCategory");

const faqSort =
    document.getElementById("faqSort");

const faqLoading =
    document.getElementById("faqLoading");

const faqError =
    document.getElementById("faqError");

const noFaqs =
    document.getElementById("noFaqs");

const faqPagination =
    document.getElementById("faqPagination");


// ==========================================
// FETCH FAQ DATA
// ==========================================

async function fetchFAQs() {

    try {

        showLoading();

        const response = await fetch("../data/faqs.json");


        // Check HTTP response

        if (!response.ok) {

            throw new Error(
                `HTTP Error: ${response.status}`
            );

        }


        // Convert JSON to JavaScript array

        faqs = await response.json();


        console.log("FAQs loaded:", faqs);


        // Render FAQs

        renderFAQs();

    }

    catch (error) {

        console.error(
            "Error loading FAQs:",
            error
        );

        showError(
            "Unable to load FAQs. Please try again later."
        );

    }

}


// ==========================================
// FILTER + SEARCH + SORT
// ==========================================

function getProcessedFAQs() {

    let processedFAQs = [...faqs];


    // ======================================
    // SEARCH
    // ======================================

    const searchText =
        faqSearch.value
            .toLowerCase()
            .trim();


    if (searchText !== "") {

        processedFAQs =
            processedFAQs.filter(faq => {

                return (
                    faq.question
                        .toLowerCase()
                        .includes(searchText)

                    ||

                    faq.answer
                        .toLowerCase()
                        .includes(searchText)
                );

            });

    }


    // ======================================
    // CATEGORY FILTER
    // ======================================

    const selectedCategory =
        faqCategory.value;


    if (selectedCategory !== "all") {

        processedFAQs =
            processedFAQs.filter(faq => {

                return faq.category === selectedCategory;

            });

    }


    // ======================================
    // SORTING
    // ======================================

    const sortValue =
        faqSort.value;


    if (sortValue === "questionAsc") {

        processedFAQs.sort((a, b) =>
            a.question.localeCompare(b.question)
        );

    }


    else if (sortValue === "questionDesc") {

        processedFAQs.sort((a, b) =>
            b.question.localeCompare(a.question)
        );

    }


    else if (sortValue === "categoryAsc") {

        processedFAQs.sort((a, b) =>
            a.category.localeCompare(b.category)
        );

    }


    else if (sortValue === "categoryDesc") {

        processedFAQs.sort((a, b) =>
            b.category.localeCompare(a.category)
        );

    }


    return processedFAQs;

}


// ==========================================
// RENDER FAQs
// ==========================================

function renderFAQs() {

    const processedFAQs =
        getProcessedFAQs();


    // ======================================
    // RESET PAGE IF NECESSARY
    // ======================================

    const totalPages =
        Math.ceil(
            processedFAQs.length / faqsPerPage
        );


    if (
        totalPages > 0 &&
        currentPage > totalPages
    ) {

        currentPage = totalPages;

    }


    // ======================================
    // NO RESULTS
    // ======================================

    if (processedFAQs.length === 0) {

        faqContainer.innerHTML = "";

        noFaqs.style.display = "block";

        faqPagination.innerHTML = "";

        return;

    }


    noFaqs.style.display = "none";


    // ======================================
    // PAGINATION
    // ======================================

    const startIndex =
        (currentPage - 1) * faqsPerPage;


    const endIndex =
        startIndex + faqsPerPage;


    const paginatedFAQs =
        processedFAQs.slice(
            startIndex,
            endIndex
        );


    // ======================================
    // CREATE HTML
    // ======================================

    faqContainer.innerHTML =
        paginatedFAQs.map(faq => {

            return `

                <div class="faq-item">

                    <button
                        class="faq-question"
                        type="button"
                    >

                        <span>
                            ${faq.question}
                        </span>

                        <span class="faq-icon">
                            +
                        </span>

                    </button>


                    <div class="faq-answer">

                        <span class="faq-category">
                            ${faq.category}
                        </span>

                        <p>
                            ${faq.answer}
                        </p>

                    </div>

                </div>

            `;

        }).join("");


    // ======================================
    // ADD FAQ CLICK EVENTS
    // ======================================

    addFAQEvents();


    // ======================================
    // RENDER PAGINATION
    // ======================================

    renderPagination(
        totalPages
    );

}


// ==========================================
// FAQ ACCORDION
// ==========================================

function addFAQEvents() {

    const questions =
        document.querySelectorAll(
            ".faq-question"
        );


    questions.forEach(question => {

        question.addEventListener(
            "click",
            () => {

                const faqItem =
                    question.closest(
                        ".faq-item"
                    );


                faqItem.classList.toggle(
                    "active"
                );


                const icon =
                    question.querySelector(
                        ".faq-icon"
                    );


                if (
                    faqItem.classList.contains(
                        "active"
                    )
                ) {

                    icon.textContent = "−";

                }

                else {

                    icon.textContent = "+";

                }

            }
        );

    });

}


// ==========================================
// PAGINATION
// ==========================================

function renderPagination(totalPages) {

    faqPagination.innerHTML = "";


    if (totalPages <= 1) {

        return;

    }


    // ======================================
    // PREVIOUS BUTTON
    // ======================================

    const previousButton =
        document.createElement("button");


    previousButton.textContent =
        "← Previous";


    previousButton.disabled =
        currentPage === 1;


    previousButton.addEventListener(
        "click",
        () => {

            if (currentPage > 1) {

                currentPage--;

                renderFAQs();

                scrollToFAQs();

            }

        }
    );


    faqPagination.appendChild(
        previousButton
    );


    // ======================================
    // PAGE NUMBERS
    // ======================================

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const pageButton =
            document.createElement("button");


        pageButton.textContent =
            page;


        if (page === currentPage) {

            pageButton.classList.add(
                "active"
            );

        }


        pageButton.addEventListener(
            "click",
            () => {

                currentPage = page;

                renderFAQs();

                scrollToFAQs();

            }
        );


        faqPagination.appendChild(
            pageButton
        );

    }


    // ======================================
    // NEXT BUTTON
    // ======================================

    const nextButton =
        document.createElement("button");


    nextButton.textContent =
        "Next →";


    nextButton.disabled =
        currentPage === totalPages;


    nextButton.addEventListener(
        "click",
        () => {

            if (currentPage < totalPages) {

                currentPage++;

                renderFAQs();

                scrollToFAQs();

            }

        }
    );


    faqPagination.appendChild(
        nextButton
    );

}


// ==========================================
// SEARCH EVENT
// ==========================================

faqSearch.addEventListener(
    "input",
    () => {

        currentPage = 1;

        renderFAQs();

    }
);


// ==========================================
// CATEGORY FILTER EVENT
// ==========================================

faqCategory.addEventListener(
    "change",
    () => {

        currentPage = 1;

        renderFAQs();

    }
);


// ==========================================
// SORT EVENT
// ==========================================

faqSort.addEventListener(
    "change",
    () => {

        currentPage = 1;

        renderFAQs();

    }
);


// ==========================================
// LOADING STATE
// ==========================================

function showLoading() {

    faqLoading.style.display = "block";

    faqError.style.display = "none";

    noFaqs.style.display = "none";

    faqContainer.innerHTML = "";

    faqPagination.innerHTML = "";

}


// ==========================================
// ERROR STATE
// ==========================================

function showError(message) {

    faqLoading.style.display = "none";

    faqError.style.display = "block";

    faqError.textContent = message;

    faqContainer.innerHTML = "";

    noFaqs.style.display = "none";

    faqPagination.innerHTML = "";

}


// ==========================================
// SCROLL TO FAQ SECTION
// ==========================================

function scrollToFAQs() {

    faqContainer.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });

}


// ==========================================
// START APPLICATION
// ==========================================

fetchFAQs();