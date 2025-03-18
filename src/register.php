<?php

/*
 * Copyright (c) 2025 Roel Derickx (https://github.com/roelderickx/)
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
?>
<html>
<head>
  <title>Register hostname</title>
</head>
<body>
<?php
ob_start();
include 'database_parameters.inc';
ob_end_clean();

error_reporting(E_ALL|E_STRICT);

define('HTTP_MOVED_PERMANENTLY', 301);


function verify_https() {

    // Check if invoked over HTTPS, redirect otherwise
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {
         header('Location: ' . $_SERVER['SERVER_NAME'], true, HTTP_MOVED_PERMANENTLY);
         exit;
    }

}


function fetch_credentials() {

    if (isset($_GET['username']) && isset($_GET['pass'])) {

        $username = $_GET['username'];
        $password = $_GET['pass'];

    // No credentials provided
    } else {

        echo 'Username and password required<br/>';

    }
    
    return array($username, $password);

}


function fetch_hostname() {

    if (isset($_GET['hostname'])) {

        // TODO: Filter / validate input
        $hostname = $_GET['hostname'];

    } else {

        echo 'Hostname to register required<br/>';

    }
    
    return $hostname;

}


function fetch_tsig_key() {

    if (isset($_GET['username']) && isset($_GET['pass'])) {

        $tsig_key_algorithm = $_GET['tsig_algo'];
        $tsig_keyname = $_GET['tsig_key'];
        $tsig_secret = $_GET['tsig_pass'];

    } else {

        echo 'TSIG key is missing, registering host anyway but update will not call nsupdate<br/>';
        
        $tsig_key_algorithm = null;
        $tsig_keyname = null;
        $tsig_secret = null;

    }
    
    return array($tsig_key_algorithm, $tsig_keyname, $tsig_secret);

}


function register_hostname($db_connection, $hostname, $username, $password,
                            $tsig_key_algorithm, $tsig_keyname, $tsig_secret) {

    $salt = bin2hex(random_bytes(16));
    $encypted_password = password_hash($password . $salt, PASSWORD_BCRYPT);

    $statement = $db_connection->prepare(
        "insert into dyndns " .
            "(hostname, ip_address, auth_username, auth_password, auth_password_salt," .
            " tsig_key_algorithm, tsig_keyname, tsig_secret) " .
        "values " .
            "(:hostname, null, :username, :password, :passwordsalt," .
            " :tsigalgo, :tsigkeyname, :tsigsecret)");
    $statement->bindParam(":hostname", $hostname, PDO::PARAM_STR);
    $statement->bindParam(":username", $username, PDO::PARAM_STR);
    $statement->bindParam(":password", $encypted_password, PDO::PARAM_STR);
    $statement->bindParam(":passwordsalt", $salt, PDO::PARAM_STR);
    $statement->bindParam(":tsigalgo", $tsig_key_algorithm, PDO::PARAM_STR);
    $statement->bindParam(":tsigkeyname", $tsig_keyname, PDO::PARAM_STR);
    $statement->bindParam(":tsigsecret", $tsig_secret, PDO::PARAM_STR);
    $statement->execute();
    $statement->closeCursor();
}

/***********************************************************************************/

verify_https();
list($username, $password) = fetch_credentials();
$hostname = fetch_hostname();
list($tsig_key_algorithm, $tsig_keyname, $tsig_secret) = fetch_tsig_key();

if (isset($username) && isset($password) && isset($hostname)) {

    try {

        $db_connection = new PDO($db_connectstring, $db_user, $db_password);
        $db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        register_hostname($db_connection, $hostname, $username, $password,
                          $tsig_key_algorithm, $tsig_keyname, $tsig_secret);

        $db_connection = null;

    }
    catch (PDOException $e)
    {

        // do not expose the contents of $e, it may leak sensitive data
        header('HTTP/1.1 500 Internal server error');
        exit;

    }

}
else
{
?>
  <p>
    <form action="register.php" method="GET">
      <label for="username">Username:</label>
      <input type="text" id="username" name="username"><br/>
      <label for="pass">Password:</label>
      <input type="text" id="pass" name="pass"><br/>
      <label for="hostname">Hostname:</label>
      <input type="text" id="hostname" name="hostname"><br/>
      <label for="tsig_algo">TSIG algorithm:</label>
      <input type="text" id="tsig_algo" name="tsig_algo"><br/>
      <label for="tsig_key">TSIG key:</label>
      <input type="text" id="tsig_key" name="tsig_key"><br/>
      <label for="tsig_pass">TSIG pass:</label>
      <input type="text" id="tsig_pass" name="tsig_pass"><br/>
      <input type="submit" value="Register">
    </form>
  </p>
<?php
}
?>
</body>
</html>

