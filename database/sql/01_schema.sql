-- ============================================================
-- Esquema de base de datos: turnos_ies
-- Sistema de turnos para Auditorio, Sala de Informática y
-- Sala de Capacitación — IES Nuevo Horizonte
-- Equivalente exacto a las migraciones de Laravel del proyecto.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tablas base de Laravel (mínimas necesarias)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(50) NOT NULL DEFAULT 'docente', -- docente | administrador
  `dni` VARCHAR(50) DEFAULT NULL,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `carrera_id` BIGINT UNSIGNED DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_dni_unique` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Carreras
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carreras` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users`
  ADD CONSTRAINT `users_carrera_id_foreign`
  FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- Espacios (Auditorio / Sala de Informática / Sala de Capacitación)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `espacios` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL, -- auditorio | sala_informatica | sala_capacitacion
  `capacidad` INT UNSIGNED NOT NULL,
  `equipamiento` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Turnos (reservas)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `turnos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `espacio_id` BIGINT UNSIGNED NOT NULL,
  `docente_id` BIGINT UNSIGNED NOT NULL,
  `carrera_id` BIGINT UNSIGNED DEFAULT NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `motivo` VARCHAR(255) NOT NULL,
  `cantidad_asistentes_aproximada` INT UNSIGNED NOT NULL,
  `curso` VARCHAR(100) DEFAULT NULL, -- obligatorio solo para sala_informatica
  `estado` VARCHAR(50) NOT NULL DEFAULT 'pendiente', -- pendiente|aprobado|rechazado|cancelado
  `observaciones` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  KEY `turnos_espacio_id_fecha_index` (`espacio_id`, `fecha`),
  KEY `turnos_estado_index` (`estado`),
  CONSTRAINT `turnos_espacio_id_foreign` FOREIGN KEY (`espacio_id`) REFERENCES `espacios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `turnos_docente_id_foreign` FOREIGN KEY (`docente_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `turnos_carrera_id_foreign` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
