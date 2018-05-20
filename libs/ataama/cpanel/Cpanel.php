<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/17/18
 * Time: 5:20 PM
 */

namespace ataama\cpanel;

use GuzzleHttp\Client;

/**
 * cPanel/WHM API
 *
 * Provides easy to use class for calling some CPanel/WHM API functions.
 *
 * Based on mgufrone/cpanel-php by Mochamad Gufron <mgufronefendi@gmail.com>
 *
 * @version v1.0.0
 * @since v1.0.0
 */
class Cpanel implements CpanelInterface
{
    /**
     * @var string Username of your whm server. Must be string
     *
     * @since v1.0.0
     */
    private $username;

    /**
     * @var string Password or long hash of your whm server.
     *
     * @since v1.0.0
     */
    private $password;

    /**
     * @var string session token
     *
     * @since v1.0.0
     */
    private $session;

    /**
     * @var string Authentication type you want to use. You can set as 'hash', 'password', or 'session'.
     *
     * @since v1.0.0
     */
    private $auth_type;

    /**
     * @var string Host of your whm/cpanel server. You must set it with full host with its port and protocol.
     *
     * @since v1.0.0
     */
    private $host;

    /**
     * @var array Sets of headers that will be sent at request.
     *
     * @since v1.0.0
     */
    protected $headers = array();

    /**
     * @var integer Query timeout (Guzzle option)
     *
     * @since v1.0.0
     */
    protected $timeout = 10;

    /**
     * @var integer Connection timeout (Guzzle option)
     *
     * @since v1.0.0
     */
    protected $connection_timeout = 4;

    /**
     * @var \GuzzleHttp\Cookie\FileCookieJar Cookie jar cache
     *
     * @since v1.0.0
     */
    protected $jar;

    /**
     * @var string Cookie file filename
     *
     * @since v1.0.0
     */
    protected $cookiepath = './cpanelconn.cookies';

    /**
     * @var \GuzzleHttp\Client The HTTP Client
     *
     * @since v1.0.0
     */
    protected $client;

    /**
     * @var string Classname
     *
     * @since v1.0.0
     */
    protected $classname;

    /**
     * Class constructor. The options must be contain username, host, and password.
     *
     * @param array $options options that will be passed and processed
     *
     * @throws \Exception
     * @since v1.0.0
     */
    public function __construct($options = array())
    {
        $this->classname = substr(strrchr(get_class($this), '\\'), 1);
        if (!empty($options)) {
            if (!empty($options['auth_type'])) {
                $this->setAuthType($options['auth_type']);
            }

            $this->checkOptions($options)
                ->setHost($options['host'])
                ->setAuthorization($options['username'], $options['password']);

            if (!empty($options['apiclient'])) {
                $this->setClient($options['apiclient']);
            }
        }
        return $this;
    }

    /**
     * Magic method who will call the CPanel/WHM Api.
     *
     * @param string $function function name that will be called
     * @param array $arguments parameter that should be passed when calling API function
     *
     * @return string result of called functions
     *
     * @throws ConnectionException
     * @throws \ErrorException
     * @since v1.0.0
     */
    public function __call($function, $arguments)
    {
        if (count($arguments) > 0) {
            $arguments = $arguments[0];
        }
        return $this->runQuery($function, $arguments);
    }

    /**
     * checking options for 'username', 'password', and 'host'. If they are not set, some exception will be thrown.
     *
     * @param array $options list of options that will be checked
     *
     * @return self
     * @throws \Exception
     * @since v1.0.0
     */
    private function checkOptions($options)
    {
        if (empty($options['username'])) {
            throw new \Exception('Username is not set', 2301);
        }
        if (empty($options['password'])) {
            throw new \Exception('Password or hash is not set', 2302);
        }
        if (empty($options['host'])) {
            throw new \Exception('CPanel Host is not set', 2303);
        }

        return $this;
    }

    /**
     * get API HTTP Client.
     *
     * @return object return the HTTP client
     *
     * @since v1.0.0
     */
    public function getClient()
    {
        if (!empty($this->client)) {
            return $this->client;
        }
        $host = $this->getHost();
        $this->client = new Client(['base_uri' => $host]);
        return $this->client;
    }

