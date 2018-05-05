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

class RecordConverter extends BaseRecordConverter
{
    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null)
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
     * @return HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convert()
    {
        $record = $this->container;
        $host = new HostVerificationObject($record['type'], $record['name'], $record['data'], $record['ttl'], (int) $record['weight']);
        $host->setVerified($record['verified']);
        return $host;
    }

    /**
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
    public function getHostname()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     * @param string $name
     * @return $this
     */
    public function setHostname($name)
    {
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->container['type'];
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->container['type'] = $type;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->container['data'];
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->container['data'] = $address;
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
     */
    public function setTtl(string $ttl): void
    {
        $this->container['ttl'] = $ttl;
    }
}