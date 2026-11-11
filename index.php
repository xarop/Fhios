<?php
// Developed by https://xarop.com
$texto = trim($_GET['texto'] ?? '');
$resultado = null;

if ($texto !== '') {
    ob_start();
    include __DIR__ . '/api.php';
    $resultado = json_decode(ob_get_clean(), true);
    header('Content-Type: text/html; charset=utf-8');
}

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function libro(array $l)
{
    return '<div class="libro"><img src="' . h($l['portada']) . '" alt=""><div><b>' . h($l['titulo'])
        . '</b><br>' . h($l['autor']) . '<br>ISBN ' . h($l['isbn']) . ' · ' . h($l['fecha_nov']) . '</div></div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Buscador de libros (sin JS)</title>
<style>
body{font-family:sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem}
form{display:flex;gap:.5rem}
input{flex:1;padding:.5rem}
button{padding:.5rem 1rem}
.libro{display:flex;gap:1rem;margin:.5rem 0;padding:.5rem;border:1px solid #ddd}
.libro img{width:60px}
footer{margin-top:2rem}
</style>
</head>
<body>
<h1>Buscador de libros</h1>
<form method="get" action="index.php">
  <input name="texto" value="<?= h($texto) ?>" placeholder="Título o autor" required autofocus>
  <button>Buscar</button>
</form>
<p><a href="index.html">Versión con JavaScript</a></p>

<?php if ($resultado === null): ?>
<?php elseif (isset($resultado['error'])): ?>
  <p><?= h($resultado['error']) ?></p>
<?php else: ?>
  <h2>Libros (<?= count($resultado['libros']) ?>)</h2>
  <?php foreach ($resultado['libros'] as $l) echo libro($l); ?>
  <?php if (!$resultado['libros']): ?><p>Sin resultados</p><?php endif; ?>

  <h2>Autores (<?= count($resultado['autores']) ?>)</h2>
  <?php foreach ($resultado['autores'] as $a): ?>
    <h3><?= h($a['autor']) ?></h3>
    <?php foreach ($a['ultimos_libros'] as $l) echo libro($l); ?>
  <?php endforeach; ?>
  <?php if (!$resultado['autores']): ?><p>Sin resultados</p><?php endif; ?>
<?php endif; ?>
<footer>Developed by <a href="https://xarop.com" target="_blank" rel="noopener noreferrer">xarop.com</a></footer>
</body>
</html>
