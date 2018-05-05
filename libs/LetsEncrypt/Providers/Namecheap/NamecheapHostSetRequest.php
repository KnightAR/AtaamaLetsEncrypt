<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/6/18
 * Time: 12:14 PM
 */

namespace LetsEncrypt\Host;


class NamecheapHostSetRequest
{
    /**
     * @var HostEntries $entries
     */
    public $entries;

    /**
     * NamecheapHostSetRequest constructor.
     * @param HostEntries $entries
     */
    public function __construct(HostEntries $entries)
    {
        $this->entries = $entries;
    }

    /**
     * @return array
     */
    function toArray(): Array
    {
        $output = [
            'TLD' => $this->entries->getTLD(),
            'SLD' => $this->entries->getSLD(),
            'EmailType' => 'FWD'
        ];

        foreach ($this->entries as $i => $entry) {
            $entry = $entry->convertProvider();
            $hostnum = ($i + 1);
            $output = array_merge($output, [
                "HostName{$hostnum}" => $entry->getHostname(),
                "RecordType{$hostnum}" => $entry->getType(),
                "Address{$hostnum}" => $entry->getAddress(),
                "MXPref{$hostnum}" => $entry->getMXPref(),
                "TTL{$hostnum}" => $entry->getTtl()
            ]);
            if ($entry->getType() == 'MX') {
                $output['EmailType'] = $entry->getEmailType();
            }
        }

        return $output;
    }
}