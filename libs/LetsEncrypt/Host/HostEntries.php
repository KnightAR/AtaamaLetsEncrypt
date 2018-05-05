<?php

namespace LetsEncrypt\Host;

/*
 * Object representing an array of host entries
 */
class HostEntries implements \Iterator, HostEntryInterface
{
    protected $position = 0;
    protected $entries = array();
    protected $TLD;
    protected $SLD;

    /**
     * HostEntries constructor.
     * @param $domain
     * @throws \Exception
     */
    public function __construct($domain)
    {
        //explode the domain to get the TLD
        $exploded_domain = explode('.', $domain, 2);
        if (count($exploded_domain) <= 1) {
            throw new \Exception('Invalid domain: ' . $domain);
        }

        $this->TLD = $exploded_domain[1];
        $this->SLD = $exploded_domain[0];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->entries[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->entries[$this->position]);
    }

    public function getTLD()
    {
        return $this->TLD;
    }

    public function getSLD()
    {
        return $this->SLD;
    }

    public function getType()
    {
        return $this->entries[$this->position]->getType();
    }

    public function getHostname()
    {
        return $this->entries[$this->position]->getHostname();
    }

    public function getAddress()
    {
        return $this->entries[$this->position]->getAddress();
    }

    public function getTtl()
    {
        return $this->entries[$this->position]->getTtl();
    }

    public function getMXPref()
    {
        return $this->entries[$this->position]->getMXPref();
    }

    public function getEmailType()
    {
        return $this->entries[$this->position]->getEmailType();
    }

    public function addEntry(\LetsEncrypt\Providers\BaseRecordConverter $entry)
    {
        $this->entries[] = $entry;
    }
}