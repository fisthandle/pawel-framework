# PFrame Testing Improvements — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rozbudowa `PFrameTesting.php` o HTTP testing, response/flash/session assertions i RefreshDatabase, żeby nowe projekty na PFrame miały zero boilerplate do testowania kontrolerów.

**Architecture:** Wszystko w jednym pliku `src/PFrameTesting.php` (copy-paste deployment, jak `PFrame.php`). Nowe traity: `HttpTesting`, `ResponseAssertions`, `FlashAssertions`, `SessionAssertions`, `RefreshDatabase`. Każdy trait jest niezależny i composable. `TestCase` integruje wszystkie.

**Tech Stack:** PHP 8.4+, PHPUnit 11, PFrame (App, Request, Response, Csrf, Flash)

---

## Kontekst

### Obecny stan `PFrameTesting.php`
- `DatabaseTransactions` — begin/rollback per test
- `DatabaseAssertions` — assertDatabaseHas/Missing/Count
- `ActingAs` — `$_SESSION['user']` mocking
- `TestCase` — composable base class łączący powyższe
- Importy: `use PFrame\Base;` i `use PHPUnit\Framework\TestCase`

### Kluczowe API PFrame
- `App::handle(Request $request): Response` — full request→response cycle (łapie HttpException i Throwable, zawsze zwraca Response)
- `App::get()`, `App::post()` — rejestracja routów dla GET i POST
- `App::route(string $methods, ...)` — rejestracja routów dla dowolnych metod (PUT, DELETE, PATCH, etc.)
- **App NIE MA `put()`, `delete()`, `patch()`** — używaj `$app->route('PUT', ...)`
- `Request` — named constructor params: `method`, `path`, `query`, `post`, `server`, `headers`, `cookies`, `files`, `ip`, `body`
- `Response` — public properties: `body` (string), `status` (int), `headers` (array)
- `Csrf::FIELD_NAME = 'csrf_token'`, `Csrf::token()` generuje/zwraca token z `$_SESSION`
- `Controller::validateCsrf()` — sprawdza CSRF token z POST data lub X-Csrf-Token header
- `Flash` — zapisuje do `$_SESSION['_flash_messages']` jako `[['type' => ..., 'text' => ...], ...]`
- `Flash::get()` jest destrukcyjny (czyści po odczytaniu) — assertions muszą czytać `$_SESSION` bezpośrednio
- `Flash::SESSION_KEY` jest `private const` — FlashAssertions musi zduplikować klucz (nieuniknione)

### Istniejący wzorzec testów (FullCycleTest.php)
```php
$app = new App();
$app->get('/', TestHomeCtrl::class, 'index');
$response = $app->handle(new Request(method: 'GET', path: '/'));
$this->assertSame(200, $response->status);
```

---

## Uwaga: importy w PFrameTesting.php

Obecnie plik importuje `use PFrame\Base;` i `use PHPUnit\Framework\TestCase`. Nowe traity wymagają dodatkowych importów na początku pliku (wewnątrz `namespace PFrame\Testing`):

```php
use PFrame\App;
use PFrame\Csrf;
use PFrame\Request;
use PFrame\Response;
```

Dodaj je w Task 1 (przy pierwszej modyfikacji pliku).

---

## Task 1: ResponseAssertions trait

Trait z assertions na obiekcie `Response`. Niezależny od `HttpTesting` — można używać z ręcznie zbudowanymi Response'ami.

**Files:**
- Modify: `src/PFrameTesting.php` — dodaj trait `ResponseAssertions`
- Create: `tests/Unit/Testing/ResponseAssertionsTest.php`

### Step 1: Write the failing test

