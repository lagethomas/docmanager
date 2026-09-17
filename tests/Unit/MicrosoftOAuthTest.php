<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Services\MicrosoftOAuth;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MicrosoftOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testIsEnabledReturnsFalseWhenUnconfigured(): void
    {
        $oauth = new MicrosoftOAuth('', '', '', '');
        $this->assertFalse($oauth->isEnabled());
    }

    public function testIsEnabledReturnsTrueWhenConfigured(): void
    {
        $oauth = new MicrosoftOAuth('client-id-123', 'secret', 'tenant', 'https://example.com/login/oauth/callback');
        $this->assertTrue($oauth->isEnabled());
    }

    public function testGetAuthorizationUrlGeneratesValidUrlAndSessionKeys(): void
    {
        $oauth = new MicrosoftOAuth('client-id-123', 'secret-xyz', 'my-tenant', 'https://example.com/login/oauth/callback');
        $url = $oauth->getAuthorizationUrl();

        $this->assertStringStartsWith('https://login.microsoftonline.com/my-tenant/oauth2/v2.0/authorize?', $url);
        $this->assertStringContainsString('client_id=client-id-123', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode('https://example.com/login/oauth/callback'), $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);

        $this->assertNotEmpty($_SESSION['oauth2state'] ?? null);
        $this->assertNotEmpty($_SESSION['oauth2code_verifier'] ?? null);
    }

    public function testGetAuthorizationUrlThrowsExceptionWhenDisabled(): void
    {
        $this->expectException(RuntimeException::class);
        $oauth = new MicrosoftOAuth('', '', '', '');
        $oauth->getAuthorizationUrl();
    }
}
