<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryFactoryTest extends AbstractBaseTest
{
    /**
     * @var SummaryFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SummaryFactory(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider createForElementalExistenceAssertionDataProvider
     */
    public function testCreateForElementalExistenceAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
            $this->factory->createForElementalExistenceAssertion($elementIdentifier, $comparison)
        );
    }

    public function createForElementalExistenceAssertionDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();
        $grandparentParentChildIdentifier = '$".grandparent":5 >> $".parent":4 >> $".child":3';

        return [
            'non-derived non-descendant element exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element exists assertion, CSS selector, ordinal position 2' => [
                'elementIdentifier' => new ElementIdentifier('.selector', 2),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$".selector":2') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('2') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant attribute exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new AttributeIdentifier('.selector', 'attribute_name'),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Attribute ' . $cof->createComment('$".selector".attribute_name') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - attribute name: ' . $cof->createComment('attribute_name') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element exists assertion, XPath expression, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('//div/h1'),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$"//div/h1"') . ' identified by:' . "\n" .
                    '    - XPath expression: ' . $cof->createComment('//div/h1') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived descendant element exists assertion (parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(
                            new ElementIdentifier('.parent')
                        ),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$".parent" >> $".child"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.child') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.parent') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived descendant element exists assertion (grandparent parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child', 3))
                        ->withParentIdentifier(
                            (new ElementIdentifier('.parent', 4))
                                ->withParentIdentifier(
                                    new ElementIdentifier('.grandparent', 5)
                                )
                        ),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment($grandparentParentChildIdentifier) . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.child') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('3') . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.parent') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('4') . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.grandparent') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('5') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element not-exists assertion' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'not-exists',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does exist'
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForElementalToScalarComparisonAssertionDataProvider
     */
    public function testCreateForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
            $this->factory->createForElementalToScalarComparisonAssertion(
                $elementIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForElementalToScalarComparisonAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'is' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element ' . $consoleOutputFactory->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $consoleOutputFactory->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $consoleOutputFactory->createComment('1') . "\n" .
                    '  is not equal to expected value' . "\n" .
                    '  - expected: ' . $consoleOutputFactory->createComment('expected') . "\n" .
                    '  - actual:   ' . $consoleOutputFactory->createComment('actual')
                ,
            ],
            'is-not' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* Element ' . $consoleOutputFactory->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $consoleOutputFactory->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $consoleOutputFactory->createComment('1') . "\n" .
                    '  is equal to expected value' . "\n" .
                    '  - expected: ' . $consoleOutputFactory->createComment('expected') . "\n" .
                    '  - actual:   ' . $consoleOutputFactory->createComment('expected')
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForScalarToScalarComparisonAssertionDataProvider
     */
    public function testCreateScalarToScalarComparisonAssertion(
        string $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
            $this->factory->createForScalarToScalarComparisonAssertion(
                $identifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForScalarToScalarComparisonAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'is' => [
                'identifier' => '$page.title',
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* $page.title is not equal to expected value' . "\n" .
                    '  - expected: ' . $consoleOutputFactory->createComment('expected') . "\n" .
                    '  - actual:   ' . $consoleOutputFactory->createComment('actual')
                ,
            ],
            'is-not' => [
                'identifier' => '$page.title',
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* $page.title is equal to expected value' . "\n" .
                    '  - expected: ' . $consoleOutputFactory->createComment('expected') . "\n" .
                    '  - actual:   ' . $consoleOutputFactory->createComment('expected')
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForElementalToElementalComparisonAssertionDataProvider
     */
    public function testCreateForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
            $this->factory->createForElementalToElementalComparisonAssertion(
                $identifier,
                $valueIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForElementalToElementalComparisonAssertionDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();

        return [
            'is' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$".identifier"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.identifier') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  is not equal to element ' . $cof->createComment('$".value"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.value') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    "\n" .
                    '  - expected: ' . $cof->createComment('expected') . "\n" .
                    '  - actual:   ' . $cof->createComment('actual')
                ,
            ],
            'is-not' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* Element ' . $cof->createComment('$".identifier"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.identifier') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  is equal to element ' . $cof->createComment('$".value"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.value') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    "\n" .
                    '  - expected: ' . $cof->createComment('expected') . "\n" .
                    '  - actual:   ' . $cof->createComment('expected')
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForScalarToElementalComparisonDataProvider
     */
    public function testCreateForScalarToElementalComparison(
        string $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummary
    ) {
        $this->assertSame(
            $expectedSummary,
            $this->factory->createForScalarToElementalComparisonAssertion(
                $identifier,
                $valueIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForScalarToElementalComparisonDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();

        return [
            'is' => [
                'identifier' => '$page.title',
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* $page.title is not equal to element ' .
                    $cof->createComment('$".value"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.value') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    "\n" .
                    '  - expected: ' . $cof->createComment('expected') . "\n" .
                    '  - actual:   ' . $cof->createComment('actual')
                ,
            ],
            'is-not' => [
                'identifier' => '$page.title',
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* $page.title is equal to element ' .
                    $cof->createComment('$".value"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.value') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    "\n" .
                    '  - expected: ' . $cof->createComment('expected') . "\n" .
                    '  - actual:   ' . $cof->createComment('expected')
                ,
            ],
        ];
    }
}