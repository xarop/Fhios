# Prueba técnica – Buscador de libros y autores

Resolución del ejercicio planteado en `enunciado.docx` sobre los datos de `dataset.json`.

Contenido:

1. [Resolución estricta del enunciado](#1-resolución-estricta-del-enunciado)
2. [Referencia de la API](#2-referencia-de-la-api)
3. [Frontend de prueba (con y sin JS)](#3-frontend-de-prueba)
4. [Uso de la API desde distintos entornos](#4-uso-de-la-api-desde-distintos-entornos)
5. [Tests](#5-tests)

## Estructura

| Fichero | Descripción |
|---|---|
| `api.php` | **Entregable del enunciado**: webservice PHP |
| `dataset.json` | Datos (debe estar junto a `api.php`) |
| `index.html` | Frontend con JavaScript |
| `sin-js.php` | Frontend sin JavaScript |
| `tests/test.php` | Tests |
| `enunciado.docx` | Enunciado |

Requisitos: PHP 7.0+ con `mbstring`. Arranque rápido:

```bash
php -S localhost:8000
```

---

## 1. Resolución estricta del enunciado

Solo se necesita `api.php` + `dataset.json` en el mismo directorio.

| Requisito | Resolución |
|---|---|
| Webservice PHP que busca libros por título y autor | `api.php` |
| Respuesta JSON con libros y autores por separado (dos arrays) | `{"libros": [...], "autores": [...]}` |
| Entrada por GET: `texto` | `api.php?texto=...` |
| Ampliación: en cada autor, sus dos últimos libros por `fecha_nov` descendente | Campo `ultimos_libros` dentro de cada elemento de `autores` |
| Búsqueda por palabras exactas, sin patrones ni similitudes | Se compara palabra a palabra (ver reglas) |
| `dataset.json` en el mismo directorio que el PHP | Se lee con `__DIR__ . '/dataset.json'` |
| Entrega: un fichero PHP | `api.php` es autocontenido |

Decisiones de interpretación:

- `libros` contiene los libros cuyo **título** coincide; `autores`, los autores cuyo **nombre** coincide.
- Si el texto tiene varias palabras, todas deben aparecer en el campo (`Jorge Franco` → autor Jorge Franco).
- El dataset trae espacios sobrantes en algunos autores (`" Osho"`); se normalizan con `trim`.
- Mayúsculas/minúsculas no influyen; las tildes sí (`Paraiso` ≠ `Paraíso`).

---

## 2. Referencia de la API

`GET /api.php?texto=<texto>`

| Parámetro | Descripción |
|---|---|
| `texto` | Texto a buscar. Vacío o ausente → ambos arrays vacíos |

Respuesta `200 application/json`:

```json
{
  "libros": [
    {
      "titulo": "Humo",
      "autor": "Gabriela Alemán",
      "isbn": "9789588979403",
      "fecha_nov": "20170203",
      "portada": "http://static.megustaleer.com/images/libros_244_x/9789588979403.jpg"
    }
  ],
  "autores": [
    {
      "autor": "Jorge Franco",
      "ultimos_libros": [
        { "titulo": "Mala noche", "autor": "Jorge Franco", "isbn": "9789585428409", "fecha_nov": "20171006", "portada": "..." },
        { "titulo": "Santa suerte", "autor": "Jorge Franco", "isbn": "9789585428140", "fecha_nov": "20170526", "portada": "..." }
      ]
    }
  ]
}
```

Errores:

| Código | Causa | Cuerpo |
|---|---|---|
| 405 | Método distinto de GET | `{"error": "Método no permitido"}` |
| 500 | No se pudo leer `dataset.json` | `{"error": "No se pudo leer dataset.json"}` |

Incluye `Access-Control-Allow-Origin: *`, por lo que puede llamarse desde otro origen.

```bash
curl "http://localhost:8000/api.php?texto=Jorge+Franco"
```

---

## 3. Frontend de prueba

Extras, no requeridos por el enunciado.

- **Con JavaScript** – `index.html` (<http://localhost:8000/>): formulario que llama a la API con `fetch` y pinta libros, autores y el JSON en bruto. Es estático: puede abrirse desde cualquier servidor web.
- **Sin JavaScript** – `sin-js.php` (<http://localhost:8000/sin-js.php>): formulario GET; el HTML se genera en servidor reutilizando `api.php`. Necesita PHP.

---

## 4. Uso de la API desde distintos entornos

En los ejemplos, `BASE` es la URL donde se sirve `api.php` (p. ej. `http://localhost:8000/api.php`).

### PHP (sin framework)

```php
$url = 'http://localhost:8000/api.php?' . http_build_query(['texto' => 'Jorge Franco']);
$respuesta = json_decode(file_get_contents($url), true);

foreach ($respuesta['autores'] as $autor) {
    echo $autor['autor'] . ': ' . count($autor['ultimos_libros']) . " libros\n";
}
```

Con cURL:

```php
$ch = curl_init('http://localhost:8000/api.php?texto=' . urlencode('Humo'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$respuesta = json_decode(curl_exec($ch), true);
curl_close($ch);
```

### Laravel

Cliente HTTP (`app/Http/Controllers/LibrosController.php`):

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LibrosController extends Controller
{
    public function buscar(Request $request)
    {
        $data = Http::get(config('services.libros.url'), ['texto' => $request->query('texto', '')])
            ->throw()
            ->json();

        return view('libros', $data); // variables $libros y $autores
    }
}
```

`config/services.php`:

```php
'libros' => ['url' => env('LIBROS_API_URL', 'http://localhost:8000/api.php')],
```

`routes/web.php`:

```php
Route::get('/libros', [App\Http\Controllers\LibrosController::class, 'buscar']);
```

Vista `resources/views/libros.blade.php`:

```blade
<form method="get"><input name="texto" value="{{ request('texto') }}"><button>Buscar</button></form>

@foreach ($libros as $l)
  <p>{{ $l['titulo'] }} – {{ $l['autor'] }}</p>
@endforeach

@foreach ($autores as $a)
  <h3>{{ $a['autor'] }}</h3>
  @foreach ($a['ultimos_libros'] as $l)
    <p>{{ $l['titulo'] }} ({{ $l['fecha_nov'] }})</p>
  @endforeach
@endforeach
```

Alternativa: copiar `api.php` y `dataset.json` a `public/` y llamarlo en `/api.php`.

### React

```jsx
import { useState } from 'react';

const API = 'http://localhost:8000/api.php';

export default function Buscador() {
  const [texto, setTexto] = useState('');
  const [datos, setDatos] = useState(null);

  async function buscar(e) {
    e.preventDefault();
    const r = await fetch(`${API}?texto=${encodeURIComponent(texto)}`);
    setDatos(await r.json());
  }

  return (
    <>
      <form onSubmit={buscar}>
        <input value={texto} onChange={e => setTexto(e.target.value)} />
        <button>Buscar</button>
      </form>
      {datos && (
        <>
          <h2>Libros</h2>
          {datos.libros.map(l => <p key={l.isbn}>{l.titulo} – {l.autor}</p>)}
          <h2>Autores</h2>
          {datos.autores.map(a => (
            <div key={a.autor}>
              <h3>{a.autor}</h3>
              {a.ultimos_libros.map(l => <p key={l.isbn}>{l.titulo} ({l.fecha_nov})</p>)}
            </div>
          ))}
        </>
      )}
    </>
  );
}
```

La API envía CORS abierto, así que funciona desde `localhost:3000`/`5173`. En desarrollo también puede usarse un proxy (`vite.config.js` → `server.proxy`).

### HTML + JavaScript

```html
<input id="texto"><button id="ok">Buscar</button>
<pre id="salida"></pre>
<script>
  document.getElementById('ok').onclick = async () => {
    const t = document.getElementById('texto').value;
    const r = await fetch('api.php?texto=' + encodeURIComponent(t));
    document.getElementById('salida').textContent = JSON.stringify(await r.json(), null, 2);
  };
</script>
```

Versión completa en `index.html`.

### HTML sin JavaScript

Basta un formulario GET apuntando a una página PHP que consuma la API (ver `sin-js.php`). Si solo se quiere ver el JSON, el formulario puede apuntar directamente a `api.php`:

```html
<form method="get" action="api.php">
  <input name="texto" required>
  <button>Buscar</button>
</form>
```

### Otros

```bash
# curl
curl -G "http://localhost:8000/api.php" --data-urlencode "texto=Jorge Franco"
```

```python
# Python
import requests
print(requests.get("http://localhost:8000/api.php", params={"texto": "Humo"}).json())
```

---

## 5. Tests

```bash
php tests/test.php
```

Comprueban estructura de la respuesta, palabra exacta frente a subcadena, mayúsculas, dos últimos libros ordenados por `fecha_nov`, normalización de espacios en autores, casos sin resultados y rechazo de métodos distintos de GET.
