<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/18/18
 * Time: 12:10 PM
 */

namespace LetsEncrypt\Providers;


interface ProviderInterface
{
    /**
     * Provider constructor.
     * @param string $domain
     * @param array $options
     * @param bool $sandbox
     * @throws \Exception
     */
    public function __construct(string $domain, array $options = [], bool $sandbox = false);

    /**
     * Add new records
     * @param array $records
     */
    public function addRecords(array $records): void;

    /**
     * Get Old Records
     */
    public function getOldRecords(): void;

    /**
     * Push Hosts, add new records
     * @return bool
     */
    public function pushHosts(): bool;

    /**
     * Add or Replace Records
     * @param array $records
     * @return bool
     */
    public function addOrReplaceRecords(array $records = []): bool;
}