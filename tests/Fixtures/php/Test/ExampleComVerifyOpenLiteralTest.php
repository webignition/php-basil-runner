<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BaseBasilTestCase\Statement;

class ExampleComVerifyOpenLiteralTest extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com/');
        self::setBasilTestPath('{{ test_path }}');
    }

    public function testF0f81bc625442f2edd8f05ccc64de6b1()
    {
        $this->setBasilStepName('verify page is open');

        // $page.url is "https://example.com/"
        $statement = Statement::createAssertion('$page.url is "https://example.com/"');
        $this->currentStatement = $statement;
        $this->expectedValue = "https://example.com/" ?? null;
        $this->examinedValue = self::$client->getCurrentURL() ?? null;
        $this->assertEquals(
            $this->expectedValue,
            $this->examinedValue,
            '{
            "assertion": {
                "source": "$page.url is \\"https:\\/\\/example.com\\/\\"",
                "identifier": "$page.url",
                "comparison": "is",
                "value": "\\"https:\\/\\/example.com\\/\\""
            }
        }'
        );
        $this->completedStatements[] = $statement;
    }
}
