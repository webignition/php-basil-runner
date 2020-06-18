<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\ResultPrinter;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ResultPrinterTest extends AbstractBaseTest
{
    /**
     * @dataProvider printerOutputDataProvider
     *
     * @param string[] $testPaths
     * @param string[] $stepNames
     * @param int[] $endStatuses
     * @param array<int, StatementInterface[]> $handledStatements
     * @param array<mixed> $expectedValues
     * @param array<mixed> $examinedValues
     * @param string $expectedOutput
     */
    public function testPrinterOutput(
        array $testPaths,
        array $stepNames,
        array $endStatuses,
        array $handledStatements,
        array $expectedValues,
        array $examinedValues,
        string $expectedOutput
    ) {
        $tests = $this->createBasilTestCases(
            $testPaths,
            $stepNames,
            $endStatuses,
            $handledStatements,
            $expectedValues,
            $examinedValues
        );

        $outResource = fopen('php://memory', 'w+');

        if (is_resource($outResource)) {
            $printer = new ResultPrinter($outResource);

            $this->exercisePrinter($printer, $tests);

            rewind($outResource);
            $outContent = stream_get_contents($outResource);
            fclose($outResource);

            $this->assertSame($expectedOutput, $outContent);
        } else {
            $this->fail('Failed to open resource "php://memory" for reading and writing');
        }
    }

    public function printerOutputDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $cof = new ConsoleOutputFactory();

        return [
            'single test' => [
                'testPaths' => [
                    $root . '/test.yml',
                ],
                'stepNames' => [
                    'step one',
                ],
                'endStatuses' => [
                    BaseTestRunner::STATUS_PASSED,
                ],
                'handledStatements' => [
                    [
                        $assertionParser->parse('$page.url is "http://example.com/"'),
                    ],
                ],
                'expectedValues' => [
                    null,
                ],
                'examinedValues' => [
                    null,
                ],
                'expectedOutput' =>
                '<test-name>test.yml</test-name>' . "\n" .
                    '  <icon-success /> <success>step one</success>' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/"' . "\n" .
                    "\n"
                ,
            ],
            'multiple tests' => [
                'testPaths' => [
                    $root . '/test1.yml',
                    $root . '/test2.yml',
                    $root . '/test2.yml',
                    $root . '/test3.yml',
                ],
                'stepNames' => [
                    'test one step one',
                    'test two step one',
                    'test two step two',
                    'test three step one',
                ],
                'endStatuses' => [
                    BaseTestRunner::STATUS_PASSED,
                    BaseTestRunner::STATUS_PASSED,
                    BaseTestRunner::STATUS_PASSED,
                    BaseTestRunner::STATUS_FAILURE,
                ],
                'handledStatements' => [
                    [
                        $assertionParser->parse('$page.url is "http://example.com/"'),
                        $assertionParser->parse('$page.title is "Hello, World!"'),
                    ],
                    [
                        $actionParser->parse('click $".successful"'),
                        $assertionParser->parse('$page.url is "http://example.com/successful/"')
                    ],
                    [
                        $actionParser->parse('click $".back"'),
                        $assertionParser->parse('$page.url is "http://example.com/"'),
                    ],
                    [
                        $actionParser->parse('click $".new"'),
                        $assertionParser->parse('$page.url is "http://example.com/new/"'),
                    ],
                ],
                'expectedValues' => [
                    null,
                    null,
                    null,
                    'http://example.com/new/',
                ],
                'examinedValues' => [
                    null,
                    null,
                    null,
                    'http://example.com/',
                ],
                'expectedOutput' =>
                    '<test-name>test1.yml</test-name>' . "\n" .
                    '  <icon-success /> <success>test one step one</success>' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.title is "Hello, World!"' . "\n" .
                    "\n" .
                    '<test-name>test2.yml</test-name>' . "\n" .
                    '  <icon-success /> <success>test two step one</success>' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $".successful"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/successful/"' . "\n" .
                    "\n" .
                    '  <icon-success /> <success>test two step two</success>' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $".back"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/"' . "\n" .
                    "\n" .
                    '<test-name>test3.yml</test-name>' . "\n" .
                    '  <icon-failure /> <failure>test three step one</failure>' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $".new"' . "\n" .
                    '    ' . $cof->createFailure('x') . ' ' . $cof->createHighlightedFailure(
                        '$page.url is "http://example.com/new/"'
                    ) . "\n" .
                    '    * ' . $cof->createComment('http://example.com/')
                    . ' is not equal to ' . $cof->createComment('http://example.com/new/') . "\n" .
                    "\n"
                ,
            ],
        ];
    }

    /**
     * @param ResultPrinter $printer
     * @param BasilTestCaseInterface[] $tests
     */
    private function exercisePrinter(ResultPrinter $printer, array $tests): void
    {
        foreach ($tests as $test) {
            $printer->startTest($test);
            $printer->endTest($test, 0.1);
        }
    }

    /**
     * @param string[] $testPaths
     * @param string[] $stepNames
     * @param int[] $endStatuses
     * @param array<int, StatementInterface[]> $handledStatements
     * @param array<mixed> $expectedValues
     * @param array<mixed> $examinedValues
     *
     * @return BasilTestCaseInterface[]
     */
    private function createBasilTestCases(
        array $testPaths,
        array $stepNames,
        array $endStatuses,
        array $handledStatements,
        array $expectedValues,
        array $examinedValues
    ): array {
        $testCases = [];

        foreach ($testPaths as $testIndex => $testPath) {
            $basilTestCase = \Mockery::mock(BasilTestCaseInterface::class);
            $basilTestCase
                ->shouldReceive('getBasilTestPath')
                ->andReturnValues($testPaths);

            $basilTestCase
                ->shouldReceive('getBasilStepName')
                ->andReturn($stepNames[$testIndex]);

            $basilTestCase
                ->shouldReceive('getStatus')
                ->andReturn($endStatuses[$testIndex]);

            $basilTestCase
                ->shouldReceive('getHandledStatements')
                ->andReturn($handledStatements[$testIndex]);

            $basilTestCase
                ->shouldReceive('getExpectedValue')
                ->andReturn($expectedValues[$testIndex]);

            $basilTestCase
                ->shouldReceive('getExaminedValue')
                ->andReturn($examinedValues[$testIndex]);

            $basilTestCase
                ->shouldReceive('getLastException')
                ->andReturnNull();

            $basilTestCase
                ->shouldReceive('getCurrentDataSet')
                ->andReturnNull();

            $testCases[] = $basilTestCase;
        }

        return $testCases;
    }
}
