-- =========================================================
-- STUDENT HUB - Student Management Module (MySQLi)
-- Database : my_student_hub   (same one used by db_mysqli.php)
-- Import   : phpMyAdmin > Import, or  mysql -u root < my_student_hub_schema.sql
-- Admin login after import:  admin@studenthub.com / Admin@123
-- =========================================================

CREATE DATABASE IF NOT EXISTS my_student_hub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE my_student_hub;

-- ---------- users (needed by login.php) ----------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    student_id    VARCHAR(50)  DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_student_id (student_id)
) ENGINE=InnoDB;

-- If your existing users table has no "role" column, run:
-- ALTER TABLE users ADD role ENUM('student','admin') NOT NULL DEFAULT 'student';

INSERT IGNORE INTO users (full_name, email, student_id, password_hash, role) VALUES
('Administrator', 'admin@studenthub.com', NULL,
 '$2b$12$f1C/uyDcwNRs01bKp2bEvuBwolDG2e56MG7IyzMfVRzTKyX3te5V.', 'admin');

-- ---------- courses ----------
CREATE TABLE IF NOT EXISTS courses (
    course_id   INT NOT NULL AUTO_INCREMENT,
    course_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (course_id),
    UNIQUE KEY uq_course_name (course_name)
) ENGINE=InnoDB;

INSERT IGNORE INTO courses (course_name) VALUES
('Information Technology'),
('Computer Science'),
('Computer Engineering');

-- ---------- students ----------
CREATE TABLE IF NOT EXISTS students (
    id        INT NOT NULL AUTO_INCREMENT,
    name      VARCHAR(100) NOT NULL,
    email     VARCHAR(150) NOT NULL,
    mobile    VARCHAR(15)  NOT NULL,
    course_id INT NOT NULL,
    year      TINYINT NOT NULL,
    gender    VARCHAR(20)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_email (email),
    KEY idx_students_course (course_id),
    CONSTRAINT fk_students_course FOREIGN KEY (course_id)
        REFERENCES courses (course_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------- 24 seeded records ----------
INSERT IGNORE INTO students (name, email, mobile, course_id, year, gender)
SELECT s.name, s.email, s.mobile, c.course_id, s.yr, s.gender
FROM (
    SELECT 'Aarav Patel'      AS name, 'aarav.patel@example.com'   AS email, '9876500001' AS mobile, 'Information Technology' AS course, 1 AS yr, 'Male'   AS gender UNION ALL
    SELECT 'Priya Sharma',     'priya.sharma@example.com',   '9876500002', 'Computer Science',      2, 'Female' UNION ALL
    SELECT 'Rohan Mehta',      'rohan.mehta@example.com',    '9876500003', 'Computer Engineering',  3, 'Male'   UNION ALL
    SELECT 'Ananya Desai',     'ananya.desai@example.com',   '9876500004', 'Information Technology',2, 'Female' UNION ALL
    SELECT 'Karan Joshi',      'karan.joshi@example.com',    '9876500005', 'Computer Science',      1, 'Male'   UNION ALL
    SELECT 'Isha Trivedi',     'isha.trivedi@example.com',   '9876500006', 'Computer Engineering',  2, 'Female' UNION ALL
    SELECT 'Vivek Shah',       'vivek.shah@example.com',     '9876500007', 'Information Technology',3, 'Male'   UNION ALL
    SELECT 'Neha Gupta',       'neha.gupta@example.com',     '9876500008', 'Computer Science',      3, 'Female' UNION ALL
    SELECT 'Dhruv Parmar',     'dhruv.parmar@example.com',   '9876500009', 'Computer Engineering',  1, 'Male'   UNION ALL
    SELECT 'Sneha Kulkarni',   'sneha.kulkarni@example.com', '9876500010', 'Information Technology',1, 'Female' UNION ALL
    SELECT 'Aditya Verma',     'aditya.verma@example.com',   '9876500011', 'Computer Science',      2, 'Male'   UNION ALL
    SELECT 'Meera Nair',       'meera.nair@example.com',     '9876500012', 'Computer Engineering',  3, 'Female' UNION ALL
    SELECT 'Harsh Vora',       'harsh.vora@example.com',     '9876500013', 'Information Technology',2, 'Male'   UNION ALL
    SELECT 'Kavya Reddy',      'kavya.reddy@example.com',    '9876500014', 'Computer Science',      1, 'Female' UNION ALL
    SELECT 'Yash Thakkar',     'yash.thakkar@example.com',   '9876500015', 'Computer Engineering',  2, 'Male'   UNION ALL
    SELECT 'Riya Bhatt',       'riya.bhatt@example.com',     '9876500016', 'Information Technology',3, 'Female' UNION ALL
    SELECT 'Manav Solanki',    'manav.solanki@example.com',  '9876500017', 'Computer Science',      3, 'Male'   UNION ALL
    SELECT 'Pooja Chauhan',    'pooja.chauhan@example.com',  '9876500018', 'Computer Engineering',  1, 'Female' UNION ALL
    SELECT 'Jay Modi',         'jay.modi@example.com',       '9876500019', 'Information Technology',2, 'Male'   UNION ALL
    SELECT 'Tanvi Pandya',     'tanvi.pandya@example.com',   '9876500020', 'Computer Science',      2, 'Female' UNION ALL
    SELECT 'Nikhil Rana',      'nikhil.rana@example.com',    '9876500021', 'Computer Engineering',  3, 'Male'   UNION ALL
    SELECT 'Shreya Iyer',      'shreya.iyer@example.com',    '9876500022', 'Information Technology',1, 'Female' UNION ALL
    SELECT 'Arjun Singh',      'arjun.singh@example.com',    '9876500023', 'Computer Science',      3, 'Male'   UNION ALL
    SELECT 'Divya Rathod',     'divya.rathod@example.com',   '9876500024', 'Computer Engineering',  2, 'Other'
) AS s
JOIN courses c ON c.course_name = s.course;
