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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * jonasarts\Bundle\RegistryBundle\Entity\System
 *
 * @ORM\Table(name="system")
 * @ORM\Entity(repositoryClass="jonasarts\Bundle\RegistryBundle\Entity\SystemRepository")
 * @UniqueEntity({"systemkey", "name"})
 */
class System
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var string $systemkey
     * @ORM\Column(name="systemkey", type="string", length=255)
     */
    private $systemkey;
    
    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", length=255) 
     */
    private $name;
    
    /**
     * @var string $type
     * @ORM\Column(name="type", type="string", length=3) 
     */
    private $type;
    
    /**
     * @var string $value
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * Populate system entity with given values.
     */
    public function LoadByValues($id, $userid, $systemkey, $name, $type, $value)
    {
        $this->id = $id;
        $this->systemkey = $systemkey;
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }
    
    /**
     * Populate system entity with given array values.
     */
    public function LoadByArray(array $row)
    {
        $this->id = $row['id'];
        $this->systemkey = $row['systemkey'];
        $this->name = $row['name'];
        $this->type = $row['type'];
        $this->value = $row['value'];
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set systemkey
     *
     * @param string $systemkey
     * @return System
     */
    public function setSystemKey($systemkey)
    {
        $this->systemkey = $systemkey;
    
        return $this;
    }

    /**
     * Get systemkey
     *
     * @return string 
     */
    public function getSystemKey()
    {
        return $this->systemkey;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return System
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return System
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return System
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Dump system entitiy to string
     */
    public function __toString()
    {
        return $this->systemkey.'/'.$this->name . " => " . $this->value;
    }
}