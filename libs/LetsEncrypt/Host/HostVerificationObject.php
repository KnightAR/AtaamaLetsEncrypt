<?php

namespace LetsEncrypt\Host;

class HostVerificationObject extends HostEntryObject
{
    protected $verified = false;

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     */
    public function setVerified($verified = false): void
    {
        $this->verified = $verified;
    }
}