```php
<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit\Testing;

use PFrame\Response;
use PFrame\Testing\ResponseAssertions;
use PHPUnit\Framework\TestCase;

class ResponseAssertionsTest extends TestCase {
    use ResponseAssertions;

    private Response $response;

    // --- Status assertions ---

    public function testAssertStatusPassesOnMatch(): void {
        $this->response = new Response('', 201);
        $this->assertStatus(201);
    }

    public function testAssertStatusFailsOnMismatch(): void {
        $this->response = new Response('', 404);
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertStatus(200);
    }

    public function testAssertOk(): void {
        $this->response = new Response('OK', 200);
        $this->assertOk();
    }

    public function testAssertNotFound(): void {
        $this->response = new Response('', 404);
        $this->assertNotFound();
    }

    public function testAssertForbidden(): void {
        $this->response = new Response('', 403);
        $this->assertForbidden();
    }

    public function testAssertUnauthorized(): void {
        $this->response = new Response('', 401);
        $this->assertUnauthorized();
    }

    // --- Redirect assertions ---

    public function testAssertRedirectPassesOn302(): void {
        $this->response = new Response('', 302, ['Location' => '/dashboard']);
        $this->assertRedirect();
    }

    public function testAssertRedirectPassesOn301(): void {
        $this->response = new Response('', 301, ['Location' => '/new-url']);
        $this->assertRedirect();
    }

    public function testAssertRedirectToPassesOnMatchingUrl(): void {
        $this->response = new Response('', 302, ['Location' => '/login']);
        $this->assertRedirectTo('/login');
    }

    public function testAssertRedirectToFailsOnWrongUrl(): void {
        $this->response = new Response('', 302, ['Location' => '/dashboard']);
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertRedirectTo('/login');
    }

    // --- Body assertions ---

    public function testAssertSeeFindsTextInBody(): void {
        $this->response = new Response('<h1>Welcome back</h1>');
        $this->assertSee('Welcome');
    }

    public function testAssertSeeFailsWhenTextMissing(): void {
        $this->response = new Response('<h1>Hello</h1>');
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertSee('Goodbye');
    }

    public function testAssertDontSeePassesWhenTextMissing(): void {
        $this->response = new Response('<h1>Hello</h1>');
        $this->assertDontSee('Goodbye');
    }

    public function testAssertDontSeeFailsWhenTextPresent(): void {
        $this->response = new Response('<h1>Hello</h1>');
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertDontSee('Hello');
    }

    // --- JSON assertions ---

    public function testAssertJsonPassesOnValidJson(): void {
        $this->response = Response::json(['success' => true, 'count' => 3]);
        $this->assertJson(['success' => true]);
    }

    public function testAssertJsonChecksSubset(): void {
        $this->response = Response::json(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertJson(['a' => 1, 'c' => 3]);
    }

    public function testAssertJsonFailsOnMismatch(): void {
        $this->response = Response::json(['success' => false]);
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertJson(['success' => true]);
    }

    // --- Header assertions ---

    public function testAssertHeaderPassesOnMatch(): void {
        $this->response = new Response('', 200, ['Content-Type' => 'application/json']);
        $this->assertHeader('Content-Type', 'application/json');
    }

    public function testAssertHeaderExistsWithoutValueCheck(): void {
        $this->response = new Response('', 200, ['X-Custom' => 'anything']);
        $this->assertHeader('X-Custom');
    }

    public function testAssertHeaderMissing(): void {
        $this->response = new Response('', 200, []);
        $this->assertHeaderMissing('X-Custom');
    }
}
```

### Step 2: Run test to verify it fails

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/ResponseAssertionsTest.php`
Expected: FAIL — trait `PFrame\Testing\ResponseAssertions` not found

### Step 3: Write implementation

Add to `src/PFrameTesting.php` (after `ActingAs` trait, before `TestCase` class):

```php
trait ResponseAssertions {
    protected Response $response;

    protected function assertStatus(int $expected): void {
        $this->assertSame($expected, $this->response->status,
            "Expected status $expected, got {$this->response->status}");
    }

    protected function assertOk(): void {
        $this->assertStatus(200);
    }

    protected function assertNotFound(): void {
        $this->assertStatus(404);
    }

    protected function assertForbidden(): void {
        $this->assertStatus(403);
    }

    protected function assertUnauthorized(): void {
        $this->assertStatus(401);
    }

    protected function assertRedirect(?string $url = null): void {
        $this->assertTrue(
            $this->response->status >= 300 && $this->response->status < 400,
            "Expected redirect (3xx), got {$this->response->status}"
        );
        if ($url !== null) {
            $this->assertRedirectTo($url);
        }
    }

    protected function assertRedirectTo(string $url): void {
        $this->assertRedirect();
        $location = $this->response->headers['Location'] ?? '';
        $this->assertSame($url, $location,
            "Expected redirect to '$url', got '$location'");
    }

    protected function assertSee(string $text): void {
        $this->assertStringContainsString($text, $this->response->body,
            "Response body does not contain '$text'");
    }

    protected function assertDontSee(string $text): void {
        $this->assertStringNotContainsString($text, $this->response->body,
            "Response body should not contain '$text'");
    }

