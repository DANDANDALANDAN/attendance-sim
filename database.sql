CREATE DATABASE IF NOT EXISTS attendance_sim;
USE attendance_sim;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(100) NOT NULL,
  role VARCHAR(20) DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  grade_level TINYINT,
  section VARCHAR(20),
  emergency_contact VARCHAR(15),
  photo VARCHAR(255) DEFAULT 'default.png'
);

CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  time DATETIME,
  type ENUM('IN','OUT'),
  FOREIGN KEY (student_id) REFERENCES students(id)
);

INSERT INTO users (username, password, role) VALUES
('admin', 'admin123', 'admin');

INSERT INTO students (first_name, last_name, grade_level, section, emergency_contact)
VALUES 
('Juan','Dela Cruz',9,'Rizal','09171234567'),
('Maria','Santos',10,'A','09172345678'),
('Pedro','Reyes',7,'B','09173456789'),
('Ana','Lopez',12,'C','09174567890');
