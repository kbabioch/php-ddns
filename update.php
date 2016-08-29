<?php

error_reporting(E_ALL|E_STRICT);

define('HTTP_MOVED_PERMANENTLY', 301);

define('TTL_MIN', 30);
define('TTL_MAX', 86400);

$key = 'hmac-sha256:name KEY')

// Check if invoked over HTTPS, redirect otherwise
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {
     header('Location: ' . $_SERVER['SERVER_NAME'], true, HTTP_MOVED_PERMANENTLY);
     exit;
}

$c = print_r($_SERVER, true);
file_put_contents('debug', $c, FILE_APPEND);

// Check for credentials via HTTP Basic Auth
if (isset($_SERVER['PHP_AUTH_USER'])) {

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

// Check for user crendentials via GET
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
 
// Default TTL to 30 if not explicetely set
$ttl = (int) ($_GET['ttl'] ?? 30);

if ($ttl < TTL_MIN) {

    $ttl = TTL_MIN;

} elseif ($ttl > TTL_MAX) {

    $ttl = TTL_MAX;

}

if (isset($_GET['hostname'])) {

    // TODO: Filter / validate input
    $hostname = $_GET['hostname'];

} else {

    echo 'nxdomain';
    exit;

}

// User / hostname validation (TODO: Fix (database))
if ($hostname == 'HOSTNAME' && $password == 'PASSWORD') {

} else {

   echo 'badauth';
   exit;

}

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

// TODO: Fix fixed key
$output = <<<NSUPDATE
key $key
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

    echo 'success';

} else {

    echo 'error';

}

// TODO: Check last update, -> DDoS ...
