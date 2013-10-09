<?php

namespace jonasarts\Bundle\RegistryBundle\Entity;

use Symfony\Component\HttpFoundation\ParameterBag;

class SystemBag extends ParameterBag
{
    /**
     * Constructor.
     *
     * @param array $parameters An array of system entities
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
    public function replace(array $systemkeys = array())
    {
        $this->parameters = array();
        $this->add($systemkeys);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function set($key, $value)
    {
        if (!is_array($value) && !$value instanceof System) {
            throw new \InvalidArgumentException('Must be an array or an instance of System.');
        }

        parent::set($key, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function add(array $systemkeys = array())
    {
        //foreach ($systemkeys as $key => $system) {
        //    $this->set($key, $system);
        //}

        foreach ($systemkeys as $system) {
            $this->set($system->getSystemKey().'/'.$system->getName(), $system);
        }
    }

    /**
     * Return a value
     */
    public function getValue($systemkey, $default = NULL)
    {
        $s = $this->get($systemkey);
        if (!$s) {
            return $default;
        } else {
            return $s->getValue();
        }
    }

    /**
     * Dump entity as string
     */
    public function __toString()
    {
        $result = "";

        foreach ($this->parameters as $system) {
            if (trim($result) != "") $result .= ", ";
            $result .= $system->__toString();
        }

        return $result;
    }

}