<?php

/**
 * XMail Password Driver
 *
 * Driver for XMail password
 *
 * @version 2.0
 *
 * @author Helio Cavichiolo Jr <helio@hcsistemas.com.br>
 *
 * Setup xmail_host, xmail_user, xmail_pass and xmail_port into
 * config.inc.php of password plugin as follows:
 *
 * $config['xmail_host'] = 'localhost';
 * $config['xmail_user'] = 'YourXmailControlUser';
 * $config['xmail_pass'] = 'YourXmailControlPass';
 * $config['xmail_port'] = 6017;
 *
 * Copyright (C) The Roundcube Dev Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 */

class rcube_xmail_password
{
    public function save($currpass, $newpass)
    {
        $rcmail = rcmail::get_instance();
        [$user, $domain] = explode('@', $_SESSION['username']);

        $xmail = new XMail();

        $xmail->hostname = $rcmail->config->get('xmail_host');
        $xmail->username = $rcmail->config->get('xmail_user');
        $xmail->password = $rcmail->config->get('xmail_pass');
        $xmail->port = $rcmail->config->get('xmail_port');

        if (!$xmail->connect()) {
            rcube::raise_error('Password plugin: Unable to connect to mail server', true);
            return PASSWORD_CONNECT_ERROR;
        }

        if (!$xmail->send("userpasswd\t" . $domain . "\t" . $user . "\t" . $newpass . "\n")) {
            $xmail->close();
            rcube::raise_error('Password plugin: Unable to change password', true);
            return PASSWORD_ERROR;
        }

        $xmail->close();
        return PASSWORD_SUCCESS;
    }
}

class XMail
{
    public $socket;
    public $hostname = 'localhost';
    public $username = 'xmail';
    public $password = '';
    public $port = 6017;

    public function send($msg)
    {
        socket_write($this->socket, $msg);
        if (substr(socket_read($this->socket, 512, \PHP_BINARY_READ), 0, 1) != '+') {
            return false;
        }

        return true;
    }

    public function connect()
    {
        $this->socket = socket_create(\AF_INET, \SOCK_STREAM, 0);
        if (!$this->socket) {
            return false;
        }

        $result = socket_connect($this->socket, $this->hostname, $this->port);
        if (!$result) {
            socket_close($this->socket);
            return false;
        }

        if (substr(socket_read($this->socket, 512, \PHP_BINARY_READ), 0, 1) != '+') {
            socket_close($this->socket);
            return false;
        }

        if (!$this->send("{$this->username}\t{$this->password}\n")) {
            socket_close($this->socket);
            return false;
        }

        return true;
    }

    public function close()
    {
        $this->send("quit\n");
        socket_close($this->socket);
    }
}
