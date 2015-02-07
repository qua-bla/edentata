<?php

/*
 * Copyright (C) 2015 Michael Herold <quabla@hemio.de>
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
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace hemio\edentata\module\email;

use hemio\edentata\sql;

/**
 * Description of DbQueries
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class Db extends \hemio\edentata\ModuleDb {

    public static function emailAddressToArgs($address, $prefix = '') {
        $arg = [];
        $arg['p_' . $prefix . 'localpart'] = explode('@', $address)[0];
        $arg['p_' . $prefix . 'domain'] = explode('@', $address)[1];

        return $arg;
    }

    /**
     * 
     * @return \PDOStatement
     */
    public function getMailboxes() {
        $stmt = new sql\QuerySelectFunction(
                $this->pdo, 'email.sel_mailbox'
        );

        return $stmt->execute();
    }

    public function createMailbox(array $params) {
        $stmt = new sql\QuerySelectFunction(
                $this->pdo
                , 'email.ins_mailbox'
                , $params
        );

        return $stmt->execute();
    }

    public function mailboxPassword(array $params) {
        $stmt = new sql\QuerySelectFunction(
                $this->pdo
                , 'email.upd_mailbox'
                , $params
        );

        return $stmt->execute();
    }

    public function getAliases($mailboxLocalpart, $mailboxDomain) {
        $stmt = new sql\QuerySelectFunction(
                $this->pdo
                , 'email.sel_alias'
        );
        $stmt->options('WHERE mailbox_localpart = :localpart AND mailbox_domain = :domain');

        return $stmt->execute(['localpart' => $mailboxLocalpart, 'domain' => $mailboxDomain]);
    }

    public function createAlias(array $params) {
        $stmt = new sql\QuerySelectFunction(
                $this->pdo
                , 'email.ins_alias'
                , $params
        );

        return $stmt->execute();
    }

    /**
     * 
     * @return \PDOStatement
     */
    public function getPossibleDomains() {
        $stmt = new sql\QuerySelectFunction(
                $this->pdo, 'dns.sel_available_service'
        );
        $stmt->options('WHERE service = :service');

        return $stmt->execute(['service' => 'email']);
    }

}
