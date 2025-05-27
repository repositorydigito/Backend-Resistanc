-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.0.42 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para modelorsistanc
CREATE DATABASE IF NOT EXISTS `modelorsistanc` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `modelorsistanc`;

-- Volcando estructura para tabla modelorsistanc.additional_services
CREATE TABLE IF NOT EXISTS `additional_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `description` text,
  `price_soles` decimal(10,2) NOT NULL,
  `duration_min` int unsigned NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.benefit_usage_log
CREATE TABLE IF NOT EXISTS `benefit_usage_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `benefit_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `usage_type` enum('priority_booking','discount_applied','free_shake','auto_enrollment','guest_pass','pt_session') COLLATE utf8mb4_unicode_ci NOT NULL,
  `usage_value` decimal(8,2) DEFAULT NULL COMMENT 'Valor monetario del beneficio usado',
  `usage_details` json DEFAULT NULL COMMENT 'Detalles específicos del uso',
  `period_month` tinyint unsigned NOT NULL,
  `period_year` smallint unsigned NOT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `order_id` (`order_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `idx_benefit_usage_user` (`user_id`,`period_year`,`period_month`),
  KEY `idx_benefit_usage_benefit` (`benefit_id`,`used_at`),
  KEY `idx_benefit_usage_period` (`period_year`,`period_month`),
  CONSTRAINT `benefit_usage_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `benefit_usage_log_ibfk_2` FOREIGN KEY (`benefit_id`) REFERENCES `membership_benefits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `benefit_usage_log_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `benefit_usage_log_ibfk_4` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `benefit_usage_log_ibfk_5` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro detallado del uso de beneficios por usuario';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_package_id` bigint unsigned NOT NULL,
  `class_schedule_id` bigint unsigned NOT NULL,
  `booking_reference` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `companions_count` tinyint unsigned DEFAULT '0',
  `companion_details` json DEFAULT NULL COMMENT 'Información de acompañantes',
  `selected_seats` json DEFAULT NULL COMMENT 'Array de asientos seleccionados',
  `booking_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `arrival_time` timestamp NULL DEFAULT NULL,
  `checkout_time` timestamp NULL DEFAULT NULL,
  `status` enum('pending','confirmed','checked_in','completed','cancelled','no_show','waitlisted') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `booking_type` enum('presencial','virtual') COLLATE utf8mb4_unicode_ci DEFAULT 'presencial',
  `virtual_access_granted` tinyint(1) DEFAULT '0',
  `virtual_join_time` timestamp NULL DEFAULT NULL,
  `virtual_duration_minutes` tinyint unsigned DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancellation_fee` decimal(8,2) DEFAULT '0.00',
  `rating` tinyint unsigned DEFAULT NULL COMMENT '1-5 stars',
  `review_comment` text COLLATE utf8mb4_unicode_ci,
  `has_extra_options` tinyint(1) DEFAULT '0' COMMENT 'Flag para optimizar consultas',
  `technical_issues` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `idx_bookings_user` (`user_id`,`status`),
  KEY `idx_bookings_schedule` (`class_schedule_id`,`status`),
  KEY `idx_bookings_package` (`user_package_id`),
  KEY `idx_bookings_date` (`booking_date`),
  KEY `idx_bookings_status` (`status`),
  KEY `idx_bookings_has_extras` (`has_extra_options`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_package_id`) REFERENCES `user_packages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_companions` CHECK ((`companions_count` <= 3)),
  CONSTRAINT `chk_rating` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reservas de clases por usuarios con soporte virtual y presencial';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.booking_extra_options
CREATE TABLE IF NOT EXISTS `booking_extra_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL COMMENT 'Producto base si aplica',
  `product_variant_id` bigint unsigned DEFAULT NULL COMMENT 'Variante específica si aplica',
  `option_type` enum('shake_base','shake_flavor','supplement_type','recovery_service','wellness_preference','dietary_restriction','special_request','equipment_preference','music_preference','intensity_level') COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_value` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_description` text COLLATE utf8mb4_unicode_ci COMMENT 'Descripción detallada de la personalización',
  `additional_cost` decimal(8,2) DEFAULT '0.00',
  `is_free_benefit` tinyint(1) DEFAULT '0' COMMENT 'Si es beneficio gratuito de membresía',
  `discount_applied` decimal(8,2) DEFAULT '0.00',
  `status` enum('pending','confirmed','prepared','delivered','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `preparation_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notas para preparación en cocina/servicio',
  `prepared_by_staff_id` bigint unsigned DEFAULT NULL,
  `prepared_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_extra_booking` (`booking_id`),
  KEY `idx_booking_extra_product` (`product_id`),
  KEY `idx_booking_extra_variant` (`product_variant_id`),
  KEY `idx_booking_extra_type` (`option_type`,`status`),
  KEY `idx_booking_extra_status` (`status`),
  KEY `idx_booking_extra_options_booking_type` (`booking_id`,`option_type`,`status`),
  CONSTRAINT `booking_extra_options_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_extra_options_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_extra_options_ibfk_3` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_additional_cost_positive` CHECK ((`additional_cost` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Opciones y personalizaciones adicionales para reservas y servicios';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.booking_seats
CREATE TABLE IF NOT EXISTS `booking_seats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_schedule_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `seat_number` tinyint unsigned NOT NULL,
  `seat_row` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'A, B, C, etc. para organización visual',
  `seat_position` tinyint unsigned DEFAULT NULL COMMENT '1, 2, 3, etc. dentro de la fila',
  `status` enum('available','reserved','occupied','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `reserved_until` timestamp NULL DEFAULT NULL COMMENT 'Tiempo límite de reserva temporal',
  `equipment_type` enum('bike','reformer','mat') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de equipo en la posición',
  `equipment_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID específico del equipo',
  `special_needs` text COLLATE utf8mb4_unicode_ci COMMENT 'Notas especiales del puesto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_seat_per_schedule` (`class_schedule_id`,`seat_number`),
  KEY `idx_booking_seats_schedule` (`class_schedule_id`,`status`),
  KEY `idx_booking_seats_status` (`status`),
  KEY `idx_booking_seats_schedule_status` (`class_schedule_id`,`status`,`seat_number`),
  KEY `idx_booking_seats_booking` (`booking_id`),
  CONSTRAINT `booking_seats_ibfk_1` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_seats_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_seat_number` CHECK ((`seat_number` between 1 and 50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestión específica de asientos/puestos para clases presenciales';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.cart_items
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cart_prod_var` (`cart_id`,`product_id`,`variant_id`),
  KEY `fk_ci_prod` (`product_id`),
  KEY `fk_ci_var` (`variant_id`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_ci_var` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.classes
CREATE TABLE IF NOT EXISTS `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discipline_id` bigint unsigned NOT NULL,
  `instructor_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `type` enum('presencial','en_vivo','grabada') COLLATE utf8mb4_unicode_ci DEFAULT 'presencial',
  `duration_minutes` tinyint unsigned NOT NULL,
  `max_capacity` tinyint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `difficulty_level` enum('beginner','intermediate','advanced','all_levels') COLLATE utf8mb4_unicode_ci DEFAULT 'all_levels',
  `music_genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_requirements` text COLLATE utf8mb4_unicode_ci,
  `is_featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','draft') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_classes_discipline` (`discipline_id`,`status`),
  KEY `idx_classes_instructor` (`instructor_id`,`status`),
  KEY `idx_classes_studio` (`studio_id`),
  KEY `idx_classes_type` (`type`,`status`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `classes_ibfk_3` FOREIGN KEY (`studio_id`) REFERENCES `studios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clases de fitness disponibles';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.class_schedules
CREATE TABLE IF NOT EXISTS `class_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `instructor_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `scheduled_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_capacity` tinyint unsigned NOT NULL,
  `available_spots` tinyint unsigned NOT NULL,
  `booked_spots` tinyint unsigned DEFAULT '0',
  `waitlist_spots` tinyint unsigned DEFAULT '0',
  `booking_opens_at` timestamp NULL DEFAULT NULL COMMENT 'Cuándo se abre la reserva',
  `booking_closes_at` timestamp NULL DEFAULT NULL COMMENT 'Cuándo se cierra la reserva',
  `cancellation_deadline` timestamp NULL DEFAULT NULL,
  `special_notes` text COLLATE utf8mb4_unicode_ci,
  `is_holiday_schedule` tinyint(1) DEFAULT '0',
  `status` enum('scheduled','in_progress','completed','cancelled','postponed') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_schedule` (`class_id`,`scheduled_date`,`start_time`),
  KEY `studio_id` (`studio_id`),
  KEY `idx_class_schedules_date` (`scheduled_date`,`status`),
  KEY `idx_class_schedules_class` (`class_id`,`scheduled_date`),
  KEY `idx_class_schedules_instructor` (`instructor_id`,`scheduled_date`),
  KEY `idx_class_schedules_availability` (`available_spots`,`status`),
  CONSTRAINT `class_schedules_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_schedules_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `class_schedules_ibfk_3` FOREIGN KEY (`studio_id`) REFERENCES `studios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Horarios programados para clases específicas';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.class_waitlist
CREATE TABLE IF NOT EXISTS `class_waitlist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_schedule_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('waiting','notified','accepted','expired') DEFAULT 'waiting',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_schedule` (`class_schedule_id`,`user_id`),
  KEY `fk_wl_user` (`user_id`),
  CONSTRAINT `fk_wl_schedule` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.coach_ratings
CREATE TABLE IF NOT EXISTS `coach_ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `score` tinyint unsigned NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_coach` (`instructor_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `coach_ratings_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coach_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coach_ratings_chk_1` CHECK ((`score` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.disciplines
CREATE TABLE IF NOT EXISTS `disciplines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'cycling, solidreformer, pilates_mat',
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_hex` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Color para UI (#FF5733)',
  `equipment_required` json DEFAULT NULL COMMENT 'Equipos necesarios',
  `difficulty_level` enum('beginner','intermediate','advanced','all_levels') COLLATE utf8mb4_unicode_ci DEFAULT 'all_levels',
  `calories_per_hour_avg` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` tinyint unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_disciplines_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Disciplinas fitness disponibles';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.discount_codes
CREATE TABLE IF NOT EXISTS `discount_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `type` enum('percent','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `usage_limit` int unsigned DEFAULT NULL,
  `times_used` int unsigned DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.instructors
CREATE TABLE IF NOT EXISTS `instructors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'Si el instructor tiene cuenta de usuario',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialties` json NOT NULL COMMENT 'Array de discipline IDs',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `certifications` json DEFAULT NULL COMMENT 'Certificaciones y títulos',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_handle` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_head_coach` tinyint(1) DEFAULT '0',
  `experience_years` tinyint unsigned DEFAULT NULL,
  `rating_average` decimal(3,2) DEFAULT '0.00',
  `total_classes_taught` int unsigned DEFAULT '0',
  `hire_date` date DEFAULT NULL,
  `hourly_rate_soles` decimal(8,2) DEFAULT NULL,
  `status` enum('active','inactive','on_leave','terminated') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `availability_schedule` json DEFAULT NULL COMMENT 'Horarios disponibles por día',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_instructors_status` (`status`),
  KEY `idx_instructors_head_coach` (`is_head_coach`,`status`),
  KEY `idx_instructors_rating` (`rating_average`),
  KEY `idx_instructors_user` (`user_id`),
  CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Instructores de fitness y sus especialidades';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.instructor_discipline
CREATE TABLE IF NOT EXISTS `instructor_discipline` (
  `instructor_id` bigint unsigned NOT NULL,
  `discipline_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`instructor_id`,`discipline_id`),
  KEY `fk_insd_dis` (`discipline_id`),
  CONSTRAINT `fk_insd_dis` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insd_ins` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.login_audits
CREATE TABLE IF NOT EXISTS `login_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'NULL para intentos fallidos sin usuario identificado',
  `email_attempted` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IPv4 o IPv6',
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_method` enum('email_password','social_google','social_facebook','social_apple') COLLATE utf8mb4_unicode_ci DEFAULT 'email_password',
  `success` tinyint(1) NOT NULL,
  `failure_reason` enum('invalid_credentials','account_suspended','email_not_verified','too_many_attempts','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_audits_user` (`user_id`,`created_at`),
  KEY `idx_login_audits_ip` (`ip`,`created_at`),
  KEY `idx_login_audits_success` (`success`,`created_at`),
  KEY `idx_login_audits_email` (`email_attempted`,`created_at`),
  KEY `idx_login_audits_failed_attempts` (`ip`,`success`,`created_at`),
  CONSTRAINT `login_audits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría completa de intentos de acceso al sistema';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.membership_benefits
CREATE TABLE IF NOT EXISTS `membership_benefits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `membership_level` enum('resistance','gold','black') COLLATE utf8mb4_unicode_ci NOT NULL,
  `benefit_type` enum('priority_booking','discount_percentage','free_shakes','auto_enrollment','guest_passes','personal_training') COLLATE utf8mb4_unicode_ci NOT NULL,
  `benefit_value` json NOT NULL COMMENT 'Valor específico del beneficio',
  `monthly_allowance` int unsigned DEFAULT NULL COMMENT 'Cantidad mensual permitida',
  `monthly_used` int unsigned DEFAULT '0',
  `yearly_allowance` int unsigned DEFAULT NULL,
  `yearly_used` int unsigned DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `auto_renew` tinyint(1) DEFAULT '1',
  `activated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_reset_at` timestamp NULL DEFAULT NULL COMMENT 'Último reset mensual/anual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_membership_benefits_user` (`user_id`,`membership_level`),
  KEY `idx_membership_benefits_level` (`membership_level`,`benefit_type`),
  KEY `idx_membership_benefits_active` (`is_active`,`expires_at`),
  CONSTRAINT `membership_benefits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Beneficios automáticos según nivel de membresía';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notification_type` enum('booking_reminder','class_cancelled','package_expiring','payment_failed','membership_upgrade','promotion','system_maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_text` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `channels` json NOT NULL COMMENT 'push, email, sms',
  `scheduled_for` timestamp NULL DEFAULT NULL COMMENT 'Para notificaciones programadas',
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`,`status`),
  KEY `idx_notifications_scheduled` (`scheduled_for`,`status`),
  KEY `idx_notifications_type` (`notification_type`,`status`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistema de notificaciones multi-canal';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `order_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_type` enum('purchase','booking_extras','subscription','gift') COLLATE utf8mb4_unicode_ci DEFAULT 'purchase',
  `subtotal_soles` decimal(10,2) NOT NULL,
  `tax_amount_soles` decimal(8,2) DEFAULT '0.00',
  `shipping_amount_soles` decimal(8,2) DEFAULT '0.00',
  `discount_amount_soles` decimal(8,2) DEFAULT '0.00',
  `total_amount_soles` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT 'PEN',
  `status` enum('pending','confirmed','processing','preparing','ready','delivered','cancelled','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_status` enum('pending','authorized','paid','partially_paid','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `delivery_method` enum('pickup','delivery','digital') COLLATE utf8mb4_unicode_ci DEFAULT 'pickup',
  `delivery_date` date DEFAULT NULL,
  `delivery_time_slot` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_address` json DEFAULT NULL,
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `promocode_used` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `discount_code_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`,`status`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_payment_status` (`payment_status`),
  KEY `idx_orders_number` (`order_number`),
  KEY `idx_orders_date` (`created_at`),
  KEY `fk_order_coupon` (`discount_code_id`),
  CONSTRAINT `fk_order_coupon` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Órdenes de compra para productos y servicios adicionales';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `variant_info` json DEFAULT NULL COMMENT 'Información de variante seleccionada',
  `quantity` tinyint unsigned NOT NULL,
  `unit_price_soles` decimal(8,2) NOT NULL,
  `total_price_soles` decimal(8,2) NOT NULL,
  `customizations` json DEFAULT NULL COMMENT 'Personalizaciones del producto',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  KEY `idx_order_items_variant` (`product_variant_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items individuales dentro de órdenes de compra';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.packages
CREATE TABLE IF NOT EXISTS `packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: 1CLASER, PAQUETE5R, PAQUETE40R, RSISTANC360',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classes_quantity` int unsigned NOT NULL,
  `price_soles` decimal(8,2) NOT NULL,
  `original_price_soles` decimal(8,2) DEFAULT NULL COMMENT 'Precio original para mostrar descuentos',
  `validity_days` int unsigned NOT NULL COMMENT 'Días de vigencia del paquete',
  `package_type` enum('presencial','virtual','mixto') COLLATE utf8mb4_unicode_ci DEFAULT 'presencial',
  `billing_type` enum('one_time','monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci DEFAULT 'one_time',
  `is_virtual_access` tinyint(1) DEFAULT '0',
  `priority_booking_days` tinyint unsigned DEFAULT '0' COMMENT 'Días de anticipación para reservar',
  `auto_renewal` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `is_popular` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','coming_soon','discontinued') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `display_order` tinyint unsigned DEFAULT '0',
  `features` json DEFAULT NULL COMMENT 'Características y beneficios del paquete',
  `restrictions` json DEFAULT NULL COMMENT 'Restricciones y condiciones',
  `target_audience` enum('beginner','intermediate','advanced','all') COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_packages_status` (`status`,`display_order`),
  KEY `idx_packages_type` (`package_type`,`status`),
  KEY `idx_packages_price` (`price_soles`),
  KEY `idx_packages_featured` (`is_featured`,`is_popular`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paquetes de clases y membresías disponibles';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `short_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_soles` decimal(8,2) NOT NULL,
  `cost_price_soles` decimal(8,2) DEFAULT NULL,
  `compare_price_soles` decimal(8,2) DEFAULT NULL COMMENT 'Precio de comparación/original',
  `stock_quantity` int unsigned DEFAULT '0',
  `min_stock_alert` int unsigned DEFAULT '5',
  `weight_grams` int unsigned DEFAULT NULL,
  `dimensions` json DEFAULT NULL COMMENT 'Dimensiones del producto',
  `images` json DEFAULT NULL COMMENT 'URLs de imágenes del producto',
  `nutritional_info` json DEFAULT NULL COMMENT 'Para batidos y suplementos',
  `ingredients` json DEFAULT NULL,
  `allergens` json DEFAULT NULL,
  `product_type` enum('shake','supplement','merchandise','service','gift_card') COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_variants` tinyint(1) DEFAULT '0' COMMENT 'Si necesita variantes (tallas, colores)',
  `is_virtual` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `is_available_for_booking` tinyint(1) DEFAULT '0' COMMENT 'Si se puede agregar en reservas',
  `status` enum('active','inactive','out_of_stock','discontinued') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_products_category` (`category_id`,`status`),
  KEY `idx_products_type` (`product_type`,`status`),
  KEY `idx_products_sku` (`sku`),
  KEY `idx_products_featured` (`is_featured`,`status`),
  KEY `idx_products_booking` (`is_available_for_booking`,`status`),
  KEY `idx_products_stock` (`stock_quantity`,`min_stock_alert`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Productos para eCommerce: batidos, suplementos, merchandise';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.product_categories
CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` bigint unsigned DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` tinyint unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_product_categories_parent` (`parent_id`,`is_active`),
  KEY `idx_product_categories_slug` (`slug`),
  CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de productos para eCommerce';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.product_product_tag
CREATE TABLE IF NOT EXISTS `product_product_tag` (
  `product_id` bigint unsigned NOT NULL,
  `product_tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`product_tag_id`),
  KEY `fk_pt_tag` (`product_tag_id`),
  CONSTRAINT `fk_pt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_tag` FOREIGN KEY (`product_tag_id`) REFERENCES `product_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.product_tags
CREATE TABLE IF NOT EXISTS `product_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.product_variants
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SKU único para cada variante',
  `variant_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre descriptivo de la variante',
  `size` enum('XXS','XS','S','M','L','XL','XXL','2XL','3XL') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flavor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Para suplementos/batidos',
  `intensity` enum('light','medium','strong') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Para productos con intensidad',
  `price_modifier` decimal(8,2) DEFAULT '0.00' COMMENT 'Modificador sobre precio base',
  `cost_price` decimal(8,2) DEFAULT NULL COMMENT 'Precio de costo para análisis',
  `stock_quantity` int unsigned DEFAULT '0',
  `min_stock_alert` int unsigned DEFAULT '5',
  `max_stock_capacity` int unsigned DEFAULT NULL,
  `weight_grams` int unsigned DEFAULT NULL,
  `dimensions_cm` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'LxWxH en cm',
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_featured` tinyint(1) DEFAULT '0',
  `is_default` tinyint(1) DEFAULT '0' COMMENT 'Variante por defecto del producto',
  `sort_order` tinyint unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_product_variants_product` (`product_id`,`is_active`),
  KEY `idx_product_variants_sku` (`sku`),
  KEY `idx_product_variants_stock` (`stock_quantity`,`min_stock_alert`),
  KEY `idx_product_variants_size_color` (`size`,`color`),
  KEY `idx_product_variants_product_active_stock` (`product_id`,`is_active`,`stock_quantity`),
  KEY `idx_product_variants_default` (`product_id`,`is_default`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_price_modifier_range` CHECK ((`price_modifier` between -(9999.99) and 9999.99)),
  CONSTRAINT `chk_stock_positive` CHECK ((`stock_quantity` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Variantes específicas de productos con control granular de stock y pricing';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.promocodes
CREATE TABLE IF NOT EXISTS `promocodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discount_type` enum('percentage','fixed_amount','free_shipping','buy_x_get_y') COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(8,2) NOT NULL,
  `minimum_amount` decimal(8,2) DEFAULT NULL COMMENT 'Monto mínimo para aplicar',
  `maximum_discount` decimal(8,2) DEFAULT NULL COMMENT 'Descuento máximo aplicable',
  `applicable_to` enum('packages','products','both') COLLATE utf8mb4_unicode_ci DEFAULT 'both',
  `applicable_items` json DEFAULT NULL COMMENT 'IDs específicos si aplica',
  `usage_limit_total` int unsigned DEFAULT NULL,
  `usage_limit_per_user` tinyint unsigned DEFAULT '1',
  `usage_count` int unsigned DEFAULT '0',
  `starts_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_first_time_only` tinyint(1) DEFAULT '0',
  `target_audience` json DEFAULT NULL COMMENT 'Criterios de audiencia objetivo',
  `created_by_admin_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_promocodes_code` (`code`,`is_active`),
  KEY `idx_promocodes_active` (`is_active`,`starts_at`,`expires_at`),
  KEY `idx_promocodes_applicable` (`applicable_to`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Códigos promocionales y sistema de descuentos';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.promocode_usage
CREATE TABLE IF NOT EXISTS `promocode_usage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promocode_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `discount_amount` decimal(8,2) NOT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `idx_promocode_usage_code` (`promocode_id`),
  KEY `idx_promocode_usage_user` (`user_id`),
  CONSTRAINT `promocode_usage_ibfk_1` FOREIGN KEY (`promocode_id`) REFERENCES `promocodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promocode_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promocode_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promocode_usage_ibfk_4` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de uso de códigos promocionales';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.shopping_carts
CREATE TABLE IF NOT EXISTS `shopping_carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `item_count` int unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_guest` (`session_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.social_accounts
CREATE TABLE IF NOT EXISTS `social_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` enum('google','facebook','apple','instagram','tiktok') COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_uid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID único del proveedor',
  `provider_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_avatar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` text COLLATE utf8mb4_unicode_ci COMMENT 'Token de acceso del proveedor',
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_provider_uid` (`provider`,`provider_uid`),
  KEY `idx_social_accounts_user` (`user_id`),
  KEY `idx_social_accounts_provider` (`provider`,`is_active`),
  KEY `idx_social_accounts_email` (`provider_email`),
  CONSTRAINT `social_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Autenticación social y cuentas vinculadas';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.studios
CREATE TABLE IF NOT EXISTS `studios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_capacity` tinyint unsigned NOT NULL,
  `equipment_available` json DEFAULT NULL,
  `amenities` json DEFAULT NULL COMMENT 'Vestuarios, duchas, etc.',
  `studio_type` enum('cycling','reformer','mat','multipurpose') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_studios_type` (`studio_type`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estudios/salas donde se imparten las clases';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.studio_locations
CREATE TABLE IF NOT EXISTS `studio_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `country` char(2) DEFAULT 'PE',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.subscriptions
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `payment_method_id` bigint unsigned NOT NULL,
  `subscription_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre descriptivo de la suscripción',
  `status` enum('active','paused','cancelled','failed','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `billing_amount` decimal(8,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT 'PEN',
  `billing_frequency` enum('weekly','monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci DEFAULT 'monthly',
  `trial_period_days` tinyint unsigned DEFAULT '0',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `next_billing_date` date NOT NULL,
  `last_billing_date` date DEFAULT NULL,
  `billing_cycle_count` int unsigned DEFAULT '0',
  `failed_attempts` tinyint unsigned DEFAULT '0',
  `max_failed_attempts` tinyint unsigned DEFAULT '3',
  `grace_period_days` tinyint unsigned DEFAULT '7',
  `auto_renew` tinyint(1) DEFAULT '1',
  `prorate_charges` tinyint(1) DEFAULT '1',
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancellation_requested_by` enum('user','admin','system') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_code` (`subscription_code`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `idx_subscriptions_user` (`user_id`,`status`),
  KEY `idx_subscriptions_next_billing` (`next_billing_date`,`status`),
  KEY `idx_subscriptions_status` (`status`),
  KEY `idx_subscriptions_package` (`package_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `subscriptions_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `user_payment_methods` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Suscripciones recurrentes para paquetes como RSISTANC 360';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `payment_method_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `user_package_id` bigint unsigned DEFAULT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `transaction_type` enum('package_purchase','product_order','subscription_payment','refund','chargeback','fee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_soles` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT 'PEN',
  `exchange_rate` decimal(10,4) DEFAULT NULL,
  `gateway_provider` enum('culqi','niubiz','paypal','stripe','izipay','payu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_response` json DEFAULT NULL COMMENT 'Respuesta completa del gateway',
  `confirmation_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded','disputed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refund_reason` text COLLATE utf8mb4_unicode_ci,
  `processed_at` timestamp NULL DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `fees` json DEFAULT NULL COMMENT 'Comisiones y fees aplicados',
  `metadata` json DEFAULT NULL COMMENT 'Metadata adicional del pago',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `order_id` (`order_id`),
  KEY `user_package_id` (`user_package_id`),
  KEY `idx_transactions_user` (`user_id`,`payment_status`),
  KEY `idx_transactions_gateway` (`gateway_transaction_id`),
  KEY `idx_transactions_status` (`payment_status`),
  KEY `idx_transactions_type` (`transaction_type`),
  KEY `idx_transactions_date` (`created_at`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `user_payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`user_package_id`) REFERENCES `user_packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial completo de transacciones financieras';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Teléfono principal formato E.164',
  `user_type` enum('sin_plan','con_plan','admin','instructor') COLLATE utf8mb4_unicode_ci DEFAULT 'sin_plan',
  `status` enum('active','inactive','suspended','pending_verification') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_type_status` (`user_type`,`status`),
  KEY `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios principales del sistema RSISTANC';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_contacts
CREATE TABLE IF NOT EXISTS `user_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Formato E.164 +51XXXXXXXXX',
  `address_line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Peru',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_type` enum('home','work','emergency','other') COLLATE utf8mb4_unicode_ci DEFAULT 'home',
  `is_primary` tinyint(1) DEFAULT '0',
  `is_billing_address` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone` (`phone`),
  KEY `idx_user_contacts_user` (`user_id`),
  KEY `idx_user_contacts_primary` (`user_id`,`is_primary`),
  KEY `idx_user_contacts_phone` (`phone`),
  KEY `idx_user_contacts_city` (`city`,`country`),
  CONSTRAINT `user_contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contactos múltiples por usuario (teléfonos y direcciones)';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_favorites
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `favoritable_type` enum('instructor','class','discipline','product') COLLATE utf8mb4_unicode_ci NOT NULL,
  `favoritable_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `priority` tinyint unsigned DEFAULT '1' COMMENT '1-5, siendo 5 la máxima prioridad',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_favorite` (`user_id`,`favoritable_type`,`favoritable_id`),
  KEY `idx_user_favorites_user` (`user_id`,`favoritable_type`),
  KEY `idx_user_favorites_priority` (`user_id`,`priority`),
  CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistema de favoritos para instructores, clases, disciplinas y productos';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_fitness_stats
CREATE TABLE IF NOT EXISTS `user_fitness_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `total_classes_completed` int unsigned DEFAULT '0',
  `current_classes_available` int unsigned DEFAULT '0',
  `wellness_sessions_count` int unsigned DEFAULT '0',
  `last_class_date` date DEFAULT NULL,
  `streak_days` int unsigned DEFAULT '0',
  `max_streak_days` int unsigned DEFAULT '0',
  `total_spent_soles` decimal(10,2) DEFAULT '0.00',
  `membership_level` enum('resistance','gold','black') COLLATE utf8mb4_unicode_ci DEFAULT 'resistance',
  `points_earned` int unsigned DEFAULT '0',
  `level_achievements` json DEFAULT NULL,
  `preferred_class_times` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_fitness_membership` (`membership_level`),
  KEY `idx_user_fitness_classes` (`total_classes_completed`),
  KEY `idx_user_fitness_streak` (`streak_days`),
  CONSTRAINT `user_fitness_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estadísticas fitness y progreso del usuario';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_packages
CREATE TABLE IF NOT EXISTS `user_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `purchase_transaction_id` bigint unsigned DEFAULT NULL,
  `classes_total` int unsigned NOT NULL,
  `classes_used` int unsigned DEFAULT '0',
  `classes_remaining` int unsigned GENERATED ALWAYS AS ((`classes_total` - `classes_used`)) STORED,
  `purchase_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `activation_date` timestamp NULL DEFAULT NULL,
  `expiry_date` timestamp NOT NULL,
  `status` enum('pending','active','expired','exhausted','suspended','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `auto_renewal_enabled` tinyint(1) DEFAULT '0',
  `purchase_price_soles` decimal(8,2) NOT NULL,
  `discount_applied` decimal(8,2) DEFAULT '0.00',
  `promocode_used` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift_from_user_id` bigint unsigned DEFAULT NULL COMMENT 'Si es un regalo',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gift_from_user_id` (`gift_from_user_id`),
  KEY `idx_user_packages_user_status` (`user_id`,`status`),
  KEY `idx_user_packages_expiry` (`expiry_date`,`status`),
  KEY `idx_user_packages_package` (`package_id`),
  KEY `idx_user_packages_active` (`user_id`,`status`,`expiry_date`),
  CONSTRAINT `user_packages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_packages_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `user_packages_ibfk_3` FOREIGN KEY (`gift_from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paquetes adquiridos por usuarios con control de consumo';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_payment_methods
CREATE TABLE IF NOT EXISTS `user_payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `payment_type` enum('credit_card','debit_card','bank_transfer','digital_wallet','crypto') COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` enum('visa','mastercard','amex','bcp','interbank','scotiabank','bbva','yape','plin','paypal') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_last_four` char(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_brand` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_expiry_month` tinyint unsigned DEFAULT NULL,
  `card_expiry_year` smallint unsigned DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number_masked` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `is_saved_for_future` tinyint(1) DEFAULT '1',
  `gateway_token` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token del gateway de pago',
  `gateway_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address` json DEFAULT NULL,
  `status` enum('active','expired','blocked','pending_verification') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `verification_status` enum('pending','verified','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_methods_user` (`user_id`,`status`),
  KEY `idx_payment_methods_default` (`user_id`,`is_default`),
  KEY `idx_payment_methods_type` (`payment_type`,`status`),
  CONSTRAINT `user_payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Métodos de pago guardados por usuario';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_preferences
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `preferred_class_times` json DEFAULT NULL COMMENT 'Horarios preferidos por día de la semana',
  `preferred_disciplines` json DEFAULT NULL COMMENT 'Array de discipline IDs preferidas',
  `preferred_instructors` json DEFAULT NULL COMMENT 'Array de instructor IDs preferidos',
  `notification_preferences` json DEFAULT NULL COMMENT 'Configuración de notificaciones',
  `dietary_restrictions` json DEFAULT NULL,
  `fitness_goals` json DEFAULT NULL,
  `music_preferences` json DEFAULT NULL,
  `equipment_preferences` json DEFAULT NULL,
  `communication_language` enum('es','en') COLLATE utf8mb4_unicode_ci DEFAULT 'es',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'America/Lima',
  `privacy_settings` json DEFAULT NULL,
  `marketing_consent` tinyint(1) DEFAULT '0',
  `data_processing_consent` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Preferencias detalladas y configuración del usuario';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.user_profiles
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `first_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('female','male','other','na') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shoe_size_eu` tinyint unsigned DEFAULT NULL COMMENT 'Talla de zapato europea (35-50)',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_conditions` text COLLATE utf8mb4_unicode_ci COMMENT 'Condiciones médicas relevantes',
  `fitness_goals` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_profiles_user` (`user_id`),
  KEY `idx_user_profiles_names` (`first_name`,`last_name`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_shoe_size` CHECK ((`shoe_size_eu` between 30 and 55))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Perfiles detallados de usuarios con información personal';

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla modelorsistanc.virtual_classes
CREATE TABLE IF NOT EXISTS `virtual_classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_schedule_id` bigint unsigned NOT NULL,
  `platform` enum('zoom','meet','teams','youtube','custom') COLLATE utf8mb4_unicode_ci DEFAULT 'zoom',
  `streaming_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_virtual_participants` tinyint unsigned DEFAULT '100',
  `current_virtual_participants` tinyint unsigned DEFAULT '0',
  `recording_enabled` tinyint(1) DEFAULT '1',
  `recording_available` tinyint(1) DEFAULT '0',
  `recording_expires_at` timestamp NULL DEFAULT NULL,
  `chat_enabled` tinyint(1) DEFAULT '1',
  `waiting_room_enabled` tinyint(1) DEFAULT '1',
  `technical_requirements` json DEFAULT NULL COMMENT 'Requisitos técnicos mínimos',
  `access_instructions` text COLLATE utf8mb4_unicode_ci,
  `is_live` tinyint(1) DEFAULT '0',
  `stream_started_at` timestamp NULL DEFAULT NULL,
  `stream_ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_virtual_classes_schedule` (`class_schedule_id`),
  KEY `idx_virtual_classes_live` (`is_live`),
  KEY `idx_virtual_classes_recording` (`recording_available`),
  CONSTRAINT `virtual_classes_ibfk_1` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración para clases virtuales RSISTANC 360';

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
