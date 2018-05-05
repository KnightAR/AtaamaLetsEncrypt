<?php

class Dns_utility
{
    /**
     * Like exec(), but supports extra options, such as timeout.
     * @param string $command the shell command to run
     * @param array $output See exec().
     * @param integer $return_var See exec().
     * @param array $options
     *  - 'timeout' the timeout in seconds (can be fractional) for the command to
     *    produce output. If the command takes longer than this, it will be sent
     *    a TERM signal. (Note that once the command starts producing output we'll
     *    block until it's all read.)
     * @return string the last line of output (possibly empty), or false on error
     * If the timeout is reached, $return_var will be set to null and null will be
     * returned.
     */
    public function exec_ex($command, &$output, &$return_var, $options = array())
    {
        $p = proc_open($command, array(1 => array('pipe', 'w')), $pipes);
        if (!is_resource($p)) {
            return false;
        }
        if (isset($options['timeout'])) {
            $timeout = (float)$options['timeout'];
            $tv_sec = floor($timeout);
            $tv_usec = floor(($timeout - $tv_sec) * 1000000);
            $outputs = array($pipes[1]);
            $ready = stream_select($outputs, $_, $_, $tv_sec, $tv_usec);
            if (!$ready) {
                fclose($pipes[1]);
                proc_terminate($p);
                $return_var = null;
                return null;
            }
        }
        $out = stream_get_contents($pipes[1]);
        if (empty($out)) {
            $output = array();
        } else {
            $output = explode("\n", rtrim($out));
        }
        fclose($pipes[1]);
        $return_var = proc_close($p);
        // now we want to return the final element of $output
        $last_element = end($output);
        reset($output);
        return $last_element;
    }

    /**
     * @param string $domain
     * @param int $timeout
     * @param string $dnsserver
     * @return bool
     */
    public function dnsqns($domain, $timeout = 1, $dnsserver = '8.8.8.8')
    {
        if (!preg_match('/^[_a-zA-Z0-9\.\-]+$/', $domain)) {
            return false;
        }
        $type = 'ns';
        $ret = $this->exec_ex('dig ' . escapeshellarg('@'. $dnsserver ) . ' +short ' . escapeshellarg($domain) . ' ' . escapeshellarg($type), $records, $exit, array('timeout' => $timeout));
        if ($ret === false || $exit !== 0) {
            return false;
        }
        return $records;
    }

    /**
     * DNS functions can be slow and have no timeout options. Use this instead.
     * It's a thin wrapper over the dig tool.
     *
     * @param string $type "a", "mx", "txt", etc.
     * @param string $hostname
     * @param integer $timeout timeout in seconds (can be fractional)
     * @return array of answers (strings, in the format returned by dnsqr)
     * Note: Valid responses include: empty (no records), false (error), null (timeout)
     */
    public function dnsqr($type, $hostname, $timeout = 1, $dnsserver = '8.8.8.8')
    {
        if (!preg_match('/^[_a-zA-Z0-9\.\-]+$/', $hostname)) {
            return false;
        }
        $ret = $this->exec_ex('dig ' . escapeshellarg('@'. $dnsserver ) . ' +short ' . escapeshellarg($hostname) . ' ' . escapeshellarg($type), $records, $exit, array('timeout' => $timeout));
        if ($ret === false || $exit !== 0) {
            return false;
        }
        return $records;
    }

    /**
     * gethostbyname() has no timeout option. Use this instead.
     *
     * @param string $hostname
     * @return string IP address or null (no IP) or false (error)
     */
    public function dnsip($hostname, $timeout = 3, $dnsserver = '8.8.8.8')
    {
        if ($hostname == 'localhost') {
            return '127.0.0.1';
        }
        if (!preg_match('/^[_a-zA-Z0-9\.\-]+$/', $hostname)) {
            return false;
        }
        $ret = $this->exec_ex('dig ' . escapeshellarg('@' . $dnsserver) . ' +short ' . escapeshellarg($hostname), $ip, $exit, array('timeout' => $timeout));
        if ($ret === false || $exit !== 0) {
            return false;
        }
        // dig can return multiple IPs separated by new lines. Return the first one.
        $ip = array_shift($ip);
        if (empty($ip)) {
            return null;
        }
        return $ip;
    }

    /**
     * gethostbyaddr() has no timeout option. Use this instead.
     *
     * @param string $ip
     * @return string hostname or null (no hostname) or false (error)
     */
    public function dnsname($ip, $timeout = 1)
    {
        if (!preg_match('/^[0-9\.]+$/', $ip)) {
            return false;
        }
        $name = $this->exec_ex('dig ' . escapeshellarg('@8.8.8.8') . ' +short -x ' . escapeshellarg($ip) . ' | rev | cut -c 2- | rev', $_, $exit, array('timeout' => $timeout));
        if ($name === false || $exit !== 0) {
            return false;
        }
        $name = trim($name);
        if (empty($name)) {
            return null;
        }
        return $name;
    }
}