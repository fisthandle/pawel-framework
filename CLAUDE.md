# PFrame

Single-file PHP 8.4+ micro-framework. Zero dependencies, copy-paste deployment.

## Architektura

- **Jeden plik:** `src/PFrame.php` — cały framework (~1650 LOC)
- **Namespace:** `PFrame` (klasy) + globalne helpery w `namespace {}`
- **Brak mail:** do maili używamy PHPMailer (zewnętrznie)
- **Fasada:** `PFrame\Base` — projekty definiują `class P1 extends \PFrame\Base` z project-specific stałymi

## Styl kodu

- **1TBS** (One True Brace Style) — klamra otwierająca w tej samej linii
- `declare(strict_types=1)` wszędzie
- PSR-12 z wyjątkiem 1TBS dla klas/metod
- Typy wszędzie, `===` zamiast `==`

## Klasy (PFrame namespace)

HttpException, Request, Response, App, Db, View, Session, Csrf, Flash, Controller, Log, Validator, Cache, Base (fasada)

## Globalne helpery

`h()`, `ha()`, `getS()`, `strlenS()`, `substrS()`, `trimS()`, `strtotimeS()`, `strip_tagsS()`, `countS()`, `explodeS()`

Konwencja: `nazwaS()` = null-safe wrapper na oryginalną funkcję PHP.

## Testy

```bash
vendor/bin/phpunit              # wszystkie
vendor/bin/phpunit tests/Unit   # tylko unit
```

## DB

- `db/sessions.sql` — schemat sesji (MySQL)
- Session handler wspiera SQLite (INSERT OR REPLACE) i MySQL (ON DUPLICATE KEY)
- Logowanie zapytań SQL jest opcjonalne: ustaw `db.log_queries` w configu na `true`

## Bezpieczeństwo i obsługa błędów

- Globalny handler błędów zamienia ostrzeżenia na 500, a błędy fatalne są łapane przez shutdown handler
- Router zwraca `405 Method Not Allowed` z nagłówkiem `Allow`
- `App::addSecurityHeaders()` dodaje bezpieczne nagłówki (CSP, HSTS dla HTTPS, XFO, XCTO, Referrer-Policy, Permissions-Policy)
- `Request::fromGlobalsWithProxies()` i `app` config `trusted_proxies` umożliwiają bezpieczne IP za proxy
- `Session::register()` utwardza konfigurację sesji; `Session::regenerate()` służy do regeneracji ID po logowaniu
- `Response::redirect()` blokuje external URL gdy HTTP_HOST jest ustawiony
- `View::renderFile()` chroni przed path traversal (separator `/` w prefix check)
