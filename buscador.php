<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

function palabras($s)
{
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    preg_match_all('/[\p{L}\p{N}]+/u', $s, $m);
    return $m[0];
}

// Coincide si todas las palabras buscadas aparecen exactas en el campo
function coincide(array $buscadas, $campo)
{
    return $buscadas && !array_diff($buscadas, palabras($campo));
}

$buscadas = palabras($_GET['texto'] ?? '');
$libros = [];
$autores = [];

$data = json_decode(file_get_contents(__DIR__ . '/dataset.json'), true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo leer dataset.json']);
    exit;
}

if ($buscadas) {
    $porAutor = [];
    foreach ($data as $libro) {
        $porAutor[trim($libro['autor'])][] = $libro;
        if (coincide($buscadas, $libro['titulo'])) {
            $libros[] = $libro;
        }
    }

    foreach ($porAutor as $nombre => $obras) {
        if (!coincide($buscadas, $nombre)) {
            continue;
        }
        usort($obras, function ($a, $b) {
            return strcmp($b['fecha_nov'], $a['fecha_nov']);
        });
        $autores[] = [
            'autor' => $nombre,
            'ultimos_libros' => array_slice($obras, 0, 2),
        ];
    }
}

echo json_encode(['libros' => $libros, 'autores' => $autores], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
