<?php

declare(strict_types=1);

namespace ISPConfigMonitoring\Tests;

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use PHPUnit\Framework\TestCase;

class ISPConfigClientTest extends TestCase
{
    private array $validConfig;

    protected function setUp(): void
    {
        $this->validConfig = [
            'soap_uri' => 'https://example.com/remote/index.php',
            'soap_location' => 'https://example.com/remote/',
            'username' => 'testuser',
            'password' => 'testpass',
            'verify_ssl' => false,
        ];
    }

    public function testConstructorWithValidConfig(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Should not throw exception
        new ISPConfigClient($this->validConfig);
    }

    public function testConstructorThrowsExceptionForMissingSoapUri(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Missing required configuration: soap_uri');

        $config = $this->validConfig;
        unset($config['soap_uri']);
        new ISPConfigClient($config);
    }

    public function testConstructorThrowsExceptionForMissingSoapLocation(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Missing required configuration: soap_location');

        $config = $this->validConfig;
        unset($config['soap_location']);
        new ISPConfigClient($config);
    }

    public function testConstructorThrowsExceptionForMissingUsername(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Missing required configuration: username');

        $config = $this->validConfig;
        unset($config['username']);
        new ISPConfigClient($config);
    }

    public function testConstructorThrowsExceptionForMissingPassword(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Missing required configuration: password');

        $config = $this->validConfig;
        unset($config['password']);
        new ISPConfigClient($config);
    }

    public function testConstructorThrowsExceptionForInvalidSoapUri(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Invalid SOAP URI');

        $config = $this->validConfig;
        $config['soap_uri'] = 'not-a-valid-url';
        new ISPConfigClient($config);
    }

    public function testConstructorWithEmptySoapUri(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Missing required configuration: soap_uri');

        $config = $this->validConfig;
        $config['soap_uri'] = '';
        new ISPConfigClient($config);
    }

    public function testConstructorWithEmptyUsername(): void
    {
        $this->expectException(ISPConfigException::class);
        $this->expectExceptionMessage('Missing required configuration: username');

        $config = $this->validConfig;
        $config['username'] = '';
        new ISPConfigClient($config);
    }

    public function testConstructorAcceptsHttpsSoapUri(): void
    {
        $this->expectNotToPerformAssertions();

        $config = $this->validConfig;
        $config['soap_uri'] = 'https://secure.example.com/remote/';
        new ISPConfigClient($config);
    }

    public function testConstructorAcceptsHttpSoapUri(): void
    {
        $this->expectNotToPerformAssertions();

        $config = $this->validConfig;
        $config['soap_uri'] = 'http://insecure.example.com/remote/';
        new ISPConfigClient($config);
    }

    public function testLogoutReturnsTrueWhenNotLoggedIn(): void
    {
        $client = new ISPConfigClient($this->validConfig);
        $this->assertTrue($client->logout());
    }

    public function testConstructorWithSSLVerificationEnabled(): void
    {
        $this->expectNotToPerformAssertions();

        $config = $this->validConfig;
        $config['verify_ssl'] = true;
        new ISPConfigClient($config);
    }

    public function testConstructorWithDefaultSSLVerification(): void
    {
        $this->expectNotToPerformAssertions();

        $config = $this->validConfig;
        unset($config['verify_ssl']);
        new ISPConfigClient($config);
    }
}
