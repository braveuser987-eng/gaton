<?php
// 1. Headers de CORS - Esto es lo que fallaba en InfinityFree
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de peticiones preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

function getBypass($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
    
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    // Si detecta "Open in Chrome", salta a la URL interna
    if (strpos($html, 'Open in Chrome') !== false) {
        preg_match('/let currentUrl = "(.*?)";/', $html, $matches);
        if (isset($matches[1])) return getBypass($matches[1]);
    }

    // Busca el campo "go" y extrae el base64
    if (preg_match('/name="go"\s+value="([^"]+)"/', $html, $matches)) {
        return base64_decode($matches[1]);
    }

    return null;
}

$target = $_GET['url'] ?? null;

if ($target) {
    $finalUrl = getBypass($target);
    if ($finalUrl) {
        echo json_encode(["status" => "success", "url_limpia" => $finalUrl]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo extraer la URL"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Falta parametro 'url'"]);
}
