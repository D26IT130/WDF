-- =========================================================
-- Sample rows for the USERS table (22 students + 1 extra admin)
-- Every seeded account has the password:  Student@123
-- Safe to re-run: INSERT IGNORE skips duplicates (email / student_id).
-- Run after my_student_hub_schema.sql (which creates the users table
-- and the admin@studenthub.com account).
-- =========================================================
USE my_student_hub;

INSERT IGNORE INTO users (full_name, email, student_id, password_hash, role) VALUES
('Aarav Patel', 'aarav.patel@example.com', 'ST2026001', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Priya Sharma', 'priya.sharma@example.com', 'ST2026002', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Rohan Mehta', 'rohan.mehta@example.com', 'ST2026003', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Ananya Desai', 'ananya.desai@example.com', 'ST2026004', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Karan Joshi', 'karan.joshi@example.com', 'ST2026005', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Isha Trivedi', 'isha.trivedi@example.com', 'ST2026006', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Vivek Shah', 'vivek.shah@example.com', 'ST2026007', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Neha Gupta', 'neha.gupta@example.com', 'ST2026008', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Dhruv Parmar', 'dhruv.parmar@example.com', 'ST2026009', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Sneha Kulkarni', 'sneha.kulkarni@example.com', 'ST2026010', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Aditya Verma', 'aditya.verma@example.com', 'ST2026011', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Meera Nair', 'meera.nair@example.com', 'ST2026012', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Harsh Vora', 'harsh.vora@example.com', 'ST2026013', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Kavya Reddy', 'kavya.reddy@example.com', 'ST2026014', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Yash Thakkar', 'yash.thakkar@example.com', 'ST2026015', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Riya Bhatt', 'riya.bhatt@example.com', 'ST2026016', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Manav Solanki', 'manav.solanki@example.com', 'ST2026017', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Pooja Chauhan', 'pooja.chauhan@example.com', 'ST2026018', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Jay Modi', 'jay.modi@example.com', 'ST2026019', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Tanvi Pandya', 'tanvi.pandya@example.com', 'ST2026020', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Nikhil Rana', 'nikhil.rana@example.com', 'ST2026021', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Shreya Iyer', 'shreya.iyer@example.com', 'ST2026022', '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'student'),
('Faculty Admin', 'faculty.admin@studenthub.com', NULL, '$2b$12$.Rjhm4fnOViuY0JeMVr33.FLKutAzfCYH57I2gpo8vt4kDzC.m.HW', 'admin');
