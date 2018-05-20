<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 10:41 AM
 */

namespace LetsEncrypt\Providers\Godaddy;

use LetsEncrypt\Providers\BaseProviders;
use LetsEncrypt\Providers\ProviderInterface;

class GodaddyAdapter extends BaseProviders implements ProviderInterface
{
    protected $defaultTTL = 600;
    /*
     * @var \GoDaddyDomainsClient\Api\VdomainsApi $dnsclient
     */
    protected $dnsclient;
    /*
     * @var \GoDaddyDomainsClient\ApiClient $apiclient
     */
    protected $apiclient;

    private $apikey;
    private $apisecret;

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
            'apikey' => null,
            'apisecret' => null,
            'debug' => false
        ], $options);

        parent::__construct($domain, $options, $sandbox);

        $configuration = new \GoDaddyDomainsClient\Configuration();
        $configuration->addDefaultHeader("Authorization", "sso-key " . $this->getApikey() . ":" . $this->getApisecret());
        $configuration->setDebug($options['debug']);

        if ($sandbox) {
            $configuration->setHost('api.ote-godaddy.com');
        }

        if (isset($options['apiclient'])) {
            $this->apiclient = $options['apiclient'];
        } else {
            $this->apiclient = new \GoDaddyDomainsClient\ApiClient($configuration);
        }

        if (isset($options['dnsclient'])) {
            $this->dnsclient = $options['dnsclient'];
        } else {
            $this->dnsclient = new \GoDaddyDomainsClient\Api\VdomainsApi($this->apiclient);
        }
    }

    /**
     * Get Old Records
     */
    public function getOldRecords(): void
    {
        $records = $this->dnsclient->recordGet($this->getDomain(),\GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT);

        foreach ($records as $record) {
            if (preg_match('#^_acme-challenge\.#', $record->getName())) {
                continue;
            }
            $data = $this->fromArrayAccesstoArray($record);
            $entry = new RecordConverter($data);
            $entry->setVerified(true);
            $this->hosts->addEntry($entry);
        }
    }

    /**
     * * Add new records
     * @param array $records
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $record) {
            $record = new RecordConverter($record);

            if (!preg_match('#^_acme-challenge\.#', $record->getHostname())) {
                continue;
            }

            $this->hosts->addEntry($record);
        }
    }

    /**
     * @return bool
     * @throws \GoDaddyDomainsClient\ApiException
     */
    public function pushHosts(): bool
    {
        $replace_records = [];
        foreach($this->hosts as $host) {
            $replace_records[] = $host->convertProvider();
        }
        $ret = $this->dnsclient->recordReplaceType($this->getDomain(), \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT, $replace_records);

        if (is_null($ret)) {
            return true;
        }
        return false;
    }

    /**
     * Add or Replace Records
     * @param array $records
     * @return bool
     * @throws \ErrorException
     * @throws \GoDaddyDomainsClient\ApiException
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

    /**
     * @return string
     */
    public function getApisecret(): string
    {
        return $this->apisecret;
    }

    /**
     * @param string $apisecret
     * @return BaseProviders
     */
    public function setApisecret(string $apisecret): BaseProviders
    {
        $this->apisecret = $apisecret;
        return $this;
    }
}