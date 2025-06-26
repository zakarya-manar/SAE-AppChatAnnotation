SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `User` (
    `user_id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `is_online` BOOLEAN NOT NULL DEFAULT 0,
    `user_token` VARCHAR(32) DEFAULT NULL,
    `user_connection_id` INT DEFAULT NULL
);

-- Table Message (sans emotion du sender)
CREATE TABLE `Message` (
    `message_id` INT PRIMARY KEY AUTO_INCREMENT,
    `to_user_id` INT NOT NULL,
    `from_user_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`to_user_id`) REFERENCES User(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_user_id`) REFERENCES User(`user_id`) ON DELETE CASCADE
);

-- Table Annotation (pour stocker les annotations)
CREATE TABLE `Annotation` (
    `annotation_id` INT PRIMARY KEY AUTO_INCREMENT,
    `message_id` INT NOT NULL,
    `annotator_id` INT NOT NULL,
    `emotion` ENUM('joie', 'colère', 'tristesse', 'surprise', 'dégoût', 'peur') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`annotator_id`) REFERENCES User(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`message_id`) REFERENCES Message(`message_id`) ON DELETE CASCADE
);