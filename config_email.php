<?php
/**
 * Configuración de Email para el Sistema de Capacitaciones
 *
 * IMPORTANTE: Configurar estos valores según el servidor SMTP disponible
 */

// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com');          // Servidor SMTP (ej: smtp.gmail.com, smtp.office365.com)
define('SMTP_PORT', 587);                       // Puerto SMTP (587 para TLS, 465 para SSL)
define('SMTP_SECURE', 'tls');                   // Tipo de encriptación (tls o ssl)
define('SMTP_USERNAME', 'tu-correo@ejemplo.com'); // Usuario SMTP
define('SMTP_PASSWORD', 'tu-password-aqui');    // Contraseña SMTP o App Password
define('SMTP_FROM_EMAIL', 'capacitaciones@ejemplo.com'); // Email remitente
define('SMTP_FROM_NAME', 'Sistema de Capacitaciones KWDAF'); // Nombre remitente

// Configuración de notificaciones
define('EMAIL_ENABLED', true);                  // Activar/Desactivar envío de emails
define('EMAIL_DEBUG', false);                   // Modo debug (true para ver logs)
define('EMAIL_REMINDER_HOURS', 24);            // Horas antes para recordatorio (24 = 1 día)

// Configuración de administradores (reciben copias de notificaciones importantes)
define('ADMIN_EMAILS', [
    'admin@ejemplo.com',
    'rh@ejemplo.com'
]);

// Plantillas de email
define('EMAIL_LOGO_URL', 'https://tu-servidor.com/kwdaf.png'); // URL del logo
define('EMAIL_FOOTER_TEXT', 'Este es un correo automático del Sistema de Capacitaciones. Por favor no responder.');
?>
