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

    protected $defaultTTL = 600;
    /*
     * @var \ataama\cpanel\modules\ZoneEdit $dnsclient
     */
    protected $dnsclient;

    private $username;
    private $password;
    private $host;

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
            'username' => $options['hostname'],
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
        // TODO: Implement addRecords() method.
    }

    /**
     * Get Old Records
     * @throws \Exception
     */
    public function getOldRecords(): void
    {
        $oldrecords = $this->dnsclient->fetchzone_records($this->getUsername(), [
            'domain' => $this->getDomain(),
            'customonly' => 1,
            'type' => 'TXT'
        ]);

        // TODO: Implement getOldRecords() method.
    }

    /**
     * @return bool
     */
    public function pushHosts(): bool
    {
        // TODO: Implement pushHosts() method.
    }

    /**
     * Add or Replace Records
     * @param array $records
     * @return bool
     */
    public function addOrReplaceRecords(array $records = []): bool
    {
        // TODO: Implement addOrReplaceRecords() method.
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