<?php
/**
 * SMTP Helper - Clase para enviar correos vía SMTP
 * Soporta: Gmail, Outlook, Hotmail, SendGrid, Mailgun, etc.
 */

class SMTPClient {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $socket;
    
    public function __construct($host, $port, $username, $password, $encryption = 'tls') {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
    }
    
    /**
     * Enviar correo
     * @param string $to Email destino
     * @param string $from Email origen
     * @param string $subject Asunto
     * @param string $body Cuerpo HTML
     * @return bool
     */
    public function send($to, $from, $subject, $body) {
        try {
            // 1. Conectar al servidor SMTP
            if (!$this->connect()) {
                error_log("SMTP: No se pudo conectar a $this->host:$this->port");
                return false;
            }
            
            // 2. Iniciar STARTTLS
            if ($this->encryption === 'tls') {
                $this->sendCommand('STARTTLS');
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            
            // 3. Autenticar
            if (!$this->authenticate()) {
                error_log("SMTP: Autenticación fallida");
                return false;
            }
            
            // 4. Enviar correo
            $this->sendCommand('MAIL FROM:<' . $from . '>');
            $this->sendCommand('RCPT TO:<' . $to . '>');
            $this->sendCommand('DATA');
            
            // Construir encabezados
            $headers = "From: Sistema ANCORA <$from>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: " . $subject . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            
            // Enviar el mensaje
            fwrite($this->socket, $headers . "\r\n" . $body . "\r\n.\r\n");
            $this->readResponse();
            
            // 5. Cerrar sesión
            $this->sendCommand('QUIT');
            fclose($this->socket);
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            if ($this->socket) {
                fclose($this->socket);
            }
            return false;
        }
    }
    
    /**
     * Conectar al servidor SMTP
     */
    private function connect() {
        $this->socket = @fsockopen(
            $this->host,
            $this->port,
            $errno,
            $errstr,
            10
        );
        
        if (!$this->socket) {
            error_log("SMTP Error: $errstr ($errno)");
            return false;
        }
        
        // Leer respuesta de bienvenida
        $this->readResponse();
        
        // Enviar EHLO
        $this->sendCommand('EHLO ' . gethostname());
        
        return true;
    }
    
    /**
     * Autenticarse con SMTP
     */
    private function authenticate() {
        // Enviar comando AUTH
        $this->sendCommand('AUTH LOGIN');
        
        // Enviar usuario codificado en base64
        fwrite($this->socket, base64_encode($this->username) . "\r\n");
        $this->readResponse();
        
        // Enviar contraseña codificada en base64
        fwrite($this->socket, base64_encode($this->password) . "\r\n");
        $response = $this->readResponse();
        
        // Verificar si fue exitosa la autenticación
        return strpos($response, '235') !== false;
    }
    
    /**
     * Enviar comando SMTP
     */
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
        return $this->readResponse();
    }
    
    /**
     * Leer respuesta del servidor
     */
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
}

/**
 * Función para enviar alertas por correo
 * Detecta automáticamente si usar SMTP o mail()
 */
function sendAlertEmail($to_emails, $subject, $message, $alert_type = 'caida') {
    if (!is_array($to_emails)) {
        $to_emails = [$to_emails];
    }
    
    if (empty($to_emails)) {
        error_log("sendAlertEmail: No recipients provided");
        return false;
    }
    
    // Crear cuerpo HTML del correo
    $icon = ($alert_type === 'caida') ? '⚠️' : '😴';
    $color = ($alert_type === 'caida') ? '#dc3545' : '#ffc107';
    $title = ($alert_type === 'caida') ? 'ALERTA CRÍTICA: CAÍDA DETECTADA' : 'ALERTA: ANOMALÍA EN SUEÑO';
    
    $html_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background-color: $color; color: white; padding: 20px; text-align: center; border-radius: 5px; }
            .body { margin: 20px 0; line-height: 1.6; }
            .footer { font-size: 12px; color: #666; text-align: center; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
            .alert-icon { font-size: 48px; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='alert-icon'>$icon</div>
                <h2>$title</h2>
            </div>
            <div class='body'>
                $message
            </div>
            <div class='footer'>
                <p>Sistema ANCORA - Monitoreo Inteligente</p>
                <p>Este es un correo automático, no responder.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Determinar método de envío
    if (defined('SMTP_ENABLED') && SMTP_ENABLED === true) {
        return sendViaSmtp($to_emails, $subject, $html_body);
    } else {
        return sendViaPhpMail($to_emails, $subject, $html_body);
    }
}

/**
 * Enviar vía SMTP
 */
function sendViaSmtp($to_emails, $subject, $html_body) {
    if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        error_log("SMTP: Configuración incompleta");
        return false;
    }
    
    $sent_count = 0;
    
    try {
        $smtp = new SMTPClient(
            SMTP_HOST,
            SMTP_PORT ?? 587,
            SMTP_USERNAME,
            SMTP_PASSWORD,
            SMTP_ENCRYPTION ?? 'tls'
        );
        
        foreach ($to_emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ($smtp->send($email, SMTP_USERNAME, $subject, $html_body)) {
                    error_log("Alert email sent via SMTP to: $email");
                    $sent_count++;
                } else {
                    error_log("Failed to send email via SMTP to: $email");
                }
            } else {
                error_log("Invalid email address: $email");
            }
        }
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
    }
    
    return $sent_count > 0;
}

/**
 * Enviar vía PHP mail()
 */
function sendViaPhpMail($to_emails, $subject, $html_body) {
    $sent_count = 0;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Sistema ANCORA <noreply@ancora.com>\r\n";
    
    foreach ($to_emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (mail($email, $subject, $html_body, $headers)) {
                error_log("Alert email sent via PHP mail to: $email");
                $sent_count++;
            } else {
                error_log("Failed to send email via PHP mail to: $email");
            }
        } else {
            error_log("Invalid email address: $email");
        }
    }
    
    return $sent_count > 0;
}

?>
