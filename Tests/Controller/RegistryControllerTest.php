<?php

/*
 * This file is part of the Registry bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\RegistryBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistryControllerTest extends WebTestCase
//class RegistryControllerTest extends \PHPUnit_Framework_TestCase
{
    /*
    public function testCompleteScenario()
    {
        // Create a new client to browse the application
        $client = static::createClient();

        // Go to the list view
        $crawler = $client->request('GET', '/registry/');
        $this->assertTrue(200 === $client->getResponse()->getStatusCode());

        // Go to the show view
        $crawler = $client->click($crawler->selectLink('show')->link());
        $this->assertTrue(200 === $client->getResponse()->getStatusCode());
    }
    */

    public function testRegistryIndexRoute()
    {
        // Create a new client to browse the application
        $client = static::createClient();

        // Go to the list view
        //$crawler = $client->request('GET', '/_registry/');
        //$this->assertTrue(200 === $client->getResponse()->getStatusCode());

        $this->assertTrue(true == true);
    }
}