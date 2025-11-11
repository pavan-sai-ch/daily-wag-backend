--
-- Table structure for table `users`
-- This table is the single source of truth for all people (customers, doctors, admins).
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

    -- Doctor-specific fields (NULL if role is not 'doctor')
                         `specialty` VARCHAR(100) NULL,

                         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `store`
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
-- Table structure for table `pets`
--
CREATE TABLE `pets` (
                        `pet_id` INT AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT NOT NULL COMMENT 'The owner of the pet',
                        `pet_category` VARCHAR(50) NULL,
                        `pet_breed` VARCHAR(50) NULL,
                        `pet_age` INT NULL,
                        `medical_condition` VARCHAR(255) NULL,
                        `photo_url` VARCHAR(255) NULL COMMENT 'S3 link for pet photo',

    -- Used by the Adoption feature
                        `adoption_status` ENUM('not_available', 'available', 'pending', 'adopted') NOT NULL DEFAULT 'not_available',

                        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `immunizations`
-- This is a one-to-many relationship with `pets`.
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
-- Table structure for table `bookings`
-- This table merges "appointments" and "grooming" into one logical table.
--
CREATE TABLE `bookings` (
                            `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `user_id` INT NOT NULL COMMENT 'The user who booked',
                            `pet_id` INT NOT NULL COMMENT 'The pet being booked for',
                            `doctor_id` INT NULL COMMENT 'The doctor for the medical booking (NULL for grooming)',
                            `booking_type` ENUM('medical', 'grooming') NOT NULL,
                            `booking_date` DATETIME NOT NULL,
                            `service_type` VARCHAR(150) NULL COMMENT 'e.g., "Checkup", "Basic Wash"',
                            `status` ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Pending',

                            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
                            FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`),
    -- This is the corrected line:
                            FOREIGN KEY (`doctor_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `adoption`
-- This is a historical log of completed or in-progress adoptions.
--
CREATE TABLE `adoption` (
                            `adopt_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `pet_id` INT NOT NULL,
                            `adopter_id` INT NULL COMMENT 'The user who is adopting',
                            `adoption_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `status` ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',

    -- These are the corrected lines:
                            FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE RESTRICT,
                            FOREIGN KEY (`adopter_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `orders`
-- This table holds one entry per completed order (shopping cart).
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
-- Table structure for table `order_items`
-- This is the junction table for Orders <-> Store.
--
CREATE TABLE `order_items` (
                               `order_item_id` INT AUTO_INCREMENT PRIMARY KEY,
                               `order_id` INT NOT NULL,
                               `item_id` INT NOT NULL,
                               `quantity` INT NOT NULL DEFAULT 1,
                               `price_at_purchase` DECIMAL(10,2) NOT NULL COMMENT 'Price of the item when the order was placed',

    -- These are the corrected lines:
                               FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
                               FOREIGN KEY (`item_id`) REFERENCES `store`(`item_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `payments`
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

    -- These are the corrected lines:
                            FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`),
                            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `membership`
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