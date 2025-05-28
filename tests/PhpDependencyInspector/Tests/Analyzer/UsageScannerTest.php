<?php

namespace PhpDependencyInspector\Tests\Analyzer;

use PhpDependencyInspector\Analyzer\UsageScanner;
use PHPUnit\Framework\TestCase;

class UsageScannerTest extends TestCase
{
    public function testScanFindsUsedNamespaces(): void
    {
        $scanner = new UsageScanner();
        $result = $scanner->scan(__DIR__ . '/fixtures/src');

        $this->assertContains('App\\Example', $result);
    }
}