    protected function assertJson(array $expected): void {
        $actual = json_decode($this->response->body, true);
        $this->assertNotNull($actual, 'Response body is not valid JSON');
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual, "JSON missing key '$key'");
            $this->assertSame($value, $actual[$key],
                "JSON key '$key': expected " . var_export($value, true) . ", got " . var_export($actual[$key], true));
        }
    }

    protected function assertHeader(string $name, ?string $value = null): void {
        $this->assertArrayHasKey($name, $this->response->headers,
            "Header '$name' not found in response");
        if ($value !== null) {
            $this->assertSame($value, $this->response->headers[$name],
                "Header '$name': expected '$value', got '{$this->response->headers[$name]}'");
        }
    }

    protected function assertHeaderMissing(string $name): void {
        $this->assertArrayNotHasKey($name, $this->response->headers,
            "Header '$name' should not be present");
    }
}
```

### Step 4: Run test to verify it passes

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/ResponseAssertionsTest.php`
Expected: All 17 tests PASS

### Step 5: Commit

```bash
git add src/PFrameTesting.php tests/Unit/Testing/ResponseAssertionsTest.php
git commit -m "feat(testing): add ResponseAssertions trait"
```

---

## Task 2: HttpTesting trait

Trait do symulacji HTTP requestów. Buduje `Request`, woła `App::handle()`, zapisuje wynik w `$this->response`. Automatycznie wstrzykuje CSRF token do POST/PUT/PATCH/DELETE.

**Files:**
- Modify: `src/PFrameTesting.php` — dodaj trait `HttpTesting`
- Create: `tests/Unit/Testing/HttpTestingTest.php`

**Zależności:** Wymaga `ResponseAssertions` (zapisuje do `$this->response`).

### Step 1: Write the failing test

```php
<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit\Testing;

use PFrame\App;
use PFrame\Controller;
use PFrame\Csrf;
use PFrame\HttpException;
use PFrame\Request;
use PFrame\Response;
use PFrame\Testing\HttpTesting;
use PFrame\Testing\ResponseAssertions;
use PHPUnit\Framework\TestCase;

class HttpTestingTest extends TestCase {
    use HttpTesting, ResponseAssertions;

    protected App $app;

    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
        $this->app = new App();
        $this->app->get('/', HttpTestingHomeCtrl::class, 'index');
        $this->app->get('/user/{id}', HttpTestingUserCtrl::class, 'show');
        $this->app->post('/submit', HttpTestingFormCtrl::class, 'store');
        $this->app->route('PUT', '/item/{id}', HttpTestingFormCtrl::class, 'update');
        $this->app->route('DELETE', '/item/{id}', HttpTestingFormCtrl::class, 'destroy');
        $this->app->get('/json', HttpTestingJsonCtrl::class, 'index');
    }

    protected function tearDown(): void {
        $_SESSION = [];
        parent::tearDown();
    }

    // --- GET ---

    public function testGetReturnsResponse(): void {
        $this->get('/');
        $this->assertOk();
        $this->assertSee('Welcome');
    }

    public function testGetWithQueryParams(): void {
        $this->get('/user/42');
        $this->assertOk();
        $this->assertSee('User 42');
    }

    public function testGet404(): void {
        $this->get('/nonexistent');
        $this->assertNotFound();
    }

    // --- POST with auto-CSRF ---

    public function testPostInjectsCsrfAutomatically(): void {
        $this->post('/submit', ['title' => 'Test']);
        $this->assertOk();
        $this->assertSee('title=Test');
    }

    public function testPostWithoutCsrfFails(): void {
        $this->withoutCsrf()->post('/submit', ['title' => 'Test']);
        $this->assertForbidden();
    }

    // --- PUT, DELETE ---

    public function testPutInjectsCsrf(): void {
        $this->put('/item/5', ['name' => 'Updated']);
        $this->assertOk();
        $this->assertSee('updated 5');
    }

    public function testDeleteInjectsCsrf(): void {
        $this->delete('/item/5');
        $this->assertOk();
        $this->assertSee('deleted 5');
    }

    // --- JSON ---

    public function testGetJsonResponse(): void {
        $this->get('/json');
        $this->assertOk();
        $this->assertJson(['items' => [1, 2, 3]]);
    }

    // --- Headers ---

    public function testWithHeadersSendsCustomHeaders(): void {
        $this->withHeaders(['X-Custom' => 'test'])->get('/');
        $this->assertOk();
    }

    // --- AJAX ---

    public function testAsAjaxSetsXmlHttpRequest(): void {
        $this->asAjax()->get('/');
        $this->assertOk();
    }

    // --- Chaining resets ---

    public function testWithoutCsrfResetsAfterRequest(): void {
        $this->withoutCsrf()->post('/submit', ['title' => 'X']);
        $this->assertForbidden();

        // Next request should have CSRF again
        $this->post('/submit', ['title' => 'Y']);
        $this->assertOk();
    }
}

// --- Test controllers (defined inline) ---

class HttpTestingHomeCtrl extends Controller {
    public function index(): Response {
        return new Response('Welcome');
    }
}

class HttpTestingUserCtrl extends Controller {
    public function show(): Response {
        return new Response('User ' . $this->param('id'));
    }
}

class HttpTestingFormCtrl extends Controller {
    public function beforeRoute(): void {
        $this->validateCsrf();
    }

    public function store(): Response {
        return new Response('title=' . $this->request->post('title'));
    }

    public function update(): Response {
        return new Response('updated ' . $this->param('id'));
    }

    public function destroy(): Response {
        return new Response('deleted ' . $this->param('id'));
    }
}

class HttpTestingJsonCtrl extends Controller {
    public function index(): Response {
        return Response::json(['items' => [1, 2, 3]]);
    }
}
```

