<?php

namespace jonasarts\Bundle\RegistryBundle;

use Doctrine\ORM\EntityManager;

use jonasarts\Bundle\RegistryBundle\Entity\Registry;
use jonasarts\Bundle\RegistryBundle\Entity\RegistryBag;
use jonasarts\Bundle\RegistryBundle\Entity\System;
use jonasarts\Bundle\RegistryBundle\Entity\SystemBag;

use Symfony\Component\Yaml\Yaml;

class RegistryManager
{
    private $em;

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
                return strtotime($entity->getValue());
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
                return $default;
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

        if ($result == null) { // type of result is set to correct type, don't use ===
            $root = __DIR__.'/../../../';
            $yaml = Yaml::parse($root.'app/config/'.'registry.yml');
            
            if (is_array($yaml) && array_key_exists($registrykey.'/'.$name, $yaml['registry'])) {
                $result = $yaml['registry'][$registrykey.'/'.$name];
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
                    if (strtotime($result) !== false)
                        $result = 0;
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
                return strtotime($entity->getValue());
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
                return $default;
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

        if ($result == null) { // type of result is set to correct type, don't use ===
            $root = __DIR__.'/../../../';
            $yaml = Yaml::parse($root.'app/config/'.'registry.yml');
            
            if (is_array($yaml) && array_key_exists($systemkey.'/'.$name, $yaml['system'])) {
                $result = $yaml['system'][$systemkey.'/'.$name];
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
                    if (strtotime($result) !== false)
                        $result = 0;
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
        $entity->setValue($value);

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