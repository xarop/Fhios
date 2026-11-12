<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET, OPTIONS');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

function palabras(string $s): array
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    if ($s === '') {
        return [];
    }
    preg_match_all('/[\p{L}\p{N}]+/u', $s, $m);
    return array_unique($m[0]); // Evita comparar paraules repetides a la cerca
}

function coincide(array $buscadas, string $campo): bool
{
    return $buscadas !== [] && array_diff($buscadas, palabras($campo)) === [];
}

// Limitem la longitud màxima de la cerca per evitar abusos (DoS)
$textoRaw = substr((string)($_GET['texto'] ?? ''), 0, 200);
$buscadas = palabras($textoRaw);

// Si no hi ha paraules vàlides, retornem de seguida sense ni llegir el fitxer
if ($buscadas === []) {
    echo json_encode(['libros' => [], 'autores' => []]);
    exit;
}

$rutaDataset = __DIR__ . '/dataset.json';
if (!is_readable($rutaDataset)) {
    http_response_code(500);
    echo json_encode(['error' => 'Dataset no disponible']);
    exit;
}

$data = json_decode(file_get_contents($rutaDataset), true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de formato en dataset.json']);
    exit;
}

$unicos = [];
foreach ($data as $libro) {
    if (!is_array($libro)) {
        continue;
    }
    $isbn = trim((string)($libro['isbn'] ?? ''));
    $titulo = trim((string)($libro['titulo'] ?? ''));
    $autor = trim((string)($libro['autor'] ?? ''));
    $fecha = (string)($libro['fecha_nov'] ?? '');

    $clave = $isbn . "\0" . $titulo . "\0" . $autor;
    if (!isset($unicos[$clave]) || strcmp($fecha, (string)($unicos[$clave]['fecha_nov'] ?? '')) > 0) {
        $unicos[$clave] = $libro;
    }
}

$libros = [];
$porAutor = [];

foreach ($unicos as $libro) {
    $autor = trim((string)($libro['autor'] ?? ''));
    $titulo = (string)($libro['titulo'] ?? '');

    if ($autor !== '') {
        $porAutor[$autor][] = $libro;
    }
    if (count($libros) < 50 && coincide($buscadas, $titulo)) {
        $libros[] = $libro;
    }
}

$autores = [];
foreach ($porAutor as $nombre => $obras) {
    if (!coincide($buscadas, $nombre)) {
        continue;
    }
    usort($obras, static fn($a, $b) => strcmp((string)($b['fecha_nov'] ?? ''), (string)($a['fecha_nov'] ?? '')));
    $autores[] = [
        'autor' => $nombre,
        'ultimos_libros' => array_slice($obras, 0, 2),
    ];
    if (count($autores) >= 20) {
        break;
    }
}

header('Cache-Control: public, max-age=300');
echo json_encode(
    ['libros' => $libros, 'autores' => $autores],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);