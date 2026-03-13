<?php
// Incluir configuración y clase SMTP
require_once 'config_alertas.php';
require_once 'smtp_helper.php';

$host = 'localhost';
$dbname = 'u782728970_monitoreo';
$user = 'u782728970_admin';
$pass = 'Proyectoanc0ra';
$charset = 'utf8mb4';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
session_start();

// Conexión a la base de datos usando PDO
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de conexión a Hostinger',
        'message' => $e->getMessage(),
        'detalles' => 'Verifica: host, dbname, user, password'
    ]);
    exit;
}

// Manejo de OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Decodificar el cuerpo una sola vez
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decoded = json_decode(file_get_contents('php://input'), true);
    $input = is_array($decoded) ? $decoded : [];

    // LOGIN
    if (isset($input['login']) && $input['login'] === true) {
        if (!isset($input['correo'], $input['contrasena'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM t_usuarios WHERE correo_electronico = ?");
        $stmt->execute([$input['correo']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario && $usuario['contrasena'] === $input['contrasena']) {
            // ⭐ Buscar dispositivo del usuario y guardar sesión activa
            $stmt = $pdo->prepare("SELECT id_dispositivo FROM t_dispositivos WHERE id_usuario = ? LIMIT 1");
            $stmt->execute([$usuario['id_usuario']]);
            $device = $stmt->fetch();
            
            if ($device) {
                // ⭐ GUARDAR SESIÓN ACTIVA
                $stmt = $pdo->prepare("
                    INSERT INTO t_sesion_activa (id_usuario, id_dispositivo) 
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE 
                        id_dispositivo = VALUES(id_dispositivo),
                        timestamp_login = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$usuario['id_usuario'], $device['id_dispositivo']]);
            }
            
            echo json_encode([
                'success' => true,
                'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno'],
                'id_usuario' => $usuario['id_usuario']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Correo o contraseña incorrectos.']);
        }
        exit;
    }

    // REGISTRO (solo si se envía flag `register`)
    if (isset($input['register']) && $input['register'] === true) {
        if (
            !isset($input['nombre'], $input['apellido_paterno'], $input['apellido_materno'], $input['correo'], $input['contrasena'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            exit;
        }

        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM t_usuarios WHERE correo_electronico = ?");
        $stmt->execute([$input['correo']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Este correo ya esta en uso.']);
            exit;
        }

        // Insertar usuario
        try {
            $stmt = $pdo->prepare("INSERT INTO t_usuarios (nombre, apellido_paterno, apellido_materno, correo_electronico, contrasena) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['nombre'],
                $input['apellido_paterno'],
                $input['apellido_materno'],
                $input['correo'],
                $input['contrasena']
            ]);
            echo json_encode(['success' => true, 'message' => 'Usuario registrado correctamente']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al registrar usuario: ' . $e->getMessage()]);
        }
        exit;
    }

    // Check device for user
    if (isset($input['check_device']) && !empty($input['id_usuario'])) {
        try {
            $stmt = $pdo->prepare("SELECT id_dispositivo, fecha_instalacion, nombre_identificador, Tipo_monitoreo AS tipo_monitoreo, ubicacion_lugar FROM t_dispositivos WHERE id_usuario = ? LIMIT 1");
            $stmt->execute([$input['id_usuario']]);
            $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dispositivo) {
                echo json_encode(['success' => true, 'hasDevice' => true, 'device' => $dispositivo]);
            } else {
                echo json_encode(['success' => true, 'hasDevice' => false]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al verificar dispositivo: ' . $e->getMessage()]);
        }
        exit;
    }

    // Registrar dispositivo para el usuario
    if (isset($input['register_device']) && !empty($input['id_usuario'])) {
        if (!isset($input['nombre_identificador'], $input['ubicacion_lugar'], $input['fecha_instalacion'], $input['tipo_monitoreo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos para registrar el dispositivo']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO t_dispositivos (id_usuario, nombre_identificador, ubicacion_lugar, fecha_instalacion, Tipo_monitoreo, monitoreo_pausado) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([
                $input['id_usuario'],
                $input['nombre_identificador'],
                $input['ubicacion_lugar'],
                $input['fecha_instalacion'],
                $input['tipo_monitoreo']
            ]);

            $newId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Dispositivo registrado correctamente',
                'id_dispositivo' => (int)$newId
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al registrar dispositivo: ' . $e->getMessage()]);
        }
        exit;
    }

    // ============================================
    // ENDPOINTS PARA ESP32
    // ============================================
    
    // 1. Registro del ESP32
    if (isset($input['action']) && $input['action'] === 'esp32_register') {
        $mac = $input['mac'] ?? '';
        
        error_log("ESP32_REGISTER: mac=$mac");
        
        if (empty($mac)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'MAC no proporcionada']);
            exit;
        }
        
        // Preferir asociar con dispositivo SIN MAC (pendiente)
        $stmt = $pdo->query("SELECT id_dispositivo, Tipo_monitoreo FROM t_dispositivos WHERE mac_address IS NULL OR mac_address = '' ORDER BY id_dispositivo DESC LIMIT 1");
        $pendingDevice = $stmt->fetch();

        if ($pendingDevice) {
            try {
                $upd = $pdo->prepare("UPDATE t_dispositivos SET mac_address = ? WHERE id_dispositivo = ?");
                $upd->execute([$mac, $pendingDevice['id_dispositivo']]);

                echo json_encode([
                    'success' => true,
                    'device_id' => (int)$pendingDevice['id_dispositivo'],
                    'current_mode' => $pendingDevice['Tipo_monitoreo'] ?? 'ninguno'
                ]);
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode([
                        'success' => false,
                        'error' => 'mac_address duplicada. Quita el índice UNIQUE de mac_address.'
                    ]);
                    exit;
                }
                throw $e;
            }
        }

        // Si no hay pendientes, devolver dispositivo con esta MAC
        $stmt = $pdo->prepare("SELECT id_dispositivo, Tipo_monitoreo FROM t_dispositivos WHERE mac_address = ? ORDER BY id_dispositivo DESC LIMIT 1");
        $stmt->execute([$mac]);
        $device = $stmt->fetch();
        
        if ($device) {
            echo json_encode([
                'success' => true,
                'device_id' => (int)$device['id_dispositivo'],
                'current_mode' => $device['Tipo_monitoreo']
            ]);
            exit;
        }

        // Fallback: usar tabla de pendientes
        $stmt = $pdo->prepare("SELECT * FROM t_dispositivos_pendientes WHERE mac_address = ?");
        $stmt->execute([$mac]);
        $pending = $stmt->fetch();
        
        if (!$pending) {
            $cod_pendiente = substr(md5($mac . time()), 0, 6);
            $stmt = $pdo->prepare("INSERT INTO t_dispositivos_pendientes (mac_address, cod_pendiente) VALUES (?, ?)");
            $stmt->execute([$mac, $cod_pendiente]);
        } else {
            $cod_pendiente = $pending['cod_pendiente'];
        }
        
        echo json_encode([
            'success' => true,
            'pending' => true,
            'cod_pendiente' => $cod_pendiente,
            'message' => 'Esperando configuración'
        ]);
        exit;
    }
    
    // Obtener datos del sensor
    if (isset($input['get_sensor_data'])) {
        $limit = $input['limit'] ?? 50;
        $id_dispositivo = $input['id_dispositivo'] ?? null;
        $since = $input['since'] ?? null;
        
        try {
            $sql = "SELECT id_caida AS id, id_dispositivo, fecha_hora, 'caidas' AS origen,
                           presencia, movimiento AS informacion_movimiento, parametro_movimiento,
                           NULL AS tasa_respiracion, NULL AS Frecuencia_cardiaca,
                           NULL AS en_cama, NULL AS estado_sueno, NULL AS calidad_sueno_puntaje
                    FROM t_sensores_caidas";
            $sql .= " UNION ALL ";
            $sql .= "SELECT id_sueno AS id, id_dispositivo, fecha_hora, 'sueno' AS origen,
                             NULL AS presencia, NULL AS informacion_movimiento, NULL AS parametro_movimiento,
                             frecuencia_respiratoria_promedio AS tasa_respiracion,
                             frecuencia_cardiaca_promedio AS Frecuencia_cardiaca,
                             en_cama, estado_sueno, calidad_sueno_puntaje
                      FROM t_sensores_sueño";
            
            $conditions = [];
            $params = [];
            if ($id_dispositivo) {
                $conditions[] = "id_dispositivo = ?";
                $params[] = $id_dispositivo;
            }
            if ($since) {
                $conditions[] = "fecha_hora > ?";
                $params[] = $since;
            }

            if ($conditions) {
                $where = " WHERE " . implode(" AND ", $conditions);
                $sql = "SELECT * FROM (" . $sql . ") AS union_data" . $where;
            } else {
                $sql = "SELECT * FROM (" . $sql . ") AS union_data";
            }

            $sql .= " ORDER BY fecha_hora DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    // Borrar un registro individual
    if (isset($input['delete_sensor_record'])) {
        $id = $input['id'] ?? null;
        $table = $input['table'] ?? null;
        
        if (!$id || !$table) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan parámetros']);
            exit;
        }
        
        try {
            if ($table === 'caidas') {
                $stmt = $pdo->prepare("DELETE FROM t_sensores_caidas WHERE id_caida = ?");
                $stmt->execute([$id]);
            } else if ($table === 'sueno') {
                $stmt = $pdo->prepare("DELETE FROM t_sensores_sueño WHERE id_sueno = ?");
                $stmt->execute([$id]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Tabla no válida']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Registro eliminado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Borrar todo el historial
    if (isset($input['delete_all_history'])) {
        $table = $input['table'] ?? null;
        
        if (!$table) {
            http_response_code(400);
            echo json_encode(['error' => 'Tabla no especificada']);
            exit;
        }
        
        try {
            if ($table === 'caidas') {
                $stmt = $pdo->prepare("DELETE FROM t_sensores_caidas");
                $stmt->execute();
            } else if ($table === 'sueno') {
                $stmt = $pdo->prepare("DELETE FROM t_sensores_sueño");
                $stmt->execute();
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Tabla no válida']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Historial completamente eliminado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar historial: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // 2. Guardar datos de caídas
    if (isset($input['action']) && $input['action'] === 'save_fall_data') {
        $device_id = $input['id_dispositivo'] ?? null;
        $presencia = $input['presencia'] ?? '';
        $movimiento = $input['movimiento'] ?? '';
        $parametro_movimiento = $input['parametro_movimiento'] ?? 0;
        $estado_caida = $input['estado_caida'] ?? '';
        $estado_inmovilidad = $input['estado_inmovilidad'] ?? '';
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO t_sensores_caidas 
                (id_dispositivo, presencia, movimiento, parametro_movimiento, 
                 estado_caida, estado_inmovilidad)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $device_id,
                $presencia,
                $movimiento,
                $parametro_movimiento,
                $estado_caida,
                $estado_inmovilidad
            ]);
            
            $id_caida = $pdo->lastInsertId();
            
            // VERIFICAR SI HAY CAÍDA DETECTADA
            $is_fall_detected = (
                strtolower($estado_caida ?? '') === 'caida' ||
                strtolower($movimiento ?? '') === 'caida' ||
                (is_numeric($parametro_movimiento) && $parametro_movimiento > 0.7)
            );
            
            if ($is_fall_detected && $device_id) {
                $stmt = $pdo->prepare("
                    SELECT d.*, u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno
                    FROM t_dispositivos d
                    JOIN t_usuarios u ON d.id_usuario = u.id_usuario
                    WHERE d.id_dispositivo = ?
                ");
                $stmt->execute([$device_id]);
                $device = $stmt->fetch();
                
                if ($device) {
                    $stmt = $pdo->prepare("SELECT id_contacto, correo FROM t_contactos WHERE id_usuario = ?");
                    $stmt->execute([$device['id_usuario']]);
                    $contacts = $stmt->fetchAll();
                    
                    if (!empty($contacts)) {
                        $contact_emails = array_map(fn($c) => $c['correo'], $contacts);
                        
                        $full_name = $device['nombre'] . ' ' . $device['apellido_paterno'] . ' ' . $device['apellido_materno'];
                        $device_location = $device['ubicacion_lugar'] ?? 'ubicación desconocida';
                        $timestamp = date('Y-m-d H:i:s');
                        
                        $email_subject = "⚠️ ALERTA CRÍTICA: CAÍDA DETECTADA - " . $full_name;
                        $email_message = "
                            <p><strong>Se ha detectado una posible CAÍDA</strong></p>
                            <hr>
                            <p><strong>Detalles de la Alerta:</strong></p>
                            <ul>
                                <li><strong>Paciente:</strong> $full_name</li>
                                <li><strong>Ubicación:</strong> $device_location</li>
                                <li><strong>Fecha/Hora:</strong> $timestamp</li>
                                <li><strong>Tipo de Evento:</strong> Caída Detectada</li>
                                <li><strong>Dispositivo:</strong> " . ($device['nombre_identificador'] ?? 'N/A') . "</li>
                            </ul>
                            <hr>
                            <p style='color: #dc3545;'><strong>⚠️ ACCIÓN RECOMENDADA:</strong> Verifique el estado del paciente inmediatamente.</p>
                            <p>Si es una falsa alarma, puede confirmar en el dashboard de ANCORA.</p>
                        ";
                        
                        sendAlertEmail($contact_emails, $email_subject, $email_message, 'caida');
                        
                        // Guardar alerta para CADA CONTACTO
                        foreach ($contacts as $contact) {
                            $stmt = $pdo->prepare("
                                INSERT INTO t_alertas_caidas 
                                (id_usuario, id_contacto, id_dispositivo, id_caida, tipo_alerta, titulo_alerta, mensaje, estado_alerta)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $device['id_usuario'],
                                $contact['id_contacto'],
                                $device_id,
                                $id_caida,
                                'caida_detectada',
                                'Caída Detectada',
                                "Caída detectada en $device_location",
                                'no_confirmada'
                            ]);
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar datos de caída: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // 3. Guardar datos de sueño
    if (isset($input['action']) && $input['action'] === 'save_sleep_data') {
        $device_id = $input['id_dispositivo'] ?? null;
        $en_cama = $input['en_cama'] ?? '';
        $estado_sueno = $input['estado_sueno'] ?? '';
        $duracion_despierto = $input['duracion_despierto'] ?? 0;
        $duracion_sueno_profundo = $input['duracion_sueno_profundo'] ?? 0;
        $calidad_sueno_puntaje = $input['calidad_sueno_puntaje'] ?? 0;
        $frecuencia_respiratoria = $input['frecuencia_respiratoria_promedio'] ?? 0;
        $frecuencia_cardiaca = $input['frecuencia_cardiaca_promedio'] ?? 0;
        $numero_vueltas = $input['numero_vueltas'] ?? 0;
        $eventos_apnea = $input['eventos_apnea'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO t_sensores_sueño 
                (id_dispositivo, en_cama, estado_sueno, duracion_despierto, 
                 duracion_sueno_profundo, calidad_sueno_puntaje, 
                 frecuencia_respiratoria_promedio, frecuencia_cardiaca_promedio,
                 numero_vueltas, movimiento_corporal_grande, movimiento_corporal_pequeno,
                 eventos_apnea, puntaje_calidad_general, porcentaje_sueno_ligero,
                 porcentaje_sueno_profundo, tiempo_fuera_cama, veces_fuera_cama)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $device_id, $en_cama, $estado_sueno, $duracion_despierto,
                $duracion_sueno_profundo, $calidad_sueno_puntaje, $frecuencia_respiratoria,
                $frecuencia_cardiaca, $numero_vueltas,
                $input['movimiento_corporal_grande'] ?? 0,
                $input['movimiento_corporal_pequeno'] ?? 0,
                $eventos_apnea,
                $input['puntaje_calidad_general'] ?? 0,
                $input['porcentaje_sueno_ligero'] ?? 0,
                $input['porcentaje_sueno_profundo'] ?? 0,
                $input['tiempo_fuera_cama'] ?? 0,
                $input['veces_fuera_cama'] ?? 0
            ]);
            
            $id_sueno = $pdo->lastInsertId();
            
            $has_apnea_alert = ($eventos_apnea > 0);
            $has_low_quality = ($calidad_sueno_puntaje < 40);
            $has_respiratory_issue = ($frecuencia_respiratoria < 10 || $frecuencia_respiratoria > 30);
            
            $should_send_alert = ($has_apnea_alert || $has_low_quality || $has_respiratory_issue) && $device_id;
            
            if ($should_send_alert) {
                $stmt = $pdo->prepare("
                    SELECT d.*, u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno
                    FROM t_dispositivos d
                    JOIN t_usuarios u ON d.id_usuario = u.id_usuario
                    WHERE d.id_dispositivo = ?
                ");
                $stmt->execute([$device_id]);
                $device = $stmt->fetch();
                
                if ($device) {
                    $stmt = $pdo->prepare("SELECT id_contacto, correo FROM t_contactos WHERE id_usuario = ?");
                    $stmt->execute([$device['id_usuario']]);
                    $contacts = $stmt->fetchAll();
                    
                    if (!empty($contacts)) {
                        $contact_emails = array_map(fn($c) => $c['correo'], $contacts);
                        
                        $full_name = $device['nombre'] . ' ' . $device['apellido_paterno'] . ' ' . $device['apellido_materno'];
                        $device_location = $device['ubicacion_lugar'] ?? 'ubicación desconocida';
                        $timestamp = date('Y-m-d H:i:s');
                        
                        $alert_details = "<ul>";
                        if ($has_apnea_alert) {
                            $alert_details .= "<li style='color: #dc3545;'><strong>⚠️ Apnea Detectada:</strong> Se han registrado $eventos_apnea eventos</li>";
                        }
                        if ($has_low_quality) {
                            $alert_details .= "<li style='color: #ffc107;'><strong>⚠️ Calidad Baja:</strong> Puntaje: $calidad_sueno_puntaje/100</li>";
                        }
                        if ($has_respiratory_issue) {
                            $alert_details .= "<li style='color: #ffc107;'><strong>⚠️ Respiración Anormal:</strong> Frecuencia: $frecuencia_respiratoria rpm</li>";
                        }
                        $alert_details .= "</ul>";
                        
                        $email_subject = "😴 ALERTA: ANOMALÍA EN PATRONES DE SUEÑO - " . $full_name;
                        $email_message = "
                            <p><strong>Se han detectado anomalías en los patrones de sueño</strong></p>
                            <hr>
                            <p><strong>Detalles:</strong></p>
                            <ul>
                                <li><strong>Paciente:</strong> $full_name</li>
                                <li><strong>Ubicación:</strong> $device_location</li>
                                <li><strong>Fecha/Hora:</strong> $timestamp</li>
                            </ul>
                            <hr>
                            <p><strong>Anomalías Detectadas:</strong></p>
                            $alert_details
                            <hr>
                            <p style='color: #ffc107;'><strong>⚠️ RECOMENDACIÓN:</strong> Se recomienda supervisión médica adicional.</p>
                        ";
                        
                        sendAlertEmail($contact_emails, $email_subject, $email_message, 'sleep');
                        
                        $alert_type = $has_apnea_alert ? 'apnea_detectada' : 'calidad_baja';
                        foreach ($contacts as $contact) {
                            $stmt = $pdo->prepare("
                                INSERT INTO t_alertas_sueño 
                                (id_usuario, id_contacto, id_dispositivo, id_sueno, tipo_alerta, titulo_alerta, mensaje, estado_alerta)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $device['id_usuario'],
                                $contact['id_contacto'],
                                $device_id,
                                $id_sueno,
                                $alert_type,
                                'Anomalía en Patrones de Sueño',
                                "Anomalía detectada en $device_location",
                                'no_confirmada'
                            ]);
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar datos de sueño: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // 4. Enviar alerta
    if (isset($input['action']) && $input['action'] === 'send_alert') {
        $device_id = $input['device_id'] ?? 0;
        $alert_type = $input['alert_type'] ?? '';
        
        $stmt = $pdo->prepare("SELECT d.*, u.id_usuario FROM t_dispositivos d JOIN t_usuarios u ON d.id_usuario = u.id_usuario WHERE d.id_dispositivo = ?");
        $stmt->execute([$device_id]);
        $device = $stmt->fetch();
        
        if ($device) {
            if ($alert_type === 'caida') {
                $stmt = $pdo->prepare("SELECT id_caida FROM t_sensores_caidas WHERE id_dispositivo = ? ORDER BY fecha_hora DESC LIMIT 1");
                $stmt->execute([$device_id]);
                $caida = $stmt->fetch();
                
                $stmt = $pdo->prepare("INSERT INTO t_alertas_caidas (id_usuario, id_dispositivo, id_caida, tipo_alerta, titulo_alerta, mensaje) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $device['id_usuario'],
                    $device_id,
                    $caida['id_caida'] ?? null,
                    'caida_detectada',
                    '⚠️ ALERTA: Caída Detectada',
                    'Posible caída detectada'
                ]);
            }
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}

// 5. Obtener modo actual (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_mode') {
    $device_id = $_GET['device_id'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT Tipo_monitoreo FROM t_dispositivos WHERE id_dispositivo = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    
    if ($device) {
        echo json_encode([
            'success' => true,
            'current_mode' => $device['Tipo_monitoreo'] ?? 'ninguno'
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// ============================================
// ENDPOINTS PARA CONTROL DE PAUSA
// ============================================

if (isset($input['set_monitoring_pause'])) {
    $id_dispositivo = $input['id_dispositivo'] ?? null;
    $is_paused = $input['is_paused'] ?? false;
    
    if (!$id_dispositivo) {
        http_response_code(400);
        echo json_encode(['error' => 'id_dispositivo requerido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE t_dispositivos SET monitoreo_pausado = ? WHERE id_dispositivo = ?");
        $stmt->execute([$is_paused ? 1 : 0, $id_dispositivo]);
        
        echo json_encode([
            'success' => true,
            'is_paused' => $is_paused,
            'message' => $is_paused ? 'Monitoreo pausado' : 'Monitoreo reanudado'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($input['get_monitoring_status'])) {
    $id_dispositivo = $input['id_dispositivo'] ?? null;
    
    if (!$id_dispositivo) {
        http_response_code(400);
        echo json_encode(['error' => 'id_dispositivo requerido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT monitoreo_pausado FROM t_dispositivos WHERE id_dispositivo = ?");
        $stmt->execute([$id_dispositivo]);
        $device = $stmt->fetch();
        
        $is_paused = $device ? (bool)$device['monitoreo_pausado'] : false;
        
        echo json_encode([
            'success' => true,
            'is_paused' => $is_paused,
            'can_send' => !$is_paused,
            'message' => $is_paused ? 'Monitoreo pausado - NO enviar datos' : 'Monitoreo activo - Enviar datos'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// =============== CONTACTOS DE EMERGENCIA ===============
if (isset($input['add_contact']) && !empty($input['id_usuario'])) {
    if (!isset($input['nombre'], $input['parentesco'], $input['correo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO t_contactos (id_usuario, nombre, parentesco, correo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$input['id_usuario'], $input['nombre'], $input['parentesco'], $input['correo']]);
        echo json_encode(['success' => true, 'message' => 'Contacto agregado correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al agregar contacto: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($input['get_contacts']) && !empty($input['id_usuario'])) {
    try {
        $stmt = $pdo->prepare("SELECT id_contacto, nombre, parentesco, correo FROM t_contactos WHERE id_usuario = ? ORDER BY nombre ASC");
        $stmt->execute([$input['id_usuario']]);
        $contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $contactos]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener contactos: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($input['delete_contact']) && !empty($input['id_contacto'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM t_contactos WHERE id_contacto = ? AND id_usuario = ?");
        $stmt->execute([$input['id_contacto'], $input['id_usuario']]);
        echo json_encode(['success' => true, 'message' => 'Contacto eliminado correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar contacto: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($input['update_contact']) && !empty($input['id_contacto'])) {
    if (!isset($input['nombre'], $input['parentesco'], $input['correo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE t_contactos SET nombre = ?, parentesco = ?, correo = ? WHERE id_contacto = ? AND id_usuario = ?");
        $stmt->execute([$input['nombre'], $input['parentesco'], $input['correo'], $input['id_contacto'], $input['id_usuario']]);
        echo json_encode(['success' => true, 'message' => 'Contacto actualizado correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar contacto: ' . $e->getMessage()]);
    }
    exit;
}

// ===== SESIÓN ACTIVA =====
if (isset($input['action']) && $input['action'] === 'get_active_session') {
    try {
        $stmt = $pdo->query("
            SELECT sa.id_usuario, sa.id_dispositivo, d.Tipo_monitoreo, sa.timestamp_login
            FROM t_sesion_activa sa
            JOIN t_dispositivos d ON sa.id_dispositivo = d.id_dispositivo
            WHERE sa.timestamp_login > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY sa.timestamp_login DESC
            LIMIT 1
        ");
        $session = $stmt->fetch();
        
        if ($session) {
            echo json_encode([
                'success' => true,
                'has_active_user' => true,
                'device_id' => (int)$session['id_dispositivo'],
                'current_mode' => $session['Tipo_monitoreo'],
                'last_login' => $session['timestamp_login']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'has_active_user' => false,
                'message' => 'No hay usuarios activos'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===== ALERTAS =====
if (isset($input['action']) && $input['action'] === 'get_alerts') {
    $id_usuario = $input['id_usuario'] ?? null;
    $alert_type = $input['alert_type'] ?? null;
    
    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['error' => 'id_usuario requerido']);
        exit;
    }
    
    try {
        $alerts = [];
        
        if (!$alert_type || $alert_type === 'caidas') {
            $stmt = $pdo->prepare("SELECT id_alerta, id_usuario, id_dispositivo, tipo_alerta, titulo_alerta, mensaje, estado_alerta, timestamp FROM t_alertas_caidas WHERE id_usuario = ? AND estado_alerta = 'no_confirmada' ORDER BY timestamp DESC LIMIT 10");
            $stmt->execute([$id_usuario]);
            $fall_alerts = $stmt->fetchAll();
            $alerts = array_merge($alerts, array_map(fn($a) => array_merge($a, ['tipo' => 'caida']), $fall_alerts));
        }
        
        if (!$alert_type || $alert_type === 'sueno') {
            $stmt = $pdo->prepare("SELECT id_alerta, id_usuario, id_dispositivo, tipo_alerta, titulo_alerta, mensaje, estado_alerta, timestamp FROM t_alertas_sueño WHERE id_usuario = ? AND estado_alerta = 'no_confirmada' ORDER BY timestamp DESC LIMIT 10");
            $stmt->execute([$id_usuario]);
            $sleep_alerts = $stmt->fetchAll();
            $alerts = array_merge($alerts, array_map(fn($a) => array_merge($a, ['tipo' => 'sueno']), $sleep_alerts));
        }
        
        usort($alerts, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
        
        echo json_encode(['success' => true, 'data' => $alerts]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($input['action']) && $input['action'] === 'confirm_alert') {
    $id_alerta = $input['id_alerta'] ?? null;
    $tipo_alerta = $input['tipo_alerta'] ?? null;
    $estado = $input['estado'] ?? 'confirmada';
    $notas = $input['notas'] ?? '';
    
    if (!$id_alerta || !$tipo_alerta) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros']);
        exit;
    }
    
    try {
        if ($tipo_alerta === 'caida') {
            $stmt = $pdo->prepare("UPDATE t_alertas_caidas SET estado_alerta = ?, notas_confirmacion = ? WHERE id_alerta = ?");
            $stmt->execute([$estado, $notas, $id_alerta]);
        } elseif ($tipo_alerta === 'sueno') {
            $stmt = $pdo->prepare("UPDATE t_alertas_sueño SET estado_alerta = ?, notas_confirmacion = ? WHERE id_alerta = ?");
            $stmt->execute([$estado, $notas, $id_alerta]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'tipo_alerta inválido']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Alerta actualizada correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Si llega aquí, endpoint no encontrado
http_response_code(400);
echo json_encode(['error' => 'Acción no reconocida o datos incompletos']);
exit;
