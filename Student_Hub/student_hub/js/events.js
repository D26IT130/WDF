// ============================================
// EVENTS APPLICATION
// ============================================


// ============================================
// VARIABLES
// ============================================

let allEvents = [];

let filteredEvents = [];

let currentPage = 1;

const eventsPerPage = 6;


// ============================================
// HTML ELEMENTS
// ============================================

const eventsContainer =
    document.getElementById("eventsContainer");

const eventSearch =
    document.getElementById("eventSearch");

const categoryFilter =
    document.getElementById("categoryFilter");

const sortEvents =
    document.getElementById("sortEvents");

const pagination =
    document.getElementById("pagination");

const eventsLoading =
    document.getElementById("eventsLoading");

const eventsError =
    document.getElementById("eventsError");

const noEvents =
    document.getElementById("noEvents");


// ============================================
// FETCH EVENTS
// ============================================

async function fetchEvents() {

    try {

        // Show loading message

        eventsLoading.style.display = "block";

        eventsError.style.display = "none";


        // Fetch JSON file

        const response =
            await fetch("../php/events_json.php");


        // Check response

        if (!response.ok) {

            throw new Error(
                "Unable to load events."
            );

        }


        // Convert response to JSON

        const data =
            await response.json();


        // Store data

        allEvents = data;

        filteredEvents = [...allEvents];


        // Hide loading

        eventsLoading.style.display = "none";


        // Display events

        processEvents();


    } catch (error) {

        console.error(
            "Error loading events:",
            error
        );


        eventsLoading.style.display = "none";


        eventsError.style.display = "block";


        eventsError.textContent =
            "Unable to load events. Please try again later.";

    }

}


// ============================================
// PROCESS EVENTS
// ============================================

function processEvents() {

    let result = [...allEvents];


    // ========================================
    // SEARCH
    // ========================================

    const searchText =
        eventSearch.value
            .toLowerCase()
            .trim();


    if (searchText !== "") {

        result = result.filter(function (event) {

            return (
                event.title
                    .toLowerCase()
                    .includes(searchText)

                ||

                event.description
                    .toLowerCase()
                    .includes(searchText)

                ||

                event.category
                    .toLowerCase()
                    .includes(searchText)
            );

        });

    }


    // ========================================
    // CATEGORY FILTER
    // ========================================

    const selectedCategory =
        categoryFilter.value;


    if (selectedCategory !== "all") {

        result = result.filter(function (event) {

            return event.category === selectedCategory;

        });

    }


    // ========================================
    // SORT
    // ========================================

    const sortValue =
        sortEvents.value;


    if (sortValue === "titleAsc") {

        result.sort(function (a, b) {

            return a.title.localeCompare(b.title);

        });

    }


    if (sortValue === "titleDesc") {

        result.sort(function (a, b) {

            return b.title.localeCompare(a.title);

        });

    }


    if (sortValue === "dateAsc") {

        result.sort(function (a, b) {

            return a.sortDate.localeCompare(b.sortDate);

        });

    }


    if (sortValue === "dateDesc") {

        result.sort(function (a, b) {

            return b.sortDate.localeCompare(a.sortDate);

        });

    }


    // Store final result

    filteredEvents = result;


    // Start from page 1 after filtering

    currentPage = 1;


    // Render events

    renderEvents();


    // Render pagination

    renderPagination();

}


// ============================================
// RENDER EVENTS
// ============================================

function renderEvents() {

    // Clear previous events

    eventsContainer.innerHTML = "";


    // No results

    if (filteredEvents.length === 0) {

        noEvents.style.display = "block";

        pagination.innerHTML = "";

        return;

    }


    noEvents.style.display = "none";


    // ========================================
    // PAGINATION CALCULATION
    // ========================================

    const startIndex =
        (currentPage - 1) * eventsPerPage;


    const endIndex =
        startIndex + eventsPerPage;


    const pageEvents =
        filteredEvents.slice(
            startIndex,
            endIndex
        );


    // ========================================
    // MAP EVENTS TO HTML
    // ========================================

    pageEvents.map(function (event) {

        const eventCard =
            document.createElement("div");

        eventCard.className = "event-card";


        // Poster (optional) and status come from the database
        const posterHtml = event.poster
            ? `<img src="${event.poster}" alt="Poster for ${event.title}" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px;margin-bottom:.6rem;">`
            : "";
        const statusHtml = event.status
            ? `<span class="event-category" style="margin-left:.4rem;">${event.status}</span>`
            : "";

        eventCard.innerHTML = `

            <div class="event-date">

                <span>
                    ${event.date}
                </span>

                <small>
                    ${event.month}
                </small>

            </div>


            <div class="event-info">

                ${posterHtml}
                <span class="event-category">
                    ${event.category}
                </span>
                ${statusHtml}

                <h3>
                    ${event.title}
                </h3>

                <p>
                    ${event.description}
                </p>

                <p>
                    📍 ${event.location}
                    &nbsp; | &nbsp;
                    🕐 ${event.time}
                </p>

                <button
                    class="btn event-register"
                    data-id="${event.id}"
                >
                    Register
                </button>

            </div>

        `;


        eventsContainer.appendChild(
            eventCard
        );

    });


    // Add register functionality

    addRegisterEvents();

}


