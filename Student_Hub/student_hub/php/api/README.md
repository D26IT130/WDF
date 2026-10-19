# JSON API + Fetch API (events and students)

Open **pages/api-manager.php** (admin login required). Events and Students are listed, added, edited and
deleted with `fetch()`; the page never reloads. Open the browser **Network tab** (F12) to watch every call,
or expand "Last API call" at the bottom of the page.

## Endpoints
| URL | Method | Purpose | Access |
|---|---|---|---|
| `php/api/events.php` | GET | list (`q`, `category`, `status`, `page`, `per_page`) or one (`?id=5`) | public |
| `php/api/events.php` | POST | create (JSON body) -> 201 | admin |
| `php/api/events.php?id=5` | PUT | update (JSON body) | admin |
| `php/api/events.php?id=5` | DELETE | delete (also removes poster file) | admin |
| `php/api/students.php` | GET / POST / PUT / DELETE | same, filters `q`, `course`, `year`, `gender` | admin |
| `php/api/courses.php` | GET | courses for the dropdown | admin |
| `php/api/csrf.php` | GET | CSRF token for write requests | admin |

Posters are uploaded on **Manage Events** (multipart upload); the API returns `poster_url` and keeps the poster on update.
If a server blocks PUT/DELETE, send POST with the header `X-HTTP-Method-Override: PUT` (or `DELETE`).

## Response format (always JSON)
```json
{ "success": true,  "message": "Event \"X\" was added successfully.", "data": { }, "meta": { "page": 1, "per_page": 6, "total": 15, "pages": 3 } }
{ "success": false, "message": "Please correct the highlighted fields.", "errors": { "email": "Enter a valid email address." } }
```
| Status | Meaning |
|---|---|
| 200 / 201 | success / created |
| 400 | invalid JSON or invalid id |
| 401 / 403 | not logged in / not admin or bad CSRF token |
| 404 | record not found |
| 405 | method not allowed (response has an `Allow` header) |
| 409 | duplicate (email, or event title + date) |
| 415 | Content-Type is not application/json |
| 422 | validation failed (`errors` lists each field) |
| 500 | server/database error (never an HTML page) |

## Safety
- All SQL uses MySQLi prepared statements; input is validated **before** any database call (and in the browser first).
- Write requests need an admin session **and** the `X-CSRF-Token` header.
- The page writes data with `textContent` (never `innerHTML`), so stored data cannot inject HTML.

## Things to try (for the viva)
1. Network tab: list request is `GET events.php?page=1&per_page=6`, response `Content-Type: application/json`.
2. Add a student with email `abc` -> no request is sent (browser check). Then send bad data with the Network tab / curl -> 422 JSON.
3. Add a student with an existing email -> 409 and a message under the Email field.
4. Delete -> confirm box -> `DELETE students.php?id=N` -> row disappears without reload.
5. Open `php/api/students.php` while logged out -> 401 JSON. Send DELETE to `courses.php` -> 405.
6. Stop MySQL -> the table shows an error message and a "Try again" button.
