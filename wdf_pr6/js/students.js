// ==========================================
// STUDENT PROFILE APPLICATION
// ==========================================

const studentContainer =
    document.getElementById("studentContainer");

const searchInput =
    document.getElementById("searchInput");

const courseFilter =
    document.getElementById("courseFilter");

const semesterFilter =
    document.getElementById("semesterFilter");

const sortSelect =
    document.getElementById("sortSelect");

const pagination =
    document.getElementById("pagination");

const loading =
    document.getElementById("loading");

const errorMessage =
    document.getElementById("error");

const noResults =
    document.getElementById("noResults");


let students = [];

let currentPage = 1;

const studentsPerPage = 6;


// ==========================================
// FETCH STUDENTS
// ==========================================

async function fetchStudents() {

    try {

        loading.style.display = "block";
        errorMessage.style.display = "none";

        const response =
            await fetch("../data/students.json");

        if (!response.ok) {
            throw new Error("Failed to load student data.");
        }

        students = await response.json();

        loading.style.display = "none";

        createFilters();

        displayStudents();

    } catch (error) {

        loading.style.display = "none";

        errorMessage.style.display = "block";

        errorMessage.textContent =
            "Error loading students: " + error.message;

    }
}


// ==========================================
// CREATE FILTER OPTIONS
// ==========================================

function createFilters() {

    // Courses
    const courses = [
        ...new Set(
            students.map(student => student.course)
        )
    ];

    courses.forEach(course => {

        const option =
            document.createElement("option");

        option.value = course;

        option.textContent = course;

        courseFilter.appendChild(option);

    });


    // Semesters
    const semesters = [
        ...new Set(
            students.map(student => student.semester)
        )
    ].sort((a, b) => a - b);


    semesters.forEach(semester => {

        const option =
            document.createElement("option");

        option.value = semester;

        option.textContent =
            `Semester ${semester}`;

        semesterFilter.appendChild(option);

    });

}


// ==========================================
// SEARCH + FILTER + SORT
// ==========================================

function getFilteredStudents() {

    let result = [...students];


    // SEARCH
    const searchText =
        searchInput.value
            .toLowerCase()
            .trim();


    if (searchText) {

        result = result.filter(student =>

            student.name
                .toLowerCase()
                .includes(searchText)

            ||

            student.email
                .toLowerCase()
                .includes(searchText)

            ||

            student.course
                .toLowerCase()
                .includes(searchText)

            ||

            student.city
                .toLowerCase()
                .includes(searchText)

        );

    }


    // COURSE FILTER

    if (courseFilter.value !== "all") {

        result = result.filter(
            student =>
                student.course === courseFilter.value
        );

    }


    // SEMESTER FILTER

    if (semesterFilter.value !== "all") {

        result = result.filter(
            student =>
                student.semester ==
                semesterFilter.value
        );

    }


    // SORT

    switch (sortSelect.value) {

        case "name-asc":

            result.sort((a, b) =>
                a.name.localeCompare(b.name)
            );

            break;


        case "name-desc":

            result.sort((a, b) =>
                b.name.localeCompare(a.name)
            );

            break;


        case "cgpa-high":

            result.sort(
                (a, b) => b.cgpa - a.cgpa
            );

            break;


        case "cgpa-low":

            result.sort(
                (a, b) => a.cgpa - b.cgpa
            );

            break;

    }


    return result;
}


// ==========================================
// DISPLAY STUDENTS
// ==========================================

function displayStudents() {

    const filteredStudents =
        getFilteredStudents();


    studentContainer.innerHTML = "";


    // No results

    if (filteredStudents.length === 0) {

        noResults.style.display = "block";

        pagination.innerHTML = "";

        return;

    }


    noResults.style.display = "none";


    // Pagination calculation

    const startIndex =
        (currentPage - 1) *
        studentsPerPage;

    const endIndex =
        startIndex + studentsPerPage;


    const pageStudents =
        filteredStudents.slice(
            startIndex,
            endIndex
        );


    // Render students

    pageStudents.forEach(student => {

        const card =
            document.createElement("div");

        card.className = "student-card";


        card.innerHTML = `

            <h3>${student.name}</h3>

            <p>
                <strong>Email:</strong>
                ${student.email}
            </p>

            <p>
                <strong>Course:</strong>
                ${student.course}
            </p>

            <p>
                <strong>Semester:</strong>
                ${student.semester}
            </p>

            <p>
                <strong>City:</strong>
                ${student.city}
            </p>

            <p>
                <strong>CGPA:</strong>
                ${student.cgpa}
            </p>

        `;


        studentContainer.appendChild(card);

    });


    createPagination(
        filteredStudents.length
    );

}


// ==========================================
// PAGINATION
// ==========================================

function createPagination(totalStudents) {

    pagination.innerHTML = "";


    const totalPages =
        Math.ceil(
            totalStudents / studentsPerPage
        );


    // Previous

    const previous =
        document.createElement("button");

    previous.textContent = "Previous";

    previous.disabled =
        currentPage === 1;


    previous.onclick = () => {

        currentPage--;

        displayStudents();

    };


    pagination.appendChild(previous);


    // Page numbers

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const button =
            document.createElement("button");

        button.textContent = page;


        if (page === currentPage) {
            button.classList.add("active");
        }


        button.onclick = () => {

            currentPage = page;

            displayStudents();

        };


        pagination.appendChild(button);

    }


    // Next

    const next =
        document.createElement("button");

    next.textContent = "Next";

    next.disabled =
        currentPage === totalPages;


    next.onclick = () => {

        currentPage++;

        displayStudents();

    };


    pagination.appendChild(next);

}


// ==========================================
// EVENT LISTENERS
// ==========================================

searchInput.addEventListener(
    "input",
    () => {

        currentPage = 1;

        displayStudents();

    }
);


courseFilter.addEventListener(
    "change",
    () => {

        currentPage = 1;

        displayStudents();

    }
);


semesterFilter.addEventListener(
    "change",
    () => {

        currentPage = 1;

        displayStudents();

    }
);


sortSelect.addEventListener(
    "change",
    () => {

        currentPage = 1;

        displayStudents();

    }
);


// ==========================================
// START
// ==========================================

fetchStudents();
