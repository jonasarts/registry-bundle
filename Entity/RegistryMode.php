<?php

/*
 * This file is part of the Registry bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\RegistryBundle\Entity;

abstract class RegistryMode
{
    const MODE_DOCTRINE = 1;
    const MODE_REDIS = 2;

    private static $_constants = null;  // array

    private static function getConstants()
    {
        if (is_null(self::$_constants)) {
            $rc = new \ReflectionClass(get_called_class());
            self::$_constants = $rc->getConstants();
        }

        return self::$_constants;
    }

    public static function getAll()
    {
        //return array(self::CONFIRMED, self::TENTATIVE, self::INTERNAL);
        return self::getConstants();
    }

    public static function getChoices()
    {
        $array = array();

        foreach (self::getConstants() as $value) {
            $array[$value] = self::getString($value);
        }

        return $array;
    }

    public static function getString($value)
    {
        switch ($value) {
            case self::MODE_DOCTRINE:
                return 'mode.doctrine';
                break;
            case self::MODE_REDIS:
                return 'mode.redis';
                break;
        }
    }
}
