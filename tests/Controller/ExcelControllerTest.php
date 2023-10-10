<?php

// tests/Controller/ExcelControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Controller\ExcelController;
use PHPUnit\Framework\TestCase;

class ExcelControllerTest extends WebTestCase
{
		public function testIndexPageIsSuccessful()
    {
        $client = static::createClient();

        $client->request('GET', '/excel');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Servers List API');
    }

    public function testStrToGBs()
    {
        $excelController = new ExcelController();

        // Test cases
        $testCases = [
            [
                'input' => '2x2TB',
                'expectedOutput' => 4000,
            ],
            [
                'input' => '4TB',
                'expectedOutput' => 4000,
            ],
            [
                'input' => '2TBSATA2',
                'expectedOutput' => 2000,
            ],
            [
                'input' => '16GBDDR3',
                'expectedOutput' => 16,
            ],
            [
                'input' => '2x500GBSATA2',
                'expectedOutput' => 1000,
            ],
        ];

        // Run the test cases
        foreach ($testCases as $testCase) {
            $actualOutput = $excelController->strToGBs($testCase['input']);
            $this->assertEquals($testCase['expectedOutput'], $actualOutput);
        }
    }
}
