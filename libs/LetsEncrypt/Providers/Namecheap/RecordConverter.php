<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 12:37 PM
 */

namespace LetsEncrypt\Providers\Namecheap;

use LetsEncrypt\Providers\BaseRecordConverter;
use LetsEncrypt\Host\HostVerificationObject;

class RecordConverter extends BaseRecordConverter
{
    private $attributeMap = [
        'Type' => 'type',
        'Name' => 'name',
        'Address' => 'data',
        'MXPref' => 'priority',
        'TTL' => 'ttl',
        'Service' => 'service',
        'Protocol' => 'protocol',
        'Port' => 'port',
        'Priority' => 'weight',
        'EmailType' => 'emailtype'
    ];

    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null)
    {
        foreach($this->attributeMap as $key => $find) {
            $this->container[$key] = null;
            if (isset($data[$key])) {
                $this->container[$key] = $data[$key];
            } elseif (isset($data[$find])) {
                $this->container[$key] = $data[$find];
            }
        }
        if (!isset($this->container['EmailType']) || is_null($this->container['EmailType'])) {
            $this->container['EmailType'] = 'FWD';
        }
        $this->container['verified'] = isset($data['verified']) ? (bool) $data['verified'] : false;
    }

    /**
     * @return HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convert()
    {
        $record = $this->container;
        $host = new HostVerificationObject($record['Type'], $record['Name'], $record['Address'], $record['TTL'], (int) $record['MXPref'], $record['EmailType']);
        $host->setVerified($record['verified']);
        return $host;
    }

    /**
     * @return HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convertProvider()
    {
        return $this->convert();
    }

    /**
     * Gets name
     * @return string
     */
    public function getHostname()
    {
        return $this->container['Name'];
    }

    /**
     * Sets name
     * @param string $name
     * @return $this
     */
    public function setHostname($name)
    {
        $this->container['Name'] = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->container['Type'];
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->container['Type'] = $type;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->container['Address'];
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->container['Address'] = $address;
    }

    /**
     * @return string
     */
    public function getTtl(): string
    {
        return $this->container['TTL'];
    }

    /**
     * @param string $ttl
     */
    public function setTtl(string $ttl): void
    {
        $this->container['TTL'] = $ttl;
    }

    /**
     * @return int
     */
    public function getMXPref(): int
    {
        return $this->container['MXPref'];
    }

    /**
     * @return string
     */
    public function getEmailType(): string
    {
        return $this->container['EmailType'];
    }
}