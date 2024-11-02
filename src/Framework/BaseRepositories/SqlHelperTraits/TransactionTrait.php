<?php
/*
 garlic-hub: Digital Signage Management Platform

 Copyright (C) 2024 Nikolaos Sagiadinos <garlic@saghiadinos.de>
 This file is part of the garlic-hub source code

 This program is free software: you can redistribute it and/or  modify
 it under the terms of the GNU Affero General Public License, version 3,
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace App\Framework\BaseRepositories\SqlHelperTraits;

use App\Framework\Database\DBHandler;

trait TransactionTrait
{
	/**
	 * @var DBHandler Database adapter
	 */
	protected DBHandler $dbh;

	/**
	 * Begins a database transaction.
	 *
	 * @return void
	 */
	public function beginTransaction(): void
	{
		$this->dbh->beginTransaction();
	}

	/**
	 * Commits a database transaction.
	 *
	 * @return void
	 */
	public function commitTransaction(): void
	{
		$this->dbh->commitTransaction();
	}

	/**
	 * Rolls back a database transaction.
	 *
	 * @return void
	 */
	public function rollbackTransaction(): void
	{
		$this->dbh->rollbackTransaction();
	}

	/**
	 * Checks if a transaction is active.
	 *
	 * @return bool
	 */
	public function isTransactionActive(): bool
	{
		return $this->dbh->hasActiveTransaction();
	}

}