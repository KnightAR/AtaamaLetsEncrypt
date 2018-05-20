<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/18/18
 * Time: 4:52 PM
 */

namespace ataama\cpanel\modules;


class ZoneEditTest extends \PHPUnit\Framework\TestCase
{
    public $apiclient;
    /*
     * @var $dnsclient \ataama\cpanel\modules\ZoneEdit The DNS Client
     */
    public $dnsclient;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $this->getMockClient();

        $this->dnsclient = new \ataama\cpanel\modules\ZoneEdit([
            'auth_type' => 'session',
            'username' => 'test',
            'password' => 'password',
            'host' => 'https://example.com:2083',
            'apiclient' => $this->apiclient
        ]);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockClient($returnnew = false)
    {
        if (!empty($this->apiclient) && !$returnnew) {
            return $this->apiclient;
        }

        $apiclient = $this->getMockBuilder('\GuzzleHttp\Client')
            ->setConstructorArgs([])
            ->getMock();

        $this->apiclient = $apiclient;
        return $this->apiclient;
    }

    public function testFetchzone_records()
    {
        $this->getMockClient()->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(function($METHOD, $uri) {
                $ret = null;
                if (preg_match('#\/login\/#i', $uri)) {
                    $ret = file_get_contents(dirname(__FILE__) . '/../mockloginresponse.txt');

                } else if (preg_match('#\/json-api\/cpanel#i', $uri)) {
                    $ret = file_get_contents(dirname(__FILE__) . '/../fetchzone_records.json');
                }

                $response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')
                    ->setConstructorArgs([])
                    ->getMock();

                $response->expects($this->any())
                    ->method('getBody')
                    ->will($this->returnValue($ret));
                return $response;
            }));

        $ret = $this->dnsclient->fetchzone_records('username', [
            'domain' => 'example.com',
            'customonly' => 1,
            'type' => 'TXT'
        ]);

        $this->assertNotEmpty($ret);
    }
}
