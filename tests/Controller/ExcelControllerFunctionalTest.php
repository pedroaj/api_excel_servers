<?php

// tests/Controller/ExcelControllerFunctionalTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExcelControllerFunctionalTest extends WebTestCase
{
    public function testExcelPageLoad()
    {
        $client = static::createClient();

        // Make a GET request to the /excel page
        $client->request('GET', '/excel');

        // Assert that the response is successful (HTTP status code 200)
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
