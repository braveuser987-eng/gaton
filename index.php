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
    curl_setopt($ch, CURLOPT_ENCODING, ""); // Permite comprimir la respuesta (gzip/deflate)
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    // Cabeceras de alto nivel para evitar el 403
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
        'Cache-Control: max-age=0',
        'Referer: https://www.google.com/',
        'Sec-Ch-Ua: "Not_A brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: cross-site',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 403) {
        return ["error" => "Bloqueo 403: El servidor de destino sigue rechazando la conexion de Render."];
    }

    if (!$html) {
        return ["error" => "No hay respuesta del servidor."];
    }

    // Busqueda del enlace en el HTML
    if (preg_match('/name="go"\s+value="([^"]+)"/', $html, $matches)) {
        return ["url" => base64_decode($matches[1])];
    }
    
    // Segunda oportunidad: buscar cualquier cadena base64 larga
    if (preg_match('/value="([A-Za-z0-9+\/]{50,})={0,2}"/', $html, $matches)) {
        return ["url" => base64_decode($matches[1])];
    }

    return ["error" => "Patron no encontrado. Posible cambio de estructura o Captcha."];
}

$codigoUrl = $_GET['url'] ?? null;

if ($codigoUrl) {
    // Extraer solo el ID final (ej: fBdzr)
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
