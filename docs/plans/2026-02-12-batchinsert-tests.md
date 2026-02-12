# Plan: Testy batchInsert + uzupełnienie pokrycia Db

Data: 2026-02-12
Status: reviewed (Codex xhigh)

## Kontekst

`Db::batchInsert()` dodano z domownik — jedyna metoda Db bez testów.
`Base::batchInsert()` proxy — brak w BaseFacadeTest.
Security audit: 0 critical, framework bezpieczny.
Code simplifier: kod czysty, brak zmian.

## Zadania

Plik: `tests/Unit/DbTest.php` (Tasks 1-7)
Plik: `tests/Unit/BaseFacadeTest.php` (Task 8)

### Task 1: Happy path

```php
public function testBatchInsertBasic(): void
{
    $this->db->batchInsert('users', ['name', 'email'], [
        ['Zed', 'zed@x.com'],
        ['Kai', 'kai@x.com'],
    ]);
    $this->assertSame(4, (int) $this->db->var('SELECT COUNT(*) FROM users'));
    $this->assertSame('Zed', $this->db->var('SELECT name FROM users WHERE email = ?', 'zed@x.com'));
}
```

### Task 2: Edge cases — empty input

```php
public function testBatchInsertEmptyRowsIsNoop(): void
{
    $this->db->batchInsert('users', ['name', 'email'], []);
    $this->assertSame(2, (int) $this->db->var('SELECT COUNT(*) FROM users'));
}

public function testBatchInsertEmptyColumnsIsNoop(): void
{
    $this->db->batchInsert('users', [], [['Joe', 'j@x.com']]);
    $this->assertSame(2, (int) $this->db->var('SELECT COUNT(*) FROM users'));
}
```

### Task 3: Chunking — query log proves multiple INSERTs

Użyj Db z `log_queries => true`. 400 rows * 3 cols = 1200 params > 999 limit.
`floor(999/3) = 333` → 2 chunki (333 + 67).

```php
public function testBatchInsertChunksAtSqliteLimit(): void
{
    $db = new Db(['dsn' => 'sqlite::memory:', 'log_queries' => true]);
    $db->exec('CREATE TABLE bulk (a INTEGER NOT NULL, b TEXT NOT NULL, c INTEGER NOT NULL)');

    $rows = [];
    for ($i = 1; $i <= 400; $i++) {
        $rows[] = [$i, 'row-' . $i, $i * 10];
    }

    $db->batchInsert('bulk', ['a', 'b', 'c'], $rows);

    $this->assertSame(400, (int) $db->var('SELECT COUNT(*) FROM bulk'));
    $this->assertSame('row-1', $db->var('SELECT b FROM bulk WHERE a = ?', [1]));
    $this->assertSame(4000, (int) $db->var('SELECT c FROM bulk WHERE a = ?', [400]));

    // Prove chunking: CREATE + 2x INSERT + 2x SELECT = 5 queries minimum
    // batchInsert should produce exactly 2 INSERT queries
    $insertCount = 0;
    foreach ($db->queryLog() as $entry) {
        if (str_starts_with($entry['sql'], 'INSERT INTO bulk')) {
            $insertCount++;
        }
    }
    $this->assertSame(2, $insertCount);
}
```

### Task 4: Mode INSERT OR IGNORE

```php
public function testBatchInsertWithInsertOrIgnore(): void
{
    $this->db->exec('CREATE TABLE uniq (id INTEGER PRIMARY KEY, val TEXT)');
    $this->db->exec('INSERT INTO uniq (id, val) VALUES (1, ?)', ['existing']);

    $this->db->batchInsert('uniq', ['id', 'val'], [
        [1, 'duplicate'],
        [2, 'new'],
    ], 'INSERT OR IGNORE');

    $this->assertSame(2, (int) $this->db->var('SELECT COUNT(*) FROM uniq'));
    $this->assertSame('existing', $this->db->var('SELECT val FROM uniq WHERE id = ?', [1]));
    $this->assertSame('new', $this->db->var('SELECT val FROM uniq WHERE id = ?', [2]));
}
```

### Task 5: Mode REPLACE

```php
public function testBatchInsertWithReplace(): void
{
    $this->db->exec('CREATE TABLE rep (id INTEGER PRIMARY KEY, val TEXT)');
    $this->db->exec('INSERT INTO rep (id, val) VALUES (1, ?)', ['old']);

    $this->db->batchInsert('rep', ['id', 'val'], [
        [1, 'new'],
        [2, 'added'],
    ], 'REPLACE');

    $this->assertSame(2, (int) $this->db->var('SELECT COUNT(*) FROM rep'));
    $this->assertSame('new', $this->db->var('SELECT val FROM rep WHERE id = ?', [1]));
}
```

### Task 6: NULL values + single column

```php
public function testBatchInsertWithNullValues(): void
{
    $this->db->batchInsert('users', ['name', 'email'], [
        ['Nil', null],
    ]);
    $this->assertSame(3, (int) $this->db->var('SELECT COUNT(*) FROM users'));
    $this->assertNull($this->db->var('SELECT email FROM users WHERE name = ?', 'Nil'));
}

public function testBatchInsertSingleColumn(): void
{
    $this->db->exec('CREATE TABLE tags (name TEXT NOT NULL)');

    $rows = array_map(fn($i) => ['tag-' . $i], range(1, 50));
    $this->db->batchInsert('tags', ['name'], $rows);

    $this->assertSame(50, (int) $this->db->var('SELECT COUNT(*) FROM tags'));
}
```

### Task 7: Row too long throws PDOException

```php
public function testBatchInsertRowTooLongThrows(): void
{
    $this->expectException(\PDOException::class);
    $this->db->batchInsert('users', ['name', 'email'], [
        ['Joe', 'j@x.com', 'extra'],
    ]);
}
```

### Task 8: Base facade proxy

Plik: `tests/Unit/BaseFacadeTest.php`

Dodaj na KOŃCU `testDbShortcutsAndFlash()` (po linii 46):

```php
Base::batchInsert('items', ['name'], [['c'], ['d']]);
$this->assertSame(4, Base::var('SELECT COUNT(*) FROM items'));
```

### Task 9: Weryfikacja

```bash
vendor/bin/phpunit tests/Unit/DbTest.php tests/Unit/BaseFacadeTest.php
```

## Codex review feedback (zastosowane)

- [x] Chunking test: dodano query logging + assert 2 INSERT statements
- [x] REPLACE mode: dodano Task 5
- [x] NULL values: dodano test w Task 6
- [x] Row too long: dodano Task 7 (PDOException)
- [x] Facade test placement: explicit "na KOŃCU" po linii 46
- [ ] Associative rows — pominięto: to zachowanie PHP foreach, nie bug frameworka
- [ ] Transaction + rollback multi-chunk — pominięto: batchInsert nie zarządza transakcjami celowo (to odpowiedzialność callera)
