<?php
declare(strict_types=1);

namespace P1\Tests\Unit;

use P1\Flash;
use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase {
    protected function setUp(): void {
        $_SESSION = [];
    }

    public function testAddAndGet(): void {
        $flash = new Flash();
        $flash->add('success', 'Saved!');
        $flash->add('error', 'Oops');

        $msgs = $flash->get();
        $this->assertCount(2, $msgs);
        $this->assertSame('success', $msgs[0]['type']);
    }

    public function testGetClearsMessages(): void {
        $flash = new Flash();
        $flash->add('info', 'Note');
        $flash->get();
        $this->assertEmpty($flash->get());
    }

    public function testHas(): void {
        $flash = new Flash();
        $this->assertFalse($flash->has());
        $flash->add('info', 'x');
        $this->assertTrue($flash->has());
    }

    public function testConvenienceMethods(): void {
        $flash = new Flash();
        $flash->success('ok');
        $flash->warning('warn');
        $flash->error('err');
        $flash->info('info');

        $types = array_column($flash->get(), 'type');
        $this->assertSame(['success', 'warning', 'error', 'info'], $types);
    }
}
