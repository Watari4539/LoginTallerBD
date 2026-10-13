CREATE DATABASE IF NOT EXISTS login_taller
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE login_taller;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(120) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('M', 'F', 'O') NOT NULL,
    estado_civil ENUM('soltero', 'casado', 'divorciado') NOT NULL,
    email VARCHAR(254) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'empleado') NOT NULL DEFAULT 'empleado',
    fecha_contratacion DATE NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    curp_cifrada VARBINARY(128) NOT NULL,
    curp_iv BINARY(16) NOT NULL,
    cuenta_bancaria_cifrada VARBINARY(128) NOT NULL,
    cuenta_bancaria_iv BINARY(16) NOT NULL,
    salario_cifrado VARBINARY(128) NOT NULL,
    salario_iv BINARY(16) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_nombre_usuario (nombre_usuario)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_0900_ai_ci;
