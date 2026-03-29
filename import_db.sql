CREATE DATABASE IF NOT EXISTS `mini_event_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mini_event_db`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$12$q7Ee3PlufiBlU3yO.6j/5uVMqAmT6V9IWDdskDxJAV9rVbe64D5ka');

CREATE TABLE `app_user` (
  `id` int(11) NOT NULL,
  `username` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `app_user` (`id`, `username`, `password_hash`) VALUES
(1, 'user', '$2y$13$5YYyqomFDV7Xo5TamO0ODe6bH0ayBUXEqaffCz9tFvZAjMiVk0dLm'),
(2, 'admin', '$2y$13$Yojf4978YNjk8Y209xY5N./83MyQsos1KW0DrmoAamh8Xupdnrwhy'),
(3, 'nourhene', '$2y$13$fxIVbgzzs9HjnUOjvLe41.Sml8uRW6RsscBhmw44CTVa3ttjl.l16'),
(4, 'nounou', '$2y$13$o0/w7GvTd0Ss3OPRtmj8qOmBLctAw4Uloas3Dujzh9ROy7XM1iqT2');

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260327122400', '2026-03-27 12:58:40', 138),
('DoctrineMigrations\\Version20260327160000', '2026-03-27 12:58:40', 49);

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `seats` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `events` (`id`, `title`, `description`, `date`, `location`, `seats`, `image`) VALUES
(1, 'graduation ceremony', 'Un événement spécial dédié à la célébration de la réussite académique de nos diplômés, marquant la fin de leur parcours universitaire et le début d’un nouveau chapitre professionnel, dans une ambiance solennelle et festive.', '2026-01-24 00:00:00', 'Orient Palace Sousse', 16, 'https://th.bing.com/th/id/OIP.MG0O2Mi_fwgprkqNlnsXswHaE8?w=284&h=189&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3'),
(2, 'Hackathon', 'A competitive event where participants collaborate to build innovative projects within a limited time.', '2026-02-20 00:00:00', 'Epi', 40, 'https://cdn.techinasia.com/wp-content/uploads/2016/11/hackathon.jpg'),
(4, 'Tech Workshop', 'A hands-on workshop focused on developing technical skills and discovering new technologies.', '2026-03-01 00:00:00', 'ISSATSo', 22, 'https://www.publicdomainpictures.net/pictures/230000/velka/workshop.jpg'),
(6, '📚 Training Session', 'An educational session aimed at improving academic or professional skills.', '2026-12-05 00:00:00', 'Polytechnique', 96, 'https://images.pexels.com/photos/3184290/pexels-photo-3184290.jpeg');

CREATE TABLE `passkey_credential` (
  `id` int(11) NOT NULL,
  `user_type` varchar(16) NOT NULL,
  `user_identifier` varchar(180) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` longtext DEFAULT NULL,
  `sign_count` int(11) DEFAULT NULL,
  `transports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transports`)),
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reservations` (`id`, `event_id`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 1, 'nourhene zaouali', 'zaoualinourhene14@gmail.com', '98591411', '2025-12-18 01:51:20'),
(2, 1, 'ben ftima ala', 'nnn@j.v', '28465277', '2025-12-18 02:01:57'),
(3, 1, 'ben ftima ala', 'd@h.b', '28465277', '2025-12-18 02:02:43'),
(5, 2, 'ben ftima ala', 'v@s.v', '28465277', '2025-12-18 02:31:33'),
(6, 1, 'ben ftima ala', 'e@h.n', '28465277', '2025-12-18 03:31:58'),
(9, 2, 'nourhene', 'zaoualinourhene@gmail.com', '98591411', '2025-12-18 17:33:00'),
(10, 2, 'hamza', 'hamzakalfaoui@gmail.com', '22345671', '2025-12-18 17:33:36'),
(11, 2, 'raslen', 'raslendahmani@gmail.com', '54678902', '2025-12-18 17:33:58'),
(12, 4, 'nourhene', 'zaoualinourhene14@gmail.com', '28465277', '2025-12-18 17:34:41'),
(13, 1, 'Nourhene Zaouali', 'zaoualinourhene14@gmail.com', '28465277', '2026-03-27 22:42:35'),
(14, 1, 'Nourhene Zaouali', 'zaoualinourhene14@gmail.com', '28465277', '2026-03-27 22:42:55'),
(15, 1, 'Nourhene Zaouali', 'zaoualinourhene14@gmail.com', '28465277', '2026-03-28 00:31:47'),
(16, 1, 'Nourhene Zaouali', 'zaoualinourhene14@gmail.com', '28465277', '2026-03-29 00:27:59'),
(17, 6, 'Nourhene Zaouali', 'zaoualinourhene14@gmail.com', '28465277', '2026-03-29 00:43:06'),
(18, 6, 'hamza', 'liohamza2003@gmail.com', '28465277', '2026-03-29 01:19:59'),
(19, 6, 'Nourhene Zaouali', 'nourhenezaouali@gmail.com', '28465277', '2026-03-29 01:20:18'),
(20, 6, 'Nourhene Zaouali', 'nourhenezaouali@gmail.com', '28465277', '2026-03-29 01:50:55');

ALTER TABLE `admin` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `app_user` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `UNIQ_88BDF3E9F85E0677` (`username`);
ALTER TABLE `doctrine_migration_versions` ADD PRIMARY KEY (`version`);
ALTER TABLE `events` ADD PRIMARY KEY (`id`);
ALTER TABLE `passkey_credential` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `uniq_passkey_credential_id` (`credential_id`), ADD KEY `idx_passkey_user` (`user_type`,`user_identifier`);
ALTER TABLE `reservations` ADD PRIMARY KEY (`id`), ADD KEY `fk_event` (`event_id`);

ALTER TABLE `admin` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `app_user` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `events` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `passkey_credential` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `reservations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE `reservations` ADD CONSTRAINT `fk_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;
