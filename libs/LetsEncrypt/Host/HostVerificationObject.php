<?php

namespace LetsEncrypt\Host;

class HostVerificationObject extends HostEntryObject
{
    protected $verified = false;
    protected $deleted = false;

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
    public function setVerified(bool $verified = false): void
    {
        $this->verified = $verified;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted = false): void
    {
        $this->deleted = $deleted;
    }
}