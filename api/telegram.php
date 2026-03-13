<?php
/**
 * TELEGRAM NOTIFICATIONS API
 * Sistema de notificaciones para ANCORA mediante Telegram Bot
 */

// ⭐ CONFIGURA TU TOKEN DE TELEGRAM AQUÍ
define('TELEGRAM_BOT_TOKEN', '8477297927:AAGSoVVqjz5DUWj31eek6LzyprKphSydjPg'); // Reemplaza con tu token del BotFather

/**
 * Enviar mensaje de alerta por Telegram
 * 
 * @param string $chatId - ID del chat de Telegram del contacto
 * @param string $mensaje - Mensaje a enviar
 * @param string $parseMode - Formato del mensaje (HTML o Markdown)
 * @return array - Respuesta de la API de Telegram
 */
function enviarAlertaTelegram($chatId, $mensaje, $parseMode = 'HTML') {
    $botToken = TELEGRAM_BOT_TOKEN;
    
    // Validar que el token esté configurado
    if ($botToken === 'TU_TOKEN_AQUI' || empty($botToken)) {
        error_log('❌ ERROR: Token de Telegram no configurado en telegram.php');
        return [
            'success' => false,
            'error' => 'Token de Telegram no configurado'
        ];
    }
    
    // Validar chat_id
    if (empty($chatId)) {
        error_log('❌ ERROR: chat_id vacío');
        return [
            'success' => false,
            'error' => 'chat_id requerido'
        ];
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => true
    ];
    
    // Usar cURL para enviar el mensaje
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("❌ cURL Error al enviar Telegram: $curlError");
        return [
            'success' => false,
            'error' => $curlError
        ];
    }
    
    $response = json_decode($result, true);
    
    if ($httpCode === 200 && isset($response['ok']) && $response['ok'] === true) {
        error_log("✅ Mensaje Telegram enviado a chat_id: $chatId");
        return [
            'success' => true,
            'message_id' => $response['result']['message_id'] ?? null,
            'response' => $response
        ];
    } else {
        $errorMsg = $response['description'] ?? 'Error desconocido';
        error_log("❌ Error Telegram API (HTTP $httpCode): $errorMsg");
        return [
            'success' => false,
            'error' => $errorMsg,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
}

/**
 * Enviar alerta de CAÍDA por Telegram
 * 
 * @param array $device - Información del dispositivo
 * @param array $caida - Datos de la caída detectada
 * @param string $chatId - Chat ID del contacto
 * @param string $nombreContacto - Nombre del contacto
 * @return array - Resultado del envío
 */
function enviarAlertaCaidaTelegram($device, $caida, $chatId, $nombreContacto = '') {
    $ubicacion = $device['ubicacion_lugar'] ?? 'Ubicación desconocida';
    $nombreDispositivo = $device['nombre_identificador'] ?? 'Dispositivo';
    $fechaHora = $caida['fecha_hora'] ?? date('Y-m-d H:i:s');
    $estadoCaida = $caida['estado_caida'] ?? 'Detectada';
    $parametro = $caida['parametro_movimiento'] ?? 'N/A';
    
    $mensaje = "🚨 <b>ALERTA DE CAÍDA DETECTADA</b> 🚨\n\n";
    $mensaje .= "📍 <b>Ubicación:</b> $ubicacion\n";
    $mensaje .= "📱 <b>Dispositivo:</b> $nombreDispositivo\n";
    $mensaje .= "⏰ <b>Fecha/Hora:</b> $fechaHora\n";
    $mensaje .= "⚠️ <b>Estado:</b> $estadoCaida\n";
    $mensaje .= "📊 <b>Parámetro:</b> $parametro\n\n";
    $mensaje .= "🔔 Por favor, verifica el estado de la persona inmediatamente.\n\n";
    $mensaje .= "Sistema de monitoreo ANCORA";
    
    return enviarAlertaTelegram($chatId, $mensaje);
}

/**
 * Enviar alerta de SUEÑO por Telegram
 * 
 * @param array $device - Información del dispositivo
 * @param array $sueno - Datos del sueño anormales
 * @param string $chatId - Chat ID del contacto
 * @param string $nombreContacto - Nombre del contacto
 * @return array - Resultado del envío
 */
function enviarAlertaSuenoTelegram($device, $sueno, $chatId, $nombreContacto = '') {
    $ubicacion = $device['ubicacion_lugar'] ?? 'Ubicación desconocida';
    $nombreDispositivo = $device['nombre_identificador'] ?? 'Dispositivo';
    $fechaHora = $sueno['fecha_hora'] ?? date('Y-m-d H:i:s');
    $calidadSueno = $sueno['calidad_sueno_puntaje'] ?? 'N/A';
    $eventosApnea = $sueno['eventos_apnea'] ?? 0;
    $frecResp = $sueno['frecuencia_respiratoria_promedio'] ?? 'N/A';
    
    $mensaje = "😴 <b>ALERTA DE PATRÓN DE SUEÑO ANORMAL</b> 😴\n\n";
    $mensaje .= "📍 <b>Ubicación:</b> $ubicacion\n";
    $mensaje .= "📱 <b>Dispositivo:</b> $nombreDispositivo\n";
    $mensaje .= "⏰ <b>Fecha/Hora:</b> $fechaHora\n\n";
    
    if ($eventosApnea > 0) {
        $mensaje .= "⚠️ <b>Eventos de apnea:</b> $eventosApnea\n";
    }
    if ($calidadSueno !== 'N/A' && $calidadSueno < 40) {
        $mensaje .= "📉 <b>Calidad de sueño:</b> $calidadSueno/100 (Baja)\n";
    }
    if ($frecResp !== 'N/A' && ($frecResp < 10 || $frecResp > 30)) {
        $mensaje .= "🫁 <b>Frecuencia respiratoria:</b> $frecResp rpm (Anormal)\n";
    }
    
    $mensaje .= "\n🔔 Se recomienda verificar el estado de la persona.\n\n";
    $mensaje .= "Sistema de monitoreo ANCORA";
    
    return enviarAlertaTelegram($chatId, $mensaje);
}

/**
 * Obtener información del bot de Telegram
 * Útil para verificar que el token es válido
 */
function obtenerInfoBot() {
    $botToken = TELEGRAM_BOT_TOKEN;
    
    if ($botToken === 'TU_TOKEN_AQUI' || empty($botToken)) {
        return [
            'success' => false,
            'error' => 'Token no configurado'
        ];
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/getMe";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

/**
 * Obtener el chat_id de un usuario que ha iniciado conversación con el bot
 * (Requiere que el usuario haya enviado /start al bot primero)
 */
function obtenerActualizacionesBot($limit = 10) {
    $botToken = TELEGRAM_BOT_TOKEN;
    
    if ($botToken === 'TU_TOKEN_AQUI' || empty($botToken)) {
        return [
            'success' => false,
            'error' => 'Token no configurado'
        ];
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/getUpdates?limit=$limit";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}
?>
