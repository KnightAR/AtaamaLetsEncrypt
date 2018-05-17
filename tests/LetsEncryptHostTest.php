<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 8:31 PM
 */

namespace Test;

use LetsEncrypt\Host\HostVerificationObject;
use LetsEncrypt\Host\HostVerificationEntries;
use LetsEncrypt\Providers\Namecheap\RecordConverter;

class LetsEncryptHostTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @throws \LetsEncrypt\Host\exceptions\HostEntryException
     */
    public function HostVerificationObject()
    {
        $assoc = [
            'getType' => '@',
            'getHostname' => crc32(rand()),
            'getAddress' => crc32(rand()),
            'getTtl' => rand(600,1800),
            'getMXPref' => rand(10,100),
            'getEmailType' => crc32(rand()),
            'isVerified' => false
        ];
        $host = new HostVerificationObject($assoc['getType'], $assoc['getHostname'],  $assoc['getAddress'], $assoc['getTtl'], $assoc['getMXPref'], $assoc['getEmailType']);
        $this->assertInstanceOf('\LetsEncrypt\Host\HostVerificationObject', $host);

        foreach($assoc as $getter => $val) {
            $this->assertEquals($val, $host->$getter());
        }

        try {
            new HostVerificationObject('invalid', $assoc['getHostname'],  $assoc['getAddress'], $assoc['getTtl'], $assoc['getMXPref'], $assoc['getEmailType']);
        } catch(\LetsEncrypt\Host\exceptions\HostEntryException $e) {
            $this->assertInstanceOf('\LetsEncrypt\Host\exceptions\HostEntryException', $e);
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function HostVerificationEntires()
    {
        $entires = new HostVerificationEntries('example.com');
        $this->assertInstanceOf('\LetsEncrypt\Host\HostVerificationEntries', $entires);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function RecordConverter()
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

        $_assoc = [
            'getType' => $assoc['type'],
            'getHostname' => $assoc['name'],
            'getAddress' => $assoc['data'],
            'getTtl' => $assoc['ttl'],
            'getMXPref' => $assoc['priority'],
            'getEmailType' => $assoc['emailtype'],
            'isVerified' => $assoc['verified']
        ];

        $entires = new HostVerificationEntries('example.com');
        $entires->addEntry($record);

        reset($entires);
        foreach ($_assoc as $getter => $val) {
            $this->assertEquals($val, $entires->$getter());
        }

        foreach($entires as $entry) {
            $this->assertInstanceOf('\LetsEncrypt\Providers\Namecheap\RecordConverter', $entry);
        }

        try {
            new HostVerificationEntries('');
        } catch(\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);
        }
    }

    /**
     * @test
     */
    public function RecordConverterArray()
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

        $this->assertEquals('@', $record['Type']);

        unset($record['Type']);
        $this->assertFalse(isset($record['Type']));

        $record['Type'] = 'MX';
        $this->assertEquals('MX', $record['Type']);

        $this->assertTrue(isset($record['Type']));

        $this->assertFalse(isset($record[0]));
        $record[] = 'New';
        $this->assertTrue(isset($record[0]));
    }
}