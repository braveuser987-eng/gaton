<?php
// Configuración de cabeceras para evitar problemas de visualización y CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/**
 * Función principal para obtener el enlace mediante cURL
 */
function obtenerEnlaceFinal($codigo) {
    // Reconstruimos la URL de destino
    $url = "https://softurl.in/" . $codigo;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecciones
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evitar errores de certificado
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // El "disfraz" de navegador
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    $html = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    // Si el servidor nos bloquea con un 403
    if ($info['http_code'] == 403) {
        return ["error" => "Bloqueo 403 detectado por el servidor de destino."];
    }

    if (!$html) {
        return ["error" => "No se pudo obtener el contenido de la web."];
    }

    // Buscamos el valor de "go" que está en Base64
    if (preg_match('/name="go"\s+value="([^"]+)"/', $html, $matches)) {
        return ["url" => base64_decode($matches[1])];
    }

    return ["error" => "No se encontró el enlace oculto en el HTML."];
}

// Recibir el parámetro 'url' (que ahora es solo el código como fBdzr)
$codigo = $_GET['url'] ?? null;

if ($codigo) {
    // Limpiamos el código por si viene con espacios o barras
    $codigo = trim(basename($codigo));
    
    $resultado = obtenerEnlaceFinal($codigo);

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
    echo json_encode([
        "status" => "error",
        "message" => "Debe proporcionar un código en el parámetro 'url'."
    ]);
}
