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

use Symfony\Component\HttpFoundation\ParameterBag;

class RegistryBag extends ParameterBag
{
    /**
     * Constructor.
     *
     * @param array $parameters An array of registry entities
     *
     * @api
     */
    public function __construct(array $parameters = array())
    {
        $this->replace($parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function replace(array $registrykeys = array())
    {
        $this->parameters = array();
        $this->add($registrykeys);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function set($key, $value)
    {
        if (!is_array($value) && !$value instanceof Registry) {
            throw new \InvalidArgumentException('Must be an array or an instance of Registry.');
        }

        parent::set($key, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function add(array $registrykeys = array())
    {
        //foreach ($registrykeys as $key => $registry) {
        //    $this->set($key, $registry);
        //}

        foreach ($registrykeys as $registry) {
            $this->set($registry->getRegistryKey().'/'.$registry->getName(), $registry);
        }
    }

    /**
     * Return a value
     */
    public function getValue($registrykey, $default = NULL)
    {
        $r = $this->get($registrykey);
        if (!$r) {
            return $default;
        } else {
            return $r->getValue();
        }
    }

    /**
     * Dump entity as a string
     */
    public function __toString()
    {
        $result = "";

        foreach ($this->parameters as $registry) {
            if (trim($result) != "") $result .= ", ";
            $result .= $registry->__toString();
        }

        return $result;
    }

}