### Step 2: Run test to verify it fails

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/HttpTestingTest.php`
Expected: FAIL — trait `PFrame\Testing\HttpTesting` not found

### Step 3: Write implementation

Add to `src/PFrameTesting.php` (after `ResponseAssertions`, before `TestCase`):

```php
trait HttpTesting {
    protected App $app;
    private bool $withCsrf = true;
    private array $extraHeaders = [];

    protected function get(string $path, array $query = []): Response {
        return $this->call('GET', $path, query: $query);
    }

    protected function post(string $path, array $data = []): Response {
        return $this->call('POST', $path, post: $data);
    }

    protected function put(string $path, array $data = []): Response {
        return $this->call('PUT', $path, post: $data);
    }

    protected function patch(string $path, array $data = []): Response {
        return $this->call('PATCH', $path, post: $data);
    }

    protected function delete(string $path, array $data = []): Response {
        return $this->call('DELETE', $path, post: $data);
    }

    protected function postJson(string $path, array $data = []): Response {
        $this->extraHeaders['Content-Type'] = 'application/json';
        $this->extraHeaders['X-Requested-With'] = 'XMLHttpRequest';
        return $this->call('POST', $path, body: json_encode($data, JSON_THROW_ON_ERROR));
    }

    protected function withHeaders(array $headers): static {
        $this->extraHeaders = array_merge($this->extraHeaders, $headers);
        return $this;
    }

    protected function withoutCsrf(): static {
        $this->withCsrf = false;
        return $this;
    }

    protected function asAjax(): static {
        $this->extraHeaders['X-Requested-With'] = 'XMLHttpRequest';
        return $this;
    }

    protected function call(
        string $method,
        string $path,
        array $query = [],
        array $post = [],
        string $body = '',
    ): Response {
        $method = strtoupper($method);

        if ($this->withCsrf && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $post[Csrf::FIELD_NAME] ??= Csrf::token();
        }

        $request = new Request(
            method: $method,
            path: $path,
            query: $query,
            post: $post,
            headers: $this->extraHeaders,
            body: $body,
        );

        try {
            $this->response = $this->app->handle($request);
        } finally {
            // Reset per-request state even if handle() throws
            $this->withCsrf = true;
            $this->extraHeaders = [];
        }

        return $this->response;
    }
}
```

### Step 4: Run test to verify it passes

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/HttpTestingTest.php`
Expected: All 11 tests PASS

### Step 5: Commit

```bash
git add src/PFrameTesting.php tests/Unit/Testing/HttpTestingTest.php
git commit -m "feat(testing): add HttpTesting trait with auto-CSRF"
```

---

## Task 3: FlashAssertions trait

Assertions na flash messages. Czyta bezpośrednio z `$_SESSION['_flash_messages']` (nie przez `Flash::get()` bo ten jest destrukcyjny).

**Files:**
- Modify: `src/PFrameTesting.php` — dodaj trait `FlashAssertions`
- Create: `tests/Unit/Testing/FlashAssertionsTest.php`

### Step 1: Write the failing test

