<?php defined('BASEPATH') or exit('No direct script access allowed');

use phpseclib\Net\SFTP;

function connect($host, $user, $pass, $port = "22")
{
    $sftp = new SFTP($host, $port);

    if (!$sftp->login($user, $pass)) {
        throw new Exception('Login failed to SFTP, host: ' . $host);
    }
    return $sftp;
}
