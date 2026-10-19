/* =========================================================
   API MANAGER - asynchronous CRUD with the Fetch API
   Talks to php/api/events.php and php/api/students.php.
   List / add / update / delete happen without reloading the page.
   All data is written to the page with textContent (never innerHTML),
   so database values cannot inject HTML.
   ========================================================= */

(function () {
    "use strict";

    const API = "../php/api/";
    const PER_PAGE = 6;

    /* ---------- page elements ---------- */
    const $ = (id) => document.getElementById(id);
    const els = {
        add: $("addBtn"), msg: $("apiMessage"), form: $("apiForm"), formTitle: $("formTitle"),
        formFields: $("formFields"), save: $("saveBtn"), cancel: $("cancelBtn"),
        filters: $("filterForm"), loading: $("loadingBar"), head: $("tableHead"),
        body: $("tableBody"), pager: $("pager"), log: $("apiLog"),
    };

    /* ---------- state ---------- */
    const state = {
        resource: "events",
        page: 1,
        q: "",
        filters: {},
        editingId: null,
        courses: [],
        csrf: "",
        controller: null,   // cancels an older list request when a newer one starts
        busy: false,
    };

    /* ---------- what differs between events and students ---------- */
    const opt = (list) => list.map((v) => ({ value: v, label: v }));

    function resources() {
        return {
            events: {
                endpoint: "events.php",
                singular: "event",
                title: (r) => r.title,
                searchHint: "Search title, location or description",
                columns: [
                    { label: "Poster", cell: (r) => r.poster_url ? h("img", { class: "api-thumb", src: "../" + r.poster_url, alt: "Poster for " + r.title }) : "-" },
                    { label: "Title", cell: (r) => r.title },
                    { label: "Date & time", cell: (r) => r.event_date + " " + r.event_time },
                    { label: "Location", cell: (r) => r.location },
                    { label: "Category", cell: (r) => r.category },
                    { label: "Status", cell: (r) => h("span", { class: "api-badge" }, r.status) },
                ],
                fields: [
                    { name: "title", label: "Title", type: "text", wide: true, max: 150 },
                    { name: "description", label: "Description", type: "textarea", wide: true, max: 2000 },
                    { name: "event_date", label: "Date", type: "date" },
                    { name: "event_time", label: "Time", type: "time" },
                    { name: "location", label: "Location", type: "text", max: 150 },
                    { name: "category", label: "Category", type: "select", options: () => opt(["Workshop", "Competition", "Seminar", "Exhibition"]) },
                    { name: "status", label: "Status", type: "select", options: () => opt(["Upcoming", "Ongoing", "Completed", "Cancelled"]), initial: "Upcoming" },
                ],
                filters: [
                    { name: "category", label: "Category", options: () => opt(["Workshop", "Competition", "Seminar", "Exhibition"]) },
                    { name: "status", label: "Status", options: () => opt(["Upcoming", "Ongoing", "Completed", "Cancelled"]) },
                ],
                validate: (v) => {
                    const e = {};
                    if (!v.title) e.title = "Title is required.";
                    else if (v.title.length > 150) e.title = "Title must be at most 150 characters.";
                    if (!v.description) e.description = "Description is required.";
                    else if (v.description.length > 2000) e.description = "Description must be at most 2000 characters.";
                    if (!/^\d{4}-\d{2}-\d{2}$/.test(v.event_date)) e.event_date = "Choose a date.";
                    if (!/^\d{2}:\d{2}/.test(v.event_time)) e.event_time = "Choose a time.";
                    if (!v.location) e.location = "Location is required.";
                    if (!v.category) e.category = "Select a category.";
                    if (!v.status) e.status = "Select a status.";
                    return e;
                },
            },
            students: {
                endpoint: "students.php",
                singular: "student",
                title: (r) => r.name,
                searchHint: "Search name, email or course",
                columns: [
                    { label: "Name", cell: (r) => r.name },
                    { label: "Email", cell: (r) => r.email },
                    { label: "Mobile", cell: (r) => r.mobile },
                    { label: "Course", cell: (r) => r.course_name },
                    { label: "Year", cell: (r) => String(r.year) },
                    { label: "Gender", cell: (r) => r.gender },
                ],
                fields: [
                    { name: "name", label: "Full name", type: "text", max: 100 },
                    { name: "email", label: "Email", type: "email", max: 150 },
                    { name: "mobile", label: "Mobile (10 digits)", type: "text", max: 10 },
                    { name: "course_id", label: "Course", type: "select", options: () => state.courses.map((c) => ({ value: String(c.course_id), label: c.course_name })) },
                    { name: "year", label: "Year", type: "select", options: () => [1, 2, 3, 4].map((y) => ({ value: String(y), label: "Year " + y })) },
                    { name: "gender", label: "Gender", type: "select", options: () => opt(["Male", "Female", "Other"]) },
                ],
                filters: [
                    { name: "course", label: "Course", options: () => state.courses.map((c) => ({ value: String(c.course_id), label: c.course_name })) },
                    { name: "year", label: "Year", options: () => [1, 2, 3, 4].map((y) => ({ value: String(y), label: "Year " + y })) },
                    { name: "gender", label: "Gender", options: () => opt(["Male", "Female", "Other"]) },
                ],
                validate: (v) => {
                    const e = {};
                    if (!v.name) e.name = "Name is required.";
                    else if (!/^[A-Za-z\u00C0-\u024F][A-Za-z\u00C0-\u024F .'-]*$/.test(v.name)) e.name = "Name may contain only letters, spaces, dots, hyphens and apostrophes.";
                    if (!v.email) e.email = "Email is required.";
                    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email)) e.email = "Enter a valid email address.";
                    if (!/^\d{10}$/.test(v.mobile)) e.mobile = "Mobile must be exactly 10 digits.";
                    if (!v.course_id) e.course_id = "Select a course.";
                    if (!v.year) e.year = "Select a year.";
                    if (!v.gender) e.gender = "Select a gender.";
                    return e;
                },
            },
        };
    }
    const RES = resources();
    const cfg = () => RES[state.resource];

    /* ---------- tiny DOM helper (children are always text, never HTML) ---------- */
    function h(tag, props, ...kids) {
        const node = document.createElement(tag);
        Object.entries(props || {}).forEach(([k, v]) => {
            if (v === false || v === null || v === undefined) return;
            if (k === "class") node.className = v;
            else node.setAttribute(k, v === true ? "" : v);
        });
        kids.flat().forEach((kid) => {
            if (kid === null || kid === undefined || kid === false) return;
            node.append(kid instanceof Node ? kid : document.createTextNode(String(kid)));
        });
        return node;
    }

    /* =====================================================
       FETCH WRAPPER - one place that handles headers, JSON,
       HTTP errors and network failures
       ===================================================== */
    class ApiError extends Error {
        constructor(message, status, errors) {
            super(message);
            this.status = status || 0;
            this.errors = errors || {};
        }
    }

    function logCall(method, url, status, payload) {
        const body = typeof payload === "string" ? payload : JSON.stringify(payload, null, 2);
        els.log.textContent = method + " " + url + "\nHTTP status: " + status + "\n\n" + body;
    }

    async function apiFetch(path, options) {
        const { method = "GET", body, signal } = options || {};
        const url = API + path;
        const headers = { Accept: "application/json" };
        const init = { method, headers, credentials: "same-origin", signal };

        if (method !== "GET") {
            headers["X-CSRF-Token"] = state.csrf;
            if (body !== undefined) {
                headers["Content-Type"] = "application/json";
                init.body = JSON.stringify(body);
            }
        }

        let res;
        try {
            res = await fetch(url, init);
        } catch (err) {
            if (err.name === "AbortError") throw err;
            logCall(method, url, "no response", String(err));
            throw new ApiError("Network error: the server could not be reached.", 0);
        }

        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch (e) { /* not JSON */ }
        logCall(method, url, res.status, json !== null ? json : text);

        if (json === null) {
            throw new ApiError("The server did not return valid JSON (HTTP " + res.status + ").", res.status);
        }
        if (!res.ok || json.success === false) {
            let msg = json.message || "Request failed.";
            if (res.status === 401) msg = "Your session has expired. Please log in again.";
            throw new ApiError(msg, res.status, json.errors);
        }
        return json;
    }

    /* ---------- messages ---------- */
    let msgTimer = null;
    function notify(type, text) {
        clearTimeout(msgTimer);
        els.msg.className = "crud-alert crud-alert-" + (type === "success" ? "success" : "error");
        els.msg.textContent = text;
        els.msg.hidden = false;
        if (type === "success") msgTimer = setTimeout(() => { els.msg.hidden = true; }, 5000);
    }
    function clearNotice() { clearTimeout(msgTimer); els.msg.hidden = true; }

    function setBusy(on) {
        state.busy = on;
        els.loading.hidden = !on;
        els.save.disabled = on;
    }

    /* =====================================================
       LIST (GET)
       ===================================================== */
    function buildQuery() {
        const p = new URLSearchParams();
        if (state.q) p.set("q", state.q);
        Object.entries(state.filters).forEach(([k, v]) => { if (v) p.set(k, v); });
        p.set("page", String(state.page));
        p.set("per_page", String(PER_PAGE));
        return p.toString();
    }

    async function loadList() {
        if (state.controller) state.controller.abort();         // drop the older request
        const controller = new AbortController();
        state.controller = controller;
        setBusy(true);

        try {
            const json = await apiFetch(cfg().endpoint + "?" + buildQuery(), { signal: controller.signal });
            const meta = json.meta;

            // the last row of the last page was just deleted -> go back one page
            if (json.data.length === 0 && meta.total > 0 && state.page > meta.pages) {
                state.page = meta.pages;
                return loadList();
            }
            renderTable(json.data);
            renderPager(meta);
        } catch (err) {
            if (err.name === "AbortError") return;
            renderTableError(err.message);
            els.pager.replaceChildren();
            notify("error", err.message);
        } finally {
            if (state.controller === controller) setBusy(false);
        }
    }

    function renderTable(rows) {
        const c = cfg();
        els.head.replaceChildren(h("tr", null, c.columns.map((col) => h("th", null, col.label)), h("th", null, "Actions")));

        if (rows.length === 0) {
            els.body.replaceChildren(h("tr", null, h("td", { colspan: c.columns.length + 1, class: "crud-empty" }, "No " + c.singular + "s match your search or filters.")));
            return;
        }

        els.body.replaceChildren(...rows.map((r) => {
            const editBtn = h("button", { type: "button", class: "crud-link" }, "Edit");
            const delBtn = h("button", { type: "button", class: "crud-link crud-link-danger" }, "Delete");
            editBtn.addEventListener("click", () => openForm(r.id));
            delBtn.addEventListener("click", () => deleteRecord(r));
            return h("tr", null,
                c.columns.map((col) => h("td", { "data-label": col.label }, col.cell(r))),
                h("td", { "data-label": "Actions", class: "crud-row-actions" }, editBtn, " ", delBtn));
        }));
    }

    function renderTableError(message) {
        const retry = h("button", { type: "button", class: "btn btn-outline" }, "Try again");
        retry.addEventListener("click", loadList);
        els.body.replaceChildren(h("tr", null, h("td", { colspan: 99, class: "crud-empty" }, message + " ", retry)));
    }

    function renderPager(meta) {
        if (meta.pages <= 1) { els.pager.replaceChildren(); return; }
        const go = (n) => { state.page = n; loadList(); };
        const btn = (label, n, extra) => {
            const b = h("button", { type: "button", class: extra || "" }, label);
            b.addEventListener("click", () => go(n));
            return b;
        };
        const items = [];
        const prev = btn("\u00AB Prev", meta.page - 1); prev.disabled = meta.page <= 1; items.push(prev);
        for (let i = 1; i <= meta.pages; i++) items.push(btn(String(i), i, i === meta.page ? "active" : ""));
        const next = btn("Next \u00BB", meta.page + 1); next.disabled = meta.page >= meta.pages; items.push(next);
        els.pager.replaceChildren(...items);
    }

    /* =====================================================
       SEARCH + FILTERS
       ===================================================== */
    function buildFilters() {
        const c = cfg();
        const search = h("input", { type: "search", id: "fq", name: "q", placeholder: c.searchHint, value: state.q });
        const parts = [h("div", { class: "crud-field crud-field-wide" }, h("label", { for: "fq" }, "Search"), search)];

        c.filters.forEach((f) => {
            const sel = h("select", { id: "f_" + f.name, name: f.name },
                h("option", { value: "" }, "All"),
                f.options().map((o) => h("option", { value: o.value, selected: state.filters[f.name] === o.value }, o.label)));
            sel.addEventListener("change", applyFilters);
            parts.push(h("div", { class: "crud-field" }, h("label", { for: "f_" + f.name }, f.label), sel));
        });

        const reset = h("button", { type: "button", class: "btn btn-outline" }, "Reset");
        reset.addEventListener("click", () => { state.q = ""; state.filters = {}; state.page = 1; buildFilters(); loadList(); });
        parts.push(h("div", { class: "crud-field crud-actions" }, h("button", { type: "submit", class: "btn" }, "Search"), reset));
        els.filters.replaceChildren(...parts);

        let timer = null;
        search.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(applyFilters, 350); });
    }

    function applyFilters() {
        const fd = new FormData(els.filters);
        state.q = String(fd.get("q") || "").trim();
        state.filters = {};
        cfg().filters.forEach((f) => { const v = String(fd.get(f.name) || ""); if (v) state.filters[f.name] = v; });
        state.page = 1;
        loadList();
    }
    els.filters.addEventListener("submit", (e) => { e.preventDefault(); applyFilters(); });

    /* =====================================================
       ADD / EDIT FORM   (POST / PUT)
       ===================================================== */
    function buildForm(values) {
        const c = cfg();
        els.formFields.replaceChildren(...c.fields.map((f) => {
            const id = "ff_" + f.name;
            let input;
            const current = values && values[f.name] !== undefined && values[f.name] !== null ? String(values[f.name]) : (f.initial || "");
            if (f.type === "textarea") {
                input = h("textarea", { id, name: f.name, rows: 4, maxlength: f.max }, current);
            } else if (f.type === "select") {
                input = h("select", { id, name: f.name },
                    h("option", { value: "" }, "Select..."),
                    f.options().map((o) => h("option", { value: o.value, selected: current === o.value }, o.label)));
            } else {
                input = h("input", { id, name: f.name, type: f.type, maxlength: f.max, value: current, autocomplete: "off" });
            }
            return h("div", { class: "crud-field" + (f.wide ? " api-field-wide" : ""), "data-field": f.name },
                h("label", { for: id }, f.label), input, h("small", { class: "crud-error", hidden: true }));
        }));
    }

    function showFieldErrors(errors) {
        els.formFields.querySelectorAll("[data-field]").forEach((box) => {
            const msg = errors[box.dataset.field];
            const small = box.querySelector("small");
            small.textContent = msg || "";
            small.hidden = !msg;
            box.classList.toggle("api-field-invalid", !!msg);
        });
        const first = Object.keys(errors)[0];
        const target = first && els.formFields.querySelector('[name="' + first + '"]');
        if (target) target.focus();
    }

    function readForm() {
        const values = {};
        cfg().fields.forEach((f) => {
            const el = els.formFields.querySelector('[name="' + f.name + '"]');
            values[f.name] = el ? el.value.trim() : "";
        });
        return values;
    }

    async function openForm(id) {
        clearNotice();
        state.editingId = id || null;
        els.formTitle.textContent = (id ? "Edit " : "Add ") + cfg().singular;
        let record = null;

        if (id) {                                             // load the fresh record with GET ?id=
            setBusy(true);
            try {
                record = (await apiFetch(cfg().endpoint + "?id=" + encodeURIComponent(id))).data;
            } catch (err) {
                notify("error", err.message);
                if (err.status === 404) loadList();
                return;
            } finally {
                setBusy(false);
            }
        }
        buildForm(record);
        showFieldErrors({});
        els.form.hidden = false;
        els.form.scrollIntoView({ behavior: "smooth", block: "nearest" });
        const first = els.formFields.querySelector("input, select, textarea");
        if (first) first.focus();
    }

    function closeForm() {
        els.form.hidden = true;
        state.editingId = null;
        els.formFields.replaceChildren();
    }

    els.form.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (state.busy) return;
        clearNotice();

        const values = readForm();
        const clientErrors = cfg().validate(values);          // 1) check in the browser first
        showFieldErrors(clientErrors);
        if (Object.keys(clientErrors).length) {
            notify("error", "Please correct the highlighted fields.");
            return;
        }

        const editing = state.editingId;
        const label = els.save.textContent;
        els.save.textContent = "Saving...";
        setBusy(true);
        try {                                                 // 2) the server validates again
            const json = await apiFetch(cfg().endpoint + (editing ? "?id=" + encodeURIComponent(editing) : ""), {
                method: editing ? "PUT" : "POST",
                body: values,
            });
            notify("success", json.message);
            closeForm();
            if (!editing) state.page = 1;                     // new records appear first
            await loadList();
        } catch (err) {
            if (err.status === 422 || err.status === 409) showFieldErrors(err.errors);
            notify("error", err.message);
        } finally {
            els.save.textContent = label;
            setBusy(false);
        }
    });

    els.cancel.addEventListener("click", () => { closeForm(); clearNotice(); });
    els.add.addEventListener("click", () => openForm(null));

    /* =====================================================
       DELETE
       ===================================================== */
    async function deleteRecord(record) {
        const c = cfg();
        if (!window.confirm('Delete ' + c.singular + ' "' + c.title(record) + '"? This cannot be undone.')) return;
        if (state.busy) return;
        clearNotice();
        setBusy(true);
        try {
            const json = await apiFetch(c.endpoint + "?id=" + encodeURIComponent(record.id), { method: "DELETE" });
            if (state.editingId === record.id) closeForm();
            notify("success", json.message);
            await loadList();
        } catch (err) {
            notify("error", err.message);
            if (err.status === 404) await loadList();         // already gone: refresh the table
        } finally {
            setBusy(false);
        }
    }

    /* =====================================================
       TABS + START-UP
       ===================================================== */
    async function ensureCourses() {
        if (state.courses.length) return;
        const json = await apiFetch("courses.php");
        state.courses = json.data;
    }

    async function switchTo(resource) {
        state.resource = resource;
        state.page = 1; state.q = ""; state.filters = {};
        closeForm(); clearNotice();
        document.querySelectorAll(".api-tab").forEach((t) => {
            const on = t.dataset.resource === resource;
            t.classList.toggle("active", on);
            t.setAttribute("aria-selected", String(on));
        });
        els.add.textContent = "+ Add " + cfg().singular;
        setBusy(true);
        try {
            if (resource === "students") await ensureCourses();
        } catch (err) {
            notify("error", err.message);
        } finally {
            setBusy(false);
        }
        buildFilters();
        loadList();
    }

    document.querySelectorAll(".api-tab").forEach((t) => {
        t.addEventListener("click", () => { if (t.dataset.resource !== state.resource) switchTo(t.dataset.resource); });
    });

    (async function start() {
        setBusy(true);
        try {
            state.csrf = (await apiFetch("csrf.php")).data.csrf;   // token needed for POST / PUT / DELETE
        } catch (err) {
            notify("error", err.message);
        } finally {
            setBusy(false);
        }
        switchTo("events");
    })();
})();
