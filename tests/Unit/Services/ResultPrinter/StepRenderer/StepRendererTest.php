<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\StepRenderer;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilDomIdentifierFactory\Factory;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryLineFactory;
use webignition\BasilRunner\Services\ResultPrinter\StatementLineRenderer;
use webignition\BasilRunner\Services\ResultPrinter\StepRenderer\StepRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StepRendererTest extends AbstractBaseTest
{
    /**
     * @var StepRenderer
     */
    private $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $consoleOutputFactory = new ConsoleOutputFactory();

        $this->renderer = new StepRenderer(
            new ConsoleOutputFactory(),
            new StatementLineRenderer($consoleOutputFactory),
            new SummaryHandler(
                Factory::createFactory(),
                new SummaryLineFactory($consoleOutputFactory)
            )
        );
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Step $step, string $expectedRenderedStep)
    {
        $this->assertSame($expectedRenderedStep, $this->renderer->render($step));
    }

    public function renderDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $successPrefix = $cof->createSuccess('✓');
        $failurePrefix = $cof->createFailure('x');

        return [
            'passed, no statements' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    []
                )),
                'expectedRenderedStep' =>
                    '  ' . $successPrefix . ' ' . $cof->createSuccess('passed step name') . "\n"
                ,
            ],
            'failed, no statements' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    []
                )),
                'expectedRenderedStep' =>
                    '  ' . $failurePrefix . ' ' .
                    $cof->createFailure('failed step name') . "\n"
                ,
            ],
            'unknown, no statements' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_ERROR,
                    'unknown step name',
                    []
                )),
                'expectedRenderedStep' =>
                    '  ' . $cof->createFailure('?') . ' ' .
                    $cof->createFailure('unknown step name') . "\n"
                ,
            ],
            'passed, click statement completed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    [
                        $actionParser->parse('click $".selector"'),
                    ]
                )),
                'expectedRenderedStep' =>
                    '  ' . $successPrefix . ' ' . $cof->createSuccess('passed step name') . "\n" .
                    '    ' . $successPrefix . ' click $".selector"'
                ,
            ],
            'failed, exists assertion failed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$".selector" exists'),
                    ]
                )),
                'expectedRenderedStep' =>
                    '  ' . $failurePrefix . ' ' . $cof->createFailure('failed step name') . "\n" .
                    '    ' . $failurePrefix . ' ' . $cof->createHighlightedFailure('$".selector" exists') . "\n" .
                    '    * Element ' . $cof->createComment('$".selector"') . ' identified by:' . "\n" .
                    '        - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '        - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '      does not exist'
                ,
            ],
            'failed, is assertion failed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$page.title is "Foo"'),
                    ],
                    'Foo',
                    'Bar'
                )),
                'expectedRenderedStep' =>
                    '  ' . $failurePrefix . ' ' . $cof->createFailure('failed step name') . "\n" .
                    '    ' . $failurePrefix . ' ' . $cof->createHighlightedFailure('$page.title is "Foo"') . "\n" .
                    '    * $page.title is not equal to expected value' . "\n" .
                    '      - expected: ' . $cof->createComment('Foo') . "\n" .
                    '      - actual:   ' . $cof->createComment('Bar')
                ,
            ],
            'failed, first assertion passed, second assertion failed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$page.url is "http://example.com/'),
                        $assertionParser->parse('$page.title is "Foo"'),
                    ],
                    'Foo',
                    'Bar'
                )),
                'expectedRenderedStep' =>
                    '  ' . $failurePrefix . ' ' . $cof->createFailure('failed step name') . "\n" .
                    '    ' . $successPrefix . ' $page.url is "http://example.com/' . "\n" .
                    '    ' . $failurePrefix . ' ' . $cof->createHighlightedFailure('$page.title is "Foo"') . "\n" .
                    '    * $page.title is not equal to expected value' . "\n" .
                    '      - expected: ' . $cof->createComment('Foo') . "\n" .
                    '      - actual:   ' . $cof->createComment('Bar')
                ,
            ],
        ];
    }

    private function createTest(
        int $status,
        string $basilStepName,
        array $handledStatements,
        string $expectedValue = '',
        string $actualValue = ''
    ): BasilTestCaseInterface {
        $test = \Mockery::mock(BasilTestCaseInterface::class);
        $test
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $test
            ->shouldReceive('getBasilStepName')
            ->andReturn($basilStepName);

        $test
            ->shouldReceive('getHandledStatements')
            ->andReturn($handledStatements);

        $test
            ->shouldReceive('getExpectedValue')
            ->andReturn($expectedValue);

        $test
            ->shouldReceive('getExaminedValue')
            ->andReturn($actualValue);

        return $test;
    }
}