<?php
namespace Test;

use \LetsEncrypt\Providers\Namecheap\RecordConverter;

class NamecheapAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $dnsclient;
    private $dns;
    private $domain = "example.com";

    public function getOptions($options)
    {
        return array_merge($options, [
            'apiuser' => 'qwertyuiopasdfghjklzxcvbnm',
            'apikey' => 'qwertyuiopASDFGHJKLZXCVBNM'
        ]);
    }

    public function getDNS()
    {
        if (is_null($this->dns)) {
            $dnsMock = $this->getMockBuilder('\Dns_utility')
                ->setConstructorArgs(array())
                ->getMock();

            $dnsMock->expects($this->any())
                ->method('dnsip')
                ->will($this->returnValue('127.0.0.1'));

            $dnsMock->expects($this->any())
                ->method('dnsqns')
                ->will($this->returnValue(['ns1.example.com.', 'ns2.example.com.']));

            $this->dns = $dnsMock;
        }
        return clone $this->dns;
    }

    public function getConfiguration()
    {
        if (is_null($this->dnsclient)) {
            $apiclient = new \Namecheap\Api\Client('asdf', 'asdf', '127.0.0.1', true);

            $this->dnsclient = $this->getMockBuilder('\Namecheap\Api\Domains\Dns')
                ->setConstructorArgs(array($apiclient))
                ->getMock();
        }

        return clone $this->dnsclient;
    }

    public function teadDown()
    {
        unset($this->dnsclient, $this->dns);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testAdapter()
    {
        $options = $this->getOptions(['dnsclient' => $this->getConfiguration(), 'dig' => $this->getDNS()]);
        $adapter = new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\NamecheapAdapter', $adapter);

        $this->assertEquals(true, $adapter->getSandbox());

        try {
            new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter('', $options, true);
        } catch(\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);
        }

        $options = $this->getOptions(['apiclient' => new \Namecheap\Api\Client('asdf', 'asdf', '127.0.0.1', true), 'dig' => $this->getDNS()]);
        new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
    }

    /**
     * @test
     */
    public function testOldRecords()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
  <Errors />
  <Warnings />
  <RequestedCommand>namecheap.domains.dns.gethosts</RequestedCommand>
  <CommandResponse Type="namecheap.domains.dns.getHosts">
    <DomainDNSGetHostsResult Domain="example.com" EmailType="FWD" IsUsingOurDNS="true">
      <host HostId="124043253" Name="@" Type="A" Address="127.0.0.1" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
      <host HostId="124043254" Name="www" Type="A" Address="127.0.0.1" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
      <host HostId="137899816" Name="_acme-challenge.test152573194379" Type="TXT" Address="test152573194359" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
    </DomainDNSGetHostsResult>
  </CommandResponse>
  <Server>PHX01APIEXT01</Server>
  <GMTTimeDifference>--4:00</GMTTimeDifference>
  <ExecutionTime>0.383</ExecutionTime>
