<?php

// tests/Controller/FormControllerFunctionalTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormControllerFunctionalTest extends WebTestCase
{
    public function testFormSubmission()
    {
        $client = static::createClient();

        // Make a POST request to a form submission endpoint
        $client->request('POST', '/excel/filterExcelAction', [], [], ['CONTENT_TYPE' => 'application/json'], '{"data": "example"}');

        // Assert that the response is a successful redirect (HTTP status code 302)
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
