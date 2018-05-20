<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 1:05 PM
 */

namespace LetsEncrypt\Providers;

use \ArrayAccess;

abstract class BaseRecordConverter implements ArrayAccess
{
    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = array();

    abstract public function convert();
    abstract public function convertProvider();

    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return (bool) $this->container['verified'];
    }

    /**
     * @param bool $verified
     */
    public function setVerified(bool $verified): void
    {
        $this->container['verified'] = $verified;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return (bool) $this->container['deleted'];
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted = false): void
    {
        $this->container['deleted'] = $deleted;
    }
}