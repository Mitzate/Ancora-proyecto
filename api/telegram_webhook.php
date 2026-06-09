<?php
/**
 * TELEGRAM WEBHOOK HANDLER
 * Recibe automáticamente mensajes del bot y guarda chat_ids
 */
require_once __DIR__ . '/telegram.php';

// Configuración de base de datos (debe coincidir con coneccion.php)
$host = 'localhost';
$dbname = 'u574321597_ancora';
$user = 'u574321597_proyectoA';
$pass = 'Megustanlasg0mitas';
$charset = 'utf8mb4';

// Logging para debug
error_log("=== TELEGRAM WEBHOOK RECIBIDO ===");

// Validar que el token esté configurado
if (TELEGRAM_BOT_TOKEN === 'TU_TOKEN_AQUI' || empty(TELEGRAM_BOT_TOKEN)) {
    http_response_code(500);
    error_log('❌ Token de Telegram no configurado');
    exit('Token not configured');
}

// Leer el cuerpo de la petición
$content = file_get_contents('php://input');
error_log("Webhook content: " . $content);

$update = json_decode($content, true);

if (!$update) {
    http_response_code(400);
    error_log('❌ JSON inválido recibido');
    exit('Invalid JSON');
}

// Conectar a la base de datos
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log('❌ Error de conexión DB: ' . $e->getMessage());
    http_response_code(500);
    exit('Database error');
}

// Extraer información del mensaje
$message = $update['message'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = $message['text'] ?? '';
$from_user = $message['from'] ?? [];
$username = $from_user['username'] ?? null;
$first_name = $from_user['first_name'] ?? 'Usuario';
$last_name = $from_user['last_name'] ?? '';

error_log("Chat ID: $chat_id");
error_log("Username: $username");
error_log("Mensaje: $text");

// Si no hay mensaje o chat_id, salir
if (!$message || !$chat_id) {
    error_log('⚠️ No hay mensaje o chat_id');
    http_response_code(200);
    exit('OK');
}

// Procesar comando /start
if (trim(strtolower($text)) === '/start') {
    error_log("✅ Comando /start detectado de chat_id: $chat_id");
    
    try {
        // Verificar si ya existe este chat_id
        $stmt = $pdo->prepare("SELECT id, telegram_username FROM t_telegram_usuarios WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Ya existe - actualizar información
            $stmt = $pdo->prepare("
                UPDATE t_telegram_usuarios 
                SET telegram_username = ?, 
                    first_name = ?, 
                    last_name = ?,
                    last_interaction = NOW()
                WHERE chat_id = ?
            ");
            $stmt->execute([$username, $first_name, $last_name, $chat_id]);
            
            error_log("✅ Chat ID actualizado: $chat_id");
            
            // Mensaje de bienvenida (usuario que ya envió /start antes)
            $respuesta = "¡Hola de nuevo, $first_name! 👋\n\n";
            $respuesta .= "Tu cuenta de Telegram ya está registrada en el sistema ANCORA.\n\n";
            $respuesta .= "📱 Chat ID: <code>$chat_id</code>\n";
            $respuesta .= "👤 Usuario: @" . ($username ?: 'No definido') . "\n\n";
            $respuesta .= "Recibirás alertas de emergencia aquí cuando sean necesarias.";
            
        } else {
            // Nuevo usuario - insertar
            $stmt = $pdo->prepare("
                INSERT INTO t_telegram_usuarios (chat_id, telegram_username, first_name, last_name)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$chat_id, $username, $first_name, $last_name]);
            
            error_log("✅ Nuevo chat ID registrado: $chat_id");
            
            // Mensaje de bienvenida (nuevo usuario)
            $respuesta = "¡Bienvenido a ANCORA! 🏥\n\n";
            $respuesta .= "Hola, $first_name. Tu cuenta de Telegram ha sido registrada exitosamente.\n\n";
            $respuesta .= "📱 Tu Chat ID: <code>$chat_id</code>\n";
            $respuesta .= "👤 Usuario: @" . ($username ?: 'No definido') . "\n\n";
            $respuesta .= "<b>¿Cómo funciona?</b>\n";
            $respuesta .= "• Cuando alguien te agregue como contacto de emergencia usando tu correo electrónico, el sistema vinculará automáticamente este chat.\n";
            $respuesta .= "• Recibirás alertas instantáneas de caídas y problemas de sueño.\n";
            $respuesta .= "• No necesitas hacer nada más, ¡ya estás listo!\n\n";
            $respuesta .= "Mantén este chat activo para recibir notificaciones importantes.";
        }

        // Auto-link: si este username está en t_contactos, guardar el chat_id ahí también
        if ($username) {
            $upd = $pdo->prepare("UPDATE t_contactos SET telegram_chat_id = ? WHERE correo = ?");
            $upd->execute([$chat_id, $username]);
            $linked = $upd->rowCount();
            if ($linked > 0) {
                error_log("✅ Auto-vinculado chat_id $chat_id a $linked contacto(s) con username: @$username");
                $respuesta .= "\n\n✅ Tu cuenta ha sido vinculada como contacto de emergencia en ANCORA.";
            }
        }

        // Enviar mensaje de confirmación al usuario
        enviarAlertaTelegram($chat_id, $respuesta, 'HTML');
        
    } catch (PDOException $e) {
        error_log('❌ Error al guardar chat_id: ' . $e->getMessage());
    }
}

// Si es cualquier otro mensaje, responder con instrucciones
elseif (!empty($text)) {
    $respuesta = "Este bot solo procesa el comando /start\n\n";
    $respuesta .= "Para registrarte en el sistema ANCORA, envía:\n";
    $respuesta .= "/start";
    
    enviarAlertaTelegram($chat_id, $respuesta);
}

// Responder 200 OK a Telegram (importante para que no reintente)
http_response_code(200);
echo 'OK';
?>
