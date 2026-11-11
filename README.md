# Fhios – Buscador de libros y autores

Webservice en PHP que busca libros por título y autores por nombre sobre `dataset.json`, más un frontend de prueba.

## Estructura

| Fichero | Descripción |
|---|---|
| `api.php` | Webservice (devuelve JSON) |
| `index.html` | Frontend con JavaScript (`fetch` a la API) |
| `sin-js.php` | Frontend sin JavaScript (formulario GET, render en servidor) |
| `tests/test.php` | Tests |
| `dataset.json` | Datos de libros (debe estar en el mismo directorio que `api.php`) |
| `enunciado.docx` | Enunciado de la prueba |

## Requisitos

PHP 7.0+ con la extensión `mbstring`.

## Ejecución

```bash
php -S localhost:8000
```

- Frontend con JS: <http://localhost:8000/>
- Frontend sin JS: <http://localhost:8000/sin-js.php>
- API: <http://localhost:8000/api.php?texto=Jorge+Franco>

## API

### `GET /api.php`

| Parámetro | Tipo | Descripción |
|---|---|---|
| `texto` | string | Texto a buscar |

**Reglas de búsqueda**

- Se divide `texto` en palabras y se comparan palabras exactas (sin patrones ni similitudes).
- No distingue mayúsculas/minúsculas, pero sí tildes (`Paraiso` ≠ `Paraíso`).
- Todas las palabras buscadas deben aparecer completas en el título (libros) o en el nombre (autores).
- Si `texto` está vacío o ausente, ambos arrays se devuelven vacíos.

**Respuesta (200, `application/json`)**

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

- `libros`: libros cuyo título coincide.
- `autores`: autores cuyo nombre coincide; `ultimos_libros` contiene sus dos últimos libros ordenados por `fecha_nov` descendente.

**Errores**

| Código | Causa | Cuerpo |
|---|---|---|
| 405 | Método distinto de GET | `{"error": "Método no permitido"}` |
| 500 | No se pudo leer `dataset.json` | `{"error": "No se pudo leer dataset.json"}` |

La respuesta incluye `Access-Control-Allow-Origin: *` (CORS).

### Ejemplo

```bash
curl "http://localhost:8000/api.php?texto=Humo"
```

## Frontend

`sin-js.php` hace lo mismo sin JavaScript: el formulario envía `texto` por GET y el HTML se genera en servidor reutilizando `api.php`.

`index.html` es un formulario que llama a la API con `fetch`, y muestra la URL consultada, los libros, los autores con sus últimos libros y el JSON en bruto.

## Tests

```bash
php tests/test.php
```

Comprueba estructura de la respuesta, búsqueda por palabra exacta, mayúsculas, últimos libros del autor y casos sin resultados.
