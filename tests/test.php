<?php
// Developed by https://xarop.com
// Uso: php tests/test.php
$api = __DIR__ . '/../api.php';
$data = json_decode(file_get_contents(__DIR__ . '/../dataset.json'), true);
$fallos = 0;

function llamar($api, $texto = null, $metodo = 'GET')
{
    $get = $texto === null ? '[]' : var_export(['texto' => $texto], true);
    $code = '$_GET=' . $get . ';$_SERVER["REQUEST_METHOD"]=' . var_export($metodo, true)
        . ';include ' . var_export($api, true) . ';';
    $out = shell_exec('php -d xdebug.mode=off -r ' . escapeshellarg($code) . ' 2>/dev/null');
    return [json_decode($out, true)];
}

function comprobar($nombre, $cond)
{
    global $fallos;
    echo ($cond ? 'OK   ' : 'FAIL ') . $nombre . "\n";
    if (!$cond) {
        $fallos++;
    }
}

// Estructura
[$r] = llamar($api, 'Humo');
comprobar('devuelve las claves libros y autores', isset($r['libros'], $r['autores']));

// Búsqueda por título
$esperados = array_filter($data, function ($l) {
    return in_array('humo', preg_split('/\P{L}+/u', mb_strtolower($l['titulo']), -1, PREG_SPLIT_NO_EMPTY));
});
comprobar('busca por título (palabra exacta)', count($r['libros']) === count($esperados) && count($esperados) > 0);

// Palabra exacta, no subcadena
[$r] = llamar($api, 'Hum');
comprobar('no busca por subcadena', $r['libros'] === [] && $r['autores'] === []);

// Mayúsculas
[$a] = llamar($api, 'HUMO');
[$b] = llamar($api, 'humo');
comprobar('ignora mayúsculas', $a === $b && count($a['libros']) > 0);

// Autor y sus dos últimos libros
[$r] = llamar($api, 'Stephen King');
comprobar('encuentra el autor', count($r['autores']) === 1 && $r['autores'][0]['autor'] === 'Stephen King');
$ult = $r['autores'][0]['ultimos_libros'];
comprobar('devuelve dos últimos libros', count($ult) === 2);
$fechas = array_column(array_filter($data, function ($l) {
    return trim($l['autor']) === 'Stephen King';
}), 'fecha_nov');
rsort($fechas);
comprobar('ordenados por fecha_nov desc', [$ult[0]['fecha_nov'], $ult[1]['fecha_nov']] === [$fechas[0], $fechas[1]]);

// Registros duplicados del mismo libro
[$r] = llamar($api, 'Topito terremoto');
$topito = array_filter($r['libros'], function ($l) {
    return $l['isbn'] === '9788448847906';
});
comprobar('no repite un libro con distintas portadas', count($topito) === 1 && count($r['libros']) === 3);
[$r] = llamar($api, 'Nueve días de abril');
comprobar('conserva la fecha más reciente del libro duplicado', count($r['libros']) === 1 && $r['libros'][0]['fecha_nov'] === '20170511');
[$r] = llamar($api, 'Rosa Montero');
$isbns = array_column($r['autores'][0]['ultimos_libros'], 'isbn');
comprobar('no repite libros en los últimos libros del autor', count($isbns) === 2 && count(array_unique($isbns)) === 2);

// Autor con espacios sobrantes en el dataset
[$r] = llamar($api, 'Osho');
comprobar('normaliza espacios del autor', count($r['autores']) === 1 && $r['autores'][0]['autor'] === 'Osho');

// Sin resultados / vacío / ausente
[$r] = llamar($api, 'zzzzxxxx');
comprobar('sin coincidencias', $r === ['libros' => [], 'autores' => []]);
[$r] = llamar($api, '');
comprobar('texto vacío', $r === ['libros' => [], 'autores' => []]);
[$r] = llamar($api, null);
comprobar('sin parámetro', $r === ['libros' => [], 'autores' => []]);

// Método
[$r] = llamar($api, 'Humo', 'POST');
comprobar('POST rechazado con error', isset($r['error']) && !isset($r['libros']));

echo $fallos ? "\n$fallos fallo(s)\n" : "\nTodo correcto\n";
exit($fallos ? 1 : 0);
