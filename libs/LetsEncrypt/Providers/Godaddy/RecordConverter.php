<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 12:37 PM
 */

namespace LetsEncrypt\Providers\Godaddy;

use LetsEncrypt\Providers\BaseRecordConverter;
use LetsEncrypt\Host\HostVerificationObject;
use LetsEncrypt\Providers\RecordConverterInterface;

class RecordConverter extends BaseRecordConverter implements RecordConverterInterface
{
    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = [])
    {
        $this->container['type'] = isset($data['type']) ? $data['type'] : null;
        $this->container['name'] = isset($data['name']) ? $data['name'] : null;
        $this->container['data'] = isset($data['data']) ? $data['data'] : null;
        $this->container['priority'] = isset($data['priority']) ? $data['priority'] : null;
        $this->container['ttl'] = isset($data['ttl']) ? $data['ttl'] : null;
        $this->container['service'] = isset($data['service']) ? $data['service'] : null;
        $this->container['protocol'] = isset($data['protocol']) ? $data['protocol'] : null;
        $this->container['port'] = isset($data['port']) ? $data['port'] : null;
        $this->container['weight'] = isset($data['weight']) ? $data['weight'] : null;
        $this->container['verified'] = isset($data['verified']) ? (bool) $data['verified'] : false;
    }

    /**
     * Convert to HostVerificationObject
     * @return HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convert(): HostVerificationObject
    {
        $record = $this->container;
        $host = new HostVerificationObject($record['type'], $record['name'], $record['data'], $record['ttl'], (int) $record['weight']);
        $host->setVerified($record['verified']);
        return $host;
    }

    /**
     * Convert to Providers Object
     * @return \GoDaddyDomainsClient\Model\DNSRecordCreateType|null
     */
    public function convertProvider()
    {
        return new \GoDaddyDomainsClient\Model\DNSRecordCreateType($this->container);
    }

    /**
     * Gets name
     * @return string
     */
    public function getHostname(): string
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     * @param string $name
     * @return $this
     */
    public function setHostname(string $name): BaseRecordConverter
    {
        $this->container['name'] = $name;
        return $this;
    }

    /**
     * Get Type
     * @return string
     */
    public function getType(): string
    {
        return $this->container['type'];
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): BaseRecordConverter
    {
        $this->container['type'] = $type;
        return $this;
    }

    /**
     * Get Address
     * @return string
     */
    public function getAddress(): string
    {
        return $this->container['data'];
    }

    /**
     * Set Address
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): BaseRecordConverter
    {
        $this->container['data'] = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getTtl(): string
    {
        return $this->container['ttl'];
    }

    /**
     * @param string $ttl
     * @return $this
     */
    public function setTtl(string $ttl): BaseRecordConverter
    {
        $this->container['ttl'] = $ttl;
        return $this;
    }

    /**
     * Get MX Pref
     * @return int
     */
    public function getMXPref(): int
    {
        return (int) $this->container['weight'];
    }

    /**
     * Get MX Pref
     * @param int $mxpref
     * @return $this
     */
    public function setMXPref(int $mxpref): BaseRecordConverter
    {
        $this->container['weight'] = $mxpref;
        return $this;
    }
}