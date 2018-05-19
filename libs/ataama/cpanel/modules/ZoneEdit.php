<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/18/18
 * Time: 4:49 PM
 */

namespace ataama\cpanel\modules;

/**
 * Class ZoneEdit
 * @package ataama\cpanel\modules
 *
 * cPanel API Module: ZoneEdit
 *
 * CPanel Doc: https://documentation.cpanel.net/display/DD/cPanel+API+2+Modules+-+ZoneEdit
 *
 * Methods:
 * @method array fetchzone_records($username, $params)
 * @method array add_zone_record($username, $params)
 * @method array edit_zone_record($username, $params)
 */
class ZoneEdit extends \ataama\cpanel\Cpanel
{
    /**
     * @param $function
     * @param $arguments
     * @return array|null
     * @throws \ErrorException
     * @throws \ataama\cpanel\ConnectionException
     */
    public function __call($function, $arguments)
    {
        $username = $arguments[0];
        $params = [];
        if (count($arguments) > 1) {
            $params = $arguments[1];
        }
        return $this->cpanel($this->classname, $function, $username, $params);
    }
}