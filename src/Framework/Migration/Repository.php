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

namespace App\Framework\Migration;

use App\Framework\BaseRepositories\Sql;
use App\Framework\Exceptions\DatabaseException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class Repository extends Sql
{
	const MIGRATION_TABLE_NAME = '_migration_version';
	private string $fieldName = 'version';

	public function __construct(Connection $connection)
	{
		$this->connection   = $connection;
		parent::__construct($connection, self::MIGRATION_TABLE_NAME, 'version');
	}

	/**
	 * @throws Exception
	 */
	public function createMigrationTable(): void
	{
		$sql = 'CREATE TABLE IF NOT EXISTS ' . self::MIGRATION_TABLE_NAME . ' (version INTEGER PRIMARY KEY);';
		$this->connection->executeStatement($sql);
	}

	/**
	 * @throws Exception
	 */
	public function getAppliedMigrations(): array
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->select($this->fieldName)->from($this->table);

		return $queryBuilder->executeQuery()->fetchAllAssociativeIndexed();
	}

	/**
	 * @throws DatabaseException|Exception
	 */
	public function applySqlBatch(string $sqlBatch): static
	{
		$this->connection->beginTransaction();

		try
		{
			$statements = array_filter(array_map('trim', explode(';', $sqlBatch)));
			foreach ($statements as $statement)
			{
				if (!empty($statement))
					$this->connection->executeStatement($statement);
			}
			$this->connection->commit();
		}
		catch (Exception $e)
		{
			$this->connection->rollback();
			$message = $e->getMessage() . ' SQL: ' . $sqlBatch;
			$code = $e->getCode();
			throw new DatabaseException($message, $code);
		}
		return $this;
	}

	/**
	 * Shows columns of the table.
	 *
	 * @return array Columns data
	 * @throws Exception
	 */
	public function showColumns(): array
	{
		return $this->connection->createSchemaManager()->listTableColumns($this->getTable());
	}

	/**
	 * @throws Exception
	 */
	public function showTables(): array
	{
		return $this->connection->createSchemaManager()->listTables();
	}

}