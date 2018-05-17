<?php
namespace Test;

use LetsEncrypt\Providers\Godaddy\RecordConverter;
use LetsEncrypt\Host\HostVerificationEntries;

class GodaddyAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $VdomainsApi;
    private $dns;
    private $domain = "example.com";

    public function getOptions($options)
    {
        return array_merge($options, [
            'apikey' => 'qwertyuiopasdfghjklzxcvbnm',
            'apisecret' => 'qwertyuiopASDFGHJKLZXCVBNM'
        ]);
    }

    public function getConfiguration()
    {
        if (is_null($this->VdomainsApi)) {
            $configuration = new \GoDaddyDomainsClient\Configuration();
            $configuration->addDefaultHeader("Authorization", "sso-key asdf:asdf");
            $configuration->setHost('api.ote-godaddy.com');

            $apiclient = new \GoDaddyDomainsClient\ApiClient($configuration);

            $this->VdomainsApi = $this->getMockBuilder('\GoDaddyDomainsClient\Api\VdomainsApi')
                ->setConstructorArgs(array($apiclient))
                ->getMock();
        }

        return clone $this->VdomainsApi;
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

    public function teadDown()
    {
        unset($this->VdomainsApi, $this->dns);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testAdapter()
    {
        $options = $this->getOptions(['dnsclient' => $this->getConfiguration(), 'dig' => $this->getDNS()]);
        $adapter = new \LetsEncrypt\Providers\Godaddy\GodaddyAdapter($this->domain, $options, true);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Godaddy\GodaddyAdapter', $adapter);

        $this->assertEquals(true, $adapter->getSandbox());

        try {
            new \LetsEncrypt\Providers\Godaddy\GodaddyAdapter('', $options, true);
        } catch(\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);
        }

        $configuration = new \GoDaddyDomainsClient\Configuration();
        $configuration->addDefaultHeader("Authorization", "sso-key asdf:asdf");
        $configuration->setHost('api.ote-godaddy.com');

        $apiclient = new \GoDaddyDomainsClient\ApiClient($configuration);

        $options = $this->getOptions(['apiclient' => $apiclient]);
        new \LetsEncrypt\Providers\Godaddy\GodaddyAdapter($this->domain, $options, true);
    }

    /**
     * @test
     */
    public function testOldRecords()
    {
        $expected = [
            'type' => 'A',
            'name' => '@',
            'data' => '127.0.0.1',
            'priority' => 10,
            'ttl' => 600,
            'service' => '',
            'protocol' => '',
            'port' => '',
            'weight' => '',
            'verified' => ''
        ];
        $response = [
            new \GoDaddyDomainsClient\Model\DNSRecord($expected),
            new \GoDaddyDomainsClient\Model\DNSRecord([
                'type' => 'TXT',
                'name' => '_acme-challenge.test152572483087',
                'data' => 'test152572483020',
                'priority' => 10,
                'ttl' => 600,
                'service' => '',
                'protocol' => '',
                'port' => '',
                'weight' => '',
                'verified' => ''
            ])
        ];

        $mock = $this->getConfiguration();
        $mock->expects($this->once())
            ->method('recordGet')
            ->will($this->returnValue($response));

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $this->getDNS()]);
        $adapter = new \LetsEncrypt\Providers\Godaddy\GodaddyAdapter($this->domain, $options, true);

        $adapter->getOldRecords();

        $hosts = $adapter->getHosts();
        $this->assertInstanceOf('\LetsEncrypt\Host\HostVerificationEntries', $hosts);

        foreach($hosts as $host) {
            $this->assertInstanceOf("\LetsEncrypt\Providers\Godaddy\RecordConverter", $host);

            $providerObj = $host->convertProvider();
            $this->assertInstanceOf("\GoDaddyDomainsClient\Model\DNSRecordCreateType", $providerObj);

            $convert = $host->convert();
            $this->assertInstanceOf("\LetsEncrypt\Host\HostVerificationObject", $convert);

            $this->assertEquals($expected['type'], $host->getType());
            $this->assertEquals($expected["name"], $providerObj->getName());
            $this->assertEquals($expected["data"], $providerObj->getData());
            $this->assertEquals($expected["priority"], $providerObj->getPriority());
            $this->assertEquals($expected["ttl"], $providerObj->getTtl());
            $this->assertEquals($expected["service"], $providerObj->getService());
            $this->assertEquals($expected["protocol"], $providerObj->getProtocol());
            $this->assertEquals($expected["port"], $providerObj->getPort());
            $this->assertEquals($expected["weight"], $providerObj->getWeight());
        }
    }

    /**
     * @test
     */
    public function testAddRecords()
    {
        $mock = $this->getConfiguration();

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $this->getDNS()]);
        $adapter = new \LetsEncrypt\Providers\Godaddy\GodaddyAdapter($this->domain, $options, true);

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
            $this->assertInstanceOf("\LetsEncrypt\Providers\Godaddy\RecordConverter", $host);

            $providerObj = $host->convertProvider();
            $this->assertInstanceOf("\GoDaddyDomainsClient\Model\DNSRecordCreateType", $providerObj);

            $convert = $host->convert();
            $this->assertInstanceOf("\LetsEncrypt\Host\HostVerificationObject", $convert);

            $this->assertEquals($expected['type'], $host->getType());
            $this->assertEquals($expected["name"], $providerObj->getName());
            $this->assertEquals($expected["data"], $providerObj->getData());
            $this->assertEquals($expected["priority"], $providerObj->getPriority());
            $this->assertEquals($expected["ttl"], $providerObj->getTtl());
            $this->assertEquals(null, $providerObj->getService());
            $this->assertEquals(null, $providerObj->getProtocol());
            $this->assertEquals(null, $providerObj->getPort());
            $this->assertEquals(null, $providerObj->getWeight());
        }
    }

    /**
     * @test
     */
    public function testAddOrReplaceRecords()
    {
        $mock = $this->getConfiguration();

        $response = [
            new \GoDaddyDomainsClient\Model\DNSRecord([
                'type' => 'TXT',
                'name' => '_acme-challenge.test152572483087',
                'data' => 'test152572483020',
                'priority' => 10,
                'ttl' => 600,
                'service' => '',
                'protocol' => '',
                'port' => '',
                'weight' => '',
                'verified' => ''
            ])
        ];

        $mock->expects($this->once())
            ->method('recordGet')
            ->will($this->returnValue($response));

        $mock->expects($this->once())
            ->method('recordReplaceType')
            ->will($this->returnValue(null));

        $dnsMock = $this->getMockBuilder('\Dns_utility')
            ->setConstructorArgs(array())
            ->getMock();

        $dnsMock->expects($this->any())
            ->method('dnsqns')
            ->will($this->returnValue(['ns1.example.com.', 'ns2.example.com.']));

        $dnsMock->expects($this->atMost(5))
            ->method('dnsqr')
            ->will($this->returnValue(['acmetest']));

        $options = $this->getOptions(['dnsclient' => $mock, 'dig' => $dnsMock]);
        $adapter = new \LetsEncrypt\Providers\Godaddy\GodaddyAdapter($this->domain, $options, true);

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
            $this->assertEquals(true, $host->isVerified());

            $expected = $records[$i];
            $this->assertInstanceOf("\LetsEncrypt\Providers\Godaddy\RecordConverter", $host);

            $providerObj = $host->convertProvider();
            $this->assertInstanceOf("\GoDaddyDomainsClient\Model\DNSRecordCreateType", $providerObj);

            $convert = $host->convert();
            $this->assertInstanceOf("\LetsEncrypt\Host\HostVerificationObject", $convert);

            $this->assertEquals($expected['type'], $host->getType());
            $this->assertEquals($expected["name"], $providerObj->getName());
            $this->assertEquals($expected["data"], $providerObj->getData());
            $this->assertEquals($expected["priority"], $providerObj->getPriority());
            $this->assertEquals($expected["ttl"], $providerObj->getTtl());
            $this->assertEquals(null, $providerObj->getService());
            $this->assertEquals(null, $providerObj->getProtocol());
            $this->assertEquals(null, $providerObj->getPort());
            $this->assertEquals(null, $providerObj->getWeight());
        }
    }

    /**
     * @throws \Exception
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
        $this->assertInstanceOf('\LetsEncrypt\Providers\Godaddy\RecordConverter', $record);

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

        $record = new RecordConverter($assoc);
        $this->assertInstanceOf('\LetsEncrypt\Providers\Godaddy\RecordConverter', $record);

        $_assoc = [
            'getType' => $assoc['type'],
            'getHostname' => $assoc['name'],
            'getAddress' => $assoc['data'],
            'getTtl' => $assoc['ttl'],
            //'getMXPref' => $assoc['priority'],
            //'getEmailType' => $assoc['emailtype'],
            'isVerified' => $assoc['verified']
        ];

        $entires = new HostVerificationEntries('example.com');
        $entires->addEntry($record);

        reset($entires);
        foreach ($_assoc as $getter => $val) {
            $this->assertEquals($val, $entires->$getter());
        }

        foreach($entires as $entry) {
            $this->assertInstanceOf('\LetsEncrypt\Providers\Godaddy\RecordConverter', $entry);
        }

        try {
            new HostVerificationEntries('');
        } catch(\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);
        }
    }
}