</ApiResponse>
EOF;

        $apiResponse = new \Namecheap\Api\Response($xml);

        $mock = $this->getConfiguration();

        $mock->expects($this->any())
            ->method('getHosts')
            ->will($this->returnValue($apiResponse));

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $this->getDNS()]);
        $adapter = new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\NamecheapAdapter', $adapter);

        $adapter->getOldRecords();

        $hosts = $adapter->getHosts();
        //print_r($hosts);
        $this->assertInstanceOf('\LetsEncrypt\Host\HostVerificationEntries', $hosts);

        $expecteds = [];
        $expecteds[] = [
            'type' => 'A',
            'name' => '@',
            'data' => '127.0.0.1',
            'priority' => 10,
            'ttl' => 60,
            'service' => '',
            'protocol' => '',
            'port' => '',
            'weight' => 10,
            'verified' => ''
        ];
        $expecteds[] = [
            'type' => 'A',
            'name' => 'www',
            'data' => '127.0.0.1',
            'priority' => 10,
            'ttl' => 60,
            'service' => '',
            'protocol' => '',
            'port' => '',
            'weight' => 10,
            'verified' => ''
        ];

        foreach($hosts as $i => $host) {
            $expected = $expecteds[$i];
            $this->assertInstanceOf("\LetsEncrypt\Providers\Namecheap\RecordConverter", $host);

            $providerObj = $host->convertProvider();
            $this->assertInstanceOf("\LetsEncrypt\Host\HostVerificationObject", $providerObj);

            $this->assertEquals($expected["type"], $providerObj->getType());
            $this->assertEquals($expected["name"], $providerObj->getHostname());
            $this->assertEquals($expected["data"], $providerObj->getAddress());
            $this->assertEquals($expected["priority"], $providerObj->getMXPref());
            $this->assertEquals($expected["ttl"], $providerObj->getTtl());
            $this->assertEquals($expected["weight"], $providerObj->getMXPref());
            $this->assertEquals('FWD', $providerObj->getEmailType());
        }
    }

    /**
     * @test
     */
    public function testAddRecords()
    {
        $mock = $this->getConfiguration();

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $this->getDNS()]);
        $adapter = new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\NamecheapAdapter', $adapter);

        $records = [];
        $records[] = [
            'name' => '_acme-challenge.test' . time() . rand(1, 100),
            'data' => 'test' . time() . rand(1, 100),
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];
        $records[] = [
            'name' => '_acme-challenge.test' . time() . rand(1, 100),
            'data' => 'test' . time() . rand(1, 100),
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];
        //this shouldn't show up in the hosts array
        $records[] = [
            'name' => 'test' . time() . rand(1, 100),
            'data' => 'test' . time() . rand(1, 100),
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];

        $adapter->addRecords($records);

        $hosts = $adapter->getHosts();
        $this->assertInstanceOf('\LetsEncrypt\Host\HostVerificationEntries', $hosts);

        foreach($hosts as $i => $host) {
            $expected = $records[$i];
            $this->assertInstanceOf("\LetsEncrypt\Providers\Namecheap\RecordConverter", $host);

            $providerObj = $host->convertProvider();
            $this->assertInstanceOf("\LetsEncrypt\Host\HostVerificationObject", $providerObj);

            $this->assertEquals($expected["type"], $providerObj->getType());
            $this->assertEquals($expected["name"], $providerObj->getHostname());
            $this->assertEquals($expected["data"], $providerObj->getAddress());
            $this->assertEquals($expected["priority"], $providerObj->getMXPref());
            $this->assertEquals($expected["ttl"], $providerObj->getTtl());
            $this->assertEquals('FWD', $providerObj->getEmailType());
        }
    }

    /**
     * @test
     */
    public function testAddOrReplaceRecords()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
  <Errors />
  <Warnings />
  <RequestedCommand>namecheap.domains.dns.sethosts</RequestedCommand>
  <CommandResponse Type="namecheap.domains.dns.setHosts">
    <DomainDNSSetHostsResult Domain="ataamatest12345.com" EmailType="FWD" IsSuccess="true">
      <Warnings />
    </DomainDNSSetHostsResult>
  </CommandResponse>
  <Server>PHX01APIEXT01</Server>
  <GMTTimeDifference>--4:00</GMTTimeDifference>
  <ExecutionTime>0.528</ExecutionTime>
</ApiResponse>
EOF;
        $apiResponsePush= new \Namecheap\Api\Response($xml);

        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
  <Errors />
  <Warnings />
  <RequestedCommand>namecheap.domains.dns.gethosts</RequestedCommand>
  <CommandResponse Type="namecheap.domains.dns.getHosts">
    <DomainDNSGetHostsResult Domain="example.com" EmailType="FWD" IsUsingOurDNS="true">
      <host HostId="137899816" Name="_acme-challenge.test152573194379" Type="TXT" Address="acmetest" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
      <host HostId="137899817" Name="_acme-challenge.test152573194378" Type="TXT" Address="acmetest" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
    </DomainDNSGetHostsResult>
  </CommandResponse>
  <Server>PHX01APIEXT01</Server>
  <GMTTimeDifference>--4:00</GMTTimeDifference>
  <ExecutionTime>0.383</ExecutionTime>
