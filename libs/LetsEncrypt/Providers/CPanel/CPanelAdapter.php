<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/18/18
 * Time: 1:41 PM
 */

namespace LetsEncrypt\Providers\CPanel;

use ataama\cpanel\modules\ZoneEdit;
use LetsEncrypt\Providers\BaseProviders;
use LetsEncrypt\Providers\ProviderInterface;

class CPanelAdapter extends BaseProviders implements ProviderInterface
{
    /*
     * @var \ataama\cpanel\modules\ZoneEdit $dnsclient
     */
    protected $dnsclient;

    private $username;
    private $password;
    private $host;
    protected $repushAble = false;

    /**
     * NamecheapAdapter constructor.
     * @param string $domain
     * @param array $options
     * @param bool $sandbox
     * @throws \Exception
     **/
    public function __construct(string $domain, array $options = [], bool $sandbox = false)
    {
        $options = array_merge([
            'username' => null,
            'password' => null,
            'host' => null,
            'debug' => false,
            'auth_type' => 'session'
        ], $options);

        $client = new ZoneEdit([
            'username' => $options['username'],
            'password' => $options['password'],
            'host' => $options['host'],
            'auth_type' => $options['auth_type']
        ]);

        parent::__construct($domain, $options, $sandbox);

        if (isset($options['dnsclient'])) {
            $this->dnsclient = $options['dnsclient'];
        } else {
            $this->dnsclient = $client;
        }
    }

    /**
     * Add new records
     * @param array $records
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $record) {;
            $record = new RecordConverter($record);

            if (!preg_match('#^_acme-challenge\.#', $record->getHostname())) {
                continue;
            }

            if (preg_match('#\.'. str_replace('.', '\.', sprintf('%s.%s', $this->getSLD(), $this->getTLD())) . '$#', $record->getHostname())) {
                $new = str_replace(sprintf('.%s.%s', $this->getSLD(), $this->getTLD()), '', $record->getHostname());
                $record->setHostname($new);
            }

            $this->hosts->addEntry($record);
        }
    }

    /**
     * Get Old Records
     * @throws \Exception
     */
    public function getOldRecords(): void
    {
        $old_hosts = $this->dnsclient->fetchzone_records($this->getUsername(), [
            'domain' => $this->getDomain(),
            'customonly' => 1,
            'type' => 'TXT'
        ]);

        if (!empty($old_hosts)) {
            foreach ($old_hosts as $record) {
                $host = new RecordConverter($record);
                $host->setVerified(true);
                if (preg_match('#^_acme-challenge\.#', $record['name'])) {
                    $host->setDeleted(true);
                } else {
                    //We don't care about any other records!
                    continue;
                }

                if (preg_match('#\.'. str_replace('.', '\.', sprintf('%s.%s', $this->getSLD(), $this->getTLD())) . '$#', $host->getHostname())) {
                    $new = str_replace(sprintf('.%s.%s', $this->getSLD(), $this->getTLD()), '', $host->getHostname());
                    $host->setHostname($new);
                }

                $this->hosts->addEntry($host);
            }
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function pushHosts(): bool
    {
        foreach($this->hosts as $key => $host) {
            $record = array_merge(
                $host->convertProvider(),
                ['domain' => sprintf("%s.%s", $this->hosts->getSLD(), $this->hosts->getTLD())]
            );
            if ($host->isDeleted()) {
                $ret = $this->dnsclient->remove_zone_record($this->getUsername(), $record);
                if (isset($ret[0]['result']['status']) && $ret[0]['result']['status'] !== 1) {
                    print_r($ret);
                    throw new \ErrorException("Could not remove zone record, server returned an error.");
                }
            } elseif (!$host->isVerified()) {
                $ret = $this->dnsclient->add_zone_record($this->getUsername(), $record);
                if (isset($ret[0]['result']['status']) && $ret[0]['result']['status'] !== 1) {
                    print_r($ret);
                    throw new \ErrorException("Could not add new zone record, server returned an error.");
                }
            } else {
                //$ret = $this->dnsclient->edit_zone_record($this->getUsername(), $record);
                continue;
            }
        }
        return true;
    }

    /**
     * Add or Replace Records
     * @param array $records
     * @return bool
     * @throws \ErrorException
     * @throws \Exception
     */
    public function addOrReplaceRecords(array $records = []): bool
    {
        $this->getOldRecords();
        $this->addRecords($records);

        if ($this->pushHosts()) {
            $ret = $this->verify();
            return $ret;
        }

        return false;
    }

    /**
     * Get Username
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return BaseProviders
     */
    public function setUsername(string $username): BaseProviders
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get Password
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set Password
     * @param string $password
     * @return BaseProviders
     */
    public function setPassword(string $password): BaseProviders
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get Host
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Set Host
     * @param string $host
     * @return BaseProviders
     */
    public function setHostname(string $host): BaseProviders
    {
        $this->host = $host;
        return $this;
    }
}