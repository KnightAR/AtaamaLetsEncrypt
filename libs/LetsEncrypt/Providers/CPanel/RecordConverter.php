<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 12:37 PM
 */

namespace LetsEncrypt\Providers\CPanel;

use LetsEncrypt\Providers\BaseRecordConverter;
use LetsEncrypt\Host\HostVerificationObject;
use LetsEncrypt\Providers\RecordConverterInterface;

class RecordConverter extends BaseRecordConverter implements RecordConverterInterface
{
    private $attributeMap = [
        'Type' => 'type',
        'Name' => 'name',
        'MXPref' => 'weight',
        'TTL' => 'ttl',
        'Service' => 'target',
        'Protocol' => 'protocol',
        'Port' => 'port',
        'Priority' => 'priority',
        'Line' => 'line'
    ];

    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = [])
    {
        foreach($this->attributeMap as $key => $find) {
            $this->container[$key] = null;
            if (isset($data[$key])) {
                $this->container[$key] = $data[$key];
                unset($data[$key]);
            } elseif (isset($data[$find])) {
                $this->container[$key] = $data[$find];
                unset($data[$find]);
            }
        }

        if (isset($data['record'])) {
            $this->container['Address'] = $data['record'];
            unset($data['record']);
        } else if (isset($data['data'])) {
            $this->container['Address'] = $data['data'];
            unset($data['data']);
        }

        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $this->container[$key] = $val;
            }
        }

        if (preg_match('#\.$#', $this->container['Name'])) {
            $this->container['Name'] = rtrim($this->container['Name'], '.');
        }

        $this->container['verified'] = isset($data['verified']) ? (bool) $data['verified'] : false;
        $this->container['deleted'] = isset($data['deleted']) ? (bool) $data['deleted'] : false;
        if (isset($data['verified'])) { unset($data['verified']); }
        if (isset($data['deleted'])) { unset($data['deleted']); }
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
        $host->setLine($record['Line']);
        return $host;
    }

    /**
     * Convert to Providers Object
     * @return array
     * @throws \ErrorException
     */
    public function convertProvider()
    {
        $record = $this->container;
        $ret = [
            'name' => $record['Name'],
            'type' => $record['Type'],
            'ttl' => $record['TTL'],
            'class' => 'IN'
        ];
        /*if (!preg_match('#\.$#', $ret['name'])) {
            $ret['name'] .= '.';
        }*/
        if ($record['verified']) {
            $ret['line'] = $record['Line'];
        }
        switch ($record['Type']) {
            case 'TXT':
                $ret['txtdata'] = $record['Address'];
                break;
            case 'CNAME':
                $ret['cname'] = $record['Address'];
                break;
            case 'A':
            case 'AAAA':
                $ret['address'] = $record['Address'];
                break;
            case 'SRV':
                $ret['priority'] = $record['Priority'];
                $ret['target'] = $record['Address'];
                $ret['weight'] = $record['Weight'];
                $ret['port'] = $record['Port'];
                break;
            case 'MX':
            default:
                throw new \ErrorException(sprintf("Record Type %s is not supported in this call", $record['Type']));
                break;
        }
        return $ret;
    }

    /**
     * Gets hostname
     * @return string
     */
    public function getHostname(): string
    {
        return rtrim($this->container['Name'], '.');
    }

    /**
     * Sets hostname
     * @param string $name
     * @return $this
     */
    public function setHostname(string $name): BaseRecordConverter
    {
        $this->container['Name'] = rtrim($name, '.');
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