    /**
     * set API HTTP Client.
     *
     * @param Client $client The HTTP client
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setClient($client = null)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * set authorization for access.
     * It only set 'username' and 'password'.
     *
     * @param string $username Username of your whm server.
     * @param string $password Password or long hash of your whm server.
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setAuthorization($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * set API Host.
     *
     * @param string $host Host of your whm server.
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * set Authentication Type.
     *
     * @param string $auth_type Authentication type for calling API.
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setAuthType($auth_type)
    {
        $this->auth_type = $auth_type;

        return $this;
    }

    /**
     * set some header.
     *
     * @param string $name key of header you want to add
     * @param string $value value of header you want to add
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setHeader($name, $value = '')
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * set timeout.
     *
     * @param $timeout
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * set connection timeout.
     *
     * @param $connection_timeout
     *
     * @return object return as self-object
     *
     * @since v1.0.0
     */
    public function setConnectionTimeout($connection_timeout)
    {
        $this->connection_timeout = (int) $connection_timeout;

        return $this;
    }

    /**
     * get username.
     *
     * @return string return username
     *
     * @since v1.0.0
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * get authentication type.
     *
     * @return string get authentication type
     *
     * @since v1.0.0
     */
    public function getAuthType()
    {
        return $this->auth_type;
    }

    /**
     * get password or long hash.
     *
     * @return string get password or long hash
     *
     * @since v1.0.0
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * get session token
     *
     * @return string session token
     *
     * @since v1.0.0
     */
    public function getSessionToken()
    {
        return $this->session;
    }

    /**
     * get session token path
     *
     * @return string|null session token path
     *
     * @since v1.0.0
     */
    public function getSessionPath()
    {
        if (!empty($this->session)) {
            return $this->getSessionToken() . '/';
        }
        return null;
    }

    /**
     * get host of your whm server.
     *
     * @return string host of your whm server
     *
     * @since v1.0.0
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * get timeout.
     *
     * @return integer timeout of the Guzzle request
     *
     * @since v1.0.0
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * get connection timeout.
     *
     * @return integer connection timeout of the Guzzle request
     *
     * @since v1.0.0
     */
    public function getConnectionTimeout()
    {
        return $this->connection_timeout;
    }

