<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/6/18
 * Time: 12:49 PM
 */

namespace LetsEncrypt\Host;


class HostVerificationEntries extends HostEntries
{
    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->entries[$this->position]->isVerified();
    }

    public function isAllVerified()
    {
        $hosts = clone $this;
        $isVerified = true;
        foreach($hosts as $host) {
            if (!$host->isVerified()) {
                $isVerified = false;
            }
        }
        return $isVerified;
    }
}