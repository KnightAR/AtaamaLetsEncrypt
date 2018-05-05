<?php
/**
 * Created by PhpStorm.
 * User: knightar
 * Date: 5/7/18
 * Time: 11:29 PM
 */

$config = [];
$domains = [];

if (file_exists('./config.php')) {
    require_once('./config.php');
}