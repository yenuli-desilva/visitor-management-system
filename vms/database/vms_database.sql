-- =====================================================================
-- DATABASE SCRIPT: Visitor Management System (VMS)
-- Database Name:   vms_database
-- Compatibility:   MySQL 5.7+ / MySQL 8.0+ / MariaDB / phpMyAdmin
-- Description:     Complete relational schema with tables, constraints,
--                  indexes, safe foreign keys, and initial seed data.
-- =====================================================================

-- Create database if it does not exist
CREATE DATABASE `vms_database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vms_database`;

-- Disable foreign key checks during schema creation/reset
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- TABLE 1: Users
-- Description: Stores system authentication credentials and user roles.
-- Roles:
--   - 'admin': Full access to all modules, users, visitors, and reports.
--   - 'user':  Front-desk / operational staff handling check-ins & visits.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `UserID` INT NOT NULL AUTO_INCREMENT,
  `Username` VARCHAR(50) NOT NULL,
  `Password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt encrypted password hash',
  `FullName` VARCHAR(100) NOT NULL,
  `Role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `Status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `uq_users_username` (`Username`),
  INDEX `idx_users_username` (`Username`),
  INDEX `idx_users_role` (`Role`),
  INDEX `idx_users_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 2: Departments
-- Description: Organizational departments hosts belong to.
-- Relationship: One department has many visitors (1:M).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Departments`;
CREATE TABLE `Departments` (
  `DepartmentID` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(100) NOT NULL,
  `Status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`DepartmentID`),
  UNIQUE KEY `uq_departments_name` (`Name`),
  INDEX `idx_departments_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 3: Hosts
-- Description: Officers and employees whom visitors meet.
-- Constraints:
--   - DepartmentID references Departments(DepartmentID)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Hosts`;
CREATE TABLE `Hosts` (
  `HostID` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(100) NOT NULL,
  `DepartmentID` INT NOT NULL,
  `Designation` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(100) DEFAULT NULL,
  `Phone` VARCHAR(20) DEFAULT NULL,
  `OfficeRoom` VARCHAR(50) DEFAULT NULL,
  `Status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`HostID`),
  INDEX `idx_hosts_department` (`DepartmentID`),
  INDEX `idx_hosts_status` (`Status`),
  CONSTRAINT `fk_hosts_department`
    FOREIGN KEY (`DepartmentID`)
    REFERENCES `Departments` (`DepartmentID`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 4: Visitors
-- Description: Master directory of guests/visitors.
-- Constraints:
--   - DepartmentID references Departments(DepartmentID) with ON DELETE RESTRICT
--     (Prevents deletion of a department that has registered visitor history).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Visitors`;
CREATE TABLE `Visitors` (
  `VisitorID` INT NOT NULL AUTO_INCREMENT,
  `UserID` INT DEFAULT NULL,
  `Name` VARCHAR(100) NOT NULL,
  `NIC` VARCHAR(20) NOT NULL,
  `Phone` VARCHAR(20) DEFAULT NULL,
  `Email` VARCHAR(100) DEFAULT NULL,
  `Purpose` VARCHAR(255) DEFAULT NULL,
  `Host` VARCHAR(100) DEFAULT NULL,
  `DepartmentID` INT DEFAULT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`VisitorID`),
  INDEX `idx_visitors_userid` (`UserID`),
  INDEX `idx_visitors_name` (`Name`),
  INDEX `idx_visitors_nic` (`NIC`),
  INDEX `idx_visitors_department` (`DepartmentID`),
  CONSTRAINT `fk_visitors_department` 
    FOREIGN KEY (`DepartmentID`) 
    REFERENCES `Departments` (`DepartmentID`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 4: Visits
-- Description: Individual visit events and live check-in / check-out logs.
-- Constraints:
--   - VisitorID references Visitors(VisitorID) with ON DELETE CASCADE
--     (Deleting a visitor safely removes their associated historical visit logs).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Visits`;
CREATE TABLE `Visits` (
  `VisitID` INT NOT NULL AUTO_INCREMENT,
  `VisitorID` INT NOT NULL,
  `CheckIn` DATETIME DEFAULT NULL,
  `CheckOut` DATETIME DEFAULT NULL,
  `VisitDate` DATE NOT NULL,
  `Status` ENUM('checked-in', 'checked-out') NOT NULL DEFAULT 'checked-in',
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`VisitID`),
  INDEX `idx_visits_visitor` (`VisitorID`),
  INDEX `idx_visits_date` (`VisitDate`),
  INDEX `idx_visits_status` (`Status`),
  INDEX `idx_visits_checkin` (`CheckIn`),
  INDEX `idx_visits_checkout` (`CheckOut`),
  CONSTRAINT `fk_visits_visitor` 
    FOREIGN KEY (`VisitorID`) 
    REFERENCES `Visitors` (`VisitorID`) 
    ON UPDATE CASCADE 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA INSERTIONS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Departments Seed Data
-- ---------------------------------------------------------------------
INSERT INTO `Departments` (`DepartmentID`, `Name`, `Status`, `CreatedAt`) VALUES
(1, 'Front Office', 'active', NOW()),
(2, 'Administration', 'active', NOW()),
(3, 'IT Department', 'active', NOW()),
(4, 'Finance', 'active', NOW()),
(5, 'Human Resources', 'active', NOW());

-- ---------------------------------------------------------------------
-- 2. Hosts (Meeting Officers) Seed Data
-- ---------------------------------------------------------------------
INSERT INTO `Hosts` (`HostID`, `Name`, `DepartmentID`, `Designation`, `Email`, `Phone`, `OfficeRoom`, `Status`, `CreatedAt`) VALUES
(1, 'Dr. Kamal Perera', 3, 'Head of Systems & Infrastructure', 'kamal.p@vms.org', '+94 77 123 4567', 'Room 402 - Level 4', 'active', NOW()),
(2, 'Nirmala Wickramasinghe', 5, 'Senior HR Manager', 'nirmala.w@vms.org', '+94 71 234 5678', 'Room 205 - Level 2', 'active', NOW()),
(3, 'Chandana Silva', 4, 'Financial Controller & Chief Accountant', 'chandana.s@vms.org', '+94 76 345 6789', 'Room 310 - Level 3', 'active', NOW()),
(4, 'Ashan De Silva', 2, 'Director of Facilities & Operations', 'ashan.d@vms.org', '+94 78 567 8901', 'Room 108 - Level 1', 'active', NOW()),
(5, 'Front Desk Officer', 1, 'Senior Reception Executive', 'frontdesk@vms.org', '+94 11 234 5678', 'Main Lobby Counter A', 'active', NOW()),
(6, 'Samantha Alwis', 2, 'Executive Secretary to General Manager', 'samantha.a@vms.org', '+94 70 888 9999', 'Room 102 - Level 1', 'active', NOW()),
(7, 'Ruwini Gunasekara', 3, 'Senior Software Solutions Architect', 'ruwini.g@vms.org', '+94 72 333 4444', 'Room 405 - Level 4', 'active', NOW());

-- ---------------------------------------------------------------------
-- 3. Users Seed Data
-- Passwords hashed with PHP password_hash(..., PASSWORD_BCRYPT):
--   - admin: Admin@123 (Administrator account)
--   - uoc:   Uoc@123   (Ordinary staff account)
-- ---------------------------------------------------------------------
INSERT INTO `Users` (`UserID`, `Username`, `Password`, `FullName`, `Role`, `Status`, `CreatedAt`) VALUES
(1, 'admin', '$2y$10$aCx1VORmrCwMqDSKeOwfLOu7mobjM4wvqd0yaBbMnZ1DT8/tnfMPi', 'System Administrator', 'admin', 'active', NOW()),
(2, 'uoc', 'uoc', 'UOC Staff Officer', 'user', 'active', NOW());

-- ---------------------------------------------------------------------
-- 3. Sample Visitors Seed Data
-- ---------------------------------------------------------------------
INSERT INTO `Visitors` (`VisitorID`, `Name`, `NIC`, `Phone`, `Email`, `Purpose`, `Host`, `DepartmentID`, `CreatedAt`) VALUES
(1, 'Ruwan Jayasinghe', '199012345678', '+94 77 123 4567', 'ruwan.j@example.com', 'Network Security Consultation', 'Dr. Kamal Perera', 3, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'Dilani Senaratne', '855678123V', '+94 71 234 5678', 'dilani.s@hrpartners.lk', 'Panel Interview for Senior Accountant', 'Nirmala Wickramasinghe', 5, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'Kasun Fernando', '199434567890', '+94 76 345 6789', 'kasun.f@auditcorp.com', 'Quarterly Financial Statement Audit', 'Chandana Silva', 4, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'Chamari Bandara', '926789123V', '+94 75 456 7890', 'chamari.b@logistics.lk', 'Official Courier & Document Delivery', 'Front Desk Staff', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 'Sunil Wickramasinghe', '198812398745', '+94 78 567 8901', 'sunil.w@facilities.com', 'Annual Facility Fire Safety Inspection', 'Ashan De Silva', 2, NOW()),
(6, 'Anoma Priyadarshani', '957812345V', '+94 70 678 9012', 'anoma.p@solutions.io', 'ERP System Demonstration', 'Dr. Kamal Perera', 3, NOW());

-- ---------------------------------------------------------------------
-- 4. Sample Visits Seed Data
-- Past visits (checked-out) and active visits today (checked-in)
-- ---------------------------------------------------------------------
INSERT INTO `Visits` (`VisitID`, `VisitorID`, `CheckIn`, `CheckOut`, `VisitDate`, `Status`, `CreatedAt`) VALUES
-- 4 days ago (Checked out)
(1, 1, DATE_SUB(NOW(), INTERVAL '4 08:30:00' DAY_SECOND), DATE_SUB(NOW(), INTERVAL '4 06:15:00' DAY_SECOND), CURRENT_DATE - INTERVAL 4 DAY, 'checked-out', DATE_SUB(NOW(), INTERVAL 4 DAY)),
-- 3 days ago (Checked out)
(2, 2, DATE_SUB(NOW(), INTERVAL '3 09:00:00' DAY_SECOND), DATE_SUB(NOW(), INTERVAL '3 07:30:00' DAY_SECOND), CURRENT_DATE - INTERVAL 3 DAY, 'checked-out', DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- 2 days ago (Checked out)
(3, 3, DATE_SUB(NOW(), INTERVAL '2 10:15:00' DAY_SECOND), DATE_SUB(NOW(), INTERVAL '2 08:00:00' DAY_SECOND), CURRENT_DATE - INTERVAL 2 DAY, 'checked-out', DATE_SUB(NOW(), INTERVAL 2 DAY)),
-- Yesterday (Checked out)
(4, 4, DATE_SUB(NOW(), INTERVAL '1 11:00:00' DAY_SECOND), DATE_SUB(NOW(), INTERVAL '1 10:20:00' DAY_SECOND), CURRENT_DATE - INTERVAL 1 DAY, 'checked-out', DATE_SUB(NOW(), INTERVAL 1 DAY)),
-- Today (Checked out)
(5, 5, CONCAT(CURRENT_DATE, ' 08:45:00'), CONCAT(CURRENT_DATE, ' 10:30:00'), CURRENT_DATE, 'checked-out', NOW()),
-- Today (Currently in premises - Checked in)
(6, 6, CONCAT(CURRENT_DATE, ' 09:30:00'), NULL, CURRENT_DATE, 'checked-in', NOW());
