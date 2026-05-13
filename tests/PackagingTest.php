<?php

declare(strict_types=1);

namespace ISPConfigMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for packaging artifacts: launcher scripts, zabbix configs, appstream metadata, icon.
 */
class PackagingTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__);
    }

    // --- Launcher scripts ---

    public function testLauncherScriptsExist(): void
    {
        $scripts = [
            'scripts/ispconfig-discovery-websites',
            'scripts/ispconfig-discovery-emails',
            'scripts/ispconfig-discovery-mail-domains',
            'scripts/ispconfig-key-websites',
            'scripts/ispconfig-key-emails',
            'scripts/ispconfig-key-mail-domains',
        ];

        foreach ($scripts as $script) {
            $path = $this->projectRoot . '/' . $script;
            $this->assertFileExists($path, "Launcher script missing: {$script}");
        }
    }

    public function testLauncherScriptsAreExecutable(): void
    {
        $scripts = glob($this->projectRoot . '/scripts/ispconfig-*');
        $this->assertNotEmpty($scripts);

        foreach ($scripts as $script) {
            $this->assertTrue(
                is_executable($script),
                basename($script) . ' is not executable'
            );
        }
    }

    public function testLauncherScriptsHaveShebang(): void
    {
        $scripts = glob($this->projectRoot . '/scripts/ispconfig-*');

        foreach ($scripts as $script) {
            $content = file_get_contents($script);
            $this->assertStringStartsWith('#!/bin/sh', $content, basename($script) . ' missing shebang');
        }
    }

    public function testDiscoveryLaunchersCallCorrectPhpScript(): void
    {
        $mapping = [
            'ispconfig-discovery-websites' => 'autodiscovery/websites.php',
            'ispconfig-discovery-emails' => 'autodiscovery/emails.php',
            'ispconfig-discovery-mail-domains' => 'autodiscovery/mail_domains.php',
        ];

        foreach ($mapping as $launcher => $phpScript) {
            $content = file_get_contents($this->projectRoot . '/scripts/' . $launcher);
            $this->assertStringContainsString($phpScript, $content, "{$launcher} does not reference {$phpScript}");
        }
    }

    public function testKeyLaunchersCallCorrectPhpScript(): void
    {
        $mapping = [
            'ispconfig-key-websites' => 'keys/websites.php',
            'ispconfig-key-emails' => 'keys/emails.php',
            'ispconfig-key-mail-domains' => 'keys/mail_domains.php',
        ];

        foreach ($mapping as $launcher => $phpScript) {
            $content = file_get_contents($this->projectRoot . '/scripts/' . $launcher);
            $this->assertStringContainsString($phpScript, $content, "{$launcher} does not reference {$phpScript}");
        }
    }

    public function testKeyLaunchersPassArguments(): void
    {
        $scripts = glob($this->projectRoot . '/scripts/ispconfig-key-*');

        foreach ($scripts as $script) {
            $content = file_get_contents($script);
            $this->assertStringContainsString('$@', $content, basename($script) . ' does not pass arguments');
        }
    }

    // --- Zabbix agent configs ---

    public function testZabbixAgent2ConfigExists(): void
    {
        $this->assertFileExists($this->projectRoot . '/config/zabbix_agent2.d/ispconfig-monitoring.conf');
    }

    public function testZabbixConfigsUseUserParameter(): void
    {
        $config = $this->projectRoot . '/config/zabbix_agent2.d/ispconfig-monitoring.conf';
        $content = file_get_contents($config);
        $this->assertStringContainsString('UserParameter=ispconfig.websites.discovery', $content);
        $this->assertStringContainsString('UserParameter=ispconfig.website[*]', $content);
        $this->assertStringContainsString('UserParameter=ispconfig.emails.discovery', $content);
        $this->assertStringContainsString('UserParameter=ispconfig.email[*]', $content);
        $this->assertStringContainsString('UserParameter=ispconfig.maildomains.discovery', $content);
        $this->assertStringContainsString('UserParameter=ispconfig.maildomain[*]', $content);
    }

    public function testZabbixConfigsUseLauncherScripts(): void
    {
        $config = $this->projectRoot . '/config/zabbix_agent2.d/ispconfig-monitoring.conf';
        $content = file_get_contents($config);
        $this->assertStringContainsString('/usr/bin/ispconfig-discovery-websites', $content);
        $this->assertStringContainsString('/usr/bin/ispconfig-key-websites', $content);
        $this->assertStringNotContainsString('/usr/bin/php /usr/lib/', $content,
            'Config still calls PHP directly instead of launcher scripts');
    }

    // --- AppStream metadata ---

    public function testAppstreamMetadataExists(): void
    {
        $this->assertFileExists($this->projectRoot . '/debian/source/ispconfig-zabbix-monitoring.metainfo.xml');
    }

    public function testAppstreamMetadataIsValidXml(): void
    {
        $path = $this->projectRoot . '/debian/source/ispconfig-zabbix-monitoring.metainfo.xml';
        $xml = simplexml_load_file($path);
        $this->assertNotFalse($xml, 'Metainfo XML is not valid');
    }

    public function testAppstreamMetadataHasCorrectType(): void
    {
        $path = $this->projectRoot . '/debian/source/ispconfig-zabbix-monitoring.metainfo.xml';
        $xml = simplexml_load_file($path);
        $this->assertEquals('console-application', (string) $xml['type']);
    }

    public function testAppstreamMetadataReferencesStockIcon(): void
    {
        $path = $this->projectRoot . '/debian/source/ispconfig-zabbix-monitoring.metainfo.xml';
        $xml = simplexml_load_file($path);
        $this->assertEquals('stock', (string) $xml->icon['type']);
        $this->assertEquals('ispconfig-zabbix-monitoring', (string) $xml->icon);
    }

    public function testAppstreamMetadataListsBinaries(): void
    {
        $path = $this->projectRoot . '/debian/source/ispconfig-zabbix-monitoring.metainfo.xml';
        $xml = simplexml_load_file($path);

        $binaries = [];
        foreach ($xml->provides->binary as $bin) {
            $binaries[] = (string) $bin;
        }

        $this->assertContains('ispconfig-discovery-websites', $binaries);
        $this->assertContains('ispconfig-key-websites', $binaries);
        $this->assertContains('ispconfig-discovery-emails', $binaries);
        $this->assertContains('ispconfig-key-emails', $binaries);
        $this->assertContains('ispconfig-discovery-mail-domains', $binaries);
        $this->assertContains('ispconfig-key-mail-domains', $binaries);
    }

    // --- SVG Icon ---

    public function testSvgIconExists(): void
    {
        $this->assertFileExists($this->projectRoot . '/ispconfig-zabbix-monitoring.svg');
    }

    public function testSvgIconIsValidSvg(): void
    {
        $content = file_get_contents($this->projectRoot . '/ispconfig-zabbix-monitoring.svg');
        $this->assertStringContainsString('<svg', $content);
    }

    // --- debian/install references ---

    public function testDebianInstallIncludesIcon(): void
    {
        $content = file_get_contents($this->projectRoot . '/debian/install');
        $this->assertStringContainsString('ispconfig-zabbix-monitoring.svg usr/share/icons/hicolor/scalable/apps/', $content);
    }

    public function testDebianInstallIncludesMetainfo(): void
    {
        $content = file_get_contents($this->projectRoot . '/debian/install');
        $this->assertStringContainsString('metainfo.xml usr/share/metainfo/', $content);
    }

    public function testDebianInstallIncludesLauncherScripts(): void
    {
        $content = file_get_contents($this->projectRoot . '/debian/install');
        $this->assertStringContainsString('scripts/ispconfig-discovery-websites usr/bin/', $content);
        $this->assertStringContainsString('scripts/ispconfig-key-websites usr/bin/', $content);
    }

    public function testDebianInstallIncludesZabbixConfigs(): void
    {
        $content = file_get_contents($this->projectRoot . '/debian/install');
        $this->assertStringContainsString('config/zabbix_agent2.d/ispconfig-monitoring.conf etc/zabbix/zabbix_agent2.d/', $content);
        $this->assertStringNotContainsString('zabbix_agentd', $content);
    }
}
