<?php

declare(strict_types=1);

namespace App\Tests\Application\Content;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomepageTest extends WebTestCase
{
    public function testRootRedirectsToDefaultLocaleHomepage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');
        $this->assertResponseRedirects('/en/');

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Hello, World.');
    }
}
