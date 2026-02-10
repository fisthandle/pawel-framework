<?php
declare(strict_types=1);

namespace PFrame\Tests\Unit;

use PFrame\Csrf;
use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase {
    protected function setUp(): void {
        $_SESSION = [];
    }

    public function testGenerateToken(): void {
        $token = Csrf::token();
        $this->assertSame(64, strlen($token));
        $this->assertSame($token, Csrf::token());
    }

    public function testValidateCorrect(): void {
        $this->assertTrue(Csrf::validate(Csrf::token()));
    }

    public function testValidateWrong(): void {
        Csrf::token();
        $this->assertFalse(Csrf::validate('wrong'));
    }

    public function testValidateEmpty(): void {
        $this->assertFalse(Csrf::validate(null));
        $this->assertFalse(Csrf::validate(''));
    }

    public function testHiddenInput(): void {
        $html = Csrf::hiddenInput();
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
    }

    public function testActionNonce(): void {
        $nonce = Csrf::nonce('delete');
        $this->assertSame(64, strlen($nonce));
        $this->assertSame($nonce, Csrf::nonce('delete'));
        $this->assertNotSame($nonce, Csrf::nonce('edit'));
    }

    public function testVerifyNonce(): void {
        $nonce = Csrf::nonce('submit');
        $this->assertTrue(Csrf::verifyNonce('submit', $nonce));
        $this->assertFalse(Csrf::verifyNonce('submit', 'wrong'));
        $this->assertFalse(Csrf::verifyNonce('other', $nonce));
        $this->assertFalse(Csrf::verifyNonce('submit', null));
    }

    public function testHiddenInputActionUsesNonce(): void {
        $html = Csrf::hiddenInput('delete');
        $this->assertStringContainsString(Csrf::nonce('delete'), $html);
    }
}
