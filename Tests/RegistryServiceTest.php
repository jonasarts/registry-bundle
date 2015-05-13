<?php

/*
 * This file is part of the Registry bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\RegistryBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * This tests are executed on a real database!
 * Therefore they need a proper database setup.
 * Best practice is to use config_test.yml.
 * 
 * Important assumption:
 * The tests below must be executed in order
 * (to maintain write before delete operations).
 */
class RegistryServiceTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Registry
     */
    private $rm;

    const _user = 2;
    const _bln = true;
    const _int = 10;
    const _str = 'test string';
    const _flt = 0.5;
    const _dat = '2013-10-16';

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
        $this->rm = static::$kernel->getContainer()
            ->get('registry');
    }

    // registry tests

    public function testRegistryReadDefaultBln()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_bln', 'bln', true);

        $this->assertEquals($r, true);
    }

    public function testRegistryReadDefaultInt()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_int', 'int', 5);

        $this->assertEquals(5, $r);
    }

    public function testRegistryReadDefaultStr()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_str', 'str', 'test');

        $this->assertEquals('test', $r);
    }

    public function testRegistryReadDefaultFlt()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_flt', 'flt', 5.5);

        $this->assertEquals(5.5, $r);
    }

    public function testRegistryReadDefaultDat()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_dat', 'dat', strtotime('2013-10-16'));

        $this->assertEquals(strtotime('2013-10-16'), $r);
    }

    public function testRegistryReadDefaultNull()
    {
        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_null', 'int', null);

        $this->assertEquals(null, $r);
    }

    public function testRegistryReadOnce()
    {
        // read once must remove the key after reading once

        $this->rm->RegistryWrite(0, 'once_key', 'name_bln', 'bln', true);

        $r = $this->rm->RegistryReadOnce(0, 'once_key', 'name_bln', 'bln');

        $this->assertEquals($r, true);

        //$r = $this->rm->RegistryRead(0, 'once_key', 'name_bln', 'bln'); // key must be gone
        $r = $this->rm->RegistryKeyExists(0, 'once_key', 'name_bln', 'bln');

        $this->assertEquals($r, false);
    }

    public function testRegistryWriteBln()
    {
        $this->rm->RegistryWrite(0, 'key', 'name_bln', 'bln', self::_bln);

        $r = $this->rm->RegistryRead(0, 'key', 'name_bln', 'bln');

        $this->assertEquals($r, self::_bln);
    }

    public function testRegistryWriteUserBln()
    {
        $this->rm->RegistryWrite(self::_user, 'key', 'name_bln', 'bln', !self::_bln);

        $r = $this->rm->RegistryRead(self::_user, 'key', 'name_bln', 'bln');

        $this->assertEquals($r, !self::_bln);
    }

    /*
    public function testRegistryReadBln()
    {
        $r = $this->rm->RegistryRead(0, 'key', 'name_bln', 'bln');

        $this->assertEquals(true, $r);
    }
    */

    /**
     * @depends testRegistryWriteUserBln
     */
    public function testRegistryDeleteUserBln()
    {
        $this->rm->RegistryDelete(self::_user, 'key', 'name_bln', 'bln');

        $r = $this->rm->RegistryReadDefault(self::_user, 'key', 'name_bln', 'bln', !self::_bln); // this must read WriteBln value

        $this->assertEquals($r, self::_bln);
    }

    /**
     * @depends testRegistryWriteBln
     */
    public function testRegistryDeleteBln()
    {
        $this->rm->RegistryDelete(0, 'key', 'name_bln', 'bln');

        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_bln', 'bln', !self::_bln);

        $this->assertEquals($r, !self::_bln);
    }

    public function testRegistryWriteInt()
    {
        $this->rm->RegistryWrite(0, 'key', 'name_int', 'int', self::_int);

        $r = $this->rm->RegistryRead(0, 'key', 'name_int', 'int');

        $this->assertEquals($r, self::_int);
    }

    public function testRegistryWriteUserInt()
    {
        $this->rm->RegistryWrite(self::_user, 'key', 'name_int', 'int', self::_int - 1);

        $r = $this->rm->RegistryRead(self::_user, 'key', 'name_int', 'int');

        $this->assertEquals($r, self::_int - 1);
    }

    /*
    public function testRegistryReadInt()
    {
        $r = $this->rm->RegistryRead(0, 'key', 'name_int', 'int');

        $this->assertEquals($r, 10);
    }
    */

    /**
     * @depends testRegistryWriteUserInt
     */
    public function testRegistryDeleteUserInt()
    {
        $this->rm->RegistryDelete(self::_user, 'key', 'name_int', 'int');

        $r = $this->rm->RegistryReadDefault(self::_user, 'key', 'name_int', 'int', self::_int - 1); // this must read WriteInt value

        $this->assertEquals($r, self::_int);
    }

    /**
     * @depends testRegistryWriteInt
     */
    public function testRegistryDeleteInt()
    {
        $this->rm->RegistryDelete(0, 'key', 'name_int', 'int');

        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_int', 'int', self::_int + 1);

        $this->assertEquals($r, self::_int + 1);
    }

    public function testRegistryWriteStr()
    {
        $this->rm->RegistryWrite(0, 'key', 'name_str', 'str', self::_str);

        $r = $this->rm->RegistryRead(0, 'key', 'name_str', 'str');

        $this->assertEquals($r, self::_str);
    }

    public function testRegistryWriteUserStr()
    {
        $this->rm->RegistryWrite(self::_user, 'key', 'name_str', 'str', self::_str . self::_str);

        $r = $this->rm->RegistryRead(self::_user, 'key', 'name_str', 'str');

        $this->assertEquals($r, self::_str . self::_str);
    }

    /*
    public function testRegistryReadStr()
    {
        $r = $this->rm->RegistryRead(0, 'key', 'name_str', 'str');

        $this->assertEquals($r, 'test');
    }
    */

    /**
     * @depends testRegistryWriteUserStr
     */
    public function testRegistryDeleteUserStr()
    {
        $this->rm->RegistryDelete(self::_user, 'key', 'name_str', 'str');

        $r = $this->rm->RegistryReadDefault(self::_user, 'key', 'name_str', 'str', self::_str . 'default'); // this must read WriteStr value

        $this->assertEquals($r, self::_str);
    }

    /**
     * @depends testRegistryWriteStr
     */
    public function testRegistryDeleteStr()
    {
        $this->rm->RegistryDelete(0, 'key', 'name_str', 'str');

        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_str', 'str', self::_str . 'default');

        $this->assertEquals($r, self::_str . 'default');
    }

    public function testRegistryWriteFlt()
    {
        $this->rm->RegistryWrite(0, 'key', 'name_flt', 'flt', self::_flt);

        $r = $this->rm->RegistryRead(0, 'key', 'name_flt', 'flt');

        $this->assertEquals($r, self::_flt);
    }

    public function testRegistryWriteUserFlt()
    {
        $this->rm->RegistryWrite(self::_user, 'key', 'name_flt', 'flt', self::_flt + 0.1);

        $r = $this->rm->RegistryRead(self::_user, 'key', 'name_flt', 'flt');

        $this->assertEquals($r, self::_flt + 0.1);
    }

    /*
    public function testRegistryReadFlt()
    {
        $r = $this->rm->RegistryRead(0, 'key', 'name_flt', 'flt');

        $this->assertEquals($r, 0.5);
    }
    */

    /**
     * @depends testRegistryWriteUserFlt
     */
    public function testRegistryDeleteUserFlt()
    {
        $this->rm->RegistryDelete(self::_user, 'key', 'name_flt', 'flt');

        $r = $this->rm->RegistryReadDefault(self::_user, 'key', 'name_flt', 'flt', self::_flt + 0.25); // this must read WriteFlt value

        $this->assertEquals($r, self::_flt);
    }

    /**
     * @depends testRegistryWriteFlt
     */
    public function testRegistryDeleteFlt()
    {
        $this->rm->RegistryDelete(0, 'key', 'name_flt', 'flt');

        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_flt', 'flt', self::_flt + 0.25);

        $this->assertEquals($r, self::_flt + 0.25);
    }

    public function testRegistryWriteDat()
    {
        $this->rm->RegistryWrite(0, 'key', 'name_dat', 'dat', strtotime(self::_dat));

        $r = $this->rm->RegistryRead(0, 'key', 'name_dat', 'dat');

        $this->assertEquals($r, strtotime(self::_dat));
    }

    public function testRegistryWriteUserDat()
    {
        $this->rm->RegistryWrite(self::_user, 'key', 'name_dat', 'dat', strtotime('1980-01-01'));

        $r = $this->rm->RegistryRead(self::_user, 'key', 'name_dat', 'dat');

        $this->assertEquals($r, strtotime('1980-01-01'));
    }

    /*
    public function testRegistryReadDat()
    {
        $r = $this->rm->RegistryRead(0, 'key', 'name_dat', 'dat');

        $this->assertEquals($r, strtotime('2013-10-16'));
    }
    */

    /**
     * @depends testRegistryWriteUserDat
     */
    public function testRegistryDeleteUserDat()
    {
        $this->rm->RegistryDelete(self::_user, 'key', 'name_dat', 'dat');

        $r = $this->rm->RegistryReadDefault(self::_user, 'key', 'name_dat', 'dat', strtotime('now')); // this must read WriteDat value

        $this->assertEquals($r, strtotime(self::_dat));
    }

    /**
     * @depends testRegistryWriteDat
     */
    public function testRegistryDeleteDat()
    {
        $this->rm->RegistryDelete(0, 'key', 'name_dat', 'dat');

        $r = $this->rm->RegistryReadDefault(0, 'key', 'name_dat', 'dat', strtotime('now'));

        $this->assertEquals($r, strtotime('now'));
    }

    public function testRegistryWriteUser0MatchingVale()
    {
        // if user-key-value equals user-0-value, the user-key-value must be deleted on write

        $this->rm->RegistryWrite(0, 'key', 'name_int', 'int', self::_int); // user-0-value
        $this->rm->RegistryWrite(1, 'key', 'name_int', 'int', self::_int+1); // user-key-value
        
        $r = $this->rm->RegistryRead(1, 'key', 'name_int', 'int');

        $this->assertEquals($r, self::_int+1);

        $this->rm->RegistryWrite(1, 'key', 'name_int', 'int', self::_int); // this must delete the user-key-value

        //$r = $this->rm->RegistryRead(1, 'key', 'name_int', 'int');
        $r = $this->rm->RegistryKeyExists(1, 'key', 'name_int', 'int');

        //$this->assertEquals($r, self::_int);
        $this->assertEquals($r, false);

        $this->rm->RegistryDelete(0, 'key', 'name_int', 'int');
    }

    public function testGetRegistryItems()
    {
        $this->rm->RegistryWrite(self::_user, 'key/path1', 'name1', 'int', self::_int);
        $this->rm->RegistryWrite(self::_user, 'key/path1/sub', 'name1', 'int', self::_int + 1);
        $this->rm->RegistryWrite(self::_user, 'key/path2', 'name2', 'str', self::_str);
        
        $a = $this->rm->getRegistryItems(self::_user, 'key\/path1');

        $this->assertEquals(2, count($a));

        foreach ($a as $entity) {
            if ($entity->getRegistryKey() == 'key/path1') {
                $this->assertEquals(self::_int, $entity->getValue());
                $this->assertEquals('int', $entity->getType());
            } else if ($entity->getRegistryKey() == 'key/path1/sub') {
                $this->assertEquals(self::_int + 1, $entity->getValue());
                $this->assertEquals('int', $entity->getType());
            }
        }

        $this->rm->RegistryDelete(self::_user, 'key/path1', 'name1', 'int');
        $this->rm->RegistryDelete(self::_user, 'key/path1/sub', 'name1', 'int');
        $this->rm->RegistryDelete(self::_user, 'key/path2', 'name2', 'str');
    }

    //
    // system tests
    //

    public function testSystemReadDefaultBln()
    {
        $r = $this->rm->systemReadDefault('key', 'name_bln', 'bln', true);

        $this->assertEquals(true, $r);
    }

    public function testSystemReadDefaultInt()
    {
        $r = $this->rm->systemReadDefault('key', 'name_int', 'int', 5);

        $this->assertEquals(5, $r);
    }

    public function testSystemReadDefaultStr()
    {
        $r = $this->rm->systemReadDefault('key', 'name_str', 'str', 'test');

        $this->assertEquals('test', $r);
    }
    
    public function testSystemReadDefaultFlt()
    {
        $r = $this->rm->systemReadDefault('key', 'name_flt', 'flt', 5.5);

        $this->assertEquals(5.5, $r);
    }
    
    public function testSystemReadDefaultDat()
    {
        $r = $this->rm->systemReadDefault('key', 'name_dat', 'dat', strtotime('2013-10-16'));

        $this->assertEquals(strtotime('2013-10-16'), $r);
    }

    public function testSystemReadDefaultNull()
    {
        $r = $this->rm->systemReadDefault('key', 'name_null', 'int', null);

        $this->assertEquals(null, $r);
    }

    public function testSystemReadOnce()
    {
        $this->rm->SystemWrite('once_key', 'name_bln', 'bln', true);

        $r = $this->rm->SystemReadOnce('once_key', 'name_bln', 'bln');

        $this->assertEquals(true, $r);

        $r = $this->rm->SystemRead('once_key', 'name_bln', 'bln');

        $this->assertEquals(false, $r);
    }

    public function testSystemWriteBln()
    {
        $this->rm->SystemWrite('key', 'name_bln', 'bln', self::_bln);

        $r = $this->rm->SystemRead('key', 'name_bln', 'bln');

        $this->assertEquals(self::_bln, $r);
    }

    public function testSystemDeleteBln()
    {
        $this->rm->SystemDelete('key', 'name_bln', 'bln');

        $r = $this->rm->SystemReadDefault('key', 'name_bln', 'bln', !self::_bln);

        $this->assertEquals(!self::_bln, $r);
    }

    public function testSystemWriteInt()
    {
        $this->rm->SystemWrite('key', 'name_int', 'int', self::_int);

        $r = $this->rm->SystemRead('key', 'name_int', 'int');

        $this->assertEquals(self::_int, $r);
    }

    public function testSystemDeleteInt()
    {
        $this->rm->SystemDelete('key', 'name_int', 'int');

        $r = $this->rm->SystemReadDefault('key', 'name_int', 'int', self::_int + 1);

        $this->assertEquals(self::_int + 1, $r);
    }

    public function testSystemWriteStr()
    {
        $this->rm->SystemWrite('key', 'name_str', 'str', self::_str);

        $r = $this->rm->SystemRead('key', 'name_str', 'str');

        $this->assertEquals(self::_str, $r);
    }

    public function testSystemDeleteStr()
    {
        $this->rm->SystemDelete('key', 'name_str', 'str');

        $r = $this->rm->SystemReadDefault('key', 'name_str', 'str', self::_str . 'default');

        $this->assertEquals(self::_str . 'default', $r);
    }

    public function testSystemWriteFlt()
    {
        $this->rm->SystemWrite('key', 'name_flt', 'flt', self::_flt);

        $r = $this->rm->SystemRead('key', 'name_flt', 'flt');

        $this->assertEquals(self::_flt, $r);
    }

    public function testSystemDeleteFlt()
    {
        $this->rm->SystemDelete('key', 'name_flt', 'flt');

        $r = $this->rm->SystemReadDefault('key', 'name_flt', 'flt', self::_flt - 0.1);

        $this->assertEquals(self::_flt - 0.1, $r);
    }

    public function testSystemWriteDat()
    {
        $this->rm->SystemWrite('key', 'name_dat', 'dat', strtotime(self::_dat));

        $r = $this->rm->SystemRead('key', 'name_dat', 'dat');

        $this->assertEquals(strtotime(self::_dat), $r);
    }

    public function testSystemDeleteDat()
    {
        $this->rm->SystemDelete('key', 'name_dat', 'dat');

        $r = $this->rm->SystemReadDefault('key', 'name_dat', 'dat', strtotime('1990-01-01'));

        $this->assertEquals(strtotime('1990-01-01'), $r);
    }

    public function testGetSystemItems()
    {
        $this->rm->SystemWrite('key/path1', 'name1', 'int', self::_int);
        $this->rm->SystemWrite('key/path1/sub', 'name1', 'int', self::_int + 1);
        $this->rm->SystemWrite('key/path2', 'name2', 'str', self::_str);
        
        $a = $this->rm->getSystemItems('key\/path1');

        $this->assertEquals(2, count($a));

        foreach ($a as $entity) {
            if ($entity->getSystemKey() == 'key/path1') {
                $this->assertEquals(self::_int, $entity->getValue());
                $this->assertEquals('int', $entity->getType());
            } else if ($entity->getSystemKey() == 'key/path1/sub') {
                $this->assertEquals(self::_int + 1, $entity->getValue());
                $this->assertEquals('int', $entity->getType());
            }
        }

        $this->rm->SystemDelete('key/path1', 'name1', 'int');
        $this->rm->SystemDelete('key/path1/sub', 'name1', 'int');
        $this->rm->SystemDelete('key/path2', 'name2', 'str');
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}