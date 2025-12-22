<?php

declare(strict_types=1);

namespace ISPConfigMonitoring\Tests;

use ISPConfigMonitoring\ZabbixHelper;
use PHPUnit\Framework\TestCase;

class ZabbixHelperTest extends TestCase
{
    private ZabbixHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ZabbixHelper();
    }

    public function testFormatDiscoveryWithSimpleData(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $macroMap = [
            'id' => '{#ITEM_ID}',
            'name' => '{#ITEM_NAME}',
        ];

        $result = $this->helper->formatDiscovery($items, $macroMap);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('{#ITEM_ID}', array_keys($result['data'][0])[0]);
        $this->assertEquals('1', $result['data'][0]['{#ITEM_ID}']);
        $this->assertEquals('Item 1', $result['data'][0]['{#ITEM_NAME}']);
    }

    public function testFormatDiscoveryWithEmptyArray(): void
    {
        $result = $this->helper->formatDiscovery([], []);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(0, $result['data']);
    }

    public function testFormatWebsitesDiscovery(): void
    {
        $websites = [
            [
                'domain_id' => '1',
                'domain' => 'example.com',
                'server_id' => '1',
                'document_root' => '/var/www/example.com',
                'php' => 'php8.2',
                'active' => 'y',
                'ssl' => 'y',
            ],
        ];

        $result = $this->helper->formatWebsitesDiscovery($websites);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('1', $result['data'][0]['{#WEBSITE_ID}']);
        $this->assertEquals('example.com', $result['data'][0]['{#DOMAIN}']);
    }

    public function testFormatItemValueNumeric(): void
    {
        $this->assertEquals('42.5', $this->helper->formatItemValue('42.5', 'numeric'));
        $this->assertEquals('100', $this->helper->formatItemValue(100, 'numeric'));
        $this->assertEquals('0', $this->helper->formatItemValue('0', 'numeric'));
    }

    public function testFormatItemValueBoolean(): void
    {
        $this->assertEquals('1', $this->helper->formatItemValue(true, 'boolean'));
        $this->assertEquals('0', $this->helper->formatItemValue(false, 'boolean'));
        $this->assertEquals('1', $this->helper->formatItemValue('yes', 'boolean'));
        $this->assertEquals('1', $this->helper->formatItemValue('y', 'boolean'));
        $this->assertEquals('1', $this->helper->formatItemValue('true', 'boolean'));
        $this->assertEquals('1', $this->helper->formatItemValue('on', 'boolean'));
        $this->assertEquals('1', $this->helper->formatItemValue('active', 'boolean'));
        $this->assertEquals('0', $this->helper->formatItemValue('no', 'boolean'));
        $this->assertEquals('1', $this->helper->formatItemValue(1, 'boolean'));
        $this->assertEquals('0', $this->helper->formatItemValue(0, 'boolean'));
    }

    public function testFormatItemValueString(): void
    {
        $this->assertEquals('hello', $this->helper->formatItemValue('hello', 'string'));
        $this->assertEquals('test 123', $this->helper->formatItemValue('test 123', 'string'));
        $this->assertEquals('', $this->helper->formatItemValue(null, 'string'));
    }

    public function testFormatItemValueBytes(): void
    {
        $this->assertEquals('1024', $this->helper->formatItemValue('1K', 'bytes'));
        $this->assertEquals('1048576', $this->helper->formatItemValue('1M', 'bytes'));
        $this->assertEquals('1073741824', $this->helper->formatItemValue('1G', 'bytes'));
        $this->assertEquals('100', $this->helper->formatItemValue(100, 'bytes'));
    }

    public function testFormatItemValueTimestamp(): void
    {
        $result = $this->helper->formatItemValue('2025-01-01 00:00:00', 'timestamp');
        $this->assertIsString($result);
        $this->assertGreaterThan(0, (int) $result);

        $this->assertEquals('1234567890', $this->helper->formatItemValue(1234567890, 'timestamp'));
    }

    public function testFormatItemValueNull(): void
    {
        $this->assertEquals('', $this->helper->formatItemValue(null));
    }

    public function testCreateItemKey(): void
    {
        $key = $this->helper->createItemKey('ispconfig.website', 'status');
        $this->assertEquals('ispconfig.website.status', $key);
    }

    public function testCreateItemKeyWithParams(): void
    {
        $key = $this->helper->createItemKey('ispconfig.website', 'status', ['123']);
        $this->assertEquals('ispconfig.website.status[123]', $key);
    }

    public function testCreateItemKeyWithMultipleParams(): void
    {
        $key = $this->helper->createItemKey('ispconfig.website', 'info', ['123', 'active']);
        $this->assertEquals('ispconfig.website.info[123,active]', $key);
    }

    public function testValidateLLDDataValid(): void
    {
        $data = [
            'data' => [
                ['{#ITEM_ID}' => '1', '{#ITEM_NAME}' => 'Test'],
            ],
        ];

        $this->assertTrue($this->helper->validateLLDData($data));
    }

    public function testValidateLLDDataInvalidNoData(): void
    {
        $data = ['items' => []];
        $this->assertFalse($this->helper->validateLLDData($data));
    }

    public function testValidateLLDDataInvalidDataNotArray(): void
    {
        $data = ['data' => 'not an array'];
        $this->assertFalse($this->helper->validateLLDData($data));
    }

    public function testValidateLLDDataInvalidItemNotArray(): void
    {
        $data = ['data' => ['string item']];
        $this->assertFalse($this->helper->validateLLDData($data));
    }

    public function testValidateLLDDataInvalidMacroFormat(): void
    {
        $data = [
            'data' => [
                ['INVALID_MACRO' => '1'],
            ],
        ];

        $this->assertFalse($this->helper->validateLLDData($data));
    }

    public function testValidateLLDDataEmptyDataArray(): void
    {
        $data = ['data' => []];
        $this->assertTrue($this->helper->validateLLDData($data));
    }

    public function testFormatDiscoveryWithNestedValues(): void
    {
        $items = [
            [
                'id' => 1,
                'config' => [
                    'name' => 'Nested Name',
                ],
            ],
        ];

        $macroMap = [
            'id' => '{#ID}',
            'config.name' => '{#NAME}',
        ];

        $result = $this->helper->formatDiscovery($items, $macroMap);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('1', $result['data'][0]['{#ID}']);
        $this->assertEquals('Nested Name', $result['data'][0]['{#NAME}']);
    }

    public function testFormatDiscoveryWithMissingKeys(): void
    {
        $items = [
            ['id' => 1],
        ];

        $macroMap = [
            'id' => '{#ID}',
            'missing' => '{#MISSING}',
        ];

        $result = $this->helper->formatDiscovery($items, $macroMap);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('1', $result['data'][0]['{#ID}']);
        $this->assertEquals('', $result['data'][0]['{#MISSING}']);
    }

    public function testFormatDiscoveryWithBooleanValues(): void
    {
        $items = [
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
        ];

        $macroMap = [
            'id' => '{#ID}',
            'active' => '{#ACTIVE}',
        ];

        $result = $this->helper->formatDiscovery($items, $macroMap);

        $this->assertEquals('1', $result['data'][0]['{#ACTIVE}']);
        $this->assertEquals('0', $result['data'][1]['{#ACTIVE}']);
    }

    public function testFormatDiscoveryWithNullValues(): void
    {
        $items = [
            ['id' => 1, 'optional' => null],
        ];

        $macroMap = [
            'id' => '{#ID}',
            'optional' => '{#OPTIONAL}',
        ];

        $result = $this->helper->formatDiscovery($items, $macroMap);

        $this->assertEquals('', $result['data'][0]['{#OPTIONAL}']);
    }

    public function testFormatDiscoveryWithArrayValues(): void
    {
        $items = [
            ['id' => 1, 'tags' => ['tag1', 'tag2']],
        ];

        $macroMap = [
            'id' => '{#ID}',
            'tags' => '{#TAGS}',
        ];

        $result = $this->helper->formatDiscovery($items, $macroMap);

        $this->assertStringContainsString('tag1', $result['data'][0]['{#TAGS}']);
        $this->assertStringContainsString('tag2', $result['data'][0]['{#TAGS}']);
    }

    public function testOutputJSONProducesValidJSON(): void
    {
        $data = ['data' => ['{#ID}' => '1']];

        ob_start();
        $this->helper->outputJSON($data);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($data, $decoded);
    }

    public function testOutputJSONPrettyProducesValidJSON(): void
    {
        $data = ['data' => ['{#ID}' => '1']];

        ob_start();
        $this->helper->outputJSONPretty($data);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($data, $decoded);
        $this->assertStringContainsString("\n", $output); // Should have newlines for pretty print
    }

    public function testCreateItemKeyWithSpecialCharacters(): void
    {
        $key = $this->helper->createItemKey('ispconfig', 'test', ['param with spaces']);
        $this->assertStringContainsString('[', $key);
        $this->assertStringContainsString(']', $key);
    }

    public function testFormatBytesWithDecimalNumbers(): void
    {
        $result = $this->helper->formatItemValue('1.5M', 'bytes');
        $this->assertEquals('1572864', $result);
    }

    public function testFormatBytesInvalidFormat(): void
    {
        $result = $this->helper->formatItemValue('invalid', 'bytes');
        $this->assertEquals('0', $result);
    }

    public function testFormatTimestampInvalidValue(): void
    {
        $result = $this->helper->formatItemValue('invalid date', 'timestamp');
        $this->assertEquals('0', $result);
    }
}
