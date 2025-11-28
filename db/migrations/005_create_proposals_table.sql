
CREATE TABLE IF NOT EXISTS `proposals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `community_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `end_time` TIMESTAMP NOT NULL,
  `status` ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`community_id`) REFERENCES `communities`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `proposal_votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `proposal_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `vote` ENUM('up', 'down') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_proposal_vote` (`user_id`, `proposal_id`),
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);
