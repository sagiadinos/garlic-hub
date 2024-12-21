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

namespace App\Framework\BaseRepositories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Abstract class Base
 *
 * Provides a base model for database operations.
 */
abstract class Sql
{

	use FindOperationsTrait;

	protected string $idField;
	protected Connection $connection;

	public function __construct(Connection $connection, string $table, string $idField)
	{
		$this->connection   = $connection;
		$this->table        = $table;
		$this->idField     = $idField;
	}


	public function getTable(): string
	{
		return $this->table;
	}

	public function getIdField(): string
	{
		return $this->idField;
	}

	/**
	 * Inserts a new record into the database and returns the new insert id.
	 *
	 * creates internally a prepared statement
	 *
	 * @throws Exception
	 */
	public function insert(array $fields): int
	{
		$this->connection->insert($this->getTable(), $fields);
		return (int) $this->connection->lastInsertId();
	}


	/**
	 * Updates a record in the database by ID and returns the affected rows.
	 *
	 * creates internally a prepared statement
	 *
	 * @throws Exception
	 */
	public function update(int|string $id, array $fields): int
	{
		return (int) $this->connection->update($this->getTable(), $fields, [$this->getIdField() => $id]);
	}

	/**
	 * Updates records in the database with a custom WHERE clause.
	 *
	 * @param array $fields Fields to update
	 * @param array $conditions Conditions to match for where clause
	 * @return int Number of affected rows
	 * @throws Exception
	 */
	public function updateWithWhere(array $fields, array $conditions): int
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->update($this->getTable());

		foreach ($fields as $field => $value)
		{
			$queryBuilder->set($field, ":set_$field");
			$queryBuilder->setParameter("set_$field", $value);
		}

		foreach ($conditions as $field => $value)
		{
			$queryBuilder->andWhere("$field = :$field");
			$queryBuilder->setParameter($field, $value);
		}

		return (int) $queryBuilder->executeStatement();
	}

	/**
	 * Deletes a record from the database by ID and returns the affected rows.
	 *
	 * @throws Exception
	 */
	public function delete(int|string $id): int
	{
		return $this->deleteByField($this->getIdField(), $id);
	}

	/**
	 * Deletes records from the database by a specific field and a value.
	 * Returns the affected rows
	 *
	 * @throws Exception
	 */
	public function deleteByField(string $field, mixed $value): int
	{
		return (int) $this->connection->delete($this->getTable(), [$field => $value]);
	}

	/**
	 * Deletes records from the database with a custom WHERE clause.
	 * @throws Exception
	 */
	public function deleteBy(array $conditions): int
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->delete($this->getTable());

		$this->determineConditions($queryBuilder, $conditions);

		return (int) $queryBuilder->executeStatement();
	}

	protected function determineConditions(QueryBuilder $queryBuilder, array $conditions): void
	{
		foreach ($conditions as $field => $value)
		{
			$queryBuilder->andWhere("$field = :$field");
			$queryBuilder->setParameter($field, $value);
		}
	}

	protected function determineLeftJoins(QueryBuilder $queryBuilder, array $joins): void
	{
		foreach ($joins as $table => $onCondition)
		{
			$queryBuilder->leftJoin($this->table, $table, $table, $onCondition);
		}
	}

}