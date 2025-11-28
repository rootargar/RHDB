-- =====================================================
-- Script de Actualización de Base de Datos
-- Sistema de Capacitaciones KWDAF
-- =====================================================

USE RHDB;
GO

-- =====================================================
-- 1. Verificar/Crear campos en tabla usuarios
-- =====================================================

-- Verificar si existe el campo 'rol' en usuarios
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('usuarios') AND name = 'rol')
BEGIN
    ALTER TABLE usuarios ADD rol NVARCHAR(50) NULL;
    PRINT 'Campo rol agregado a usuarios';
END
ELSE
BEGIN
    PRINT 'Campo rol ya existe en usuarios';
END
GO

-- Verificar si existe el campo 'correo' en usuarios
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('usuarios') AND name = 'correo')
BEGIN
    ALTER TABLE usuarios ADD correo NVARCHAR(100) NULL;
    PRINT 'Campo correo agregado a usuarios';
END
ELSE
BEGIN
    PRINT 'Campo correo ya existe en usuarios';
END
GO

-- Actualizar usuarios sin rol a 'Empleado' por defecto
UPDATE usuarios SET rol = 'Empleado' WHERE rol IS NULL OR rol = '';
GO

-- =====================================================
-- 2. Crear tabla de logs de email
-- =====================================================

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'email_logs')
BEGIN
    CREATE TABLE email_logs (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        IdCapacitacion INT NULL,
        IdEmpleado INT NULL,
        EmailDestino NVARCHAR(100) NOT NULL,
        TipoNotificacion NVARCHAR(50) NOT NULL,
        Asunto NVARCHAR(255) NOT NULL,
        Estado NVARCHAR(20) NOT NULL,
        MensajeError NVARCHAR(MAX) NULL,
        FechaEnvio DATETIME DEFAULT GETDATE(),
        CONSTRAINT FK_email_logs_capacitacion FOREIGN KEY (IdCapacitacion) REFERENCES capacitaciones(Id),
        CONSTRAINT FK_email_logs_empleado FOREIGN KEY (IdEmpleado) REFERENCES usuarios(Clave)
    );
    PRINT 'Tabla email_logs creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla email_logs ya existe';
END
GO

-- Crear índices para mejorar rendimiento
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_email_logs_fecha' AND object_id = OBJECT_ID('email_logs'))
BEGIN
    CREATE INDEX IX_email_logs_fecha ON email_logs(FechaEnvio);
    PRINT 'Índice IX_email_logs_fecha creado';
END
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_email_logs_tipo' AND object_id = OBJECT_ID('email_logs'))
BEGIN
    CREATE INDEX IX_email_logs_tipo ON email_logs(TipoNotificacion);
    PRINT 'Índice IX_email_logs_tipo creado';
END
GO

-- =====================================================
-- 3. Crear tabla de auditoría de accesos (opcional)
-- =====================================================

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'auditoria_accesos')
BEGIN
    CREATE TABLE auditoria_accesos (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Usuario NVARCHAR(50) NOT NULL,
        Rol NVARCHAR(50) NOT NULL,
        Modulo NVARCHAR(50) NOT NULL,
        Accion NVARCHAR(50) NOT NULL,
        Resultado NVARCHAR(20) NOT NULL,
        DireccionIP NVARCHAR(50) NULL,
        FechaHora DATETIME DEFAULT GETDATE()
    );
    PRINT 'Tabla auditoria_accesos creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla auditoria_accesos ya existe';
END
GO

-- Crear índice por fecha
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_auditoria_fecha' AND object_id = OBJECT_ID('auditoria_accesos'))
BEGIN
    CREATE INDEX IX_auditoria_fecha ON auditoria_accesos(FechaHora);
    PRINT 'Índice IX_auditoria_fecha creado';
END
GO

-- =====================================================
-- 4. Verificar estructura de tabla usuarios
-- =====================================================

-- Script de verificación (solo muestra información)
PRINT '=== Estructura de tabla usuarios ===';
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'usuarios'
ORDER BY ORDINAL_POSITION;
GO

-- =====================================================
-- 5. Datos iniciales para roles (si no existen)
-- =====================================================

-- Asegurarse de que exista al menos un usuario administrador
IF NOT EXISTS (SELECT 1 FROM usuarios WHERE rol = 'Administrador')
BEGIN
    PRINT 'ADVERTENCIA: No hay usuarios con rol Administrador.';
    PRINT 'Asegúrese de crear al menos un usuario administrador.';
END
GO

-- =====================================================
-- Resumen de cambios
-- =====================================================

PRINT '';
PRINT '=== RESUMEN DE CAMBIOS ===';
PRINT 'Script ejecutado exitosamente.';
PRINT '';
PRINT 'Tablas verificadas/creadas:';
PRINT '  - usuarios (con campos rol y correo)';
PRINT '  - email_logs (para notificaciones)';
PRINT '  - auditoria_accesos (para registro de accesos)';
PRINT '';
PRINT 'IMPORTANTE:';
PRINT '  1. Asegúrese de tener al menos un usuario con rol "Administrador"';
PRINT '  2. Configure config_email.php con sus credenciales SMTP';
PRINT '  3. Revise que todos los usuarios tengan un rol asignado';
PRINT '';
GO
