<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit;

use PFrame\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase {
    public function testStatusCode(): void {
        $e = new HttpException(404, 'Not Found');
        $this->assertSame(404, $e->statusCode);
        $this->assertSame('Not Found', $e->getMessage());
        $this->assertSame(404, $e->getCode());
    }

    public function testFactories(): void {
        $this->assertSame(404, HttpException::notFound()->statusCode);
        $this->assertSame(403, HttpException::forbidden()->statusCode);
        $this->assertSame(401, HttpException::unauthorized()->statusCode);
    }
}
