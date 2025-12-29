<?php
// 1. Cabeceras CORS para permitir peticiones desde cualquier sitio
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Responder rápido a peticiones de verificación (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Función para obtener el HTML y extraer el enlace final
 */
function extraerEnlace($codigo) {
    // Reconstruimos la URL completa
    $urlCompleta = "https://softurl.in/" . $codigo;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlCompleta);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // User Agent real para evitar bloqueos básicos
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    // Lógica de Bypass: Buscar el valor del input "go" que contiene el Base64
    if (preg_match('/name="go"\s+value="([^"]+)"/', $html, $matches)) {
        return base64_decode($matches[1]);
    }

    return null;
}

// 2. Procesar la petición
$codigoUrl = $_GET['url'] ?? null;

if ($codigoUrl) {
    // Limpiamos el código por si envías caracteres extraños
    $codigoUrl = trim(str_replace(['https://softurl.in/', '/'], '', $codigoUrl));
    
    $resultado = extraerEnlace($codigoUrl);

    if ($resultado) {
        echo json_encode([
            "status" => "success",
            "codigo" => $codigoUrl,
            "url_limpia" => $resultado
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "No se pudo encontrar el enlace en el HTML"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Falta el parámetro 'url' con el código"
    ]);
}
