<<<<<<< HEAD
<<<<<<< HEAD
-- MedMS Database Schema
-- Medical Management System for Schools

-- Drop database if it exists
DROP DATABASE IF EXISTS medical_management;

-- Create database
CREATE DATABASE medical_management;
USE medical_management;

-- User Roles Table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table (for all users)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    school_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    first_login BOOLEAN DEFAULT TRUE,
    has_seen_demo BOOLEAN DEFAULT FALSE,
    demo_completed BOOLEAN DEFAULT FALSE,
    demo_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Student Information
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    grade_level VARCHAR(20),
    date_of_birth DATE NULL,
    gender VARCHAR(20),
    blood_type VARCHAR(10),
    emergency_contact_name VARCHAR(100) NULL,
    emergency_contact_number VARCHAR(20) NULL,
    emergency_contact_relationship VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Teacher Information
CREATE TABLE teachers (
    teacher_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    department VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender VARCHAR(20),
    blood_type VARCHAR(10),
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_number VARCHAR(20) NOT NULL,
    emergency_contact_relationship VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Staff Information 
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    date_of_birth DATE NULL,
    gender VARCHAR(20),
    blood_type VARCHAR(10),
    emergency_contact_name VARCHAR(100) NULL,
    emergency_contact_number VARCHAR(20) NULL,
    emergency_contact_relationship VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Doctor Information
CREATE TABLE doctors (
    doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(100) UNIQUE NOT NULL,
    availability_status ENUM('Available', 'Busy', 'Off-duty') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Nurse Information
CREATE TABLE nurses (
    nurse_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(100) UNIQUE NOT NULL,
    availability_status ENUM('Available', 'Busy', 'Off-duty') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Medical Staff Schedule (shared between doctors and nurses)
CREATE TABLE medical_schedule (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Appointments Table
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration INT DEFAULT 30, -- Duration in minutes
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-show') DEFAULT 'Scheduled',
    reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Medical History
CREATE TABLE medical_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    condition_name VARCHAR(100) NOT NULL,
    diagnosis_date DATE,
    treatment TEXT,
    is_chronic BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Allergies
CREATE TABLE allergies (
    allergy_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    allergy_name VARCHAR(100) NOT NULL,
    severity ENUM('Mild', 'Moderate', 'Severe'),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Medications
CREATE TABLE medications (
    medication_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    dosage_instructions TEXT,
    side_effects TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Medications (medications assigned to users)
CREATE TABLE user_medications (
    user_medication_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    medication_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(medication_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Prescriptions
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    instructions TEXT,
    issue_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('Active', 'Completed', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Prescription Items
CREATE TABLE prescription_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    medication_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(medication_id)
);

-- Consultations
CREATE TABLE consultations (
    consultation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    consultation_date DATETIME NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    treatment TEXT,
    follow_up_date DATE,
    notes TEXT,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Documents (for uploading medical histories, reports, etc.)
CREATE TABLE documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- AI Consultation Logs
CREATE TABLE ai_consultations (
    consultation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    response TEXT NOT NULL,
    consultation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    feedback ENUM('Helpful', 'Not Helpful', 'None') DEFAULT 'None',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- First Aid Tips
CREATE TABLE first_aid_tips (
    tip_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    emergency_level ENUM('Low', 'Medium', 'High') NOT NULL,
    keywords VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- System Logs
CREATE TABLE system_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Medical Supplies Inventory
CREATE TABLE medical_supplies (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    current_quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL,
    reorder_level INT NOT NULL,
    expiry_date DATE,
    supplier VARCHAR(100),
    unit_cost DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Medical Records
CREATE TABLE medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    record_date DATETIME NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    notes TEXT,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- INSERT INITIAL DATA

-- Insert roles
INSERT INTO roles (role_name, description) VALUES 
('Admin', 'System administrator with full access'),
('Doctor', 'Medical professional who can diagnose and prescribe medication'),
('Nurse', 'Medical staff who can provide basic care and assist doctors'),
('Teacher', 'School faculty member'),
('Student', 'Enrolled student'),
('Staff', 'School staff member');

-- Insert sample medical supplies
INSERT INTO medical_supplies (item_name, description, current_quantity, unit, reorder_level, supplier, unit_cost) VALUES 
('Bandages', 'Sterile adhesive bandages', 100, 'pieces', 50, 'Medical Supplies Co.', 0.50),
('Gauze', 'Sterile gauze pads', 75, 'packs', 30, 'Healthcare Products Inc.', 2.00),
('Antiseptic Solution', 'Betadine solution', 20, 'bottles', 10, 'Pharma Supplies', 5.00),
('Cotton Balls', 'Sterile cotton balls', 150, 'packs', 50, 'Medical Supplies Co.', 1.00),
('Surgical Masks', 'Disposable face masks', 200, 'pieces', 100, 'Healthcare Products Inc.', 0.25);

=======
=======
>>>>>>> 6555137 (Added my changes)
-- MedMS Database Schema
-- Medical Management System for Schools

-- Drop database if it exists
DROP DATABASE IF EXISTS medical_management;

-- Create database
CREATE DATABASE medical_management;
USE medical_management;

-- User Roles Table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table (for all users)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    school_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    first_login BOOLEAN DEFAULT TRUE,
    has_seen_demo BOOLEAN DEFAULT FALSE,
    demo_completed BOOLEAN DEFAULT FALSE,
    demo_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Student Information
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    grade_level VARCHAR(20),
    date_of_birth DATE NULL,
    gender VARCHAR(20),
    blood_type VARCHAR(10),
    emergency_contact_name VARCHAR(100) NULL,
    emergency_contact_number VARCHAR(20) NULL,
    emergency_contact_relationship VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Teacher Information
CREATE TABLE teachers (
    teacher_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    department VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender VARCHAR(20),
    blood_type VARCHAR(10),
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_number VARCHAR(20) NOT NULL,
    emergency_contact_relationship VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Staff Information 
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    date_of_birth DATE NULL,
    gender VARCHAR(20),
    blood_type VARCHAR(10),
    emergency_contact_name VARCHAR(100) NULL,
    emergency_contact_number VARCHAR(20) NULL,
    emergency_contact_relationship VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Doctor Information
CREATE TABLE doctors (
    doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(100) UNIQUE NOT NULL,
    availability_status ENUM('Available', 'Busy', 'Off-duty') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Nurse Information
CREATE TABLE nurses (
    nurse_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(100) UNIQUE NOT NULL,
    availability_status ENUM('Available', 'Busy', 'Off-duty') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Medical Staff Schedule (shared between doctors and nurses)
CREATE TABLE medical_schedule (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Appointments Table
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration INT DEFAULT 30, -- Duration in minutes
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-show') DEFAULT 'Scheduled',
    reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Medical History
CREATE TABLE medical_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    condition_name VARCHAR(100) NOT NULL,
    diagnosis_date DATE,
    treatment TEXT,
    is_chronic BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Allergies
CREATE TABLE allergies (
    allergy_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    allergy_name VARCHAR(100) NOT NULL,
    severity ENUM('Mild', 'Moderate', 'Severe'),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Medications
CREATE TABLE medications (
    medication_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    dosage_instructions TEXT,
    side_effects TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Medications (medications assigned to users)
CREATE TABLE user_medications (
    user_medication_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    medication_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(medication_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Prescriptions
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    instructions TEXT,
    issue_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('Active', 'Completed', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Prescription Items
CREATE TABLE prescription_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    medication_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(medication_id)
);

-- Consultations
CREATE TABLE consultations (
    consultation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    consultation_date DATETIME NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    treatment TEXT,
    follow_up_date DATE,
    notes TEXT,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Documents (for uploading medical histories, reports, etc.)
CREATE TABLE documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- AI Consultation Logs
CREATE TABLE ai_consultations (
    consultation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    response TEXT NOT NULL,
    consultation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    feedback ENUM('Helpful', 'Not Helpful', 'None') DEFAULT 'None',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- First Aid Tips
CREATE TABLE first_aid_tips (
    tip_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    emergency_level ENUM('Low', 'Medium', 'High') NOT NULL,
    keywords VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- System Logs
CREATE TABLE system_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Medical Supplies Inventory
CREATE TABLE medical_supplies (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    current_quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL,
    reorder_level INT NOT NULL,
    expiry_date DATE,
    supplier VARCHAR(100),
    unit_cost DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Medical Records
CREATE TABLE medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    record_date DATETIME NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    notes TEXT,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- INSERT INITIAL DATA

-- Insert roles
INSERT INTO roles (role_name, description) VALUES 
('Admin', 'System administrator with full access'),
('Doctor', 'Medical professional who can diagnose and prescribe medication'),
('Nurse', 'Medical staff who can provide basic care and assist doctors'),
('Teacher', 'School faculty member'),
('Student', 'Enrolled student'),
('Staff', 'School staff member');

-- Insert sample medical supplies
INSERT INTO medical_supplies (item_name, description, current_quantity, unit, reorder_level, supplier, unit_cost) VALUES 
('Bandages', 'Sterile adhesive bandages', 100, 'pieces', 50, 'Medical Supplies Co.', 0.50),
('Gauze', 'Sterile gauze pads', 75, 'packs', 30, 'Healthcare Products Inc.', 2.00),
('Antiseptic Solution', 'Betadine solution', 20, 'bottles', 10, 'Pharma Supplies', 5.00),
('Cotton Balls', 'Sterile cotton balls', 150, 'packs', 50, 'Medical Supplies Co.', 1.00),
('Surgical Masks', 'Disposable face masks', 200, 'pieces', 100, 'Healthcare Products Inc.', 0.25);

<<<<<<< HEAD
>>>>>>> 6555137 (Added my changes)
=======
>>>>>>> 6555137 (Added my changes)
