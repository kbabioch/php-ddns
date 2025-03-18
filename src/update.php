<?php

/*
 * Copyright (c) 2015, 2016 Karol Babioch <karol@babioch.de>
 *               2025 Roel Derickx (https://github.com/roelderickx/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

ob_start();
include 'database_parameters.inc';
ob_end_clean();

error_reporting(E_ALL|E_STRICT);

define('HTTP_MOVED_PERMANENTLY', 301);

define('TTL_MIN', 30);
define('TTL_MAX', 86400);


function verify_https() {

    // Check if invoked over HTTPS, redirect otherwise
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {
         header('Location: ' . $_SERVER['SERVER_NAME'], true, HTTP_MOVED_PERMANENTLY);
         exit;
    }

}


function fetch_credentials() {

    // Check for credentials via HTTP Basic Auth
    if (isset($_SERVER['PHP_AUTH_USER'])) {

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

    // Check for user credentials via GET
    } elseif (isset($_GET['username']) && isset($_GET['pass'])) {

        $username = $_GET['username'];
        $password = $_GET['pass'];

    // No credentials provided
    } else {

        // Ask for credentials via HTTP Basic Auth
        header('WWW-Authenticate: Basic realm="TODO"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'badauth';
        exit;

    }
    
    return array($username, $password);

}


function fetch_hostname() {

    if (isset($_GET['hostname'])) {

        // TODO: Filter / validate input
        $hostname = $_GET['hostname'];

    } else {

        echo 'nxdomain';
        exit;

    }
    
    return $hostname;

}


function fetch_myip() {

    if (isset($_GET['myip'])) {

        // TODO: Filter / validate
        $myip = $_GET['myip'];

    } elseif (isset($_SERVER['REMOTE_ADDR'])) {

        // TODO myip() function
        $myip = $_SERVER['REMOTE_ADDR'];

    } else {

        echo 'badip';
        exit;

    }
    
    return $myip;

}


function fetch_type($myip) {

    // TODO: Logic for type vs IP (i.e. AAAA vs 192.168.0.1)
    if (isset($_GET['type']) && ($_GET['type'] === 'A' || $_GET['type'] === 'AAAA')) {

        // TODO: Filter
        $type = $_GET['type'];

    // Determine type by IP
    } elseif(filter_var($myip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {    

        $type = 'A';

    } elseif (filter_var($myip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

        $type = 'AAAA';

    } else {

       echo 'badtype';
       exit;

    }
    
    return $type;

}


function fetch_ttl() {

    // Default TTL to 30 if not explicetely set
    $ttl = (int) ($_GET['ttl'] ?? 30);

    if ($ttl < TTL_MIN) {

        $ttl = TTL_MIN;

    } elseif ($ttl > TTL_MAX) {

        $ttl = TTL_MAX;

    }
    
    return $ttl;

}


function verify_credentials($db_connection, $hostname, $username, $password) {

    $tsig_key = '';

    $statement = $db_connection->prepare(
        "select dyndns_id, auth_username, auth_password, auth_password_salt, " .
               "tsig_key_algorithm, tsig_keyname, tsig_secret " .
        "from dyndns " .
        "where hostname = :hostname");
    $statement->bindParam(":hostname", $hostname, PDO::PARAM_STR);
    $statement->execute();

    if ($row = $statement->fetch(PDO::FETCH_ASSOC))
    {
        // verify credentials
        if ($username === $row['auth_username'] &&
            password_verify($password . $row['auth_password_salt'], $row['auth_password'])) {

            if (!empty($row['tsig_key_algorithm']) &&
                !empty($row['tsig_keyname']) &&
                !empty($row['tsig_secret'])) {

                $tsig_key = $row['tsig_key_algorithm'] . ':' .
                                  $row['tsig_keyname'] . ' ' .
                                  $row['tsig_secret'];

            }

        } else {

           echo 'badauth';
           exit;

        }
    }
    else
    {
        // hostname is not registered here, this is in fact a bad auth
        echo 'badauth';
        exit;
    }

    return $tsig_key;

}


function save_myip($db_connection, $hostname, $myip)
{
    $statement = $db_connection->prepare(
        "update dyndns " .
        "set ip_address = :myip " .
        "where hostname = :hostname");
    $statement->bindParam(":myip", $myip, PDO::PARAM_STR);
    $statement->bindParam(":hostname", $hostname, PDO::PARAM_STR);
    $statement->execute();
    $statement->closeCursor();
}


function update_dns($tsig_key, $hostname, $myip, $type, $ttl) {

    $output = <<<NSUPDATE
key $tsig_key
prereq yxrrset $hostname $type
del $hostname IN $type
add $hostname $ttl $type $myip
show
send
answer
NSUPDATE;

    $desc = array(
        0 => array('pipe', 'r'),
        1 => array('file', 'stdout', 'a'),
        2 => array('file', 'stderr', 'a')
    );

    $pipes = array();
    $cwd = '/var/empty';
    $env = array();

    $proc = proc_open('/usr/bin/nsupdate', $desc, $pipes, $cwd, $env);

    if (!is_resource($proc)) {

        echo 'badprocess';
        exit;

    }

    fwrite($pipes[0], $output);
    fclose($pipes[0]);

    // TODO: Close file handles for stdout and stderr
    $ret = proc_close($proc);

    if ($ret === 0) {

        echo 'good';

    } else {

        echo 'error';

    }
}

/***********************************************************************************/

verify_https();
list($username, $password) = fetch_credentials();
$hostname = fetch_hostname();
$myip = fetch_myip();
$type = fetch_type($myip);
$ttl = fetch_ttl();

try {

    $db_connection = new PDO($db_connectstring, $db_user, $db_password);
    $db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tsig_key = verify_credentials($db_connection, $hostname, $username, $password);
    save_myip($db_connection, $hostname, $myip);

    if (!empty($tsig_key)) {

        update_dns($tsig_key, $hostname, $myip, $type, $ttl);

    } else {

        echo 'good';

    }

    $db_connection = null;
    
    // TODO: Check last update, -> DDoS ...

}
catch (PDOException $e)
{

    // do not expose the contents of $e, it may leak sensitive data
    header('HTTP/1.1 500 Internal server error');
    exit;

}

