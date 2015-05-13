<?php

/*
 * This file is part of the Registry bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\RegistryBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityManager;

use jonasarts\Bundle\RegistryBundle\Entity\Registry as RegKey;
use jonasarts\Bundle\RegistryBundle\Entity\RegistryBag;
use jonasarts\Bundle\RegistryBundle\Entity\RegistryMode;
use jonasarts\Bundle\RegistryBundle\Entity\System as SysKey;
use jonasarts\Bundle\RegistryBundle\Entity\SystemBag;

use Symfony\Component\Yaml\Yaml;

class Registry
{
    private $container; // service container
    private $em; // entity manager

    private $registry; // doctrine repository
    private $system; // doctrine repository
    private $redis; // phpredis client

    private $mode; // RegistryMode
    
    private $key_name_delimiter; // char

    private $use_yaml; // boolean; use yaml default values?
    private $yaml; // registry default key-name/values, array loaded from yaml file

    private $redis_prefix; // string
    private $redis_key_name_delimiter; // char

    const SYSTEM_HASH_KEY = 'system'; // redis hash key part for system keys

    /**
     * Constructor
     */
    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->em = $entityManager;
        $this->registry = null;
        $this->system = null;
        $this->redis = null;
        $this->yaml = null;

        // load
        $mode = $this->container->getParameter('registry.globals.mode');
        // $this->key_name_delimiter = $this->container->getParameter('registry.globals.delimiter');
        $this->redis_prefix = $this->container->getParameter('registry.redis.prefix');
        $this->redis_key_name_delimiter = $this->container->getParameter('registry.redis.delimiter');

        // apply
        if ($mode == 'redis') {
            $this->setMode(RegistryMode::MODE_REDIS); // sets $mode & $key_name_delimiter
            //$this->setDefaultKeysEnabled(false); // sets $use_yaml
        } else {
            $this->setMode(RegistryMode::MODE_DOCTRINE); // sets $mode & $key_name_delimiter
            //$this->setDefaultKeysEnabled(true); // sets $use_yaml
        }

        if (trim($this->redis_prefix) == '') {
            // empty prefix is not allowed
            $this->redis_prefix = 'registry' . $this->redis_key_name_delimiter;
        } else {
            // append the key-name delimiter
            $this->redis_prefix .= $this->redis_key_name_delimiter;
        }
    }

    /**
     * Registry Configuration Methods
     */

    /**
     * @param integer $mode
     * @return boolean
     */
    private function isMode($mode)
    {
        return $this->mode == $mode;
    }

    /**
     * @return boolean
     */
    private function isModeDoctrine()
    {
        return $this->isMode(RegistryMode::MODE_DOCTRINE);
    }

    /**
     * @return boolean
     */
    private function isModeRedis()
    {
        return $this->isMode(RegistryMode::MODE_REDIS);
    }

    /**
     * @return boolean
     */
    public function hasYaml()
    {
        return $this->use_yaml;
    }

    /**
     * @param integer $mode
     * @return Registry
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        $this->registry = null;
        $this->system = null;
        $this->redis = null;

        if ($this->isModeDoctrine()) {
            $this->registry = $this->em->getRepository('RegistryBundle:Registry');
            $this->system = $this->em->getRepository('RegistryBundle:System');

            $this->key_name_delimiter = $this->container->getParameter('registry.globals.delimiter');
        } else if ($this->isModeRedis()) {
            $this->redis = $this->container->get('snc_redis.registry');

            $this->key_name_delimiter = $this->container->getParameter('registry.redis.delimiter');
        }

        $this->setDefaultKeysEnabled($mode == RegistryMode::MODE_DOCTRINE);

        return $this;
    }

    /**
     * @param boolean $enabled
     * @return Registry
     */
    public function setDefaultKeysEnabled($enabled)
    {
        $this->use_yaml = false;
        $this->yaml = null;

        if ($enabled) {
            $filename = $this->container->getParameter('registry.globals.defaultkeys');
            
            $this->use_yaml = file_exists($filename);

            if ($this->use_yaml) {
                $this->yaml = Yaml::parse($filename); // load/parse yaml file into array
            }
        }

        return $this;
    }

    /**
     * Registry Methods
     */

    /**
     * Delete registry key from database.
     * 
     * @param integer $userid
     * @param string $registrykey
     * @param string $name
     * @param string $type
     * @return boolean
     */
    public function RegistryDelete($userid, $registrykey, $name, $type)
    {
        if ($this->isModeRedis()) {
            //return $this->redis->delete($this->redis_prefix . (string)$userid . $this->redis_key_name_delimiter . $registrykey . $this->redis_key_name_delimiter . $name) > 0;
            return $this->redis->hDel($this->redis_prefix . (string)$userid, $registrykey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type) > 0;
        } else if ($this->isModeDoctrine()) {
            $entity = $this->registry->findOneBy(array('userid' => $userid, 'registrykey' => $registrykey, 'name' => $name, 'type' => $type));

            if ($entity) {
                $this->em->remove($entity);
                $this->em->flush();
            }

            return !is_null($entity);
        }
    }

    /**
     * Short method to RegistryDelete
     * 
     * @see RegistryDelete
     */
    public function rd($uid, $rk, $n, $t)
    {
        return $this->RegistryDelete($uid, $rk, $n, $t);
    }

    /** 
     * Read registry key from database.
     * If no key is found, the default value will be returned.
     * 
     * @param integer $userid
     * @param string $registrykey
     * @param string $string
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    public function RegistryReadDefault($userid, $registrykey, $name, $type, $default)
    {
        if ($this->isModeRedis()) {
            // find own key
            //$value = $this->redis->get($this->redis_prefix . (string)$userid . $this->redis_key_name_delimiter . $registrykey . $this->redis_key_name_delimiter . $name);
            $value = $this->redis->hGet($this->redis_prefix . (string)$userid, $registrykey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type);

            if ($value === false) {
                // find default key
                //$value = $this->redis->get($this->redis_prefix . '0' . $this->redis_key_name_delimiter . $registrykey . $this->redis_key_name_delimiter . $name);
                $value = $this->redis->hGet($this->redis_prefix . '0', $registrykey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type);
            }
        } else if ($this->isModeDoctrine()) {
            // find own key
            if ($userid != 0) {
                $entity = $this->registry->findOneBy(array('userid' => $userid, 'registrykey' => $registrykey, 'name' => $name));
            } else {
                $entity = null;
            }

            if (!$entity) {
                // find default key
                $entity = $this->registry->findOneBy(array('userid' => 0, 'registrykey' => $registrykey, 'name' => $name));
            }

            if ($entity) {
                $value = (string)$entity->getValue();
            } else {
                $value = false;
            }
        }

        if (is_string($value)) {
            switch($type) {
                case 'i':
                case 'int':
                case 'integer':
                    return (integer)$value;
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    return (boolean)$value;
                    break;
                case 's':
                case 'str':
                case 'string':
                    return (string)$value;
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    return (float)$value;
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    $value = $value; // this always is a string
                    if (is_numeric($value)) { // don't use is_int here
                        return (integer)$value;
                    } else {
                        return strtotime($value);
                    }
                    break;
                default:
                    return $value;
            }
        } else {
            // special default null handling
            if (is_null($default)) return $default;
            // regular
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
     * Short method to RegistryReadDefault.
     * 
     * @see RegistryReadDefault
     */
    public function rrd($uid, $rk, $n, $t, $d)
    {
        return $this->RegistryReadDefault($uid, $rk, $n, $t, $d);
    }

    /**
     * Read registry key from database.
     * 
     * @param integer $userid
     * @param string $registrykey
     * @param string $string
     * @param string $type
     * @return mixed
     */
    public function RegistryRead($userid, $registrykey, $name, $type)
    {
        $result = $this->RegistryReadDefault($userid, $registrykey, $name, $type, null);    

        if (($result === null) && ($this->use_yaml)) {
            if (is_array($this->yaml) && is_array($this->yaml['registry']) && array_key_exists($registrykey.$this->key_name_delimiter.$name, $this->yaml['registry'])) {
                $result = $this->yaml['registry'][$registrykey.$this->key_name_delimiter.$name];
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
     * Short method to RegistryRead.
     * 
     * @see RegistryRead
     */
    public function rr($uid, $rk, $n, $t)
    {
        return $this->RegistryRead($uid, $rk, $n, $t);
    }

    /**
     * Read registry key from database and delete it immediately.
     * 
     * @param integer $userid
     * @param string $registrykey
     * @param string $string
     * @param string $type
     * @return mixed
     */
    public function RegistryReadOnce($userid, $registrykey, $name, $type)
    {
        $r = $this->RegistryRead($userid, $registrykey, $name, $type);

        $this->RegistryDelete($userid, $registrykey, $name, $type);

        return $r;
    }

    /**
     * Short method to RegistryReadOnce.
     */
    public function rro($uid, $rk, $n, $t)
    {
        return $this->registryReadOnce($uid, $rk, $n, $t);
    }

    /**
     * Write registry key to database.
     * 
     * @param integer $userid
     * @param string $registrykey
     * @param string $string
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    public function RegistryWrite($userid, $registrykey, $name, $type, $value)
    {
        // value = default key value?
        if ($userid != 0) {
            $result = $this->RegistryRead(0, $registrykey, $name, $type);
            if ($result) {
                if ($result == $value) {
                    // equals default value, delete own key
                    return $this->RegistryDelete($userid, $registrykey, $name, $type);
                }
            }
        }

        // convert value to string
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
                break;
            default:
                ;
        }

        // not default key, insert / update
        if ($this->isModeRedis()) {
            //return $this->redis->set($this->redis_prefix . (string)$userid . $this->redis_key_name_delimiter . $registrykey . $this->redis_key_name_delimiter . $name, $value);
            return $this->redis->hSet($this->redis_prefix . (string)$userid, $registrykey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type, $value);
        } else if ($this->isModeDoctrine()) {
            $entity = $this->registry->findOneBy(array('userid' => $userid, 'registrykey' => $registrykey, 'name' => $name));

            if (!$entity) {
                $entity = new RegKey();
                $entity->setUserid($userid);
                $entity->setRegistryKey($registrykey);
                $entity->setName($name);
            }

            $entity->setType($type);
            $entity->setValue($value);

            $this->em->persist($entity);
            $this->em->flush();

            return !is_null($entity);
        }
    }

    /**
     * Short method to RegistryWrite.
     * 
     * @see RegistryWrite
     */
    public function rw($uid, $rk, $n, $t, $v)
    {
        return $this->RegistryWrite($uid, $rk, $n, $t, $v);
    }

    /**
     * Read / Load a set of registry keys from database.
     * 
     * Important: This only works with Doctrine database type!
     * 
     * @param integer $userid
     * @param string  $path
     * @return RegistryBag|Null
     */
    public function getRegistryBag($userid, $path)
    {
        if ($this->isModeRedis()) {
            return null;
        }

        $entities = $this->registry->loadByPath($userid, $path);

        return new RegistryBag($entities);
    }

    /**
     * Read a set of registry keys and return them as array.
     * 
     * @param integer $userid
     * @param string  $path
     * @return array
     */
    public function getRegistryItems($userid, $path)
    {
        $entities = array();

        if ($this->isModeRedis()) {
            // redis load
            $array = $this->redis->hGetAll($this->redis_prefix . (string)$userid);
            // filter the result array
            foreach (array_keys($array) as $key) {
                if (preg_match('/^'.$path.'/', $key)) {
                    // $key = key; key:name:type
                    // $value = $array[$key]; value
                    
                    // explode key by redis_key_name_delimiter to get key:name:type
                    $s = explode($this->redis_key_name_delimiter, $key);

                    if (count($s) <> 3) {
                        throw new \Exception('Redis key format is not correct! (key'.$this->redis_key_name_delimiter.'name'.$this->redis_key_name_delimiter.'name)');
                    }

                    $entity = new RegKey();
                    $entity->loadByValues(0, $userid, $s[0], $s[1], $s[2], $array[$key]);
                    $entities[] = $entity;
                }
            }
            // cleanup
            unset($array);
        } else if ($this->isModeDoctrine()) {
            // doctrine load
            $entities = $this->registry->loadByPath($userid, $path);
        }

        return $entities;
    }

    /**
     * System Methods
     */

    /**
     * Delete system key from database.
     * 
     * @param string $systemkey
     * @param string $name
     * @param string $type
     * @return boolean
     */
    public function SystemDelete($systemkey, $name, $type)
    {
        if ($this->isModeRedis()) {
            //return $this->redis->delete($this->redis_prefix . $systemkey . $this->redis_key_name_delimiter . $name) > 0;
            return $this->redis->hDel($this->redis_prefix . self::SYSTEM_HASH_KEY, $systemkey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type) > 0;
        } else if ($this->isModeDoctrine()) {
            $entity = $this->system->findOneBy(array('systemkey' => $systemkey, 'name' => $name, 'type' => $type));

            if ($entity) {
                $this->em->remove($entity);
                $this->em->flush();
            }

            return !is_null($entity);
        }
    }

    /**
     * Short method to SystemDelete
     * 
     * @see SystemDelete
     */
    public function sd($sk, $n, $t)
    {
        return $this->SystemDelete($sk, $n, $t);
    }

    /**
     * Read system key from database.
     * If no key is found, the default value will be returned.
     * 
     * @param string $systemkey
     * @param string $string
     * @param string type
     * @param mixed $default
     * @return mixed
     */
    public function SystemReadDefault($systemkey, $name, $type, $default)
    {
        if ($this->isModeRedis()) {
            //$value = $this->redis->get($this->redis_prefix . $systemkey . $this->redis_key_name_delimiter . $name);    
            $value = $this->redis->hGet($this->redis_prefix . self::SYSTEM_HASH_KEY, $systemkey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type);    
        } else if ($this->isModeDoctrine()) {
            // find default key
            $entity = $this->system->findOneBy(array('systemkey' => $systemkey, 'name' => $name));
            
            if ($entity) {
                $value = (string)$entity->getValue();
            } else {
                $value = false;
            }
        }

        if (is_string($value)) {
            switch($type) {
                case 'i':
                case 'int':
                case 'integer':
                    return (integer)$value;
                    break;
                case 'b':
                case 'bln':
                case 'boolean':
                    return (boolean)$value;
                    break;
                case 's':
                case 'str':
                case 'string':
                    return (string)$value;
                    break;
                case 'f':
                case 'flt':
                case 'float':
                    return (float)$value;
                    break;
                case 'd':
                case 'dat':
                case 'date':
                case 't':
                case 'tim':
                case 'time':
                    $value = $value; // this always is a string
                    if (is_numeric($value)) { // don't use is_int here
                        return (integer)$value;
                    } else {
                        return strtotime($value);
                    }
                    break;
                default:
                    return $value;
            }
        } else {
            // special default null handling
            if (is_null($default)) return $default;
            // regular
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
     * Short method to SystemReadDefault.
     * 
     * @see SystemReadDefault
     */
    public function srd($sk, $n, $t, $d)
    {
        return $this->SystemReadDefault($sk, $n, $t, $d);
    }

    /**
     * Read system key from database.
     * 
     * @param string $systemkey
     * @param string $string
     * @param string type
     * @return mixed
     */
    public function SystemRead($systemkey, $name, $type)
    {
        $result = $this->SystemReadDefault($systemkey, $name, $type, null); 

        if (($result === null) && ($this->use_yaml)) {
            if (is_array($this->yaml) && is_array($this->yaml['system']) && array_key_exists($systemkey.$this->key_name_delimiter.$name, $this->yaml['system'])) {
                $result = $this->yaml['system'][$systemkey.$this->key_name_delimiter.$name];
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
     * Short method to SystemRead.
     * 
     * @see SystemRead
     */
    public function sr($sk, $n, $t)
    {
        return $this->SystemRead($sk, $n, $t);
    }

    /**
     * Read system key from database and delete it immediately.
     * 
     * @param string $registrykey
     * @param string $string
     * @param string $type
     * @return mixed
     */
    public function SystemReadOnce($systemkey, $name, $type)
    {
        $r = $this->SystemRead($systemkey, $name, $type);
        
        $this->SystemDelete($systemkey, $name, $type);

        return $r;
    }

    /**
     * Short method to SystemReadOnce.
     */
    public function sro($sk, $n, $t)
    {
        return $this->SystemReadOnce($sk, $n, $t);
    }

    /**
     * Write system key to database.
     * 
     * @param string $systemkey
     * @param string $string
     * @param string type
     * @param mixed value
     * @return boolean
     */
    public function SystemWrite($systemkey, $name, $type, $value)
    {
        // convert value to string
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
                break;
            default:
                ;
        }

        // insert / update
        if ($this->isModeRedis()) {
            //return $this->redis->set($this->redis_prefix . $systemkey . $this->redis_key_name_delimiter . $name, $value);
            return $this->redis->hSet($this->redis_prefix . self::SYSTEM_HASH_KEY, $systemkey . $this->redis_key_name_delimiter . $name . $this->redis_key_name_delimiter . $type, $value);
        } else if ($this->isModeDoctrine()) {
            $entity = $this->system->findOneBy(array('systemkey' => $systemkey, 'name' => $name));

            if (!$entity) {
                $entity = new SysKey();
                $entity->setSystemKey($systemkey);
                $entity->setName($name);
            }

            $entity->setType($type);
            $entity->setValue($value);

            $this->em->persist($entity);
            $this->em->flush();

            return !is_null($entity);
        }
    }

    /**
     * Short method to SystemWrite.
     * 
     * @see SystemWrite
     */
    public function sw($sk, $n, $t, $v)
    {
        return $this->SystemWrite($sk, $n, $t, $v);
    }

    /**
     * Read / Load a set of system keys from database.
     * 
     * Important: This only works with Doctrine database type!
     * 
     * @param string  $path
     * @return RegistryBag|Null
     */
    public function getSystemBag($path)
    {
        if ($this->isModeRedis()) {
            return null;
        }

        $entities = $this->system->loadByPath($path);

        return new SystemBag($entities);
    }

    /**
     * Read a set of registry keys and return them as array.
     * 
     * Important: This works with Redis too, but can get very slow
     * as all hash values must be retrieved to filter them by code.
     * 
     * @param string  $path
     * @return array
     */
    public function getSystemItems($path)
    {
        $entities = array();

        if ($this->isModeRedis()) {
            // redis load
            $array = $this->redis->hGetAll($this->redis_prefix . self::SYSTEM_HASH_KEY);
            // filter the result array
            foreach (array_keys($array) as $key) {
                if (preg_match('/^'.$path.'/', $key)) {
                    // $key = key; key:name:type
                    // $value = $array[$key]; value

                    // explode key by redis_key_name_delimiter to get key:name:type
                    $s = explode($this->redis_key_name_delimiter, $key);

                    if (count($s) <> 3) {
                        throw new \Exception('Redis key format is not correct! (key'.$this->redis_key_name_delimiter.'name'.$this->redis_key_name_delimiter.'type)');
                    }

                    $entity = new SysKey();
                    $entity->loadByValues(0, $s[0], $s[1], $s[2], $array[$key]);
                    $entities[] = $entity;
                }
            }
            // cleanup
            unset($array);
        } else if ($this->isModeDoctrine()) {
            // doctrine load
            $entities = $this->system->loadByPath($path);
        }

        return $entities;
    }
}
