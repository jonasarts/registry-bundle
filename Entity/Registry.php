<?php

namespace jonasarts\Bundle\RegistryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * ja\RegistryBundle\Entity\Registry
 *
 * @ORM\Table(name="registry")
 * @ORM\Entity(repositoryClass="ja\RegistryBundle\Entity\RegistryRepository")
 * @UniqueEntity({"userid", "registrykey", "name"})
 */
class Registry
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
     * @var integer $userid;
     * @ORM\Column(name="userid", type="integer") 
     */
    private $userid;
    
    /**
     * @var string $registrykey
     * @ORM\Column(name="registrykey", type="string", length=255)
     */
    private $registrykey;
    
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
     * Populate registry entity with given values.
     */
    public function LoadByValues($id, $userid, $registrykey, $name, $type, $value)
    {
        $this->id = $id;
        $this->userid = $userid;
        $this->registrykey = $registrykey;
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Populate registry entity with given array values.
     */
    public function LoadByArray(array $row)
    {
        $this->id = $row['id'];
        $this->userid = $row['userid'];
        $this->registrykey = $row['registrykey'];
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
     * Set userid
     *
     * @param integer $userid
     * @return Registry
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;
    
        return $this;
    }

    /**
     * Get userid
     *
     * @return integer 
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set registrykey
     *
     * @param string $registrykey
     * @return Registry
     */
    public function setRegistryKey($registrykey)
    {
        $this->registrykey = $registrykey;
    
        return $this;
    }

    /**
     * Get registrykey
     *
     * @return string 
     */
    public function getRegistryKey()
    {
        return $this->registrykey;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Registry
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
     * @return Registry
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
     * @return Registry
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
     * Dump registry entitiy to string
     */
    public function __toString()
    {
        return $this->registrykey.'/'.$this->name . " => " . $this->value . " (" . $this->userid . ")";
    }
}