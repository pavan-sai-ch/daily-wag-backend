--
-- Table: users
-- Single source of truth for all roles (Customers, Doctors, Admins, Staff).
--
CREATE TABLE `users` (
                         `user_id` INT AUTO_INCREMENT PRIMARY KEY,
                         `first_name` VARCHAR(50) NOT NULL,
                         `last_name` VARCHAR(50) NOT NULL,
                         `email` VARCHAR(100) NOT NULL UNIQUE,
                         `password` VARCHAR(255) NOT NULL,
                         `phone` VARCHAR(20) NULL,
                         `address` VARCHAR(255) NULL,
                         `role` ENUM('user', 'doctor', 'admin', 'staff') NOT NULL DEFAULT 'user',
                         `specialty` VARCHAR(100) NULL COMMENT 'Only for doctors',
                         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: store (Products)
--
CREATE TABLE `store` (
                         `item_id` INT AUTO_INCREMENT PRIMARY KEY,
                         `item_name` VARCHAR(50) NOT NULL,
                         `description` VARCHAR(200) NULL,
                         `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                         `stock` INT NOT NULL DEFAULT 0,
                         `photo_url` VARCHAR(255) NULL COMMENT 'S3 link for product photo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: pets
--
CREATE TABLE `pets` (
                        `pet_id` INT AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT NOT NULL COMMENT 'The owner of the pet',
                        `pet_name` VARCHAR(50) NOT NULL,
                        `pet_category` VARCHAR(50) NULL,
                        `pet_breed` VARCHAR(50) NULL,
                        `pet_age` INT NULL,
                        `medical_condition` VARCHAR(255) NULL,
                        `photo_url` VARCHAR(255) NULL COMMENT 'S3 link for pet photo',
                        `adoption_status` ENUM('not_available', 'available', 'pending', 'adopted') NOT NULL DEFAULT 'not_available',
                        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: immunizations
--
CREATE TABLE `immunizations` (
                                 `immun_id` INT AUTO_INCREMENT PRIMARY KEY,
                                 `pet_id` INT NOT NULL,
                                 `vaccine_name` VARCHAR(50) NOT NULL,
                                 `vaccine_date` DATE NULL,
                                 `due_date` DATE NULL,
                                 `comments` VARCHAR(200) NULL,
                                 FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: bookings (Appointments & Grooming)
--
CREATE TABLE `bookings` (
                            `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `user_id` INT NOT NULL COMMENT 'The user who booked',
                            `pet_id` INT NOT NULL COMMENT 'The pet being booked for',
                            `doctor_id` INT NULL COMMENT 'The doctor for the medical booking (NULL for grooming)',
                            `booking_type` ENUM('medical', 'grooming') NOT NULL,
                            `booking_date` DATETIME NOT NULL,
                            `service_type` VARCHAR(150) NULL COMMENT 'e.g., "Checkup", "Basic Wash"',
                            `checkin_time` DATETIME NULL,
                            `status` ENUM('Pending', 'Confirmed', 'Checked In', 'Completed', 'Cancelled', 'No Show') NOT NULL DEFAULT 'Pending',
                            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
                            FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`),
                            FOREIGN KEY (`doctor_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: adoption
--
CREATE TABLE `adoption` (
                            `adopt_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `pet_id` INT NOT NULL,
                            `adopter_id` INT NULL COMMENT 'The user who is adopting',
                            `adoption_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `status` ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
                            FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE RESTRICT,
                            FOREIGN KEY (`adopter_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: orders
--
CREATE TABLE `orders` (
                          `order_id` INT AUTO_INCREMENT PRIMARY KEY,
                          `user_id` INT NOT NULL,
                          `grand_total` DECIMAL(10,2) NOT NULL,
                          `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                          `payment_method` ENUM('cash', 'card') NOT NULL,
                          `delivery_type` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
                          `tracking_number` VARCHAR(50) NULL,
                          `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                          FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: order_items
--
CREATE TABLE `order_items` (
                               `order_item_id` INT AUTO_INCREMENT PRIMARY KEY,
                               `order_id` INT NOT NULL,
                               `item_id` INT NOT NULL,
                               `quantity` INT NOT NULL DEFAULT 1,
                               `price_at_purchase` DECIMAL(10,2) NOT NULL COMMENT 'Price of the item when the order was placed',
                               FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
                               FOREIGN KEY (`item_id`) REFERENCES `store`(`item_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: payments
--
CREATE TABLE `payments` (
                            `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `order_id` INT NOT NULL,
                            `user_id` INT NOT NULL,
                            `amount` DECIMAL(10,2) NOT NULL,
                            `payment_method` ENUM('cash', 'card') NOT NULL,
                            `payment_status` ENUM('pending', 'complete', 'failed', 'refunded') DEFAULT 'pending',
                            `transaction_id` VARCHAR(100) UNIQUE NULL,
                            `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`),
                            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: membership
--
CREATE TABLE `membership` (
                              `membership_id` INT AUTO_INCREMENT PRIMARY KEY,
                              `user_id` INT NOT NULL,
                              `plan_details` VARCHAR(100) NOT NULL,
                              `start_date` DATE NOT NULL,
                              `end_date` DATE NOT NULL,
                              `status` ENUM('not active', 'active', 'expired') NOT NULL DEFAULT 'not active',
                              FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: schedule
--
CREATE TABLE `schedule` (
                            `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `user_id` INT NULL COMMENT 'NULL for Grooming/General, Set for Doctors',
                            `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
                            `start_time` TIME NOT NULL,
                            `end_time` TIME NOT NULL,
                            `is_active` BOOLEAN DEFAULT TRUE,
                            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                            UNIQUE KEY `unique_schedule` (`user_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table: user_visits (Telemetry)
--
CREATE TABLE `user_visits` (
                               `visit_id` INT AUTO_INCREMENT PRIMARY KEY,
                               `user_id` INT NULL,
                               `ip_address` VARCHAR(45) NOT NULL,
                               `page_visited` VARCHAR(100) NOT NULL,
                               `visit_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
                               FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;