<?php

/*
 * This file is part of the Registry bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\RegistryBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistryManagerTest extends WebTestCase
{
    /*
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $rm;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
        $this->rm = static::$kernel->getContainer()
            ->get('registry_manager');
    }

    // registry tests

    public function testRegistryReadDefaultBln()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name', 'bln', true);

        $this->assertEquals($r, true);
    }

    public function testRegistryReadDefaultInt()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name', 'int', 5);

        $this->assertEquals(5, $r);
    }

    public function testRegistryReadDefaultStr()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name', 'str', 'test');

        $this->assertEquals('test', $r);
    }

    public function testRegistryReadDefaultFlt()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name', 'flt', 0.5);

        $this->assertEquals(0.5, $r);
    }

    public function testRegistryReadDefaultDat()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name', 'dat', strtotime('2013-10-16'));

        $this->assertEquals(strtotime('2013-10-16'), $r);
    }

    public function testRegistryReadInt()
    {
        $r = $this->rm->registryRead(0, 'settings', 'page_size', 'int');

        $this->assertEquals(10, $r);
    }

    // system tests

    public function testSystemReadDefaultBln()
    {
        $r = $this->rm->systemReadDefault('key', 'name', 'bln', true);

        $this->assertEquals(true, $r);
    }

    public function testSystemReadDefaultInt()
    {
        $r = $this->rm->systemReadDefault('key', 'name', 'int', 5);

        $this->assertEquals(5, $r);
    }

    public function testSystemReadDefaultStr()
    {
        $r = $this->rm->systemReadDefault('key', 'name', 'str', 'test');

        $this->assertEquals('test', $r);
    }
    
    public function testSystemReadDefaultFlt()
    {
        $r = $this->rm->systemReadDefault('key', 'name', 'flt', 0.5);

        $this->assertEquals(0.5, $r);
    }
    
    public function testSystemReadDefaultDat()
    {
        $r = $this->rm->systemReadDefault('key', 'name', 'dat', strtotime('2013-10-16'));

        $this->assertEquals(strtotime('2013-10-16'), $r);
    }

    public function testSystemReadInt()
    {
        $r = $this->rm->systemRead('settings', 'page_size', 'int');

        $this->assertEquals(10, $r);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}