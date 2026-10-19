# Student Management Module (PHP + MySQLi)

## Setup (XAMPP)
1. Copy the project into `htdocs/` and start Apache + MySQL.
2. phpMyAdmin > Import > `php/my_student_hub_schema.sql` (creates DB `my_student_hub`, tables, 24 students, 1 admin).
3. Open `http://localhost/<folder>/pages/login.html` and sign in as **Admin**:
   `admin@studenthub.com` / `Admin@123`
4. Admin page > **Manage Students** (`php/students/index.php`).

## Files
| File | Purpose |
|---|---|
| `php/students/index.php` | SELECT: list, search (name/email/course), filters (course/year/gender), pagination |
| `php/students/form.php` | INSERT (add) and UPDATE (edit) with validation |
| `php/students/delete.php` | DELETE after a confirmation page (POST only) |
| `php/students/_bootstrap.php` | admin check, MySQLi connection, CSRF, flash messages, validation |
| `php/my_student_hub_schema.sql` | schema + 24 seeded records |

## Viva points
- Every query uses `prepare()` + `bind_param()` + `execute()`: no user input is concatenated into SQL (SQL injection safe).
- Output is escaped with `htmlspecialchars` (XSS safe); forms carry a CSRF token.
- Delete never runs from a link: GET shows "Are you sure?", only POST deletes.
- Duplicate email is caught via the UNIQUE key (MySQL error 1062) and shown as a friendly message.
- LIKE wildcards typed by the user are escaped; pagination uses COUNT(*) + LIMIT/OFFSET.
- Foreign key `students.course_id -> courses` with ON DELETE RESTRICT keeps data consistent.

## Users module (operates on the `users` table)
- Import `php/users_seed.sql` (22 students + 1 admin; password for all seeded accounts: `Student@123`).
- `php/users/index.php` list, search (name/email/student ID), filter by role, pagination
- `php/users/form.php` add / edit (password hashed with `password_hash`, blank = keep old)
- `php/users/delete.php` delete after confirmation; you cannot delete your own account

## Events module (admin)
- Import `php/events_schema.sql` (15 sample events). Posters are optional and added via the form.
- `php/events/index.php` list, search (title/location/description), filter (category/status), pagination, quick status change
- `php/events/form.php` add / edit + poster upload; `delete.php` confirm + delete (also removes the poster file)
- Poster validation (server side): JPG/PNG/WEBP only, max 2 MB, real MIME type via `finfo`, `getimagesize()` decode check, random file name
- Files are saved in `uploads/events/` (its `.htaccess` blocks script execution)
- `php/events_json.php` feeds the public `pages/events.html` from the database
- If uploads fail on XAMPP, check `upload_max_filesize` / `post_max_size` in php.ini (defaults are fine for 2 MB)

## Admin dashboard, role-based menu and audit logs
- Import `php/admin_dashboard_schema.sql` (creates `registrations` + `audit_logs`, 40 sample registrations, sample audit rows).
- `pages/admin-dashboard.php` (admins only): user / student / event / registration counts, users by role, events by status,
  students by course, latest registrations, newest users, popular events, recent audit activity. No JavaScript is used.
- `php/admin/audit_logs.php`: full audit trail with filters and pagination (read-only).
- Menu: `php/menu.php` defines the menu once per role (guest / student / admin). PHP pages render it directly;
  static .html pages get it from `php/menu_json.php` through `js/script.js`.
- Audit logging (`php/audit.php`): login, failed login, logout, create / update / delete of users, students and events,
  event status changes, and event registrations (both the PHP pages and the JSON API).
- Students register for events with the Register button on the public Events page (`php/api/register_event.php`).
- After login an admin lands on the admin dashboard; a student who opens it gets "Access denied" (403).
