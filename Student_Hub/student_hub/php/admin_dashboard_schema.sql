-- =========================================================
-- STUDENT HUB - Admin dashboard tables
-- Import AFTER my_student_hub_schema.sql, users_seed.sql and events_schema.sql
-- (database: my_student_hub). Safe to re-run.
-- =========================================================
USE my_student_hub;

-- ---------- event registrations (a user registers for an event) ----------
CREATE TABLE IF NOT EXISTS registrations (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    event_id      INT UNSIGNED NOT NULL,
    status        ENUM('registered','cancelled') NOT NULL DEFAULT 'registered',
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registration (user_id, event_id),
    KEY idx_reg_event (event_id),
    KEY idx_reg_time (registered_at)
) ENGINE=InnoDB;

-- ---------- audit trail of important actions ----------
CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT DEFAULT NULL,
    user_name  VARCHAR(100) NOT NULL DEFAULT 'System',
    user_role  VARCHAR(20)  DEFAULT NULL,
    action     VARCHAR(30)  NOT NULL,
    entity     VARCHAR(30)  NOT NULL,
    entity_id  INT DEFAULT NULL,
    details    VARCHAR(255) NOT NULL DEFAULT '',
    ip_address VARCHAR(45)  DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_time (created_at),
    KEY idx_audit_action (action),
    KEY idx_audit_entity (entity)
) ENGINE=InnoDB;

-- ---------- sample registrations (matched by user email + event title) ----------
INSERT IGNORE INTO registrations (user_id, event_id, status, registered_at)
SELECT u.id, e.id, 'registered', p.at
FROM (
    SELECT 'aarav.patel@example.com' AS email, 'Student Project Exhibition' AS title, '2026-10-09 04:35:00' AS at
    UNION ALL
    SELECT 'aarav.patel@example.com' AS email, 'Web Development Workshop' AS title, '2026-09-29 07:56:00' AS at
    UNION ALL
    SELECT 'aditya.verma@example.com' AS email, 'Database Management Workshop' AS title, '2026-10-02 05:37:00' AS at
    UNION ALL
    SELECT 'aditya.verma@example.com' AS email, 'Hackathon 2026' AS title, '2026-10-01 07:02:00' AS at
    UNION ALL
    SELECT 'ananya.desai@example.com' AS email, 'AI and Machine Learning Seminar' AS title, '2026-10-03 02:58:00' AS at
    UNION ALL
    SELECT 'ananya.desai@example.com' AS email, 'Student Project Exhibition' AS title, '2026-10-16 19:34:00' AS at
    UNION ALL
    SELECT 'dhruv.parmar@example.com' AS email, 'Coding Challenge' AS title, '2026-10-17 01:55:00' AS at
    UNION ALL
    SELECT 'dhruv.parmar@example.com' AS email, 'Programming Competition' AS title, '2026-10-01 19:57:00' AS at
    UNION ALL
    SELECT 'harsh.vora@example.com' AS email, 'Public Speaking Competition' AS title, '2026-10-01 05:46:00' AS at
    UNION ALL
    SELECT 'harsh.vora@example.com' AS email, 'Student Project Exhibition' AS title, '2026-09-28 12:23:00' AS at
    UNION ALL
    SELECT 'isha.trivedi@example.com' AS email, 'Coding Challenge' AS title, '2026-10-17 14:23:00' AS at
    UNION ALL
    SELECT 'isha.trivedi@example.com' AS email, 'Higher Education Seminar' AS title, '2026-10-07 07:46:00' AS at
    UNION ALL
    SELECT 'jay.modi@example.com' AS email, 'AI and Machine Learning Seminar' AS title, '2026-10-17 15:06:00' AS at
    UNION ALL
    SELECT 'jay.modi@example.com' AS email, 'Student Project Exhibition' AS title, '2026-10-14 23:34:00' AS at
    UNION ALL
    SELECT 'karan.joshi@example.com' AS email, 'Database Management Workshop' AS title, '2026-10-14 15:53:00' AS at
    UNION ALL
    SELECT 'kavya.reddy@example.com' AS email, 'Database Management Workshop' AS title, '2026-09-30 23:25:00' AS at
    UNION ALL
    SELECT 'kavya.reddy@example.com' AS email, 'Student Innovation Expo' AS title, '2026-09-28 03:54:00' AS at
    UNION ALL
    SELECT 'manav.solanki@example.com' AS email, 'Career Guidance Seminar' AS title, '2026-09-30 14:20:00' AS at
    UNION ALL
    SELECT 'manav.solanki@example.com' AS email, 'Database Management Workshop' AS title, '2026-10-12 21:54:00' AS at
    UNION ALL
    SELECT 'meera.nair@example.com' AS email, 'Coding Challenge' AS title, '2026-10-01 10:56:00' AS at
    UNION ALL
    SELECT 'meera.nair@example.com' AS email, 'Photography Exhibition' AS title, '2026-10-01 07:21:00' AS at
    UNION ALL
    SELECT 'neha.gupta@example.com' AS email, 'Database Management Workshop' AS title, '2026-10-12 17:17:00' AS at
    UNION ALL
    SELECT 'neha.gupta@example.com' AS email, 'Entrepreneurship Seminar' AS title, '2026-10-01 19:11:00' AS at
    UNION ALL
    SELECT 'nikhil.rana@example.com' AS email, 'Coding Challenge' AS title, '2026-10-08 18:23:00' AS at
    UNION ALL
    SELECT 'nikhil.rana@example.com' AS email, 'Higher Education Seminar' AS title, '2026-10-04 21:41:00' AS at
    UNION ALL
    SELECT 'pooja.chauhan@example.com' AS email, 'Coding Challenge' AS title, '2026-10-12 03:16:00' AS at
    UNION ALL
    SELECT 'pooja.chauhan@example.com' AS email, 'Python Programming Workshop' AS title, '2026-09-25 01:55:00' AS at
    UNION ALL
    SELECT 'priya.sharma@example.com' AS email, 'Career Guidance Seminar' AS title, '2026-09-30 23:27:00' AS at
    UNION ALL
    SELECT 'priya.sharma@example.com' AS email, 'Database Management Workshop' AS title, '2026-10-03 22:14:00' AS at
    UNION ALL
    SELECT 'riya.bhatt@example.com' AS email, 'Student Project Exhibition' AS title, '2026-10-04 23:22:00' AS at
    UNION ALL
    SELECT 'riya.bhatt@example.com' AS email, 'Web Development Workshop' AS title, '2026-10-17 05:28:00' AS at
    UNION ALL
    SELECT 'rohan.mehta@example.com' AS email, 'Coding Challenge' AS title, '2026-10-06 03:12:00' AS at
    UNION ALL
    SELECT 'rohan.mehta@example.com' AS email, 'Python Programming Workshop' AS title, '2026-10-09 04:01:00' AS at
    UNION ALL
    SELECT 'shreya.iyer@example.com' AS email, 'JavaScript Workshop' AS title, '2026-10-03 19:58:00' AS at
    UNION ALL
    SELECT 'shreya.iyer@example.com' AS email, 'Student Project Exhibition' AS title, '2026-09-28 06:12:00' AS at
    UNION ALL
    SELECT 'sneha.kulkarni@example.com' AS email, 'Student Project Exhibition' AS title, '2026-10-01 14:10:00' AS at
    UNION ALL
    SELECT 'tanvi.pandya@example.com' AS email, 'Database Management Workshop' AS title, '2026-10-08 22:16:00' AS at
    UNION ALL
    SELECT 'vivek.shah@example.com' AS email, 'JavaScript Workshop' AS title, '2026-10-07 13:29:00' AS at
    UNION ALL
    SELECT 'vivek.shah@example.com' AS email, 'Student Project Exhibition' AS title, '2026-09-30 18:56:00' AS at
    UNION ALL
    SELECT 'yash.thakkar@example.com' AS email, 'Coding Challenge' AS title, '2026-10-17 00:30:00' AS at
) AS p
JOIN users  u ON u.email = p.email
JOIN events e ON e.title = p.title;