```php
<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit\Testing;

use PFrame\Flash;
use PFrame\Testing\FlashAssertions;
use PHPUnit\Framework\TestCase;

class FlashAssertionsTest extends TestCase {
    use FlashAssertions;

    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testAssertFlashFindsMessageByType(): void {
        (new Flash())->success('Record saved');
        $this->assertFlash('success', 'Record saved');
    }

    public function testAssertFlashFindsMessageByTypeOnly(): void {
        (new Flash())->error('Something failed');
        $this->assertFlash('error');
    }

    public function testAssertFlashFailsWhenNoMessages(): void {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertFlash('success');
    }

    public function testAssertFlashFailsWhenWrongType(): void {
        (new Flash())->warning('Watch out');
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertFlash('error');
    }

    public function testAssertFlashFailsWhenWrongText(): void {
        (new Flash())->success('Saved');
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertFlash('success', 'Deleted');
    }

    public function testAssertNoFlashPassesWhenEmpty(): void {
        $this->assertNoFlash();
    }

    public function testAssertNoFlashPassesWhenTypeAbsent(): void {
        (new Flash())->success('OK');
        $this->assertNoFlash('error');
    }

    public function testAssertNoFlashFailsWhenTypePresent(): void {
        (new Flash())->error('Bad');
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertNoFlash('error');
    }

    public function testAssertNoFlashFailsWhenAnyPresent(): void {
        (new Flash())->info('Note');
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertNoFlash();
    }

    public function testMultipleFlashMessages(): void {
        $flash = new Flash();
        $flash->success('First');
        $flash->success('Second');
        $flash->error('Oops');

        $this->assertFlash('success', 'First');
        $this->assertFlash('success', 'Second');
        $this->assertFlash('error', 'Oops');
        $this->assertNoFlash('warning');
    }
}
```

### Step 2: Run test to verify it fails

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/FlashAssertionsTest.php`
Expected: FAIL — trait not found

### Step 3: Write implementation

Add to `src/PFrameTesting.php`:

```php
trait FlashAssertions {
    private const FLASH_SESSION_KEY = '_flash_messages';

