<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 11:19 AM
 */

namespace LetsEncrypt\Providers;

use LetsEncrypt\Host\HostVerificationEntries;

abstract class BaseProviders
{
    protected $defaultTTL = 60;
    protected $intitalWait = 61;
    protected $dnsclient;
    protected $apiclient;
    protected $dig;
    protected $nameservers;
    protected $domain;
    protected $TLD;
    protected $SLD;
    protected $sandbox;
    protected $retry = true;
    protected $repushAble = true;

    /*
     * @var HostEntries $hosts
     */
    protected $hosts;

    /**
     * BaseProviders constructor.
     * @param string $domain
     * @param array $options
     * @param bool $sandbox
     * @throws \Exception
     */
    public function __construct(string $domain, array $options = [], bool $sandbox = false)
    {
        $options = array_merge([
            'retry' => true
        ], $options);

        foreach($options as $key => $option) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($option);
            }
        }

        if (isset($options['dig'])) {
            $this->dig = $options['dig'];
        } else {
            $this->dig = new \Dns_utility();
        }

        $this->setDomain($domain);
        $this->setSandbox($sandbox);
        $this->hosts = new HostVerificationEntries($this->getDomain());
    }

    /**
     * Add new records
     * @param array $records
     */
    abstract public function addRecords(array $records): void;

    /**
     * Get Old Records
     */
    abstract public function getOldRecords(): void;

    /**
     * @return bool
     */
    abstract public function pushHosts(): bool;

    /**
     * Add or Replace Records
     * @param array $records
     * @return bool
     */
    abstract public function addOrReplaceRecords(): bool;

    /**
     * @return HostVerificationEntries
     */
    public function getHosts(): HostVerificationEntries
    {
        return $this->hosts;
    }

    /**
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTTL;
    }

    /**
     * @return string
     */
    public function getTLD(): string
    {
        return $this->TLD;
    }

    /**
     * @return string
     */
    public function getSLD(): string
    {
        return $this->SLD;
    }

    /**
     * @return bool
     */
    public function getSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * @param bool $sandbox
     * @return BaseProviders
     */
    public function setSandbox(bool $sandbox): BaseProviders
    {
        $this->sandbox = $sandbox;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRetry(): bool
    {
        return $this->retry;
    }

    /**
     * @param bool $retry
     * @return BaseProviders
     */
    public function setRetry(bool $retry): BaseProviders
    {
        $this->retry = $retry;
        return $this;
    }

    /**
     * @param string $domain
     * @return BaseProviders
     * @throws \Exception
     */
    public function setDomain(string $domain): BaseProviders
    {
        $exploded_domain = explode('.', $domain, 2);
        if (count($exploded_domain) <= 1) {
            throw new \Exception('Invalid domain: ' . $domain);
        }
        $this->SLD = $exploded_domain[0];
        $this->TLD = $exploded_domain[1];

        $this->domain = $domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return sprintf('%s.%s', $this->SLD, $this->TLD);
    }

    protected function getNameservers(): array
    {
        if (is_null($this->nameservers)) {
            $ret = $this->dig->dnsqns($this->getDomain(), 10, '1.1.1.1');
            $this->nameservers = [];
            foreach ($ret as $d) {
                $d = rtrim($d, '.');
                //This is a hack for CPanel hosted domains, to be able to query them directly
                if (get_class($this) === 'LetsEncrypt\Providers\CPanel\CPanelAdapter' && empty($this->dig->dnsqr('a', $d,10,'1.1.1.1'))) {
                    //Since the nameservers are coming up without an A record, we assume the same server is the nameserver.
                    $rip = $this->dig->dnsqr('a', $this->getDomain(),10,'1.1.1.1');
                    $this->nameservers[] = array_pop($rip);
                    continue;
                }
                $this->nameservers[] = $d;
            }
        }
        return array_unique($this->nameservers);
    }

    /**
     * @param \ArrayAccess $record
     * @return array
     */
    public function fromArrayAccesstoArray($record): array
    {
        $getters = $record->getters();
        $data = [];
        foreach($getters as $key => $getter) {
            $data[$key] = $record->$getter();
        }
        return $data;
    }

    public function sleep_for(int $seconds): void
    {
        // @codeCoverageIgnoreStart
        if (!class_exists('\PHPUnit\Framework\TestCase', false)) {
            $round = floor($seconds / 10);
            $initital = (int)($seconds - ($round * 10));
            print "Waiting {$seconds} seconds for population... ";
            sleep($initital);
            foreach (range(1, $round) as $sleep) {
                sleep(10);
                print (($sleep * 10) + $initital) . '... ';
            }
            print PHP_EOL;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return bool
     * @throws \ErrorException
     */
    public function verify(): bool
    {
        $this->sleep_for(70);

        while (!$this->hosts->isAllVerified()) {
            $this->checkNameservers();

            //Check again if all is verified and if not, re-sleep for 60 seconds for an additional check
            if ($this->getRetry() && !$this->hosts->isAllVerified()) {
                if ($this->repushAble && !$this->pushHosts()) {
                    throw new \ErrorException("Failed while attempting to verify hosts, repushing changes");
                }

                $this->sleep_for(60);
            }
        }
        return $this->hosts->isAllVerified();
    }

    protected function checkNameservers()
    {
        if (!$this->hosts->isAllVerified()) {
            foreach ($this->hosts as $entry) {
                //var_dump($entry->isVerified());
                if (!$entry->isVerified()) {
                    $setVerified = true;
                    $verifiedCount = 0;
                    foreach ($this->getNameservers() as $nameserver) {
                        $ret = $this->dig->dnsqr(strtolower($entry->getType()),
                            sprintf('%s.%s.%s', $entry->getHostname(), $this->hosts->getSLD(), $this->hosts->getTLD()),
                            10, $nameserver);

                        if (!$ret || !is_array($ret)) {
                            $setVerified = false;
                            break;
                        }

                        foreach ($ret as $retval) {
                            if (str_replace('"', '', $retval) == $entry->getAddress()) {
                                $verifiedCount++;
                                break;
                            }
                        }
                        /*print_r([
                            'ns' => $nameserver,
                            'host' => $entry->getHostname(),
                            'addr' => $entry->getAddress(),
                            'iV' => $entry->isVerified(),
                            'sV' => $setVerified,
                            'ret' => $ret
                        ]);*/
                    }
                    if ($setVerified && count($this->getNameservers()) != $verifiedCount) {
                        $setVerified = false;
                    }

                    if ($setVerified) {
                        $verifiedCount = 0;
                        foreach (['1.1.1.1', '1.0.0.1'] as $nameserver) {
                            $ret = $this->dig->dnsqr(strtolower($entry->getType()),
                                sprintf('%s.%s.%s', $entry->getHostname(), $this->hosts->getSLD(),
                                    $this->hosts->getTLD()), 10,
                                $nameserver);

                            if (!$ret || !is_array($ret)) {
                                $setVerified = false;
                                break;
                            }

                            foreach ($ret as $retval) {
                                if (str_replace('"', '', $retval) == $entry->getAddress()) {
                                    $verifiedCount++;
                                    break;
                                }
                            }
                            /*print_r([
                                'ns' => $nameserver,
                                'host' => $entry->getHostname(),
                                'addr' => $entry->getAddress(),
                                'iV' => $entry->isVerified(),
                                'sV' => $setVerified,
                                'ret' => $ret
                            ]);*/
                        }
                        if ($setVerified && $verifiedCount != 2) {
                            $setVerified = false;
                        }
                    }

                    $entry->setVerified($setVerified);
                }
            }
        }
    }
}