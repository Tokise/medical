DROP DATABASE IF EXISTS medical_management;
CREATE DATABASE medical_management;
USE medical_management;

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id),
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role_id INT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER last_name;
ALTER TABLE users MODIFY google_id VARCHAR(255) NULL;

CREATE TABLE specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE doctor_specializations (
    doctor_id INT,
    specialization_id INT,
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    FOREIGN KEY (specialization_id) REFERENCES specializations(id),
    PRIMARY KEY (doctor_id, specialization_id)
);

CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME,
    end_time TIME,
    max_patients INT DEFAULT 20,
    is_active BOOLEAN DEFAULT true,
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    doctor_id INT,
    schedule_id INT,
    appointment_date DATE,
    appointment_time TIME,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

CREATE TABLE medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    doctor_id INT,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

CREATE TABLE tutorials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT,
    user_role VARCHAR(50),
    sequence_order INT,
    is_required BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add target_element column to tutorials table
ALTER TABLE tutorials ADD COLUMN target_element VARCHAR(255) AFTER content;

CREATE TABLE user_tutorials (
    user_id INT,
    tutorial_id INT,
    completed BOOLEAN DEFAULT false,
    completed_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id),
    PRIMARY KEY (user_id, tutorial_id)
);

DROP TABLE IF EXISTS first_aid_guides;
DROP TABLE IF EXISTS ai_consultations;

CREATE TABLE api_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255),
    base_url VARCHAR(255),
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    priority INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ai_consultations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    query TEXT,
    response TEXT,
    category ENUM('general', 'emergency', 'first_aid', 'medication'),
    api_source_id INT,
    response_metadata JSON,
    confidence_score DECIMAL(5,2),
    feedback_rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (api_source_id) REFERENCES api_sources(id)
);

CREATE TABLE medical_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('first_aid', 'medication', 'condition', 'procedure'),
    content JSON,
    api_source_id INT,
    last_updated TIMESTAMP,
    verification_status ENUM('pending', 'verified', 'outdated') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_source_id) REFERENCES api_sources(id)
);

CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    blood_type VARCHAR(5),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert trusted medical API sources
INSERT INTO api_sources (name, description, priority) VALUES
('MedlinePlus', 'National Library of Medicine official consumer health information', 1),
('CDC API', 'Centers for Disease Control and Prevention API', 2),
('WHO API', 'World Health Organization health information', 3);

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('admin', 'System Administrator'),
('doctor', 'Medical Doctor'),
('nurse', 'Nurse'),
('student', 'Medical Student'),
('staff', 'Support Staff');

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('manage_users', 'Can manage all users'),
('view_records', 'Can view medical records'),
('edit_records', 'Can edit medical records'),
('manage_appointments', 'Can manage appointments'),
('view_dashboard', 'Can view dashboard');

-- Insert role permissions
INSERT INTO role_permissions (role_id, permission_id) 
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'admin';

-- Create admin account (password: Admin@123)
INSERT INTO users (email, password, first_name, last_name, role_id) VALUES
('admin@medical.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 1);

-- Insert sample specializations
INSERT INTO specializations (name, description) VALUES
('General Medicine', 'Primary healthcare and general medical treatment'),
('Pediatrics', 'Medical care for children'),
('Cardiology', 'Heart and cardiovascular system');

-- Update tutorials with more detailed content and target elements
INSERT INTO tutorials (title, description, content, target_element, user_role, sequence_order, is_required) VALUES
('Welcome to Dashboard', 'Learn about the dashboard', 'This is your main dashboard where you can see all important information at a glance.', '.dashboard-container', 'all', 1, true),
('Navigation Guide', 'Learn to navigate the system', 'Use the sidebar menu to access different sections of the application.', '.sidebar-nav', 'all', 2, true),
('Profile Settings', 'Customize your profile', 'Click here to update your profile information and preferences.', '.profile-section', 'all', 3, true),
('Appointment Booking', 'Book your first appointment', 'Follow these steps to book an appointment with a doctor.', '.appointment-form', 'patient', 4, true),
('Medical Records', 'Accessing medical records', 'View and manage your medical history here.', '.records-section', 'patient', 5, true),
('Doctor Schedule', 'Managing your schedule', 'Set your availability and manage appointments here.', '.schedule-manager', 'doctor', 4, true),
('Patient Management', 'Managing patients', 'Access your patient list and their records here.', '.patient-list', 'doctor', 5, true);
