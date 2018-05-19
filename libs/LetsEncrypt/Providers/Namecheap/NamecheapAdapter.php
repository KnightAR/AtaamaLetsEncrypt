<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 10:41 AM
 */

namespace LetsEncrypt\Providers\Namecheap;

use LetsEncrypt\Providers\BaseProviders;
use LetsEncrypt\Host\exceptions\HostEntryException;
use LetsEncrypt\Host\NamecheapHostSetRequest;
use LetsEncrypt\Providers\ProviderInterface;

class NamecheapAdapter extends BaseProviders implements ProviderInterface
{
    /*
     * @var \Namecheap\Api\Domains\Dns $dnsclient
     */
    protected $dnsclient;
    /*
     * @var \Namecheap\Api\Client $apiclient
     */
    protected $apiclient;

    protected $emailtype;

    /*
     * @var HostVerificationEntries $verifyhosts
     */
    protected $verifyhosts;

    private $apiuser;
    private $apikey;

    /**
     * NamecheapAdapter constructor.
     * @param string $domain
     * @param array $options
     * @param bool $sandbox
     * @throws \Exception
     */
    public function __construct(string $domain, array $options = [], bool $sandbox = false)
    {
        $options = array_merge([
            'apiuser' => null,
            'apikey' => null
        ], $options);

        parent::__construct($domain, $options, $sandbox);

        $client_ip = $this->dig->dnsip('web.live.ataama.com', 10, '1.1.1.1');

        if (isset($options['apiclient'])) {
            $this->apiclient = $options['apiclient'];
        } else {
            $this->apiclient = new \Namecheap\Api\Client($this->getApiuser(), $this->getApikey(), $client_ip, $this->sandbox);
        }

        if (isset($options['dnsclient'])) {
            $this->dnsclient = $options['dnsclient'];
        } else {
            $this->dnsclient = new \Namecheap\Api\Domains\Dns($this->apiclient);
        }
    }

    protected function getSLDTLD(): array
    {
        return ['SLD' => $this->getSLD(), 'TLD' => $this->getTLD()];
    }

    /**
     * Get Old Records
     * @throws \ErrorException|\Exception
     */
    public function getOldRecords(): void
    {
        $response = $this->dnsclient->getHosts($this->getSLDTLD());
        $response_data = $response->data();

        if (isset($response_data['DomainDNSGetHostsResult']['IsUsingOurDNS']) && $response_data['DomainDNSGetHostsResult']['IsUsingOurDNS'] === 'false') {
            throw new \ErrorException($this->getDomain(). " isn't using Namecheap DNS server");
        }

        $old_hosts = $response_data['DomainDNSGetHostsResult'];
        $this->emailtype = isset($old_hosts['EmailType']) ? $old_hosts['EmailType'] : 'FWD';

        if (isset($old_hosts['host'])) {
            foreach ($old_hosts['host'] as $record) {
                if (preg_match('#^_acme-challenge\.#', $record['Name'])) {
                    continue;
                }
                $record['emailtype'] = $this->emailtype;
                //print_r($record);
                $host = new RecordConverter($record);
                $host->setVerified(true);
                $this->hosts->addEntry($host);
            }
        }
    }

    /**
     * Add new records
     * @param array $records
     * @throws HostEntryException
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $record) {
            $record['emailtype'] = $this->emailtype;
            $record = new RecordConverter($record);

            if (!preg_match('#^_acme-challenge\.#', $record->getHostname())) {
                continue;
            }

            $this->hosts->addEntry($record);
        }
    }

    /**
     * Push Hosts, add new records
     * @return bool
     */
    public function pushHosts(): bool
    {
        $HostSetRequest = new NamecheapHostSetRequest($this->hosts);
        $newHosts = $HostSetRequest->toArray();

        $response = $this->dnsclient->setHosts($newHosts);

        if ($response->getStatus() === 'OK') {
            return true;
        }
        return false;
    }

    /**
     * Add or Replace Records
     * @param array $records
     * @return bool
     * @throws HostEntryException
     * @throws \ErrorException
     */
    public function addOrReplaceRecords(array $records = []): bool
    {
        $this->getOldRecords();
        $this->addRecords($records);

        if ($this->pushHosts()) {
            return $this->verify();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getApiuser(): string
    {
        return $this->apiuser;
    }

    /**
     * @param string $apiuser
     * @return BaseProviders
     */
    public function setApiuser(string $apiuser): BaseProviders
    {
        $this->apiuser = $apiuser;
        return $this;
    }

    /**
     * @return string
     */
    public function getApikey(): string
    {
        return $this->apikey;
    }

    /**
     * @param string $apikey
     * @return BaseProviders
     */
    public function setApikey(string $apikey): BaseProviders
    {
        $this->apikey = $apikey;
        return $this;
    }

}