<?php
/**
 * Servicio de Notificaciones por Email
 * Sistema de Capacitaciones KWDAF
 *
 * Este servicio maneja el envío de notificaciones por correo electrónico
 * utilizando PHPMailer y registra todos los envíos en la base de datos.
 */

// Incluir PHPMailer
require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/lib/phpmailer/SMTP.php';
require_once __DIR__ . '/lib/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $conn;
    private $mailer;
    private $enabled;
    private $debug;

    /**
     * Constructor
     * @param resource $dbConnection Conexión a la base de datos SQL Server
     */
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;

        // Cargar configuración
        require_once __DIR__ . '/config_email.php';

        $this->enabled = EMAIL_ENABLED;
        $this->debug = EMAIL_DEBUG;

        // Configurar PHPMailer
        $this->mailer = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_SECURE;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';

            // Configurar remitente
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

            // Debug
            if ($this->debug) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }

        } catch (Exception $e) {
            error_log("Error configurando EmailService: " . $e->getMessage());
        }
    }

    /**
     * Envía email de asignación a capacitación
     *
     * @param array $datos Datos de la capacitación y empleado
     * @return bool True si se envió correctamente
     */
    public function enviarAsignacionCapacitacion($datos) {
        if (!$this->enabled) {
            return false;
        }

        $asunto = "Asignación a Capacitación: " . $datos['NomCurso'];
        $cuerpo = $this->getPlantillaAsignacion($datos);

        return $this->enviarEmail(
            $datos['correo'],
            $asunto,
            $cuerpo,
            'asignacion',
            $datos['IdCapacitacion'] ?? null,
            $datos['IdEmp'] ?? null
        );
    }

    /**
     * Envía email de recordatorio de capacitación
     *
     * @param array $datos Datos de la capacitación y empleado
     * @return bool True si se envió correctamente
     */
    public function enviarRecordatorioCapacitacion($datos) {
        if (!$this->enabled) {
            return false;
        }

        $asunto = "Recordatorio: Capacitación Mañana - " . $datos['NomCurso'];
        $cuerpo = $this->getPlantillaRecordatorio($datos);

        return $this->enviarEmail(
            $datos['correo'],
            $asunto,
            $cuerpo,
            'recordatorio',
            $datos['IdCapacitacion'] ?? null,
            $datos['IdEmp'] ?? null
        );
    }

    /**
     * Envía email de confirmación de asistencia
     *
     * @param array $datos Datos de la capacitación y empleado
     * @return bool True si se envió correctamente
     */
    public function enviarConfirmacionAsistencia($datos) {
        if (!$this->enabled) {
            return false;
        }

        $asunto = "Asistencia Registrada: " . $datos['NomCurso'];
        $cuerpo = $this->getPlantillaAsistencia($datos);

        return $this->enviarEmail(
            $datos['correo'],
            $asunto,
            $cuerpo,
            'asistencia',
            $datos['IdCapacitacion'] ?? null,
            $datos['IdEmp'] ?? null
        );
    }

    /**
     * Envía email de certificado disponible
     *
     * @param array $datos Datos de la capacitación, empleado y certificado
     * @return bool True si se envió correctamente
     */
    public function enviarCertificadoDisponible($datos) {
        if (!$this->enabled) {
            return false;
        }

        $asunto = "Certificado Disponible: " . $datos['NomCurso'];
        $cuerpo = $this->getPlantillaCertificado($datos);

        return $this->enviarEmail(
            $datos['correo'],
            $asunto,
            $cuerpo,
            'certificado',
            $datos['IdCapacitacion'] ?? null,
            $datos['IdEmp'] ?? null
        );
    }

    /**
     * Método principal para enviar emails
     *
     * @param string $destinatario Email del destinatario
     * @param string $asunto Asunto del email
     * @param string $cuerpoHTML Cuerpo del email en HTML
     * @param string $tipo Tipo de notificación
     * @param int|null $idCapacitacion ID de la capacitación
     * @param string|null $idEmpleado ID del empleado
     * @return bool True si se envió correctamente
     */
    private function enviarEmail($destinatario, $asunto, $cuerpoHTML, $tipo, $idCapacitacion = null, $idEmpleado = null) {
        try {
            // Limpiar destinatarios previos
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Configurar destinatario
            $this->mailer->addAddress($destinatario);

            // Configurar contenido
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $asunto;
            $this->mailer->Body = $cuerpoHTML;
            $this->mailer->AltBody = strip_tags($cuerpoHTML);

            // Enviar
            $resultado = $this->mailer->send();

            // Registrar en la base de datos
            $this->registrarLog(
                $idCapacitacion,
                $idEmpleado,
                $destinatario,
                $tipo,
                $asunto,
                'enviado',
                null
            );

            return true;

        } catch (Exception $e) {
            $mensajeError = $this->mailer->ErrorInfo;
            error_log("Error enviando email: " . $mensajeError);

            // Registrar error en la base de datos
            $this->registrarLog(
                $idCapacitacion,
                $idEmpleado,
                $destinatario,
                $tipo,
                $asunto,
                'error',
                $mensajeError
            );

            return false;
        }
    }

    /**
     * Registra el envío de email en la base de datos
     */
    private function registrarLog($idCapacitacion, $idEmpleado, $emailDestino, $tipo, $asunto, $estado, $mensajeError) {
        $sql = "INSERT INTO email_logs (IdCapacitacion, IdEmpleado, EmailDestino, TipoNotificacion, Asunto, Estado, MensajeError)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            $idCapacitacion,
            $idEmpleado,
            $emailDestino,
            $tipo,
            $asunto,
            $estado,
            $mensajeError
        );

        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            error_log("Error registrando log de email: " . print_r(sqlsrv_errors(), true));
        }
    }

    /**
     * Obtiene capacitaciones que necesitan recordatorio
     * (Capacitaciones que inician en las próximas 24 horas y no han recibido recordatorio)
     *
     * @return array Lista de capacitaciones pendientes de recordatorio
     */
    public function obtenerCapacitacionesParaRecordatorio() {
        $horasRecordatorio = EMAIL_REMINDER_HOURS;

        $sql = "SELECT DISTINCT
                    c.Id as IdCapacitacion,
                    c.IdEmp,
                    c.Empleado,
                    c.NomCurso,
                    c.FechaIni,
                    c.FechaFin,
                    c.Area,
                    u.correo
                FROM capacitaciones c
                INNER JOIN usuarios u ON c.IdEmp = u.Clave
                WHERE c.FechaIni >= DATEADD(HOUR, ?, GETDATE())
                    AND c.FechaIni <= DATEADD(HOUR, ?, GETDATE())
                    AND u.correo IS NOT NULL
                    AND u.correo <> ''
                    AND NOT EXISTS (
                        SELECT 1 FROM email_logs el
                        WHERE el.IdCapacitacion = c.Id
                            AND el.TipoNotificacion = 'recordatorio'
                            AND el.Estado = 'enviado'
                    )";

        $params = array($horasRecordatorio - 1, $horasRecordatorio + 1);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        $capacitaciones = array();
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $capacitaciones[] = $row;
            }
        }

        return $capacitaciones;
    }

    // ============================================
    // PLANTILLAS DE EMAIL
    // ============================================

    /**
     * Plantilla HTML para asignación a capacitación
     */
    private function getPlantillaAsignacion($datos) {
        $empleado = htmlspecialchars($datos['Empleado'] ?? 'Empleado');
        $nombreCurso = htmlspecialchars($datos['NomCurso'] ?? 'Capacitación');
        $area = htmlspecialchars($datos['Area'] ?? '');
        $fechaIni = isset($datos['FechaIni']) ? $datos['FechaIni']->format('d/m/Y') : '';
        $fechaFin = isset($datos['FechaFin']) ? $datos['FechaFin']->format('d/m/Y') : '';

        return $this->getPlantillaBase("
            <h2 style='color: #2c3e50;'>Has sido asignado a una capacitación</h2>
            <p>Estimado/a <strong>{$empleado}</strong>,</p>
            <p>Te informamos que has sido asignado/a a la siguiente capacitación:</p>

            <div style='background-color: #f8f9fa; padding: 20px; border-left: 4px solid #007bff; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #007bff;'>{$nombreCurso}</h3>
                <p><strong>Área:</strong> {$area}</p>
                <p><strong>Fecha de inicio:</strong> {$fechaIni}</p>
                <p><strong>Fecha de finalización:</strong> {$fechaFin}</p>
            </div>

            <p>Por favor, asegúrate de asistir puntualmente y llevar el material necesario.</p>
            <p>Recibirás un recordatorio un día antes del inicio de la capacitación.</p>
        ");
    }

    /**
     * Plantilla HTML para recordatorio de capacitación
     */
    private function getPlantillaRecordatorio($datos) {
        $empleado = htmlspecialchars($datos['Empleado'] ?? 'Empleado');
        $nombreCurso = htmlspecialchars($datos['NomCurso'] ?? 'Capacitación');
        $area = htmlspecialchars($datos['Area'] ?? '');
        $fechaIni = isset($datos['FechaIni']) ? $datos['FechaIni']->format('d/m/Y H:i') : '';

        return $this->getPlantillaBase("
            <h2 style='color: #ff6b6b;'>⏰ Recordatorio: Capacitación Mañana</h2>
            <p>Estimado/a <strong>{$empleado}</strong>,</p>
            <p>Te recordamos que <strong>mañana</strong> tienes programada la siguiente capacitación:</p>

            <div style='background-color: #fff3cd; padding: 20px; border-left: 4px solid #ff6b6b; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #ff6b6b;'>{$nombreCurso}</h3>
                <p><strong>Área:</strong> {$area}</p>
                <p><strong>Fecha y hora:</strong> {$fechaIni}</p>
            </div>

            <p><strong>Importante:</strong></p>
            <ul>
                <li>Llega 10 minutos antes del inicio</li>
                <li>Lleva tu identificación</li>
                <li>Prepara el material necesario</li>
            </ul>

            <p>¡Te esperamos!</p>
        ");
    }

    /**
     * Plantilla HTML para confirmación de asistencia
     */
    private function getPlantillaAsistencia($datos) {
        $empleado = htmlspecialchars($datos['Empleado'] ?? 'Empleado');
        $nombreCurso = htmlspecialchars($datos['NomCurso'] ?? 'Capacitación');
        $fechaFin = isset($datos['FechaFin']) ? $datos['FechaFin']->format('d/m/Y') : '';

        return $this->getPlantillaBase("
            <h2 style='color: #28a745;'>✓ Asistencia Registrada</h2>
            <p>Estimado/a <strong>{$empleado}</strong>,</p>
            <p>Confirmamos que tu asistencia ha sido registrada exitosamente en la capacitación:</p>

            <div style='background-color: #d4edda; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #28a745;'>{$nombreCurso}</h3>
                <p><strong>Fecha de finalización:</strong> {$fechaFin}</p>
            </div>

            <p>Tu certificado estará disponible próximamente. Recibirás una notificación cuando esté listo.</p>
            <p>¡Felicidades por completar la capacitación!</p>
        ");
    }

    /**
     * Plantilla HTML para certificado disponible
     */
    private function getPlantillaCertificado($datos) {
        $empleado = htmlspecialchars($datos['Empleado'] ?? 'Empleado');
        $nombreCurso = htmlspecialchars($datos['NomCurso'] ?? 'Capacitación');
        $rutaCertificado = htmlspecialchars($datos['ruta_archivo'] ?? '');

        return $this->getPlantillaBase("
            <h2 style='color: #6f42c1;'>🎓 Tu Certificado está Disponible</h2>
            <p>Estimado/a <strong>{$empleado}</strong>,</p>
            <p>¡Felicidades! Tu certificado de la capacitación ya está disponible:</p>

            <div style='background-color: #e7e3fc; padding: 20px; border-left: 4px solid #6f42c1; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #6f42c1;'>{$nombreCurso}</h3>
            </div>

            <p>Puedes consultar tu certificado accediendo al sistema de capacitaciones.</p>
            <p>Este certificado es válido y puede ser utilizado para tu desarrollo profesional.</p>

            <p><strong>¡Enhorabuena por tu logro!</strong></p>
        ");
    }

    /**
     * Plantilla base HTML para todos los emails
     */
    private function getPlantillaBase($contenido) {
        $logoUrl = EMAIL_LOGO_URL ?? '';
        $footer = EMAIL_FOOTER_TEXT ?? '';
        $year = date('Y');

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Sistema de Capacitaciones</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                            <!-- Header -->
                            <tr>
                                <td style='background-color: #007bff; padding: 30px; text-align: center;'>
                                    <h1 style='margin: 0; color: #ffffff; font-size: 24px;'>Sistema de Capacitaciones KWDAF</h1>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    {$contenido}
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #dee2e6;'>
                                    <p style='margin: 0; font-size: 12px; color: #6c757d;'>{$footer}</p>
                                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #6c757d;'>
                                        &copy; {$year} KWDAF. Todos los derechos reservados.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}
?>
