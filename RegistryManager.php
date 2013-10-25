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

use Doctrine\ORM\EntityManager;

use jonasarts\Bundle\RegistryBundle\Entity\Registry;
use jonasarts\Bundle\RegistryBundle\Entity\RegistryBag;
use jonasarts\Bundle\RegistryBundle\Entity\System;
use jonasarts\Bundle\RegistryBundle\Entity\SystemBag;

use Symfony\Component\Yaml\Yaml;

class RegistryManager
{
    private $em; // entity manager
    private $yaml; // registry default key-name/values yaml file
    
    private $has_yaml; // boolean; use yaml default values?

    private function getEntityManager()
    {
        if (!$this->em) {
            throw $this->createNotFoundException('RegistryManager: No entity manager found');
        }

        return $this->em;
    }

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;

        $root = __DIR__.'/../../../../../../'; // todo: how to optimize this
        $filename = $root.'app/config/registry.yml'; // todo: perhaps this should be customizable
        $this->has_yaml = file_exists($filename);

        if ($this->has_yaml) {
            $this->yaml = Yaml::parse($filename);
        }
    }

    /**
     * Registry Methods
     */

    /**
     * Delete registry key from database.
     */
    public function RegistryDelete($userid, $registrykey, $name)
    {
        //$em = $this->getDoctrine()->getManager();
        $em = $this->getEntityManager();

        $entity = $em->getRepository('RegistryBundle:Registry')->findOneBy(array('userid' => $userid, 'registrykey' => $registrykey, 'name' => $name));

        if ($entity) {
            $em->remove($entity);
            $em->flush();
        }
    }

    /** 
     * Read registry key from database.
     * If no key is found, the default value will be returned.
     */
    public function RegistryReadDefault($userid, $registrykey, $name, $type, $default)
    {
        //$em = $this->getDoctrine()->getManager();
        $em = $this->getEntityManager();

        // find own key
        if ($userid != 0) {
            $entity = $em->getRepository('RegistryBundle:Registry')->findOneBy(array('userid' => $userid, 'registrykey' => $registrykey, 'name' => $name));
        } else {
            $entity = NULL;
        }

        if (!$entity) {
            // find default key
            $entity = $em->getRepository('RegistryBundle:Registry')->findOneBy(array('userid' => 0, 'registrykey' => $registrykey, 'name' => $name));
        }

        if ($entity) {
            switch($type) {
                case 'i':
                case 'int':
                case 'integer':
                    return (integer)$entity->getValue();
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    return (boolean)$entity->getValue();
                    break;
                case 's':
                case 'str':
                case 'string':
                    return (string)$entity->getValue();
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    return (float)$entity->getValue();
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    $value = $entity->getValue(); // this always is a string
                    if (is_numeric($value)) { // don't use is_int here
                        return (integer)$value;
                    } else {
                        return strtotime($value);
                    }
                    break;
                default:
                    return $entity->getValue();
            }
        } else {
            switch ($type) {
                case 'i':
                case 'int':
                case 'integer':
                    return (integer)$default;
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    return (boolean)$default;
                    break;
                case 's':
                case 'str':
                case 'string':
                    return (string)$default;
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    return (float)$default;
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    if ($default instanceof \DateTime) {
                        return $default;
                    } else if (is_int($default)) {
                        return $default;
                    } else if (is_string($default)) {
                        return strtotime($default);
                    }
                    break;
                default:
                    return $default;
            }
        }
    }

    /**
     * Read registry key from database.
     */
    public function RegistryRead($userid, $registrykey, $name, $type)
    {
        $result = $this->RegistryReadDefault($userid, $registrykey, $name, $type, null);    

        if (($result == null) && ($this->has_yaml)) { // type of result is set to correct type, don't use ===
            if (is_array($this->yaml) && array_key_exists($registrykey.'/'.$name, $this->yaml['registry'])) {
                $result = $this->yaml['registry'][$registrykey.'/'.$name];
            }

            switch ($type) {
                case 'i':
                case 'int':
                case 'integer':
                    if (!is_int($result)) 
                        $result = 0;
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    if (!is_bool($result))
                        $result = false;
                    break;
                case 's':
                case 'str':
                case 'string':
                    if (!is_string($result))
                        $result = '';
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    if (!is_double($result))
                        $result = 0.00;
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    if ($result instanceof \DateTime) {
                        // nothing to do
                    } else if (is_int($result)) {
                        // nothing to do
                    } else if (is_string($result)) {
                        $result = strtotime($result);
                    }
                    break;
                default:
                    // nothing
                    break;
            }
        }

        return $result;
    }

    /**
     * Write registry key to database.
     */
    public function RegistryWrite($userid, $registrykey, $name, $type, $value)
    {
        // value = default key value?
        if ($userid != 0) {
            $result = $this->RegistryRead(0, $registrykey, $name, $type);
            if ($result) {
                if ($result == $value) {
                    // equals default value, delete own key
                    $this->RegistryDelete($userid, $registrykey, $name);
                    return;
                }
            }
        }

        // not default key, insert / update
        //$em = $this->getDoctrine()->getManager();
        $em = $this->getEntityManager();

        $entity = $em->getRepository('RegistryBundle:Registry')->findOneBy(array('userid' => $userid, 'registrykey' => $registrykey, 'name' => $name));

        if (!$entity) {
            $entity = new Registry();
            $entity->setUserid($userid);
            $entity->setRegistryKey($registrykey);
            $entity->setName($name);
            $entity->setType($type);
        }

        $entity->setType($type);
        switch ($type) {
            case 'd':
            case 'dat':
            case 'date':
            case 't':
            case 'tim':
            case 'time':
                if ($value instanceof \DateTime) {
                    // convert DateTime to string
                    $value = $value->format("c");
                } else if (is_int($value)) {
                    // nothing to do
                } else if (is_string($value)) {
                    // nothing to do
                }
                $entity->setValue($value);
                break;
            default:
                $entity->setValue($value);
        }

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Read / Load a set of registry keys from database.
     */
    public function getRegistryBag($userid, $path)
    {
        $em = $this->getEntityManager();

        $entities = $em->getRepository('RegistryBundle:Registry')->loadByPath($userid, $path);

        return new RegistryBag($entities);
    }

    /**
     * System Methods
     */

    /**
     * Delete system key from database.
     */
    public function SystemDelete($systemkey, $name)
    {
        //$em = $this->getDoctrine()->getManager();
        $em = $this->getEntityManager();

        $entity = $em->getRepository('RegistryBundle:System')->findOneBy(array('systemkey' => $systemkey, 'name' => $name));

        if ($entity) {
            $em->remove($entity);
            $em->flush();
        }
    }

    /**
     * Read system key from database.
     * If no key is found, the default value will be returned.
     */
    public function SystemReadDefault($systemkey, $name, $type, $default)
    {
        //$em = $this->getDoctrine()->getManager();
        $em = $this->getEntityManager();

        // find default key
        $entity = $em->getRepository('RegistryBundle:System')->findOneBy(array('systemkey' => $systemkey, 'name' => $name));

        if ($entity) {
            switch($type) {
                case 'i':
                case 'int':
                case 'integer':
                    return (integer)$entity->getValue();
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    return (boolean)$entity->getValue();
                    break;
                case 's':
                case 'str':
                case 'string':
                    return (string)$entity->getValue();
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    return (float)$entity->getValue();
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    $value = $entity->getValue(); // this always is a string
                    if (is_numeric($value)) { // don't use is_int here
                        return (integer)$value;
                    } else {
                        return strtotime($value);
                    }
                    break;
                default:
                    return $entity->getValue();
            }
        } else {
            switch ($type) {
                case 'i':
                case 'int':
                case 'integer':
                    return (integer)$default;
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    return (boolean)$default;
                    break;
                case 's':
                case 'str':
                case 'string':
                    return (string)$default;
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    return (float)$default;
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    if ($default instanceof \DateTime) {
                        return $default;
                    } else if (is_int($default)) {
                        return $default;
                    } else if (is_string($default)) {
                        return strtotime($default);
                    }
                    break;
                default:
                    return $default;
            }
        }
    }

    /**
     * Read system key from database.
     */
    public function SystemRead($systemkey, $name, $type)
    {
        $result = $this->SystemReadDefault($systemkey, $name, $type, null); 

        if (($result == null) && ($this->has_yaml)) { // type of result is set to correct type, don't use ===
            if (is_array($this->yaml) && array_key_exists($systemkey.'/'.$name, $this->yaml['system'])) {
                $result = $this->yaml['system'][$systemkey.'/'.$name];
            }

            switch ($type) {
                case 'i':
                case 'int':
                case 'integer':
                    if (!is_int($result)) 
                        $result = 0;
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    if (!is_bool($result))
                        $result = false;
                    break;
                case 's':
                case 'str':
                case 'string':
                    if (!is_string($result))
                        $result = '';
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    if (!is_double($result))
                        $result = 0.00;
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    if ($result instanceof \DateTime) {
                        // nothing to do
                    } else if (is_int($result)) {
                        // nothing to do
                    } else if (is_string($result)) {
                        $result = strtotime($result);
                    }
                    break;
                default:
                    // nothing
                    break;
            }
        }

        return $result;
    }

    /**
     * Write system key to database.
     */
    public function SystemWrite($systemkey, $name, $type, $value)
    {
        //$em = $this->getDoctrine()->getManager();
        $em = $this->getEntityManager();

        $entity = $em->getRepository('RegistryBundle:System')->findOneBy(array('systemkey' => $systemkey, 'name' => $name));

        if (!$entity) {
            $entity = new System();
            $entity->setSystemKey($systemkey);
            $entity->setName($name);
            $entity->setType($type);
        }

        $entity->setType($type);
        switch ($type) {
            case 'd':
            case 'dat':
            case 'date':
            case 't':
            case 'tim':
            case 'time':
                if ($value instanceof \DateTime) {
                    // convert DateTime to string
                    $value = $value->format("c");
                } else if (is_int($value)) {
                    // nothing to do
                } else if (is_string($value)) {
                    // nothing to do
                }
                $entity->setValue($value);
                break;
            default:
                $entity->setValue($value);
        }

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Read / Load a set of system keys from database.
     */
    public function getSystemBag($path)
    {
        $em = $this->getEntityManager();

        $entities = $em->getRepository('RegistryBundle:System')->loadByPath($path);

        return new SystemBag($entities);
    }
}