<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

function extraerEnlace($codigo) {
    $urlCompleta = "https://softurl.in/" . $codigo;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlCompleta);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // User Agent más moderno y completo
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    // Añadimos un referer para que parezca una visita orgánica
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$html || $httpCode !== 200) return ["error" => "No se pudo conectar. Código HTTP: $httpCode"];

    // INTENTO 1: Buscar el input name="go"
    if (preg_match('/name="go"\s+value="([^"]+)"/', $html, $matches)) {
        return ["url" => base64_decode($matches[1])];
    }

    // INTENTO 2: Buscar cualquier cadena que parezca Base64 larga dentro de un value (Plan B)
    if (preg_match('/value="([A-Za-z0-9+\/]{50,})={0,2}"/', $html, $matches)) {
        return ["url" => base64_decode($matches[1])];
    }

    // Si falla, devolvemos un trozo del HTML para debug (opcional)
    return ["error" => "No se encontro el patron. Longitud HTML: " . strlen($html)];
}

$codigoUrl = $_GET['url'] ?? null;

if ($codigoUrl) {
    // Limpieza profunda del código
    $codigoUrl = trim(basename(parse_url($codigoUrl, PHP_URL_PATH)));
    
    $resultado = extraerEnlace($codigoUrl);

    if (isset($resultado['url'])) {
        echo json_encode([
            "status" => "success",
            "url_limpia" => $resultado['url']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "details" => $resultado['error']
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Falta parametro url"]);
}
