<?php
declare(strict_types=1);

namespace P1\Tests\Unit;

use P1\App;
use P1\Db;
use P1\P1;
use PHPUnit\Framework\TestCase;

class P1FacadeTest extends TestCase {
    public function testAppReturnsInstance(): void {
        $app = new App();
        $this->assertSame($app, P1::app());
    }

    public function testConfigViaFacade(): void {
        $app = new App();
        $app->setConfig('test_key', 'test_val');
        $this->assertSame('test_val', P1::config('test_key'));
    }

    public function testClassAlias(): void {
        if (!class_exists('P1Alias', false)) {
            class_alias(P1::class, 'P1Alias');
        }

        new App();
        $this->assertSame(P1::app(), \P1Alias::app());
    }

    public function testDbShortcutsAndFlash(): void {
        $app = new App();
        $db = new Db(['dsn' => 'sqlite::memory:']);
        $db->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
        $app->setDb($db);

        P1::exec('INSERT INTO items (name) VALUES (?)', ['a']);
        $id = P1::insertGetId('INSERT INTO items (name) VALUES (?)', ['b']);

        $this->assertSame(2, $id);
        $this->assertSame(2, P1::var('SELECT COUNT(*) FROM items'));
        $this->assertSame('a', P1::row('SELECT * FROM items WHERE id = 1')['name']);
        $this->assertSame(['a', 'b'], P1::col('SELECT name FROM items ORDER BY id'));
        $this->assertCount(2, P1::results('SELECT * FROM items'));
        $this->assertInstanceOf(\P1\Flash::class, P1::flash());
    }

    public function testUrlShortcut(): void {
        $app = new App();
        $app->get('/o/{slug}', FacadeStubCtrl::class, 'index', name: 'ad.show');

        $this->assertSame('/o/s', P1::url('ad.show', ['slug' => 's']));
    }
}

class FacadeStubCtrl {
    public function index(): \P1\Response {
        return new \P1\Response('ok');
    }
}
