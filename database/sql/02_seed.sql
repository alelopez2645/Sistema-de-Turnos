-- ============================================================
-- Datos de prueba para turnos_ies
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `carreras` (`nombre`, `created_at`, `updated_at`) VALUES
  ('Profesorado de Educación Primaria', NOW(), NOW()),
  ('Profesorado de Educación Inicial', NOW(), NOW()),
  ('Profesorado de Inglés', NOW(), NOW()),
  ('Tecnicatura Superior en Administración', NOW(), NOW());

INSERT INTO `espacios` (`nombre`, `tipo`, `capacidad`, `equipamiento`, `created_at`, `updated_at`) VALUES
  ('Auditorio', 'auditorio', 250, 'Pantalla gigante, conexión a internet, audio profesional', NOW(), NOW()),
  ('Sala de Informática', 'sala_informatica', 32, '32 PCs de escritorio', NOW(), NOW()),
  ('Sala de Capacitación', 'sala_capacitacion', 40, 'A confirmar', NOW(), NOW());

-- Contraseña: Admin123!
INSERT INTO `users` (`name`, `email`, `password`, `rol`, `dni`, `telefono`, `carrera_id`, `created_at`, `updated_at`) VALUES
  ('Administración IES', 'admin@iesnuevohorizonte.com', '$2y$10$eLklABGaXF8Fb2.4MVGs4O2xC1Ayc9RFSjIC3Ox47QJ7OXRnykFeG', 'administrador', '00000000', NULL, NULL, NOW(), NOW());

-- Contraseña (ambos docentes): Docente123!
INSERT INTO `users` (`name`, `email`, `password`, `rol`, `dni`, `telefono`, `carrera_id`, `created_at`, `updated_at`) VALUES
  ('Marcela Sosa', 'marcela.sosa@iesnuevohorizonte.com', '$2y$10$XaNdl.hcD93TJ.q39OJz4.Mk/LtYH3lsie.pEasDvjhY1mV3Dim3a', 'docente', '30111222', '3884000001', 1, NOW(), NOW()),
  ('Julián Torres', 'julian.torres@iesnuevohorizonte.com', '$2y$10$XaNdl.hcD93TJ.q39OJz4.Mk/LtYH3lsie.pEasDvjhY1mV3Dim3a', 'docente', '30333444', '3884000002', 3, NOW(), NOW());

-- Un par de turnos de ejemplo en distintos estados
INSERT INTO `turnos` (`espacio_id`, `docente_id`, `carrera_id`, `fecha`, `hora_inicio`, `hora_fin`, `motivo`, `cantidad_asistentes_aproximada`, `curso`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES
  (1, 2, 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', '16:00:00', 'Jornada de capacitación docente', 120, NULL, 'pendiente', NULL, NOW(), NOW()),
  (2, 3, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '11:00:00', 'Práctica de laboratorio', 25, '2do año - Inglés Técnico', 'aprobado', 'Confirmado, coordinar con mantenimiento.', NOW(), NOW());
