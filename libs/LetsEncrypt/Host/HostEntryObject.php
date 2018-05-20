<?php

namespace LetsEncrypt\Host;

/**
 * Class HostEntryObject
 * @package LetsEncrypt\Host
 *
 * @method setLine($line)
 * @method int getLine()
 */
class HostEntryObject implements HostEntryInterface
{
    protected $type;
    protected $hostname;
    protected $address;
    protected $TTL;
    protected $emailType;
    protected $MXPref;

    /**
     * EntryObject constructor.
     * @param string $type
     * @param string $hostname
     * @param string $address
     * @param int $TTL
     * @param int $MXPref
     * @param string $emailType
     * @throws exceptions\HostEntryException
     */
    public function __construct(
        string $type,
        string $hostname,
        string $address,
        int $TTL = 60,
        int $MXPref = 10,
        string $emailType = 'FWD'
    ) {
        if (!in_array($type, array('@', 'A', 'CNAME', 'MX', "TXT"))) {
            throw new exceptions\HostEntryException('Invalid Record Type: ' . print_r($type, true));
        }

        $this->type = $type;

        $this->hostname = $hostname;

        $this->address = $address;

        $this->TTL = $TTL;

        $this->MXPref = $MXPref;

        $this->emailType = $emailType;
    }

    protected $container = [];

    /**
     * @param $function
     * @param $arguments
     * @return mixed|null
     */
    public function __call($function, $arguments)
    {
        if (preg_match('#^get([A-Z][a-z0-9_]{1,})#', $function, $_match)) {
            $key = ucfirst(strtolower($_match[0]));
            if (isset($this->container[$key])) {
                return $this->container[$key];
            }
            return null;
        } elseif (preg_match('#^set([A-Z][a-z0-9_]{1,})#', $function)) {
            $key = ucfirst(strtolower($_match[0]));
            $val = (count($arguments)>0) ? array_pop($arguments) : null;
            if (isset($this->container[$key])) {
                $this->container[$key] = $val;
            }
            return null;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getTtl()
    {
        return $this->TTL;
    }

    public function getMXPref()
    {
        return $this->MXPref;
    }

    public function getEmailType()
    {
        return $this->emailType;
    }
}