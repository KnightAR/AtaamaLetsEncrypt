<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/18/18
 * Time: 2:35 PM
 */

namespace ataama\cpanel;

use GuzzleHttp\Cookie\SetCookie;

class CpanelTest extends \PHPUnit\Framework\TestCase
{
    public $apiclient;
    /*
     * @var $dnsclient \ataama\cpanel\Cpanel The DNS Client
     */
    public $dnsclient;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $this->getMockClient();

        $this->dnsclient = new \ataama\cpanel\Cpanel([
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

    /**
     * @throws \Exception
     */
    public function testGetDefaultClient()
    {
        $dnsclient = new \ataama\cpanel\Cpanel([
            'auth_type' => 'session',
            'username' => 'test',
            'password' => 'password',
            'host' => 'example.com',
            'apiclient' => null

        ]);
        $this->assertInstanceOf('\GuzzleHttp\Client', $dnsclient->getClient());
    }

    public function testGetClient()
    {
        $this->assertSame($this->apiclient, $this->dnsclient->getClient());
    }

    public function mockSession()
    {
        $page = file_get_contents(dirname(__FILE__) . '/mockloginresponse.txt');

        $response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')
            ->setConstructorArgs([])
            ->getMock();

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($page));

        $this->getMockClient()->expects($this->any())
            ->method('request')
            ->will($this->returnValue($response));

        return $response;
    }

    public function testSetSessionToken()
    {
        $this->mockSession();

        $cookiejar = new \GuzzleHttp\Cookie\CookieJar();
        $cookie = new SetCookie([
            'Name'     => 'cpsession',
            'Value'    => 'awolacademy',
            'Domain'   => 'awolacademy.com',
            'Path'     => '/',
            'Max-Age'  => null,
            'Expires'  => null,
            'Secure'   => true,
            'Discard'  => false,
            'HttpOnly' => true,
            'port'     => '2083'
        ]);
        $cookiejar->setCookie($cookie);
        $this->dnsclient->setCookieJar($cookiejar);

        $this->dnsclient->setSessionToken();
        $this->assertEquals('cpsess9801228129', $this->dnsclient->getSessionToken());
    }

    public function testCpanel()
    {
        $this->getMockClient()->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(function($METHOD, $uri) {
                $ret = null;
                if (preg_match('#\/login\/#i', $uri)) {
                    $ret = file_get_contents(dirname(__FILE__) . '/mockloginresponse.txt');

                } else if (preg_match('#\/json-api\/cpanel#i', $uri)) {
                    $ret = file_get_contents(dirname(__FILE__) . '/fetchzone_records.json');
                }

                $response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')
                    ->setConstructorArgs([])
                    ->getMock();

                $response->expects($this->any())
                    ->method('getBody')
                    ->will($this->returnValue($ret));
                return $response;
            }));

        $ret = $this->dnsclient->cpanel('ZoneEdit', 'fetchzone_records', 'username', [
            'domain' => 'example.com',
            'customonly' => 1,
            'type' => 'TXT'
        ]);
        print_r($ret);

        $this->assertNotEmpty($ret);
    }

    public function testGetHost()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetTimeout()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testExecute_action()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testGetSessionPath()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetHost()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetAuthType()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testGetUsername()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetConnectionTimeout()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetAuthorization()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testGetAuthType()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetClient()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testGetSessionToken()
    {
        $this->assertInstanceOf("\GuzzleHttp\Cookie\CookieJar", $this->dnsclient->getCookieJar());
    }

    public function testGetTimeout()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testGetConnectionTimeout()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetHeader()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testGetPassword()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
