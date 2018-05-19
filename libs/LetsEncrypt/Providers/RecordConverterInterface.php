<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/18/18
 * Time: 12:34 PM
 */

namespace LetsEncrypt\Providers;


use LetsEncrypt\Host\HostVerificationObject;

interface RecordConverterInterface
{
    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null);

    /**
     * Convert to HostVerificationObject
     * @return \LetsEncrypt\Host\HostVerificationObject
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convert():  HostVerificationObject;

    /**
     * Convert to Providers Object
     * @return mixed
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function convertProvider();

    /**
     * Gets hostname
     * @return string
     */
    public function getHostname(): string;

    /**
     * Sets hostname
     * @param string $name
     * @return BaseRecordConverter
     */
    public function setHostname(string $name): BaseRecordConverter;

    /**
     * Get Type
     * @return string
     */
    public function getType(): string;

    /**
     * Set Type
     * @param string $type
     * @return BaseRecordConverter
     */
    public function setType(string $type): BaseRecordConverter;

    /**
     * Get Address
     * @return string
     */
    public function getAddress(): string;

    /**
     * Set Address
     * @param string $address
     * @return BaseRecordConverter
     */
    public function setAddress(string $address): BaseRecordConverter;

    /**
     * Get TTL
     * @return string
     */
    public function getTtl(): string;

    /**
     * Set TTL
     * @param string $ttl
     * @return BaseRecordConverter
     */
    public function setTtl(string $ttl): BaseRecordConverter;

    /**
     * Get MX Pref
     * @return int
     */
    public function getMXPref(): int;

    /**
     * Get MX Pref
     * @param int $mxpref
     * @return BaseRecordConverter
     */
    public function setMXPref(int $mxpref): BaseRecordConverter;
}