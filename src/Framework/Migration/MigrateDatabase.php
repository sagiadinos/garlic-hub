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
use App\Framework\Exceptions\CoreException;
use App\Framework\Exceptions\DatabaseException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

/**
 * Class MigrateDatabase
 * @package App\Framework\Database\Migration
 */
class MigrateDatabase extends Sql
{
	const MIGRATION_TABLE_NAME = '_migration_version';
	private string $fieldName = 'version';
	protected Connection $connection;
	protected int $version = 0;
	private string $migrationFilePath = '';
	private bool $isSilentOutput = false;
	private FilesystemOperator $filesystem;

	public function __construct(Connection $connection, FilesystemOperator $filesystem)
	{
		$this->filesystem = $filesystem;
		parent::__construct($connection, self::MIGRATION_TABLE_NAME, 'version');
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * @return string
	 */
	public function getMigrationFilePath(): string
	{
		return $this->migrationFilePath;
	}

	/**
	 * @param string $migrationFilePath
	 *
	 * @return $this
	 */
	public function setMigrationFilePath(string $migrationFilePath): MigrateDatabase
	{
		$this->migrationFilePath = $migrationFilePath;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIsSilentOutput(): bool
	{
		return $this->isSilentOutput;
	}

	/**
	 * @param boolean $silentOutput
	 *
	 * @return $this
	 */
	public function setSilentOutput(bool $silentOutput): MigrateDatabase
	{
		$this->isSilentOutput = $silentOutput;
		return $this;
	}

	/**
	 * entry point of migration executable
	 *
	 * @param int|null $targetVersion
	 *
	 * @return  $this
	 * @throws \Exception|Exception
	 */
	public function execute(int $targetVersion = null): MigrateDatabase
	{
		if (!$this->hasMigrationTable())
		{
			$this->createMigrationTable();
		}
		$currentVersion = $this->getMigrationVersion();
		list($highest, $migrations) = $this->getAvailableMigrations();

		// means, always migrate to the highest number if nothing else has been submitted
		if (!isset($targetVersion))
		{
			$targetVersion = $highest;
		}

		if (!isset($targetVersion) || $targetVersion > $highest)
		{
			$targetVersion = $currentVersion;
		}

		if ($currentVersion < $targetVersion)
		{
			$direction = 'up to ' . $targetVersion;
		}
		elseif ($currentVersion > $targetVersion)
		{
			$direction = 'down to ' . $targetVersion;
		}
		else
		{
			$direction = 'none';
		}

		$this->stdOutHeader($currentVersion, $targetVersion, $direction);

		if ($currentVersion < $targetVersion)
		{
			for ($number = $currentVersion + 1; $number <= $targetVersion; $number++)
			{
				$this->migrate($number, 'up', $migrations[$number]);
			}
		}
		elseif ($currentVersion > $targetVersion)
		{
			for ($number = $currentVersion; $number >= $targetVersion + 1; $number--)
			{
				$this->migrate($number, 'down', $migrations[$number]);
			}
		}
		else
		{
			$this->stdOut(PHP_EOL . '... Nothing to do ...' . PHP_EOL);
		}

		$this->stdOutFooter();
		return $this;
	}

	/**
	 * checks if migration table is present
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function hasMigrationTable(): bool
	{
		$result = $this->showTables();

		return !empty($result);
	}

	/**
	 * adds the migration table to database
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function createMigrationTable(): static
	{
		$sql = "CREATE TABLE IF NOT EXISTS `" . self::MIGRATION_TABLE_NAME . "` ( `version` INTEGER NOT NULL PRIMARY KEY)";
		$this->getConnection()->executeStatement($sql);

		$this->insert([$this->fieldName => 0]);

		return $this;
	}

	/**
	 * gets the current migration version from database
	 *
	 * @throws Exception
	 */
	public function getMigrationVersion(): int
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->select($this->fieldName)->from($this->table);

		return (int) $queryBuilder->executeQuery()->fetchOne();
	}

	/**
	 * updates the migration version on database
	 *
	 * @param int $version
	 *
	 * @return  $this
	 * @throws Exception
	 */
	public function updateMigrationVersion(int $version): MigrateDatabase
	{
		$queryBuilder = $this->connection->createQueryBuilder();

		$queryBuilder
			->update($this->table)
			->set($this->fieldName, ':value')
			->where($this->fieldName . ' > :threshold')
			->setParameter('value', $version)
			->setParameter('threshold', 0);

		$queryBuilder->executeStatement();

		return $this;
	}

	/**
	 * scans the migration directory for all available migration files
	 *
	 * @return array
	 * @throws \Exception
	 * @throws FilesystemException
	 */
	public function getAvailableMigrations(): array
	{
		$files      = iterator_to_array($this->filesystem->listContents($this->migrationFilePath));
		$matches    = [];
		$migrations = [];
		$highest    = 0;

		foreach ($files as $file)
		{
			if ($file['type'] === 'dir')
				continue;

			// Match file names with the expected pattern
			if (preg_match('/^(\d+)_(.*?)(?:\.(up|down)){0,1}(?:\.(sql|php))$/', $file['path'], $matches)) {
				$number = (int) $matches[1];
				$name = $matches[2];
				$direction = $matches[3];
				$extension = $matches[4];
			} else
				throw new \Exception('Wrong migration script name: [' . $file['path'] . ']');


			// Check for duplicate migrations
			if (isset($migrations[$number][$direction]))
				throw new \Exception('Migration [' . $number . ' => ' . $direction . '] doubled!');


			// Assign migration files to the correct direction (up/down)
			if ($extension === 'php')
			{
				$migrations[$number]['up'] = $this->getFileName($number, $direction, $name);
				$migrations[$number]['down'] = $this->getFileName($number, $direction, $name);
			}
			else
				$migrations[$number][$direction] = $this->getFileName($number, $direction, $name);

			// Track the highest migration number
			if ($highest < $number)
			{
				$highest = $number;
			}
		}

		return [
			$highest,
			$migrations
		];
	}

	/**
	 * Return "Fully-Qualified" migration file name with path:
	 * 	- SQL file name
	 * 	- PHP file name
	 *
	 * @param integer $number    migration number
	 * @param string  $direction "up"|"down"
	 * @param string  $name      migration name
	 *
	 * @return	string	(realpath of file name)
	 *
	 * @throws	\Exception
	 */
	protected function getFileName(int $number, string $direction, string $name): string
	{
		$pathWithPrefix = $this->migrationFilePath . str_pad($number, 3, '0', STR_PAD_LEFT) . '_';

		$sqlFileName = $pathWithPrefix . $name . '.' . $direction . '.sql';
		$phpFileName = $pathWithPrefix . $name . '.php';

		if (file_exists($sqlFileName))
		{
			$fileName = $sqlFileName;
		}
		else
			if (file_exists($phpFileName))
			{
				$fileName = $phpFileName;
			}
			else
			{
				throw new CoreException('Migration [' . $number . ' => ' . $direction . '] not found!');
			}

		return realpath($fileName);
	}

	/**
	 * @param int     $number
	 * @param string  $direction
	 * @param   array $ar_file_names
	 *
	 * @return  array|MigrateDatabase
	 * @throws \Exception|Exception
	 */
	protected function migrate(int $number, string $direction, array $ar_file_names): array|MigrateDatabase
	{
		$message = PHP_EOL . 'Run Migration No. ' . $number . ' (' . $direction . ')';
		$this->stdOut($message);

		try
		{
			if (isset($ar_file_names['php']))
			{
				$fileName = $ar_file_names['php'];
			}
			elseif (isset($ar_file_names[$direction]))
			{
				$fileName = $ar_file_names[$direction];
			}
			else
			{
				throw new \Exception('Wrong filename found: ' . var_export($ar_file_names, true));
			}

			$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

			$results = match (strtolower($fileExtension))
			{
				'sql' => $this->migrateSql($fileName),
				'php' => $this->migratePhp($fileName, $direction),
				default => throw new \Exception('unknown file extension for file ' . $fileName),
			};

			if ($direction == 'up')
			{
				$this->updateMigrationVersion($number);
			}
			else
			{
				$this->updateMigrationVersion($number - 1);
			}
		}
		catch (\Exception $e)
		{
			$this->stdOutError($number, $direction);
			echo PHP_EOL;

			printf('Code:    %s' . PHP_EOL, $e->getCode());
			printf('Message: %s' . PHP_EOL, $e->getMessage());

			echo 'Trace: ' . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL;
			throw $e;
		}

		return $results;
	}

	/**
	 * migrate a SQL file
	 *
	 * @param string $file_name
	 *
	 * @return	$this
	 *
	 * @throws DatabaseException|Exception
	 */
	protected function migrateSql(string $file_name): MigrateDatabase
	{
		$sqlContent = file_get_contents($file_name);
		$sqlArray   = preg_split('/;\s*\n/', $sqlContent, -1, PREG_SPLIT_NO_EMPTY);
		$sql        = 'none yet';

		$this->getConnection()->beginTransaction();

		try
		{
			foreach ($sqlArray as $sql)
			{
				$sql = trim($sql);
				if (!empty($sql))
				{
					// special case, split DELIMITER statements
					if (preg_match('/DELIMITER(.+)/i', $sql))
					{
						$this->stdOut('Found DELIMITER statement, skipped, migrate manually!' . PHP_EOL);
						continue;
					}

					$this->getConnection()->executeQuery($sql);
				}
				else
				{
					$this->stdOut('SQL statement was empty. Skipping...' . PHP_EOL);
				}
			}
			$this->getConnection()->commit();
		}
		catch (\Exception $e)
		{
			$this->getConnection()->rollback();
			$message = $e->getMessage() . ' SQL: ' . $sql;
			$code = $e->getCode();
			throw new DatabaseException($message, $code);
		}

		return $this;
	}

	/**
	 * executes a migration with a PHP file
	 *
	 * @param string $file_name
	 * @param string $direction
	 *
	 * @return  $this
	 * @throws \Exception|Exception
	 */
	protected function migratePhp(string $file_name, string $direction): MigrateDatabase
	{
		require_once $file_name;

		$className = $this->getClassFromFileName($file_name);
		$migration = new $className($this->getConnection());

		try
		{
			$this->getConnection()->beginTransaction();
			$migration->$direction();
			$this->getConnection()->commit();
		}
		catch (\Exception $e)
		{
			$this->getConnection()->rollback();
			throw $e;
		}
		catch (Exception $e)
		{
		}

		return $this;
	}

	/**
	 * @param string $fileName
	 *
	 * @return string|array|null
	 */
	protected function getClassFromFileName(string $fileName): string|array|null
	{
		return preg_replace(array(
			'~^(\d+_)~iUms',
			'~(\.php)$~iUms'
		), '', basename($fileName));
	}

	/**
	 * Check migration names.
	 *
	 * @param int      $highest
	 * @param	array $migrations
	 *
	 * @return	$this
	 *
	 * @throws	CoreException
	 */
	protected function checkRestrictionsOnMigrationNames(int $highest, array $migrations): MigrateDatabase
	{
		if (isset($migrations[0]))
		{
			throw new CoreException('Migrations with prefix 0 present!');
		}

		for ($number = 1; $number <= $highest; $number++)
		{
			if (!isset($migrations[$number]))
			{
				throw new CoreException('Migrations with prefix ' . $number . ' not present!');
			}

			if (isset($migrations[$number]['php']))
			{
				if (count($migrations[$number]) != 1)
				{
					throw new CoreException('There should be only one PHP migration with prefix ' . $number . '!');
				}
			}
			else
				if (!isset($migrations[$number]['php']))
				{
					if (!isset($migrations[$number]['up']))
					{
						throw new CoreException('There should be one [up] migration with prefix ' . $number . '!');
					}

					if (!isset($migrations[$number]['down']))
					{
						throw new CoreException('There should be one [down] migration with prefix ' . $number . '!');
					}

					if (count($migrations[$number]) != 2)
					{
						throw new CoreException('There should be two migrations [up/down] with prefix ' . $number . '!');
					}

					if (count($migrations[$number]['up']) != 1)
					{
						throw new CoreException('There should be only one name for a migration with prefix ' . $number . '!');
					}

					if (count($migrations[$number]['down']) != 1)
					{
						throw new CoreException('There should be only one name for a migration with prefix ' . $number . '!');
					}
				}
				else
				{
					throw new CoreException('No php extension for migration with prefix [' . $number . ']!');
				}
		}

		return $this;
	}

	/**
	 * prints the cli header on stdOut
	 *
	 * @param int    $currentVersion
	 * @param int    $targetVersion
	 * @param string $direction
	 *
	 * @return  $this
	 */
	protected function stdOutHeader(int $currentVersion, int $targetVersion, string $direction): MigrateDatabase
	{
		$db = $this->getConnectionData();
		$text = <<<TXT
----- Database migrations ----
Path:      {$this->migrationFilePath}
DB Host:   {$db['host']}
DB Name:   {$db['db_name']}
Current:   $currentVersion
Target:    $targetVersion
Direction: $direction

----------- Start ------------

TXT;
		return $this->stdOut($text);
	}

	/**
	 * prints the footer of cli
	 *
	 * @return $this
	 * @throws Exception
	 */
	protected function stdOutFooter(): MigrateDatabase
	{
		$text = <<<TXT

------------ End -------------
New Current: {$this->getMigrationVersion()}
done

TXT;

		return $this->stdOut($text);
	}

	/**
	 * prints an error message on stdOut
	 *
	 * @param int       $number
	 * @param	string $direction
	 *
	 * @return  $this
	 */
	protected function stdOutError(int $number, string $direction): MigrateDatabase
	{
		$text = <<<TXT

-----------FAILURE !!! --------

Migration no $number ($direction) failed!

Abort!

-----------FAILURE !!! --------

TXT;
		echo $text;
		return $this;
	}

	/**
	 * wrapper for echo, respecting the silent flag
	 *
	 * @param string $text
	 *
	 * @return  $this
	 */
	private function stdOut(string $text): MigrateDatabase
	{
		if (!$this->isSilentOutput)
		{
			echo $text;
		}
		return $this;
	}

	private function getConnectionData(): array
	{
		$params = $this->connection->getParams();
		$driver = $params['driver'] ?? 'unknown';

		// Ermittle Host und Datenbankname
		$host = $params['host'] ?? 'localhost';
		$name = $params['dbname'] ?? 'unknown';

		return [
			'host' => $host,
			'db_name' => $name,
			'db_driver' => $driver
		];
	}

	/**
	 * Shows columns of the table.
	 *
	 * @return array Columns data
	 * @throws Exception
	 */
	private function showColumns(): array
	{
		return $this->connection->createSchemaManager()->listTableColumns($this->getTable());
	}

	/**
	 * @throws Exception
	 */
	private function showTables(): array
	{
		return $this->connection->createSchemaManager()->listTables();
	}
}
