-- University Management System Schema and Demo Data
-- Import this file in phpMyAdmin (MySQL 8+, port 3306)
-- It creates the database `university_portal` and all required tables with demo data.

DROP DATABASE IF EXISTS `university_portal`;
CREATE DATABASE `university_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `university_portal`;

-- Users and Roles
CREATE TABLE roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES
('Admin'), ('Teacher'), ('Student'), ('Parent');

CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Demo users (password for all demo users is: password)
-- bcrypt hash for 'password' (Laravel default):
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9Gq5j/6zCw5pqx1/8RAQGa
INSERT INTO users (username, password_hash, role_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9Gq5j/6zCw5pqx1/8RAQGa', 1),
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9Gq5j/6zCw5pqx1/8RAQGa', 2),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9Gq5j/6zCw5pqx1/8RAQGa', 3),
('parent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9Gq5j/6zCw5pqx1/8RAQGa', 4);

-- Students
CREATE TABLE students (
  id INT PRIMARY KEY AUTO_INCREMENT,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  national_id VARCHAR(20) NOT NULL UNIQUE,
  grade_level VARCHAR(20) NOT NULL,
  guardian_contact VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO students (first_name, last_name, national_id, grade_level, guardian_contact) VALUES
('Ali', 'Hassan', '29801011234567', 'Year1', '+201001112223'),
('Sara', 'Mahmoud', '29902021234567', 'Year2', '+201223344556');

-- Courses
CREATE TABLE courses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  capacity INT NOT NULL DEFAULT 30,
  semester VARCHAR(20) NOT NULL
);

INSERT INTO courses (code, name, capacity, semester) VALUES
('CS101', 'Intro to Computer Science', 50, '2025S1'),
('MATH201', 'Calculus II', 40, '2025S1');

-- Enrollments
CREATE TABLE enrollments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_course (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

INSERT INTO enrollments (student_id, course_id) VALUES (1, 1), (2, 2);

-- Attendance
CREATE TABLE attendance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  attended_on DATE NOT NULL,
  status ENUM('present','absent','late') NOT NULL DEFAULT 'present',
  UNIQUE KEY uniq_attendance (student_id, course_id, attended_on),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Fees (Tuition)
CREATE TABLE fees (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(200) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  grade_level VARCHAR(20)
);

INSERT INTO fees (name, amount, due_date, grade_level) VALUES
('Tuition 2025S1 - Year1', 10000.00, '2025-10-01', 'Year1'),
('Tuition 2025S1 - Year2', 12000.00, '2025-10-01', 'Year2');

-- Installment Plans
CREATE TABLE fee_installments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  fee_id INT NOT NULL,
  student_id INT NOT NULL,
  installment_no INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  paid TINYINT(1) NOT NULL DEFAULT 0,
  paid_at DATETIME NULL,
  FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Receipts
CREATE TABLE receipts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'EGP',
  details VARCHAR(255),
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Online Payment Transactions
CREATE TABLE transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  provider ENUM('Fawry','Vodafone') NOT NULL,
  reference VARCHAR(100) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','confirmed','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  confirmed_at DATETIME NULL,
  FOREIGN KEY (student_id) REFERENCES students(id),
  UNIQUE KEY uniq_reference (reference)
);

-- Grades
CREATE TABLE grades (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  grade DECIMAL(5,2) NOT NULL,
  graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_grade (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- EAV for custom attributes (on students)
CREATE TABLE eav_entities (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entity_type VARCHAR(50) NOT NULL -- e.g., 'student'
);

CREATE TABLE eav_attributes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entity_type VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  data_type ENUM('text','number','date','bool') NOT NULL DEFAULT 'text',
  UNIQUE KEY uniq_attr (entity_type, name)
);

-- EAV Option A: Per-type value tables (no NULL columns)
CREATE TABLE eav_values_text (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value TEXT NOT NULL,
  UNIQUE KEY uniq_eav_text (entity_type, entity_id, attribute_id),
  FOREIGN KEY (attribute_id) REFERENCES eav_attributes(id) ON DELETE CASCADE
);

CREATE TABLE eav_values_number (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value DECIMAL(18,4) NOT NULL,
  UNIQUE KEY uniq_eav_number (entity_type, entity_id, attribute_id),
  FOREIGN KEY (attribute_id) REFERENCES eav_attributes(id) ON DELETE CASCADE
);

CREATE TABLE eav_values_date (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value DATE NOT NULL,
  UNIQUE KEY uniq_eav_date (entity_type, entity_id, attribute_id),
  FOREIGN KEY (attribute_id) REFERENCES eav_attributes(id) ON DELETE CASCADE
);

CREATE TABLE eav_values_bool (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value TINYINT(1) NOT NULL,
  UNIQUE KEY uniq_eav_bool (entity_type, entity_id, attribute_id),
  FOREIGN KEY (attribute_id) REFERENCES eav_attributes(id) ON DELETE CASCADE
);

-- Convenience view for unified reads across value tables
CREATE OR REPLACE VIEW eav_values_all AS
SELECT 'text' AS data_type, t.entity_type, t.entity_id, t.attribute_id,
       t.value AS value_text, NULL AS value_number, NULL AS value_date, NULL AS value_bool
FROM eav_values_text t
UNION ALL
SELECT 'number' AS data_type, n.entity_type, n.entity_id, n.attribute_id,
       NULL, n.value, NULL, NULL
FROM eav_values_number n
UNION ALL
SELECT 'date' AS data_type, d.entity_type, d.entity_id, d.attribute_id,
       NULL, NULL, d.value, NULL
FROM eav_values_date d
UNION ALL
SELECT 'bool' AS data_type, b.entity_type, b.entity_id, b.attribute_id,
       NULL, NULL, NULL, b.value
FROM eav_values_bool b;

INSERT INTO eav_entities (entity_type) VALUES ('student');

-- Classrooms
CREATE TABLE classrooms (
  id INT PRIMARY KEY AUTO_INCREMENT,
  building VARCHAR(100) NOT NULL,
  room_number VARCHAR(20) NOT NULL,
  capacity INT NOT NULL,
  UNIQUE KEY uniq_room (building, room_number)
);

-- Labs and Maintenance
CREATE TABLE labs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  building VARCHAR(100) NOT NULL,
  capacity INT NOT NULL
);

CREATE TABLE lab_maintenance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  lab_id INT NOT NULL,
  description VARCHAR(255) NOT NULL,
  maintenance_date DATE NOT NULL,
  next_check DATE NULL,
  FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE
);

-- Library
CREATE TABLE books (
  id INT PRIMARY KEY AUTO_INCREMENT,
  isbn VARCHAR(20) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  copies INT NOT NULL DEFAULT 1
);

CREATE TABLE book_loans (
  id INT PRIMARY KEY AUTO_INCREMENT,
  book_id INT NOT NULL,
  student_id INT NOT NULL,
  borrowed_at DATE NOT NULL,
  returned_at DATE NULL,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Safety Equipment
CREATE TABLE equipment (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  location VARCHAR(100) NOT NULL,
  next_inspection DATE NULL
);

CREATE TABLE equipment_inspections (
  id INT PRIMARY KEY AUTO_INCREMENT,
  equipment_id INT NOT NULL,
  inspected_on DATE NOT NULL,
  notes VARCHAR(255),
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

CREATE TABLE incident_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  equipment_id INT NULL,
  description TEXT NOT NULL,
  logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL
);

-- Communication: Appointments, Homework, SMS
CREATE TABLE appointments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  parent_name VARCHAR(100) NOT NULL,
  scheduled_at DATETIME NOT NULL,
  notes TEXT,
  FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE homework (
  id INT PRIMARY KEY AUTO_INCREMENT,
  course_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  due_date DATE NOT NULL,
  attachment_path VARCHAR(255),
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE homework_submissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  homework_id INT NOT NULL,
  student_id INT NOT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  attachment_path VARCHAR(255),
  FOREIGN KEY (homework_id) REFERENCES homework(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE sms_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  message TEXT NOT NULL,
  recipients TEXT NOT NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transportation
CREATE TABLE bus_routes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  capacity INT NOT NULL,
  driver_name VARCHAR(100)
);

CREATE TABLE transport_requests (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  route_id INT NOT NULL,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (route_id) REFERENCES bus_routes(id)
);

-- Higher Education
CREATE TABLE projects (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  student_id INT NOT NULL,
  supervisor_id INT NULL,
  milestone ENUM('proposal','final') NOT NULL DEFAULT 'proposal',
  FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (supervisor_id) REFERENCES users(id)
);

CREATE TABLE files (
  id INT PRIMARY KEY AUTO_INCREMENT,
  uploader_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  path VARCHAR(255) NOT NULL,
  course_id INT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploader_id) REFERENCES users(id),
  FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE exam_halls (
  id INT PRIMARY KEY AUTO_INCREMENT,
  hall_name VARCHAR(100) NOT NULL,
  capacity INT NOT NULL
);

CREATE TABLE exam_seating (
  id INT PRIMARY KEY AUTO_INCREMENT,
  hall_id INT NOT NULL,
  course_id INT NOT NULL,
  exam_date DATE NOT NULL,
  student_id INT NOT NULL,
  seat_no INT NOT NULL,
  UNIQUE KEY uniq_seat (hall_id, exam_date, seat_no),
  FOREIGN KEY (hall_id) REFERENCES exam_halls(id),
  FOREIGN KEY (course_id) REFERENCES courses(id),
  FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Helpful indexes
CREATE INDEX idx_students_grade ON students(grade_level);
CREATE INDEX idx_courses_semester ON courses(semester);
CREATE INDEX idx_attendance_date ON attendance(attended_on);
CREATE INDEX idx_transactions_status ON transactions(status);

-- Views for reports
CREATE OR REPLACE VIEW v_students_per_course AS
SELECT c.id AS course_id, c.code, c.name AS course_name, COUNT(e.student_id) AS student_count
FROM courses c
LEFT JOIN enrollments e ON e.course_id = c.id
GROUP BY c.id, c.code, c.name;

CREATE OR REPLACE VIEW v_enrollment_per_semester AS
SELECT c.semester, COUNT(e.id) AS enrollments
FROM courses c
LEFT JOIN enrollments e ON e.course_id = c.id
GROUP BY c.semester;

-- Track file downloads
CREATE TABLE IF NOT EXISTS file_downloads (
  id INT PRIMARY KEY AUTO_INCREMENT,
  file_id INT NOT NULL,
  user_id INT NULL,
  downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Invigilators for exams
CREATE TABLE IF NOT EXISTS exam_invigilators (
  id INT PRIMARY KEY AUTO_INCREMENT,
  hall_id INT NOT NULL,
  exam_date DATE NOT NULL,
  user_id INT NOT NULL,
  UNIQUE KEY uniq_invig (hall_id, exam_date, user_id),
  FOREIGN KEY (hall_id) REFERENCES exam_halls(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- UX Feedback
CREATE TABLE IF NOT EXISTS feedback (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
