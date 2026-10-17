document.addEventListener("DOMContentLoaded", () => {

    const DATA_URL = "../data/students.json";
    const CACHE_KEY = "studentsCache";
    const PAGE_SIZE = 6;

    const grid = document.getElementById("studentGrid");
    const message = document.getElementById("studentMessage");
    const pagination = document.getElementById("studentPagination");
    const searchInput = document.getElementById("studentSearch");
    const courseFilter = document.getElementById("courseFilter");
    const statusFilter = document.getElementById("statusFilter");
    const sortSelect = document.getElementById("sortSelect");
    const totalStudents = document.getElementById("totalStudents");

    let students = [];
    let currentPage = 1;
    let fromCache = false;

    /* ---------- FETCH (with localStorage cache fallback) ---------- */
    async function fetchStudents() {
        try {
            const response = await fetch(DATA_URL);
            if (!response.ok) throw new Error("HTTP " + response.status);

            const data = await response.json();
            if (!Array.isArray(data)) throw new Error("Invalid JSON format");

            localStorage.setItem(CACHE_KEY, JSON.stringify(data));
            fromCache = false;
            return data;
        } catch (error) {
            const cached = localStorage.getItem(CACHE_KEY);
            if (cached) {
                fromCache = true;
                return JSON.parse(cached);
            }
            throw error;
        }
    }

    /* ---------- SEARCH / FILTER / SORT / PAGINATE ---------- */
    function searchList(list, term) {
        const t = term.trim().toLowerCase();
        if (!t) return list;
        return list.filter((s) =>
            [s.name, s.email, s.city, s.course].some((f) => f.toLowerCase().includes(t))
        );
    }

    function filterList(list, course, status) {
        return list.filter((s) =>
            (course === "all" || s.course === course) &&
            (status === "all" || s.status === status)
        );
    }

    function sortList(list, key) {
        if (key === "default") return list;
        const [field, dir] = key.split("-");
        const sorted = [...list].sort((a, b) =>
            typeof a[field] === "string" ? a[field].localeCompare(b[field]) : a[field] - b[field]
        );
        return dir === "desc" ? sorted.reverse() : sorted;
    }

    function paginate(list, page) {
        const totalPages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
        const safePage = Math.min(Math.max(page, 1), totalPages);
        const start = (safePage - 1) * PAGE_SIZE;
        return { items: list.slice(start, start + PAGE_SIZE), page: safePage, totalPages };
    }

    /* ---------- RENDER ---------- */
    function getInitials(name) {
        return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
    }

    function createCard(s) {
        const card = document.createElement("article");
        card.className = "stu-card";

        const avatar = document.createElement("div");
        avatar.className = "stu-avatar";
        const initials = document.createElement("strong");
        initials.textContent = getInitials(s.name);
        const year = document.createElement("span");
        year.textContent = "YEAR " + s.year;
        avatar.append(initials, year);

        const body = document.createElement("div");
        body.className = "stu-body";

        const pill = document.createElement("span");
        pill.className = "stu-pill";
        pill.textContent = s.course;

        const name = document.createElement("h3");
        name.textContent = s.name;

        const email = document.createElement("p");
        email.className = "stu-email";
        email.textContent = s.email;

        const meta = document.createElement("p");
        meta.className = "stu-meta";
        meta.textContent = "📍 " + s.city + "  |  🎓 CGPA " + s.cgpa;

        const status = document.createElement("span");
        status.className = "stu-status " + (s.status === "Active" ? "stu-active" : "stu-inactive");
        status.textContent = s.status;

        const link = document.createElement("a");
        link.className = "stu-btn";
        link.href = "mailto:" + s.email;
        link.textContent = "Contact";

        const actions = document.createElement("div");
        actions.className = "stu-actions";
        actions.append(link, status);

        body.append(pill, name, email, meta, actions);
        card.append(avatar, body);
        return card;
    }

    function renderPagination(page, totalPages) {
        pagination.innerHTML = "";
        if (totalPages <= 1) return;

        const add = (label, target, disabled, active) => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.textContent = label;
            btn.disabled = disabled;
            if (active) {
                btn.classList.add("active");
                btn.setAttribute("aria-current", "page");
            }
            btn.addEventListener("click", () => {
                currentPage = target;
                update();
            });
            pagination.appendChild(btn);
        };

        add("Prev", page - 1, page === 1, false);
        for (let i = 1; i <= totalPages; i++) add(String(i), i, false, i === page);
        add("Next", page + 1, page === totalPages, false);
    }

    function setMessage(text, isError = false) {
        message.textContent = text;
        message.classList.toggle("error", isError);
    }

    function update() {
        const searched = searchList(students, searchInput.value);
        const filtered = filterList(searched, courseFilter.value, statusFilter.value);
        const sorted = sortList(filtered, sortSelect.value);
        const { items, page, totalPages } = paginate(sorted, currentPage);
        currentPage = page;

        grid.innerHTML = "";
        items.forEach((s) => grid.appendChild(createCard(s)));
        renderPagination(page, totalPages);

        if (sorted.length === 0) {
            setMessage("No students match your search or filters.");
        } else {
            const note = fromCache ? " (offline copy)" : "";
            setMessage("Showing " + items.length + " of " + sorted.length + " student(s)" + note + ".");
        }
    }

    function resetAndUpdate() {
        currentPage = 1;
        update();
    }

    /* ---------- INIT ---------- */
    async function init() {
        setMessage("Loading students...");
        try {
            students = await fetchStudents();

            if (totalStudents) totalStudents.textContent = students.length;

            [...new Set(students.map((s) => s.course))].sort().forEach((course) => {
                const option = document.createElement("option");
                option.value = course;
                option.textContent = course;
                courseFilter.appendChild(option);
            });

            update();
        } catch (error) {
            console.error("Could not load students:", error);
            grid.innerHTML = "";
            pagination.innerHTML = "";
            setMessage("Unable to load student data. Please try again later.", true);
        }
    }

    searchInput.addEventListener("input", resetAndUpdate);
    courseFilter.addEventListener("change", resetAndUpdate);
    statusFilter.addEventListener("change", resetAndUpdate);
    sortSelect.addEventListener("change", resetAndUpdate);

    init();
});