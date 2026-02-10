<?php
declare(strict_types=1);

namespace P1\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {
    public function testHEscapesHtml(): void {
        $this->assertSame('&lt;script&gt;', h('<script>'));
        $this->assertSame('&amp;', h('&'));
        $this->assertSame('', h(null));
        $this->assertSame('', h(''));
        $this->assertSame('hello', h('hello'));
        $this->assertSame('', h(['x']));
    }

    public function testHaEscapesArrayValue(): void {
        $data = ['name' => '<b>Joe</b>', 'age' => 30];
        $this->assertSame('&lt;b&gt;Joe&lt;/b&gt;', ha($data, 'name'));
        $this->assertSame('30', ha($data, 'age'));
        $this->assertSame('default', ha($data, 'missing', 'default'));
        $this->assertSame('', ha($data, 'missing'));
    }

    public function testSGetReturnsValueOrDefault(): void {
        $data = ['key' => 'val'];
        $this->assertSame('val', sGet($data, 'key'));
        $this->assertNull(sGet($data, 'missing'));
        $this->assertSame('def', sGet($data, 'missing', 'def'));
        $this->assertNull(sGet(null, 'key'));
    }

    public function testMlen(): void {
        $this->assertSame(0, mlen(null));
        $this->assertSame(5, mlen('hello'));
        $this->assertSame(4, mlen('żółw'));
    }

    public function testMsub(): void {
        $this->assertSame('llo', msub('hello', 2));
        $this->assertSame('żó', msub('żółw', 0, 2));
        $this->assertSame('', msub(null, 0));
    }

    public function testSTrim(): void {
        $this->assertSame('hello', sTrim('  hello  '));
        $this->assertSame('', sTrim(null));
        $this->assertSame('42', sTrim(42));
        $this->assertSame('hello', sTrim('xxxhelloxxx', 'x'));
    }

    public function testStt(): void {
        $this->assertIsInt(stt('2024-01-01'));
        $this->assertFalse(stt(null));
        $this->assertFalse(stt(''));
    }

    public function testSStrip(): void {
        $this->assertSame('hello', sStrip('<b>hello</b>'));
        $this->assertSame('', sStrip(null));
    }

    public function testSCount(): void {
        $this->assertSame(0, sCount(null));
        $this->assertSame(2, sCount([1, 2]));
        $this->assertSame(0, sCount('string'));
        $this->assertSame(0, sCount(42));
        $this->assertSame(1, sCount(new class implements \Countable {
            public function count(): int {
                return 1;
            }
        }));
    }

    public function testSExplode(): void {
        $this->assertSame(['a', 'b', 'c'], sExplode(',', 'a, b, c'));
        $this->assertSame([], sExplode(',', null));
        $this->assertSame([], sExplode(',', ''));
        $this->assertSame(['a', 'b,c'], sExplode(',', 'a,b,c', 2));
        $this->assertSame(['123'], sExplode(',', 123));
        $this->assertSame([], sExplode(',', new \stdClass()));
    }
}