-- ---------- sample audit rows (only when the table is empty) ----------
INSERT INTO audit_logs (user_name, user_role, action, entity, details, ip_address, created_at)
SELECT * FROM (
    SELECT 'Administrator' AS user_name, 'admin' AS user_role, 'LOGIN' AS action, 'session' AS entity, 'Admin logged in' AS details, '127.0.0.1' AS ip_address, '2026-10-17 09:00:00' AS created_at
) AS first_row
WHERE NOT EXISTS (SELECT 1 FROM audit_logs);

INSERT INTO audit_logs (user_name, user_role, action, entity, details, ip_address, created_at)
SELECT * FROM (
SELECT 'Administrator','admin','CREATE','event','Added event "Expert Talk"','127.0.0.1','2026-10-17 08:00:00' UNION ALL
SELECT 'Administrator','admin','UPDATE','event','Updated event "Coding Challenge"','127.0.0.1','2026-10-17 07:00:00' UNION ALL
SELECT 'Administrator','admin','STATUS_CHANGE','event','Status of "Photography Exhibition" is now Cancelled','127.0.0.1','2026-10-17 06:00:00' UNION ALL
SELECT 'Administrator','admin','CREATE','student','Added student "Aarav Patel"','127.0.0.1','2026-10-17 05:00:00' UNION ALL
SELECT 'Administrator','admin','UPDATE','user','Updated user "Priya Sharma"','127.0.0.1','2026-10-17 04:00:00' UNION ALL
SELECT 'Administrator','admin','DELETE','student','Deleted student "Test Student"','127.0.0.1','2026-10-17 03:00:00' UNION ALL
SELECT 'Administrator','admin','LOGOUT','session','Admin logged out','127.0.0.1','2026-10-17 02:00:00'
) AS more_rows
WHERE (SELECT COUNT(*) FROM audit_logs) = 1;
