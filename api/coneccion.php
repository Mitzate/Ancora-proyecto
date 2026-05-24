<?php
// Enable error reporting for easier debugging

// Importar funciones de Telegram
require_once __DIR__ . '/telegram.php';

$host = 'localhost';
$dbname = 'u574321597_ancora';
$user = 'u574321597_proyectoA';
$pass = 'Megustanlasg0mitas';
$charset = 'utf8mb4';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Preflight handling before session
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

error_log("=== NUEVA PETICIÓN ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? ''));
error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? ''));

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

// Manejo de OPTIONS ya atendido arriba

// Decodificar el cuerpo una sola vez
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    error_log('RAW INPUT: ' . $raw);
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
        exit;
    }
    $input = is_array($decoded) ? $decoded : [];

    // ============================================
    // Aceptar datos directos del ESP32 (sin action)
    // ============================================
    if (isset($input['node_id'])) {
        $device_id = $input['node_id'];
        $presencia = $input['presencia'] ?? '';
        $movimiento = $input['movimiento'] ?? '';
        $parametro = $input['parametro_movimiento'] ?? 0;
        $estado_caida = $input['estado_caida'] ?? '';

        // Validar que el dispositivo exista
        $stmt = $pdo->prepare("SELECT id_dispositivo FROM t_dispositivos WHERE id_dispositivo = ?");
        $stmt->execute([$device_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Dispositivo no registrado']);
            exit;
        }

        // Insertar en t_sensores_caidas
        $stmt = $pdo->prepare("
            INSERT INTO t_sensores_caidas 
            (id_dispositivo, presencia, movimiento, parametro_movimiento, estado_caida) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$device_id, $presencia, $movimiento, $parametro, $estado_caida]);

        echo json_encode(['success' => true, 'id_caida' => $pdo->lastInsertId()]);
        exit;
    }
    // LOGIN
 if (isset($input['login']) && $input['login'] === true) {
    error_log("=== PROCESANDO LOGIN ===");
    error_log("Correo recibido: " . $input['correo']);
    error_log("Contraseña recibida: " . $input['contrasena']);
    
    if (!isset($input['correo'], $input['contrasena'])) {
        error_log("ERROR: Faltan campos");
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos requeridos']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM t_usuarios WHERE correo_electronico = ?");
    $stmt->execute([$input['correo']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Usuario encontrado en BD: " . ($usuario ? 'SÍ' : 'NO'));
    if ($usuario) {
        error_log("Contraseña BD: " . $usuario['contrasena']);
        error_log("Contraseña input: " . $input['contrasena']);
        error_log("Comparación: " . ($usuario['contrasena'] === $input['contrasena'] ? 'COINCIDEN' : 'NO COINCIDEN'));
    }
    
    if ($usuario && $usuario['contrasena'] === $input['contrasena']) {
        error_log("Login exitoso para usuario ID: " . $usuario['id_usuario']);
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
                 $sessionSaved = true;  // ← DEFINE LA VARIABLE
            } else {
                $sessionSaved = false; // ← DEFINE LA VARIABLE
            }
            
            // Nombre completo del usuario
            $nombreCompleto = trim($usuario['nombre'] . ' ' . $usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno']);
            
            echo json_encode([
                'success' => true,
                'nombre' => $nombreCompleto,
                'user_name' => $nombreCompleto,  // Campo adicional para claridad
                'id_usuario' => $usuario['id_usuario'],
                'device_id' => $device['id_dispositivo'] ?? null,
                'session_saved' => $sessionSaved
            ]);
        } else {
            error_log("Login fallido");
            http_response_code(401);
            echo json_encode(['error' => 'Correo o contraseña incorrectos.']);
        }
        exit;
    }
///////////////////
    // Obtener todos los dispositivos del usuario
if (isset($input['get_user_devices']) && !empty($input['id_usuario'])) {
    $userId = (int)$input['id_usuario'];
    try {
        $stmt = $pdo->prepare("SELECT id_dispositivo, nombre_identificador, ubicacion_lugar, Tipo_monitoreo FROM t_dispositivos WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'devices' => $devices]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage(), 'devices' => []]);
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
    $newUserId = $pdo->lastInsertId();
    echo json_encode([
        'success' => true, 
        'message' => 'Usuario registrado correctamente',
        'id_usuario' => (int)$newUserId   // ← ¡AGREGA ESTA LÍNEA!
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar usuario: ' . $e->getMessage()]);
}
        exit;
    }

// Check device for user - múltiples dispositivos
if (isset($input['check_device']) && !empty($input['id_usuario'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id_dispositivo, 
                fecha_instalacion, 
                nombre_identificador, 
                Tipo_monitoreo AS tipo_monitoreo, 
                ubicacion_lugar 
            FROM t_dispositivos 
            WHERE id_usuario = ?
        ");
        $stmt->execute([$input['id_usuario']]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($devices) > 0) {
            echo json_encode([
                'success' => true,
                'hasDevices' => true,
                'devices' => $devices
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'hasDevices' => false
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al verificar dispositivos: ' . $e->getMessage()]);
    }
    exit;
}
/*

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
        */
    // Registrar múltiples dispositivos (desde el panel de registro)
    if (isset($input['register_devices']) && $input['register_devices'] === true) {
        if (empty($input['id_usuario']) || empty($input['dispositivos']) || !is_array($input['dispositivos'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos o formato inválido']);
            exit;
        }

        $userId = (int)$input['id_usuario'];
        $devices = $input['dispositivos'];
        $registered = [];

        try {
            // Verificar que el usuario exista
            $checkUser = $pdo->prepare("SELECT id_usuario FROM t_usuarios WHERE id_usuario = ?");
            $checkUser->execute([$userId]);
            if (!$checkUser->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado']);
                exit;
            }

            $pdo->beginTransaction();

            foreach ($devices as $device) {
                $nombre = trim($device['nombre_identificador'] ?? '');
                $ubicacion = trim($device['ubicacion_lugar'] ?? '');
                $tipo = $device['tipo_monitoreo'] ?? '';
                $fecha = $device['fecha_instalacion'] ?? date('Y-m-d');

                // Validar campo obligatorio (al menos nombre o ubicación, pero se puede ajustar)
                if (empty($nombre) && empty($ubicacion)) {
                    continue; // omitir dispositivo sin datos mínimos
                }

                $stmt = $pdo->prepare("
                    INSERT INTO t_dispositivos 
                    (id_usuario, nombre_identificador, ubicacion_lugar, fecha_instalacion, Tipo_monitoreo, monitoreo_pausado) 
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$userId, $nombre, $ubicacion, $fecha, $tipo]);
                $newId = $pdo->lastInsertId();

                $registered[] = [
                    'id_dispositivo' => (int)$newId,
                    'nombre_identificador' => $nombre,
                    'ubicacion_lugar' => $ubicacion,
                    'tipo_monitoreo' => $tipo
                ];
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => count($registered) . ' dispositivo(s) registrado(s) correctamente',
                'devices' => $registered
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al registrar dispositivos: ' . $e->getMessage()]);
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
$stmt = $pdo->query("SELECT id_dispositivo, Tipo_monitoreo, id_usuario FROM t_dispositivos WHERE mac_address IS NULL OR mac_address = '' ORDER BY id_dispositivo DESC LIMIT 1");
        $pendingDevice = $stmt->fetch();

    if ($pendingDevice) {
        try {
            $upd = $pdo->prepare("UPDATE t_dispositivos SET mac_address = ? WHERE id_dispositivo = ?");
            $upd->execute([$mac, $pendingDevice['id_dispositivo']]);

            // Obtener el user_id (podrías leerlo directamente de $pendingDevice)
            $userId = $pendingDevice['id_usuario'];  // ya viene en la consulta original
            echo json_encode([
                'success' => true,
                'device_id' => (int)$pendingDevice['id_dispositivo'],
                'user_id' => (int)$userId,
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
        /*$stmt = $pdo->prepare("SELECT id_dispositivo, Tipo_monitoreo FROM t_dispositivos WHERE mac_address = ? ORDER BY id_dispositivo DESC LIMIT 1");
        $stmt->execute([$mac]);
        $device = $stmt->fetch();
        
        if ($device) {
            echo json_encode([
                'success' => true,
                'device_id' => (int)$device['id_dispositivo'],
                'current_mode' => $device['Tipo_monitoreo']
            ]);
            exit;
        }*/

            if ($device) {
        // Obtener el user_id asociado
        $stmtUser = $pdo->prepare("SELECT id_usuario FROM t_dispositivos WHERE id_dispositivo = ?");
        $stmtUser->execute([$device['id_dispositivo']]);
        $userId = $stmtUser->fetchColumn();
        echo json_encode([
            'success' => true,
            'device_id' => (int)$device['id_dispositivo'],
            'user_id' => (int)$userId,
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
        
        // Obtener el usuario desde el cuerpo de la petición o desde la sesión
        $id_usuario = $input['id_usuario'] ?? ($_SESSION['id_usuario'] ?? null);
        
        try {
            // Construir la consulta combinada (UNION) y aplicar filtros sobre el resultado
            $sql = "SELECT * FROM (\n";
            $sql .= "  SELECT sc.id_caida AS id, sc.id_dispositivo, sc.fecha_hora, 'caidas' AS origen,\n";
            $sql .= "         d.nombre_identificador AS nombre_identificador, d.ubicacion_lugar,\n";
            $sql .= "         sc.presencia, sc.movimiento AS informacion_movimiento, sc.parametro_movimiento,\n";
            $sql .= "         NULL AS tasa_respiracion, NULL AS Frecuencia_cardiaca,\n";
            $sql .= "         NULL AS en_cama, NULL AS estado_sueno, NULL AS calidad_sueno_puntaje,\n";
            $sql .= "         NULL AS duracion_sueno_profundo, NULL AS duracion_despierto, NULL AS numero_vueltas\n";
            $sql .= "    FROM t_sensores_caidas sc\n";
            $sql .= "    LEFT JOIN t_dispositivos d ON sc.id_dispositivo = d.id_dispositivo\n";
            $sql .= "  UNION ALL\n";
            $sql .= "  SELECT ss.id_sueno AS id, ss.id_dispositivo, ss.fecha_hora, 'sueno' AS origen,\n";
            $sql .= "         d.nombre_identificador AS nombre_identificador, d.ubicacion_lugar,\n";
            $sql .= "         NULL AS presencia, NULL AS informacion_movimiento, NULL AS parametro_movimiento,\n";
            $sql .= "         ss.frecuencia_respiratoria_promedio AS tasa_respiracion,\n";
            $sql .= "         ss.frecuencia_cardiaca_promedio AS Frecuencia_cardiaca,\n";
            $sql .= "         ss.en_cama, ss.estado_sueno, ss.calidad_sueno_puntaje,\n";
            $sql .= "         ss.duracion_sueno_profundo, ss.duracion_despierto, ss.numero_vueltas\n";
            $sql .= "    FROM t_sensores_sueño ss\n";
            $sql .= "    LEFT JOIN t_dispositivos d ON ss.id_dispositivo = d.id_dispositivo\n";
            $sql .= ") AS combined";

            $conditions = [];
            $params = [];

            // FILTRO DE SEGURIDAD: Solo mostrar datos de dispositivos del usuario actual
            if ($id_usuario) {
                $conditions[] = "combined.id_dispositivo IN (SELECT id_dispositivo FROM t_dispositivos WHERE id_usuario = ?)";
                $params[] = $id_usuario;
            }
            
            if ($id_dispositivo) {
                $conditions[] = "combined.id_dispositivo = ?";
                $params[] = $id_dispositivo;
            }
            if ($since) {
                $conditions[] = "combined.fecha_hora > ?";
                $params[] = $since;
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }

            // Ordenar por fecha (más reciente primero) y limitar
            $sql .= " ORDER BY combined.fecha_hora DESC LIMIT ?";
            $params[] = (int)$limit;

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

        error_log("save_fall_data called: device_id=" . var_export($device_id, true) .
                  " presencia='" . $presencia . "' movimiento='" . $movimiento .
                  "' parametro=" . $parametro_movimiento .
                  " estado_caida='" . $estado_caida . "' estado_inmovilidad='" . $estado_inmovilidad . "'");

        // basic validation: ensure the device exists to avoid FK errors
        if (empty($device_id)) {
            error_log('❌ save_fall_data: missing device_id');
            http_response_code(400);
            echo json_encode(['error' => 'id_dispositivo inválido o ausente']);
            exit;
        }
        $checkStmt = $pdo->prepare("SELECT 1 FROM t_dispositivos WHERE id_dispositivo = ?");
        $checkStmt->execute([$device_id]);
        if (!$checkStmt->fetch()) {
            error_log('❌ save_fall_data: device_id ' . $device_id . ' no existe en t_dispositivos');
            http_response_code(400);
            echo json_encode(['error' => 'Dispositivo no registrado: ' . $device_id]);
            exit;
        }

        // Normalizar estado de caída cuando el movimiento supera el umbral (>=70)
        $umbralMovimientoCaida = 80;
        $estado_caida_calculado = $estado_caida;
        if (is_numeric($parametro_movimiento) && $parametro_movimiento >= $umbralMovimientoCaida) {
            $estado_caida_calculado = 'Caída por movimiento alto';
        }
        
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
                $estado_caida_calculado,
                $estado_inmovilidad
            ]);
            
            $rows = $stmt->rowCount();
            error_log('✅ t_sensores_caidas insert rows=' . $rows . ' id_dispositivo=' . $device_id . ' parametro=' . $parametro_movimiento);
            $id_caida = $pdo->lastInsertId();
            
            // VERIFICAR SI HAY CAÍDA DETECTADA
            // Normalizar cadenas (quitar acentos y case-insensitive) y buscar 'caid' como subcadena
            $normalize = function($s) {
                if (!is_string($s) || $s === '') return '';
                // transliterate UTF-8 accents to ASCII, then lowercase
                $trans = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
                $trans = strtolower($trans);
                // Keep only letters/numbers for robust matching
                $trans = preg_replace('/[^a-z0-9]/', '', $trans);
                return $trans;
            };

            $estado_norm = $normalize($estado_caida_calculado ?? '');
            $movimiento_norm = $normalize($movimiento ?? '');
            $inmovilidad_norm = $normalize($estado_inmovilidad ?? '');

            $is_fall_detected = false;
            if ((is_numeric($parametro_movimiento) && $parametro_movimiento >= $umbralMovimientoCaida)) {
                $is_fall_detected = true;
            }
            if (strpos($estado_norm, 'caid') !== false || strpos($movimiento_norm, 'caid') !== false || strpos($inmovilidad_norm, 'caid') !== false) {
                $is_fall_detected = true;
            }

            error_log('is_fall_detected=' . ($is_fall_detected ? '1' : '0') . ' - parametro_movimiento=' . $parametro_movimiento . ' - estado_norm=' . $estado_norm . ' - id_caida=' . $id_caida);

            // Devolver id insertado y flag para depuración/confirmación
            echo json_encode(['success' => true, 'id_caida' => (int)$id_caida, 'is_fall_detected' => $is_fall_detected]);
            // Continuar con envío de alertas si corresponde
            // solo crear y dispatchear alertas si realmente se trató de una caída
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
                    // 🔍 Buscar contactos y vincular automáticamente con chat_id de Telegram
                    try {
                        $stmt = $pdo->prepare("
                            SELECT 
                                c.id_contacto, 
                                c.nombre, 
                                c.correo,
                                tu.chat_id,
                                tu.telegram_username
                            FROM t_contactos c
                            LEFT JOIN t_telegram_usuarios tu ON c.correo = tu.telegram_username
                            WHERE c.id_usuario = ?
                        ");
                        $stmt->execute([$device['id_usuario']]);
                        $contacts = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        // Si falla el JOIN (tabla no existe), usar solo t_contactos
                        error_log("⚠️ JOIN con t_telegram_usuarios falló, usando solo t_contactos: " . $e->getMessage());
                        $stmt = $pdo->prepare("
                            SELECT 
                                id_contacto, 
                                nombre, 
                                correo,
                                telegram_chat_id as chat_id,
                                correo as telegram_username
                            FROM t_contactos
                            WHERE id_usuario = ?
                        ");
                        $stmt->execute([$device['id_usuario']]);
                        $contacts = $stmt->fetchAll();
                    }
                    
                    if (!empty($contacts)) {
                        $device_location = $device['ubicacion_lugar'] ?? 'ubicación desconocida';
                        
                        // Obtener datos de la caída para el mensaje
                        $caidaData = [
                            'fecha_hora' => date('Y-m-d H:i:s'),
                            'estado_caida' => $estado_caida_calculado,
                            'parametro_movimiento' => $parametro_movimiento
                        ];
                        
                        // Guardar alerta para CADA CONTACTO y enviar Telegram
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
                            
                            // 🔔 ENVIAR NOTIFICACIÓN POR TELEGRAM (vinculación automática)
                            if (!empty($contact['chat_id'])) {
                                $resultTelegram = enviarAlertaCaidaTelegram(
                                    $device,
                                    $caidaData,
                                    $contact['chat_id'],
                                    $contact['nombre']
                                );
                                
                                if ($resultTelegram['success']) {
                                    error_log("✅ Alerta de caída enviada por Telegram a: {$contact['nombre']} (@{$contact['telegram_username']})");
                                } else {
                                    error_log("❌ Error enviando Telegram a {$contact['nombre']}: {$resultTelegram['error']}");
                                }
                            } else {
                                error_log("⚠️ Contacto {$contact['nombre']} ({$contact['correo']}) no tiene Telegram vinculado");
                            }
                        }
                    }
                }
            }
            
            // Response already returned earlier with id_caida and is_fall_detected
        } catch (PDOException $e) {
            error_log('❌ Error al insertar save_fall_data: ' . $e->getMessage());
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
                    SELECT d.*, u.id_usuario
                    FROM t_dispositivos d
                    JOIN t_usuarios u ON d.id_usuario = u.id_usuario
                    WHERE d.id_dispositivo = ?
                ");
                $stmt->execute([$device_id]);
                $device = $stmt->fetch();
                
                if ($device) {
                    // 🔍 Buscar contactos y vincular automáticamente con chat_id de Telegram
                    try {
                        $stmt = $pdo->prepare("
                            SELECT 
                                c.id_contacto, 
                                c.nombre, 
                                c.correo,
                                tu.chat_id,
                                tu.telegram_username
                            FROM t_contactos c
                            LEFT JOIN t_telegram_usuarios tu ON c.correo = tu.telegram_username
                            WHERE c.id_usuario = ?
                        ");
                        $stmt->execute([$device['id_usuario']]);
                        $contacts = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        // Si falla el JOIN (tabla no existe), usar solo t_contactos
                        error_log("⚠️ JOIN con t_telegram_usuarios falló, usando solo t_contactos: " . $e->getMessage());
                        $stmt = $pdo->prepare("
                            SELECT 
                                id_contacto, 
                                nombre, 
                                correo,
                                telegram_chat_id as chat_id,
                                correo as telegram_username
                            FROM t_contactos
                            WHERE id_usuario = ?
                        ");
                        $stmt->execute([$device['id_usuario']]);
                        $contacts = $stmt->fetchAll();
                    }
                    
                    if (!empty($contacts)) {
                        $device_location = $device['ubicacion_lugar'] ?? 'ubicación desconocida';
                        $alert_type = $has_apnea_alert ? 'apnea_detectada' : 'calidad_baja';
                        
                        // Datos del sueño para el mensaje
                        $suenoData = [
                            'fecha_hora' => date('Y-m-d H:i:s'),
                            'calidad_sueno_puntaje' => $calidad_sueno_puntaje,
                            'eventos_apnea' => $eventos_apnea,
                            'frecuencia_respiratoria_promedio' => $frecuencia_respiratoria
                        ];
                        
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
                            
                            // 🔔 ENVIAR NOTIFICACIÓN POR TELEGRAM (vinculación automática)
                            if (!empty($contact['chat_id'])) {
                                $resultTelegram = enviarAlertaSuenoTelegram(
                                    $device,
                                    $suenoData,
                                    $contact['chat_id'],
                                    $contact['nombre']
                                );
                                
                                if ($resultTelegram['success']) {
                                    error_log("✅ Alerta de sueño enviada por Telegram a: {$contact['nombre']} (@{$contact['telegram_username']})");
                                } else {
                                    error_log("❌ Error enviando Telegram a {$contact['nombre']}: {$resultTelegram['error']}");
                                }
                            } else {
                                error_log("⚠️ Contacto {$contact['nombre']} ({$contact['correo']}) no tiene Telegram vinculado");
                            }
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

// ============================================
// CONTROL DE PAUSA (desde el panel web)
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
        $telegram_chat_id = $input['telegram_chat_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO t_contactos (id_usuario, nombre, parentesco, correo, telegram_chat_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$input['id_usuario'], $input['nombre'], $input['parentesco'], $input['correo'], $telegram_chat_id]);
        echo json_encode(['success' => true, 'message' => 'Contacto agregado correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al agregar contacto: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($input['get_contacts']) && !empty($input['id_usuario'])) {
    try {
        $stmt = $pdo->prepare("SELECT id_contacto, nombre, parentesco, correo, telegram_chat_id FROM t_contactos WHERE id_usuario = ? ORDER BY nombre ASC");
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
        $telegram_chat_id = $input['telegram_chat_id'] ?? null;
        $stmt = $pdo->prepare("UPDATE t_contactos SET nombre = ?, parentesco = ?, correo = ?, telegram_chat_id = ? WHERE id_contacto = ? AND id_usuario = ?");
        $stmt->execute([$input['nombre'], $input['parentesco'], $input['correo'], $telegram_chat_id, $input['id_contacto'], $input['id_usuario']]);
        echo json_encode(['success' => true, 'message' => 'Contacto actualizado correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar contacto: ' . $e->getMessage()]);
    }
    exit;
}

// ===== ALERTAS ENVIADAS A CONTACTOS =====
if (isset($input['action']) && $input['action'] === 'get_sent_alerts') {
    $id_usuario = $input['id_usuario'] ?? null;
    $limit = $input['limit'] ?? 50;
    
    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['error' => 'id_usuario requerido']);
        exit;
    }
    
    try {
        // Obtener alertas de caídas
        $stmt = $pdo->prepare("
            SELECT 
                ac.id_alerta_caida as id_alerta,
                'caida' as tipo,
                ac.timestamp as fecha_hora,
                tc.nombre as contacto_nombre,
                d.nombre_identificador as dispositivo,
                ac.mensaje,
                ac.titulo_alerta,
                ac.estado_alerta
            FROM t_alertas_caidas ac
            JOIN t_contactos tc ON ac.id_contacto = tc.id_contacto
            JOIN t_dispositivos d ON ac.id_dispositivo = d.id_dispositivo
            WHERE ac.id_usuario = ?
            UNION ALL
            SELECT 
                as2.id_alerta_sueno as id_alerta,
                'sueno' as tipo,
                as2.timestamp as fecha_hora,
                tc.nombre as contacto_nombre,
                d.nombre_identificador as dispositivo,
                as2.mensaje,
                as2.titulo_alerta,
                as2.estado_alerta
            FROM t_alertas_sueño as2
            JOIN t_contactos tc ON as2.id_contacto = tc.id_contacto
            JOIN t_dispositivos d ON as2.id_dispositivo = d.id_dispositivo
            WHERE as2.id_usuario = ?
            ORDER BY fecha_hora DESC
            LIMIT ?
        ");
        $stmt->execute([$id_usuario, $id_usuario, $limit]);
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $alertas]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener alertas: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ENDPOINT PARA ESP32 - Obtener estado de sesión y monitoreo
// ============================================
if (isset($input['action']) && $input['action'] === 'esp32_get_status') {
    try {
        // Construir consulta para la sesión activa más reciente (últimas 24 horas)
        $sql = "
            SELECT sa.id_usuario, sa.id_dispositivo, d.Tipo_monitoreo, d.monitoreo_pausado
            FROM t_sesion_activa sa
            JOIN t_dispositivos d ON sa.id_dispositivo = d.id_dispositivo
            WHERE sa.timestamp_login > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $params = [];

        // El ESP puede incluir su device_id para obtener sólo su propia sesión
        if (!empty($input['id_dispositivo'])) {
            $sql .= " AND sa.id_dispositivo = ?";
            $params[] = $input['id_dispositivo'];
        }

        $sql .= " ORDER BY sa.timestamp_login DESC LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $session = $stmt->fetch();
        
    if ($session) {
        echo json_encode([
            'success' => true,
            'has_active_session' => true,
            'device_id' => (int)$session['id_dispositivo'],
            'user_id' => (int)$session['id_usuario'],
            'monitoreo_pausado' => (bool)$session['monitoreo_pausado'],
            'modo' => $session['Tipo_monitoreo']
    ]);
        } else {
            // No hay sesión activa
            echo json_encode([
                'success' => true,
                'has_active_session' => false,
                'device_id' => 0,
                'monitoreo_pausado' => false,
                'modo' => 'ninguno'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
    if (isset($input['action']) && $input['action'] === 'get_user_devices_mapping') {
        $id_usuario = $input['id_usuario'] ?? null;
        
        if (!$id_usuario) {
            http_response_code(400);
            echo json_encode(['error' => 'id_usuario requerido']);
            exit;
        }
        
        try {
            // Obtener dispositivos en orden (id_dispositivo ascendente)
            // Esto asegura que el mapeo sea consistente
            $stmt = $pdo->prepare("
                SELECT 
                    id_dispositivo,
                    nombre_identificador,
                    ubicacion_lugar,
                    Tipo_monitoreo,
                    monitoreo_pausado
                FROM t_dispositivos
                WHERE id_usuario = ?
                ORDER BY id_dispositivo ASC
                LIMIT 5
            ");
            $stmt->execute([$id_usuario]);
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($devices) === 0) {
                echo json_encode([
                    'success' => true,
                    'mapping' => [],
                    'message' => 'Usuario sin dispositivos registrados'
                ]);
                exit;
            }
            
            // Crear mapeo: nodo_1 → device_id[0], nodo_2 → device_id[1], etc.
            $mapping = [];
            foreach ($devices as $index => $device) {
                $node_id = $index + 1; // 1, 2, 3, 4, 5
                $mapping[$node_id] = [
                    'device_id' => (int)$device['id_dispositivo'],
                    'nombre_identificador' => $device['nombre_identificador'],
                    'ubicacion_lugar' => $device['ubicacion_lugar'],
                    'Tipo_monitoreo' => $device['Tipo_monitoreo'],
                    'monitoreo_pausado' => (bool)$device['monitoreo_pausado']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'mapping' => $mapping,
                'total_devices' => count($devices),
                'user_id' => (int)$id_usuario
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

// ===== SESIÓN ACTIVA =====
// ===== SESIÓN ACTIVA (POST) =====
if (isset($input['action']) && $input['action'] === 'get_active_session') {
    try {
        // Construir consulta base
        $sql = "
            SELECT sa.id_usuario, sa.id_dispositivo, d.Tipo_monitoreo, sa.timestamp_login
            FROM t_sesion_activa sa
            JOIN t_dispositivos d ON sa.id_dispositivo = d.id_dispositivo
            WHERE sa.timestamp_login > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";
        
        $params = [];
        
        // Si se especifica un dispositivo, filtrar por él
        if (isset($input['id_dispositivo'])) {
            $sql .= " AND sa.id_dispositivo = ?";
            $params[] = $input['id_dispositivo'];
        }
        
        $sql .= " ORDER BY sa.timestamp_login DESC LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
                'has_active_user' => false
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
?>