    protected function assertFlash(string $type, ?string $text = null): void {
        $messages = $_SESSION[self::FLASH_SESSION_KEY] ?? [];
        $found = false;
        foreach ($messages as $msg) {
            if ($msg['type'] === $type) {
                if ($text === null || $msg['text'] === $text) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue($found,
            $text !== null
                ? "No flash message of type '$type' with text '$text'"
                : "No flash message of type '$type'"
        );
    }

    protected function assertNoFlash(?string $type = null): void {
        $messages = $_SESSION[self::FLASH_SESSION_KEY] ?? [];
        if ($type === null) {
            $this->assertEmpty($messages, 'Expected no flash messages, but found ' . count($messages));
            return;
        }
        foreach ($messages as $msg) {
            if ($msg['type'] === $type) {
                $this->fail("Unexpected flash message of type '$type': '{$msg['text']}'");
            }
        }
    }
}
```

### Step 4: Run test to verify it passes

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/FlashAssertionsTest.php`
Expected: All 10 tests PASS

### Step 5: Commit

```bash
git add src/PFrameTesting.php tests/Unit/Testing/FlashAssertionsTest.php
git commit -m "feat(testing): add FlashAssertions trait"
```

---

## Task 4: SessionAssertions trait

Assertions na stan sesji — autentykacja i dowolne klucze.

**Files:**
- Modify: `src/PFrameTesting.php` — dodaj trait `SessionAssertions`
- Create: `tests/Unit/Testing/SessionAssertionsTest.php`

### Step 1: Write the failing test

```php
<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit\Testing;

use PFrame\Testing\SessionAssertions;
use PHPUnit\Framework\TestCase;

class SessionAssertionsTest extends TestCase {
    use SessionAssertions;

    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testAssertAuthenticatedPassesWhenUserSet(): void {
        $_SESSION['user'] = ['id' => 1, 'name' => 'Joe'];
        $this->assertAuthenticated();
    }

    public function testAssertAuthenticatedFailsWhenGuest(): void {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertAuthenticated();
    }

    public function testAssertGuestPassesWhenNoUser(): void {
        $this->assertGuest();
    }

    public function testAssertGuestFailsWhenUserSet(): void {
        $_SESSION['user'] = ['id' => 1];
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertGuest();
    }

    public function testAssertSessionHasChecksKey(): void {
        $_SESSION['cart'] = [1, 2, 3];
        $this->assertSessionHas('cart');
    }

    public function testAssertSessionHasChecksKeyAndValue(): void {
        $_SESSION['locale'] = 'pl';
        $this->assertSessionHas('locale', 'pl');
    }

    public function testAssertSessionHasFailsOnMissing(): void {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertSessionHas('missing');
    }

    public function testAssertSessionHasFailsOnWrongValue(): void {
        $_SESSION['locale'] = 'en';
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertSessionHas('locale', 'pl');
    }

    public function testAssertSessionMissingPasses(): void {
        $this->assertSessionMissing('nonexistent');
    }

    public function testAssertSessionMissingFailsWhenPresent(): void {
        $_SESSION['key'] = 'value';
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertSessionMissing('key');
    }
}
```

### Step 2: Run test to verify it fails

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/SessionAssertionsTest.php`
Expected: FAIL — trait not found

### Step 3: Write implementation

Add to `src/PFrameTesting.php`:

```php
trait SessionAssertions {
    protected function assertAuthenticated(): void {
        $this->assertNotEmpty($_SESSION['user'] ?? null, 'Expected authenticated user, but session has no user');
    }

    protected function assertGuest(): void {
        $this->assertEmpty($_SESSION['user'] ?? null, 'Expected guest, but session has user');
    }

    protected function assertSessionHas(string $key, mixed ...$value): void {
        $this->assertArrayHasKey($key, $_SESSION, "Session missing key '$key'");
        if ($value !== []) {
            $this->assertSame($value[0], $_SESSION[$key],
                "Session key '$key': expected " . var_export($value[0], true) . ", got " . var_export($_SESSION[$key], true));
        }
    }

    protected function assertSessionMissing(string $key): void {
        $this->assertArrayNotHasKey($key, $_SESSION, "Session should not have key '$key'");
    }
}
```

### Step 4: Run test to verify it passes

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/SessionAssertionsTest.php`
Expected: All 10 tests PASS

### Step 5: Commit

```bash
git add src/PFrameTesting.php tests/Unit/Testing/SessionAssertionsTest.php
git commit -m "feat(testing): add SessionAssertions trait"
```

---

## Task 5: RefreshDatabase trait

Ładuje migracje z katalogu SQL raz per proces (static flag). Wykonuje się przed `DatabaseTransactions::begin()`.

**Files:**
- Modify: `src/PFrameTesting.php` — dodaj trait `RefreshDatabase`
- Create: `tests/Unit/Testing/RefreshDatabaseTest.php`
- Create: `tests/fixtures/migrations/001-users.sql`
- Create: `tests/fixtures/migrations/002-posts.sql`

### Step 1: Create test fixture migrations

`tests/fixtures/migrations/001-users.sql`:
```sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL
);
```

`tests/fixtures/migrations/002-posts.sql`:
```sql
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Step 2: Write the failing test

```php
<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit\Testing;

use PFrame\App;
use PFrame\Base;
use PFrame\Db;
use PFrame\Testing\RefreshDatabase;
use PFrame\Testing\DatabaseTransactions;
use PHPUnit\Framework\TestCase;

class RefreshDatabaseTest extends TestCase {
    use RefreshDatabase, DatabaseTransactions;

    protected function migrationPath(): string {
        return __DIR__ . '/../../fixtures/migrations';
    }

    protected function setUp(): void {
        // Ensure DB exists (SQLite :memory: via App singleton)
        if (App::instance()->config('db') === null) {
            $app = new App();
            $app->setDb(new Db(['dsn' => 'sqlite::memory:']));
        }
        $this->bootRefreshDatabase();
        parent::setUp();
        $this->setUpDatabaseTransactions();
    }

    protected function tearDown(): void {
        $this->tearDownDatabaseTransactions();
        parent::tearDown();
    }

    public function testMigrationsCreateTables(): void {
        $tables = Base::col("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $this->assertContains('users', $tables);
        $this->assertContains('posts', $tables);
    }

    public function testCanInsertAndQueryMigratedTable(): void {
        Base::exec("INSERT INTO users (name, email) VALUES (?, ?)", ['Joe', 'joe@x.com']);
        $row = Base::row("SELECT * FROM users WHERE email = ?", ['joe@x.com']);
        $this->assertSame('Joe', $row['name']);
    }

    public function testTransactionRollbackKeepsSchemaButClearsData(): void {
        // Insert data — will be rolled back by tearDown
        Base::exec("INSERT INTO users (name, email) VALUES (?, ?)", ['Ghost', 'ghost@x.com']);
        $this->assertSame(1, (int) Base::var("SELECT COUNT(*) FROM users WHERE name = ?", ['Ghost']));
        // After tearDown+setUp, this row will be gone but tables remain
        // (verified by testMigrationsCreateTables running independently)
    }

    public function testBootIsIdempotent(): void {
        // Call boot again — should be no-op (static flag)
        $this->bootRefreshDatabase();
        $tables = Base::col("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $this->assertContains('users', $tables);
    }
}
```

### Step 3: Run test to verify it fails

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/RefreshDatabaseTest.php`
Expected: FAIL — trait not found

### Step 4: Write implementation

Add to `src/PFrameTesting.php`:

```php
trait RefreshDatabase {
    private static bool $migrated = false;

    abstract protected function migrationPath(): string;

    protected function bootRefreshDatabase(): void {
        if (self::$migrated) {
            return;
        }

        $path = $this->migrationPath();
        $files = glob($path . '/*.sql');
        if ($files === false || $files === []) {
            throw new \RuntimeException("No SQL files found in '$path'");
        }
        sort($files, SORT_NATURAL);

        $db = Base::db();
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }
            $db->pdo()->exec($sql);
        }

        self::$migrated = true;
    }
}
```

**Uwaga:** Trait NIE robi `new App()` ani `setDb()` — to jest odpowiedzialność bootstrapa projektu lub setUp() testu. Trait tylko ładuje pliki SQL.

### Step 5: Run test to verify it passes

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit tests/Unit/Testing/RefreshDatabaseTest.php`
Expected: All 4 tests PASS

### Step 6: Commit

```bash
git add src/PFrameTesting.php tests/Unit/Testing/RefreshDatabaseTest.php tests/fixtures/migrations/
git commit -m "feat(testing): add RefreshDatabase trait"
```

---

## Task 6: Integrate all traits into TestCase

Update `TestCase` to use all new traits. Zachowaj backward compatibility — istniejące testy nie mogą się zepsuć.

**Files:**
- Modify: `src/PFrameTesting.php` — update class `TestCase`
- Modify: `tests/Unit/Testing/TestCaseIntegrationTest.php` — rozszerz istniejący test

### Step 1: Read existing TestCaseIntegrationTest

Run: `cat tests/Unit/Testing/TestCaseIntegrationTest.php`
— understand current test, extend it.

### Step 2: Write failing test additions

Dodaj do `TestCaseIntegrationTest` (lub nowy plik jeśli lepiej):

```php
// Verify TestCase now has all traits
public function testTestCaseHasResponseAssertions(): void {
    $this->assertTrue(method_exists($this, 'assertOk'));
    $this->assertTrue(method_exists($this, 'assertRedirect'));
    $this->assertTrue(method_exists($this, 'assertSee'));
}

public function testTestCaseHasFlashAssertions(): void {
    $this->assertTrue(method_exists($this, 'assertFlash'));
    $this->assertTrue(method_exists($this, 'assertNoFlash'));
}

public function testTestCaseHasSessionAssertions(): void {
    $this->assertTrue(method_exists($this, 'assertAuthenticated'));
    $this->assertTrue(method_exists($this, 'assertGuest'));
    $this->assertTrue(method_exists($this, 'assertSessionHas'));
}
```

### Step 3: Update TestCase class

```php
class TestCase extends PHPUnitTestCase {
    use DatabaseTransactions, DatabaseAssertions, ActingAs;
    use ResponseAssertions, FlashAssertions, SessionAssertions;

    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
        $this->setUpDatabaseTransactions();
    }

    protected function tearDown(): void {
        $this->tearDownDatabaseTransactions();
        $_SESSION = [];
        parent::tearDown();
    }
}
```

**Uwaga:** `HttpTesting` i `RefreshDatabase` NIE wchodzą do `TestCase` — wymagają konfiguracji (`$this->app`, `migrationPath()`). Projekty dodają je w swoim TestCase.

### Step 4: Run all tests

Run: `cd /home/pawel/dev/pframe && vendor/bin/phpunit`
Expected: All tests PASS (istniejące + nowe)

### Step 5: Commit

```bash
git add src/PFrameTesting.php tests/Unit/Testing/TestCaseIntegrationTest.php
git commit -m "feat(testing): integrate new traits into TestCase"
```

---

## Task 7: Update docs

**Files:**
- Modify: `docs/testing-philosophy.md` — dodaj sekcje o nowych traitach

### Step 1: Update testing-philosophy.md

Dodaj sekcje:

```markdown
## HTTP Testing

    class UserTest extends \PFrame\Testing\TestCase {
        use \PFrame\Testing\HttpTesting;

        protected App $app;

        protected function setUp(): void {
            parent::setUp();
            $this->app = new App();
            // register routes...
        }

        public function testUserList(): void {
            $this->get('/users');
            $this->assertOk();
            $this->assertSee('Users');
        }

        public function testCreateUser(): void {
            $this->actingAs(['id' => 1, 'role' => 'admin']);
            $this->post('/users', ['name' => 'Joe', 'email' => 'joe@x.com']);
            $this->assertRedirectTo('/users');
            $this->assertFlash('success', 'User created');
            $this->assertDatabaseHas('users', ['email' => 'joe@x.com']);
        }
    }

CSRF jest wstrzykiwany automatycznie do POST/PUT/PATCH/DELETE.
Opt-out: `$this->withoutCsrf()->post(...)`.

## Response assertions

    $this->assertOk();                           // status 200
    $this->assertNotFound();                     // status 404
    $this->assertForbidden();                    // status 403
    $this->assertUnauthorized();                 // status 401
    $this->assertStatus(201);                    // exact status
    $this->assertRedirect();                     // 3xx
    $this->assertRedirectTo('/login');            // 3xx + Location header
    $this->assertSee('Welcome');                 // body contains
    $this->assertDontSee('Error');               // body does not contain
    $this->assertJson(['success' => true]);      // JSON subset match
    $this->assertHeader('Content-Type', 'application/json');
    $this->assertHeaderMissing('X-Debug');

## Flash assertions

    $this->assertFlash('success', 'Saved');      // type + text
    $this->assertFlash('error');                  // type only
    $this->assertNoFlash('error');                // no flash of type
    $this->assertNoFlash();                       // no flash at all

## Session assertions

    $this->assertAuthenticated();                 // $_SESSION['user'] set
    $this->assertGuest();                         // $_SESSION['user'] empty
    $this->assertSessionHas('locale', 'pl');      // key + value
    $this->assertSessionHas('cart');              // key only
    $this->assertSessionMissing('temp');          // key absent

## RefreshDatabase

Trait do automatycznego ładowania migracji z katalogu SQL. Raz per proces (nie per test).

    class TestCase extends \PFrame\Testing\TestCase {
        use \PFrame\Testing\RefreshDatabase;

        protected function migrationPath(): string {
            return __DIR__ . '/../db/migrations';
        }

        protected function setUp(): void {
            $this->bootRefreshDatabase();  // przed parent::setUp() (przed begin())
            parent::setUp();
        }
    }

## Composable traity

| Trait | Wymaga | W TestCase |
|-------|--------|------------|
| DatabaseTransactions | Base::db() | ✅ |
| DatabaseAssertions | Base::db() | ✅ |
| ActingAs | — | ✅ |
| ResponseAssertions | $this->response | ✅ |
| FlashAssertions | $_SESSION | ✅ |
| SessionAssertions | $_SESSION | ✅ |
| HttpTesting | $this->app (App) | ❌ (wymaga config) |
| RefreshDatabase | Base::db() + migrationPath() | ❌ (wymaga config) |
```

### Step 2: Commit

```bash
git add docs/testing-philosophy.md
git commit -m "docs: update testing philosophy with new traits"
```

---

## Podsumowanie architektury

```
PFrameTesting.php (jeden plik):
├── trait DatabaseTransactions    [istniejący]
├── trait DatabaseAssertions      [istniejący]
├── trait ActingAs                [istniejący]
├── trait ResponseAssertions      [NOWY — Task 1]
├── trait HttpTesting             [NOWY — Task 2]
├── trait FlashAssertions         [NOWY — Task 3]
├── trait SessionAssertions       [NOWY — Task 4]
├── trait RefreshDatabase         [NOWY — Task 5]
└── class TestCase                [update — Task 6]
        uses: DatabaseTransactions, DatabaseAssertions, ActingAs,
              ResponseAssertions, FlashAssertions, SessionAssertions
        (HttpTesting + RefreshDatabase = opt-in per projekt)
```

Użycie w projekcie:
```php
// tests/TestCase.php
class TestCase extends \PFrame\Testing\TestCase {
    use \PFrame\Testing\HttpTesting;
    use \PFrame\Testing\RefreshDatabase;

    protected App $app;

    protected function migrationPath(): string {
        return __DIR__ . '/../db/migrations';
    }

    protected function setUp(): void {
        $this->app = new App();
        // register routes or loadConfig...
        $this->bootRefreshDatabase();
        parent::setUp();
    }

    // Factory methods per projekt
    protected function createUser(array $overrides = []): int { ... }
}
```
