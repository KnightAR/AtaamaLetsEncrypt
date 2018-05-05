<?php

namespace LetsEncrypt\Host;

interface HostEntryInterface
{
    public function getType();

    public function getHostname();

    public function getAddress();

    public function getTtl();

    public function getMXPref();

    public function getEmailType();
}