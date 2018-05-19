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
use LetsEncrypt\Providers\RecordConverterInterface;

class RecordConverter extends BaseRecordConverter implements RecordConverterInterface
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
     * Convert to HostVerificationObject
     * @return HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convert(): HostVerificationObject
    {
        $record = $this->container;
        $host = new HostVerificationObject($record['Type'], $record['Name'], $record['Address'], $record['TTL'], (int) $record['MXPref'], $record['EmailType']);
        $host->setVerified($record['verified']);
        return $host;
    }

    /**
     * Convert to Providers Object
     * @return HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convertProvider()
    {
        return $this->convert();
    }

    /**
     * Gets hostname
     * @return string
     */
    public function getHostname(): string
    {
        return $this->container['Name'];
    }

    /**
     * Sets hostname
     * @param string $name
     * @return $this
     */
    public function setHostname(string $name): BaseRecordConverter
    {
        $this->container['Name'] = $name;
        return $this;
    }

    /**
     * Get Type
     * @return string
     */
    public function getType(): string
    {
        return $this->container['Type'];
    }

    /**
     * Set Type
     * @param string $type
     * @return $this
     */
    public function setType(string $type): BaseRecordConverter
    {
        $this->container['Type'] = $type;
        return $this;
    }

    /**
     * Get Address
     * @return string
     */
    public function getAddress(): string
    {
        return $this->container['Address'];
    }

    /**
     * Set Address
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): BaseRecordConverter
    {
        $this->container['Address'] = $address;
        return $this;
    }

    /**
     * Get TTL
     * @return string
     */
    public function getTtl(): string
    {
        return $this->container['TTL'];
    }

    /**
     * Set TTL
     * @param string $ttl
     * @return $this
     */
    public function setTtl(string $ttl): BaseRecordConverter
    {
        $this->container['TTL'] = $ttl;
        return $this;
    }

    /**
     * @return int
     */
    public function getMXPref(): int
    {
        return $this->container['MXPref'];
    }

    /**
     * Get MX Pref
     * @param int $mxpref
     * @return $this
     */
    public function setMXPref(int $mxpref): BaseRecordConverter
    {
        $this->container['MXPref'] = $mxpref;
        return $this;
    }

    /**
     * Get Email Type
     * @return string
     */
    public function getEmailType(): string
    {
        return $this->container['EmailType'];
    }

    /**
     * Set Email Type
     * @param string $emailtype
     * @return $this
     */
    public function setEmailType(string $emailtype): BaseRecordConverter
    {
        $this->container['EmailType'] = $emailtype;
        return $this;
    }
}