    /**
     * Extend HTTP headers that will be sent.
     *
     * @return array list of headers that will be sent
     *
     * @since v1.0.0
     */
    private function createHeader()
    {
        $headers = $this->headers;

        $auth_type = $this->getAuthType();

        if ($auth_type !== 'session') {
            $username = $this->getUsername();
            if ('hash' == $auth_type) {
                $headers['Authorization'] = 'WHM ' . $username . ':' . preg_replace("'(\r|\n|\s|\t)'", '',
                        $this->getPassword());
            } elseif ('password' == $auth_type) {
                $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $this->getPassword());
            }
        }
        return $headers;
    }

    /**
     * @return string
     * @throws ConnectionException
     * @throws \ErrorException
     */
    public function setSessionToken()
    {
        $cookieJar = $this->getCookieJar();
        if (!empty($this->session)) {
            return $this->session;
        }

        $arguments = [
            'user' => $this->getUsername(),
            'pass' => $this->getPassword(),
            'goto_uri' => '/'
        ];

        try{
            $request = $this->getClient()->request('POST', $this->getHost() . '/login/', [
                'connection' => 'close',
                'verify' => false,
                'query' => $arguments,
                'timeout' => $this->getTimeout(),
                'connect_timeout' => $this->getConnectionTimeout(),
                'cookies' => $cookieJar
            ]);

            $page = (string) $request->getBody();

            $token = [];

            // Find the session cookie in the page headers
            if (!$cookieJar->getCookieByName('cpsession')) {
                // This will also fail if the login authentication failed. No need to explicitly check for it.
                throw new ConnectionException("Session Login Error");
            }

            // Find the cPanel session token in the page content
            if (
                preg_match('|<META HTTP-EQUIV="refresh"[^>]+URL=/(cpsess\d+)/|i', $page, $token) ||
                preg_match('|PAGE\.securityToken\s+\=\s+\"\\\/(cpsess\d+)\"\;|i', $page, $token)
            ) {
                $this->session = $token[1];
                file_put_contents($this->getCPSessionFile(), $this->session);
                return $this->session;
            }

            print $page;
            throw new ConnectionException("Token Fetch Error");
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            throw new ConnectionException("Error while running query", $e->getCode(), $e);
        }
    }

    /**
     * @return string path to cpsession cookie file
     */
    protected function getCPSessionFile()
    {
        return dirname($this->cookiepath) . '/cpsession.cookie';
    }

    /**
     * @return \GuzzleHttp\Cookie\FileCookieJar
     * @throws \ErrorException
     */
    public function &getCookieJar()
    {
        if (!is_writable(dirname($this->getCPSessionFile()))) {
            throw new \ErrorException("Can't write to Session Cookie File: " . $this->getCPSessionFile());
        } else if (file_exists($this->getCPSessionFile()) && !is_writable($this->getCPSessionFile())) {
            throw new \ErrorException("Can't write to Session Cookie File: " . $this->getCPSessionFile());
        }

        if ($this->jar) {
            return $this->jar;
        }
        if (file_exists($this->getCPSessionFile()) && (time() - filemtime($this->getCPSessionFile())) >= 1800) {
            unlink($this->getCPSessionFile());
            @unlink($this->cookiepath);
        }
        $jar = new \GuzzleHttp\Cookie\FileCookieJar($this->cookiepath, true);
        if (!$jar->getCookieByName('cpsession') && file_exists($this->getCPSessionFile()))
        {
            unlink($this->getCPSessionFile());
        } elseif (file_exists($this->getCPSessionFile())) {
            $this->session = file_get_contents($this->getCPSessionFile());
        }
        $this->jar = $jar;
        return $this->jar;
    }

    /**
     * @param \GuzzleHttp\Cookie\CookieJar $jar
     * @return $this
     */
    public function setCookieJar(\GuzzleHttp\Cookie\CookieJar $jar)
    {
        $this->jar = $jar;
        return $this;
    }

    /**
     * The executor. It will run API function and get the data.
     *
     * @param string $action function name that will be called.
     * @param array $arguments list of parameters that will be attached.
     *
     * @return string results of API call
     *
     * @since v1.0.0
     * @throws ConnectionException
     * @throws \ErrorException
     */
    protected function runQuery($action, $arguments)
    {
        if ($this->getAuthType() === 'session') {
            $this->setSessionToken();
        }

        try{
            $response = $this->getClient()->request('POST', $this->getHost() . '/' . $this->getSessionPath() . 'json-api/' . $action, [
                'headers' => $this->createHeader(),
                'verify' => false,
                'query' => $arguments,
                'timeout' => $this->getTimeout(),
                'connect_timeout' => $this->getConnectionTimeout(),
                'cookies' => $this->getCookieJar()
            ]);

            return (string) $response->getBody();
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            throw new ConnectionException("Error while running query", $e->getCode(), $e);
        }
    }

    /**
     * Use a cPanel API
     *
     * @param $module
     * @param $function
     * @param $username
     * @param array $params
     * @return null|array
     * @throws ConnectionException
     * @throws \ErrorException
     */
    public function cpanel($module, $function, $username, $params = array())
    {
        $action = 'cpanel';
        $params = array_merge($params, [
            'cpanel_jsonapi_version' => 2,
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
            'cpanel_jsonapi_user' => $username,
        ]);

        $response = $this->runQuery($action, $params);
        if (!empty($response)) {
            $response = json_decode($response, true);
            if (!array_key_exists('cpanelresult', $response)) {
                throw new \ErrorException("Incorrect CPanel Response");
            }

            if ($response['cpanelresult']['event']['result'] === 0) {
                throw new \ErrorException("CPanel Error: " . $response->cpanelresult->event->reason);
            }

            if ($response['cpanelresult']['apiversion'] !== 2 || $response['cpanelresult']['func'] !== $function || $response['cpanelresult']['module'] !== $module) {
                throw new \ErrorException("Incorrect CPanel Response");
            }

            return $response['cpanelresult']['data'];
        }
        return null;
    }

    /**
     * Use cPanel API 1 or use cPanel API 2 or use UAPI.
     *
     * @param int $api (1 = cPanel API 1, 2 = cPanel API 2, 3 = UAPI)
     * @param string $module
     * @param string $function
     * @param string $username
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function execute_action($api, $module, $function, $username, $params = array())
    {
        $action = 'cpanel';
        $params = array_merge($params, [
            'cpanel_jsonapi_apiversion' => $api,
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
            'cpanel_jsonapi_user' => $username,
        ]);
        $response = $this->runQuery($action, $params);

        return $response;
    }
}