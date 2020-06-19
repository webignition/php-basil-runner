<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\DataSet\KeyValueCollection;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\BasilRunner\Model\ResultPrinter\StepName;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\ExceptionFactory;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\StatementLineFactory;

class StepRenderer
{
    private const INDENT = '  ';

    private StatementLineFactory $statementLineFactory;
    private SummaryHandler $summaryHandler;
    private ExceptionFactory $exceptionFactory;

    public function __construct(
        StatementLineFactory $statementLineFactory,
        SummaryHandler $summaryHandler,
        ExceptionFactory $exceptionFactory
    ) {
        $this->statementLineFactory = $statementLineFactory;
        $this->summaryHandler = $summaryHandler;
        $this->exceptionFactory = $exceptionFactory;
    }

    public function render(Step $step): string
    {
        $stepName = new StepName($step);

        $content = $stepName->render() . "\n";

        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            $keyValueCollection = KeyValueCollection::fromDataSet($dataSet);
            $content .= $keyValueCollection->render() . "\n\n";
        }

        $content .= $this->renderCompletedStatements($step);

        $failedStatementLine = $step->getFailedStatementLine();

        if ($failedStatementLine instanceof StatementLine) {
            if (0 !== count($step->getCompletedStatementLines())) {
                $content .= "\n";
            }

            $content .= $this->renderFailedStatement(
                $failedStatementLine,
                $step->getExpectedValue(),
                $step->getActualValue()
            );
        }

        $lastException = $step->getLastException();
        if ($lastException instanceof \Throwable) {
            $exceptionModel = $this->exceptionFactory->create($lastException);
            $exceptionContent = '* ' . $exceptionModel->render();

            $content .= "\n" . $this->indent($exceptionContent, 2);
        }

        return $content;
    }

    private function renderCompletedStatements(Step $step): string
    {
        $renderableStatements = [];
        foreach ($step->getCompletedStatementLines() as $completedStatementLine) {
            if (false === $completedStatementLine->getIsDerived()) {
                $renderableStatements[] = $this->statementLineFactory->create($completedStatementLine);
            }
        }

        return (new RenderableCollection($renderableStatements))->render();
    }

    private function renderFailedStatement(
        StatementLine $statementLine,
        string $expectedValue,
        string $actualValue
    ): string {
        $renderableStatement = $this->statementLineFactory->create($statementLine);

        $content = $renderableStatement->render();
        $summary = null;

        $statement = $statementLine->getStatement();
        if ($statement instanceof AssertionInterface) {
            $summary = $this->summaryHandler->handle(
                $statement,
                $expectedValue,
                $actualValue
            );
        }

        if (is_string($summary)) {
            $content .= "\n";
            $content .= $this->indent($summary, 2);
        }

        return $content;
    }

    private function indent(string $content, int $depth = 1): string
    {
        $indentContent = str_repeat(self::INDENT, $depth);

        $lines = explode("\n", $content);

        array_walk($lines, function (&$line) use ($indentContent) {
            $line = $indentContent . $line;
        });

        return implode("\n", $lines);
    }
}
