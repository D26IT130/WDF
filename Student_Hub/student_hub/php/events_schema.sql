-- =========================================================
-- STUDENT HUB - Event Management (PHP + MySQLi)
-- Import in phpMyAdmin (database: my_student_hub) or:
--   mysql -u root < events_schema.sql
-- 15 sample events; posters are optional and added through the admin UI.
-- Safe to re-run: INSERT IGNORE skips events that already exist.
-- =========================================================
CREATE DATABASE IF NOT EXISTS my_student_hub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE my_student_hub;

CREATE TABLE IF NOT EXISTS events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(150) NOT NULL,
    description TEXT         NOT NULL,
    event_date  DATE         NOT NULL,
    event_time  TIME         NOT NULL,
    location    VARCHAR(150) NOT NULL,
    category    ENUM('Workshop','Competition','Seminar','Exhibition') NOT NULL,
    status      ENUM('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
    poster      VARCHAR(255) DEFAULT NULL,          -- stored file name only (uploads/events/)
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_event_title_date (title, event_date),
    KEY idx_event_date (event_date),
    KEY idx_event_status (status)
) ENGINE=InnoDB;

INSERT IGNORE INTO events (title, description, event_date, event_time, location, category, status) VALUES
('Web Development Workshop', 'Learn the fundamentals of modern web development using HTML, CSS and JavaScript.', '2026-09-15', '10:00:00', 'Computer Lab', 'Workshop', 'Completed'),
('Programming Competition', 'Test your coding and problem-solving skills in a competitive environment.', '2026-09-22', '11:00:00', 'Main Auditorium', 'Competition', 'Completed'),
('Career Guidance Seminar', 'Get career advice and learn about opportunities after graduation.', '2026-10-05', '14:00:00', 'Seminar Hall', 'Seminar', 'Completed'),
('Student Project Exhibition', 'Present innovative projects and ideas created by students.', '2026-10-18', '10:30:00', 'College Hall', 'Exhibition', 'Upcoming'),
('Python Programming Workshop', 'Learn Python programming fundamentals through practical examples.', '2026-10-25', '09:30:00', 'Computer Lab 2', 'Workshop', 'Upcoming'),
('Hackathon 2026', 'Build innovative technology solutions with your team during this exciting hackathon.', '2026-11-02', '08:00:00', 'Innovation Centre', 'Competition', 'Upcoming'),
('AI and Machine Learning Seminar', 'Explore the basics of artificial intelligence and machine learning.', '2026-11-08', '13:00:00', 'Seminar Hall', 'Seminar', 'Upcoming'),
('Photography Exhibition', 'Explore creative photography submitted by students across the campus.', '2026-11-15', '11:00:00', 'College Gallery', 'Exhibition', 'Cancelled'),
('Database Management Workshop', 'Learn database concepts, SQL queries and database design.', '2026-11-22', '10:00:00', 'Computer Lab 1', 'Workshop', 'Upcoming'),
('Public Speaking Competition', 'Improve your communication skills and compete in a public speaking event.', '2026-11-30', '12:00:00', 'Main Auditorium', 'Competition', 'Upcoming'),
('Higher Education Seminar', 'Learn about postgraduate study options and higher education opportunities.', '2026-12-05', '14:30:00', 'Seminar Hall', 'Seminar', 'Upcoming'),
('Student Innovation Expo', 'Discover creative ideas and innovative solutions developed by students.', '2026-12-10', '10:00:00', 'College Hall', 'Exhibition', 'Upcoming'),
('JavaScript Workshop', 'Learn JavaScript fundamentals, DOM manipulation and event handling.', '2026-12-15', '09:00:00', 'Computer Lab 3', 'Workshop', 'Upcoming'),
('Coding Challenge', 'Solve programming problems and demonstrate your algorithmic thinking.', '2026-12-20', '11:30:00', 'Computer Lab 1', 'Competition', 'Upcoming'),
('Entrepreneurship Seminar', 'Learn how to develop business ideas and understand the startup ecosystem.', '2026-12-28', '15:00:00', 'Seminar Hall', 'Seminar', 'Upcoming');