// ============================================
// REGISTER BUTTON
// ============================================

// Sends the registration to the server (php/api/register_event.php)
async function registerForEvent(selectedEvent) {

    try {

        // 1) security token (needs a logged-in user)
        const tokenRes = await fetch("../php/api/csrf.php", { credentials: "same-origin" });

        if (tokenRes.status === 401) {
            alert("Please log in to register for an event.");
            window.location.href = "login.html";
            return;
        }

        const token = (await tokenRes.json()).data.csrf;

        // 2) register
        const res = await fetch("../php/api/register_event.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-Token": token
            },
            body: JSON.stringify({ event_id: selectedEvent.id })
        });

        const result = await res.json();
        alert(result.message);

    } catch (error) {

        alert("Could not register right now. Please try again.");

    }

}


function addRegisterEvents() {

    const registerButtons =
        document.querySelectorAll(
            ".event-register"
        );


    registerButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const eventId =
                    Number(
                        button.dataset.id
                    );


                const selectedEvent =
                    allEvents.find(function (event) {

                        return event.id === eventId;

                    });


                if (selectedEvent) {

                    registerForEvent(selectedEvent);

                }

            }
        );

    });

}


// ============================================
// PAGINATION
// ============================================

function renderPagination() {

    pagination.innerHTML = "";


    const totalPages =
        Math.ceil(
            filteredEvents.length /
            eventsPerPage
        );


    // Don't display pagination for one page

    if (totalPages <= 1) {

        return;

    }


    // ========================================
    // PREVIOUS BUTTON
    // ========================================

    const previousButton =
        document.createElement("button");

    previousButton.textContent =
        "Previous";

    previousButton.className =
        "page-button";


    if (currentPage === 1) {

        previousButton.disabled = true;

    }


    previousButton.addEventListener(
        "click",
        function () {

            if (currentPage > 1) {

                currentPage--;

                renderEvents();

                renderPagination();

            }

        }
    );


    pagination.appendChild(
        previousButton
    );


    // ========================================
    // PAGE NUMBERS
    // ========================================

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const pageButton =
            document.createElement("button");

        pageButton.textContent =
            page;

        pageButton.className =
            "page-button";


        if (page === currentPage) {

            pageButton.classList.add(
                "active"
            );

        }


        pageButton.addEventListener(
            "click",
            function () {

                currentPage = page;

                renderEvents();

                renderPagination();

            }
        );


        pagination.appendChild(
            pageButton
        );

    }


    // ========================================
    // NEXT BUTTON
    // ========================================

    const nextButton =
        document.createElement("button");

    nextButton.textContent =
        "Next";

    nextButton.className =
        "page-button";


    if (currentPage === totalPages) {

        nextButton.disabled = true;

    }


    nextButton.addEventListener(
        "click",
        function () {

            if (currentPage < totalPages) {

                currentPage++;

                renderEvents();

                renderPagination();

            }

        }
    );


    pagination.appendChild(
        nextButton
    );

}


// ============================================
// SEARCH EVENT
// ============================================

if (eventSearch) {

    eventSearch.addEventListener(
        "input",
        function () {

            processEvents();

        }
    );

}


// ============================================
// CATEGORY FILTER
// ============================================

if (categoryFilter) {

    categoryFilter.addEventListener(
        "change",
        function () {

            processEvents();

        }
    );

}


// ============================================
// SORT EVENTS
// ============================================

if (sortEvents) {

    sortEvents.addEventListener(
        "change",
        function () {

            processEvents();

        }
    );

}


// ============================================
// START APPLICATION
// ============================================

if (eventsContainer) {

    fetchEvents();

}
