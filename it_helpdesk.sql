-- Create database
CREATE DATABASE IF NOT EXISTS it_helpdesk;
USE it_helpdesk;
-- Create teams table first
CREATE TABLE teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL
);
-- Create users table after teams
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('submitter', 'senior_officer') DEFAULT 'submitter',
    team_id INT,
    FOREIGN KEY (team_id) REFERENCES teams(team_id)
);
-- Create issue_team_mapping table
CREATE TABLE issue_team_mapping (
    issue_type VARCHAR(50) PRIMARY KEY,
    team_id INT NOT NULL,
    FOREIGN KEY (team_id) REFERENCES teams(team_id)
);
-- Create tickets table
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    issue_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    submitter_id INT NOT NULL,
    team_id INT NOT NULL,
    status ENUM('Open', 'In Progress', 'Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitter_id) REFERENCES users(user_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id),
    FOREIGN KEY (issue_type) REFERENCES issue_team_mapping(issue_type)
);
-- Create comments table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);
-- Insert sample data
INSERT INTO teams (team_name)
VALUES ('Hardware Support'),
    ('Software Support'),
    ('Network Operations'),
    ('Security Team');
INSERT INTO issue_team_mapping (issue_type, team_id)
VALUES ('Hardware', 1),
    ('Software', 2),
    ('Network', 3),
    ('Account Access', 4);
CREATE TABLE ticket_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
) ENGINE = InnoDB;
INSERT INTO users (name, email, password, role, team_id)
VALUES (
        'Test Officer',
        'officer@example.com',
        '$2y$10$4Bh2b8ziFQ.POL8iXg.7A.buqSck/1eBR1ou1aTP1rIR558heZY4C',
        'senior_officer',
        1
    );
ALTER TABLE users
ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL;
ALTER TABLE users
ADD COLUMN avatar_path VARCHAR(255) DEFAULT 'assets/default_avatar.png';
-- Optional: If you want to set existing users to a default avatar path
UPDATE users
SET avatar_path = 'assets/default_avatar.png'
WHERE avatar_path IS NULL
    OR avatar_path = '';
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'senior_officer', 'submitter') NOT NULL,
    team_id INT NULL,
    -- For senior officers
    avatar_path VARCHAR(255) DEFAULT 'assets/default_avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    submitter_id INT NOT NULL,
    issue_type VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Closed') NOT NULL DEFAULT 'Open',
    assigned_officer_id INT NULL,
    team_id INT NULL,
    -- Added to track team assignment
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitter_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_officer_id) REFERENCES users(user_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id)
);
CREATE TABLE IF NOT EXISTS ticket_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
CREATE TABLE IF NOT EXISTS ticket_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    old_status ENUM('Open', 'In Progress', 'Closed') NULL,
    new_status ENUM('Open', 'In Progress', 'Closed') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
CREATE TABLE status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    -- User who made the status change
    old_status VARCHAR(50) NOT NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
CREATE TABLE priority_history (
    priority_history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    old_priority ENUM('Low', 'Medium', 'High') NOT NULL,
    new_priority ENUM('Low', 'Medium', 'High') NOT NULL,
    changed_at DATETIME NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
CREATE TABLE team_history (
    team_history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    old_team_id INT,
    new_team_id INT,
    is_escalation BOOLEAN NOT NULL DEFAULT 0,
    changed_at DATETIME NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (old_team_id) REFERENCES teams(team_id),
    FOREIGN KEY (new_team_id) REFERENCES teams(team_id)
);
CREATE TABLE attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
);
ALTER TABLE tickets
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE;
-- Or for datetime:
-- ALTER TABLE tickets ADD COLUMN deleted_at DATETIME DEFAULT NULL;
INSERT INTO issue_team_mapping (issue_type, team_id)
VALUES ('Account', 1);
-- Replace 1 with an appropriate team_id or NULL if not immediately assigned
-- Add a new team, for example, 'Other Issues Team' with ID 5
INSERT INTO teams (team_id, team_name)
VALUES (5, 'Other Issues Team');
INSERT INTO issue_team_mapping (issue_type, team_id)
VALUES ('Other', 5);