</ApiResponse>
EOF;

        $apiResponseGetOld = new \Namecheap\Api\Response($xml);

        $mock = $this->getConfiguration();

        $mock->expects($this->any())
            ->method('getHosts')
            ->will($this->returnValue($apiResponseGetOld));

        $mock->expects($this->any())
            ->method('setHosts')
            ->will($this->returnValue($apiResponsePush));

        $dnsMock = $this->getDNS();

        $dnsMock->expects($this->atMost(5))
            ->method('dnsqr')
            ->will($this->returnValue(['acmetest']));

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $dnsMock]);
        $adapter = new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\NamecheapAdapter', $adapter);

        $records = [];
        $records[] = [
            'name' => '_acme-challenge.test',
            'data' => 'acmetest',
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];
        //this shouldn't show up in the hosts array
        $records[] = [
            'name' => 'test' . time() . rand(1, 100),
            'data' => 'test' . time() . rand(1, 100),
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];

        $adapter->addOrReplaceRecords($records);

        $hosts = $adapter->getHosts();

        $this->assertInstanceOf('\LetsEncrypt\Host\HostVerificationEntries', $hosts);

        foreach($hosts as $i => $host) {
            $expected = $records[$i];
            $this->assertInstanceOf("\LetsEncrypt\Providers\Namecheap\RecordConverter", $host);

            $providerObj = $host->convertProvider();
            $this->assertInstanceOf("\LetsEncrypt\Host\HostVerificationObject", $providerObj);

            $this->assertEquals($expected["type"], $providerObj->getType());
            $this->assertEquals($expected["name"], $providerObj->getHostname());
            $this->assertEquals($expected["data"], $providerObj->getAddress());
            $this->assertEquals($expected["priority"], $providerObj->getMXPref());
            $this->assertEquals($expected["ttl"], $providerObj->getTtl());
            $this->assertEquals('FWD', $providerObj->getEmailType());
        }
    }

    /**
     * @test
     */
    public function testRecordConverter()
    {
        $assoc = [
            'type' => '@',
            'name' => crc32(rand()),
            'data' => crc32(rand()),
            'ttl' => rand(600, 1800),
            'priority' => rand(10, 100),
            'emailtype' => crc32(rand()),
            'verified' => false
        ];

        $record = new RecordConverter($assoc);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\RecordConverter', $record);

        $sets = [
            'setType' => 'A',
            'setHostname' => $assoc['name'] . rand(1,100),
            'setAddress' => $assoc['data'] . rand(1,100),
            'setTtl' => rand(600, 1800),
            //'setMXPref' => rand(10, 100),
            //'setEmailType' => crc32(rand()),
            'isVerified' => true
        ];

        foreach($sets as $setter => $val) {
            $record->$setter($val);
        }
    }

    /**
     * @test
     * @throws \ErrorException
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function testFailuresPush()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
  <Errors />
  <Warnings />
  <RequestedCommand>namecheap.domains.dns.gethosts</RequestedCommand>
  <CommandResponse Type="namecheap.domains.dns.getHosts">
    <DomainDNSGetHostsResult Domain="example.com" EmailType="FWD" IsUsingOurDNS="true">
      <host HostId="137899816" Name="_acme-challenge.test152573194379" Type="TXT" Address="acmetest" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
      <host HostId="137899817" Name="_acme-challenge.test152573194378" Type="TXT" Address="acmetest" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
    </DomainDNSGetHostsResult>
  </CommandResponse>
  <Server>PHX01APIEXT01</Server>
  <GMTTimeDifference>--4:00</GMTTimeDifference>
  <ExecutionTime>0.383</ExecutionTime>
</ApiResponse>
EOF;

        $apiResponseGetOld = new \Namecheap\Api\Response($xml);

        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<ApiResponse Status="ERROR" xmlns="http://api.namecheap.com/xml.response">
  <Errors />
  <Warnings />
  <RequestedCommand>namecheap.domains.dns.sethosts</RequestedCommand>
  <CommandResponse Type="namecheap.domains.dns.setHosts">
    <DomainDNSSetHostsResult Domain="ataamatest12345.com" EmailType="FWD" IsSuccess="true">
      <Warnings />
    </DomainDNSSetHostsResult>
  </CommandResponse>
  <Server>PHX01APIEXT01</Server>
  <GMTTimeDifference>--4:00</GMTTimeDifference>
  <ExecutionTime>0.528</ExecutionTime>
</ApiResponse>
EOF;
        $apiResponsePush = new \Namecheap\Api\Response($xml);

        $mock = $this->getConfiguration();

        $mock->expects($this->any())
            ->method('setHosts')
            ->will($this->returnValue($apiResponsePush));

        $mock->expects($this->any())
            ->method('getHosts')
            ->will($this->returnValue($apiResponseGetOld));

        $dnsMock = $this->getDNS();

        $dnsMock->expects($this->atMost(5))
            ->method('dnsqr')
            ->will($this->returnValue(['acmetest']));

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $dnsMock]);
        $adapter = new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\NamecheapAdapter', $adapter);

        $records = [];
        $records[] = [
            'name' => '_acme-challenge.test',
            'data' => 'acmetest',
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];
        //this shouldn't show up in the hosts array
        $records[] = [
            'name' => 'test' . time() . rand(1, 100),
            'data' => 'test' . time() . rand(1, 100),
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];

        //$adapter->addRecords($records);
        $adapter->addOrReplaceRecords($records);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function testFailureGet()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<ApiResponse Status="ERROR" xmlns="http://api.namecheap.com/xml.response">
  <Errors />
  <Warnings />
  <RequestedCommand>namecheap.domains.dns.gethosts</RequestedCommand>
  <CommandResponse Type="namecheap.domains.dns.getHosts">
    <DomainDNSGetHostsResult Domain="example.com" EmailType="FWD" IsUsingOurDNS="false">
      <host HostId="137899816" Name="_acme-challenge.test152573194379" Type="TXT" Address="acmetest" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
      <host HostId="137899817" Name="_acme-challenge.test152573194378" Type="TXT" Address="acmetest" MXPref="10" TTL="60" AssociatedAppTitle="" FriendlyName="" IsActive="true" IsDDNSEnabled="false" />
    </DomainDNSGetHostsResult>
  </CommandResponse>
  <Server>PHX01APIEXT01</Server>
  <GMTTimeDifference>--4:00</GMTTimeDifference>
  <ExecutionTime>0.383</ExecutionTime>
</ApiResponse>
EOF;

        $apiResponseGetOld = new \Namecheap\Api\Response($xml);

        $mock = $this->getConfiguration();

        $mock->expects($this->any())
            ->method('getHosts')
            ->will($this->returnValue($apiResponseGetOld));

        $dnsMock = $this->getDNS();

        $dnsMock->expects($this->atMost(5))
            ->method('dnsqr')
            ->will($this->returnValue(['acmetest']));

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $dnsMock]);
        $adapter = new \LetsEncrypt\Providers\Namecheap\NamecheapAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\NamecheapAdapter', $adapter);

        $records = [];
        $records[] = [
            'name' => '_acme-challenge.test',
            'data' => 'acmetest',
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];
        //this shouldn't show up in the hosts array
        $records[] = [
            'name' => 'test' . time() . rand(1, 100),
            'data' => 'test' . time() . rand(1, 100),
            'ttl' => $adapter->getDefaultTtl(),
            'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
            'priority' => 10
        ];

        try {
            $adapter->getOldRecords();
        } catch(\ErrorException $e) {
            $this->assertInstanceOf('\ErrorException', $e);
        }
    }
}