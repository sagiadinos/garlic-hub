<?php
/*
 garlic-hub: Digital Signage Management Platform

 Copyright (C) 2025 Nikolaos Sagiadinos <garlic@saghiadinos.de>
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

namespace App\Modules\Playlists\Services;

use App\Framework\Exceptions\CoreException;
use App\Framework\Exceptions\ModuleException;
use App\Modules\Playlists\Repositories\PlaylistsRepository;
use Doctrine\DBAL\Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Log\LoggerInterface;

class PlaylistsService
{
	private readonly PlaylistsRepository $playlistRepository;
	private readonly AclValidator $aclValidator;
	private readonly LoggerInterface $logger;
	private int $UID;

	/**
	 * @param PlaylistsRepository $playlistRepository
	 * @param AclValidator $aclValidator
	 * @param LoggerInterface $logger
	 */
	public function __construct(PlaylistsRepository $playlistRepository, AclValidator $aclValidator, LoggerInterface $logger)
	{
		$this->playlistRepository = $playlistRepository;
		$this->aclValidator = $aclValidator;
		$this->logger = $logger;
	}

	public function setUID(int $UID): void
	{
		$this->UID = $UID;
	}

	public function loadPlaylistsForOverview()
	{

	}

	/**
	 * @throws CoreException
	 * @throws Exception
	 * @throws PhpfastcacheSimpleCacheException
	 */
	public function createNew($postData): int
	{
		$saveData = $this->collectDataForInsert($postData);
		// No acl checks required as every logged user can create playlists
		return $this->playlistRepository->insert($saveData);
	}

	/**
	 * @throws ModuleException
	 * @throws CoreException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws Exception
	 */
	public function update(array $postData): int
	{
		$playlistId = $postData['playlist_id'];
		$playlist = $this->playlistRepository->getFirstDataSet($this->playlistRepository->findById($playlistId));

		if (!$this->aclValidator->isPlaylistEditable($this->UID, $playlist))
		{
			$this->logger->error('Error updating playlist. '.$playlist['name'].' is not editable');
			throw new ModuleException('mediapool', 'Error updating playlist. '.$playlist['name'].' is not editable');
		}

		$saveData = $this->collectDataForUpdate($postData);

		return $this->playlistRepository->update($playlistId, $saveData);
	}

	/**
	 * @throws ModuleException
	 * @throws CoreException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws Exception
	 */
	public function delete(int $playlistId): int
	{
		$playlist = $this->playlistRepository->getFirstDataSet($this->playlistRepository->findById($playlistId));

		if (!$this->aclValidator->isPlaylistEditable($this->UID, $playlist))
		{
			$this->logger->error('Error delete playlist. '.$playlist['name'].' is not editable');
			throw new ModuleException('mediapool', 'Error delete playlist. '.$playlist['name'].' is not editable');
		}

		return $this->playlistRepository->delete($playlistId);
	}

	/**
	 * @throws ModuleException
	 * @throws CoreException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws Exception
	 */
	public function loadPlaylistForEdit(int $playlistId): array
	{
		$playlist = $this->playlistRepository->findFirstWithUserName($playlistId);

		if (!$this->aclValidator->isPlaylistEditable($this->UID, $playlist))
		{
			$this->logger->error('Error loading playlist. '.$playlist['name'].' is not editable');
			throw new ModuleException('mediapool', 'Error Loading Playlist. '.$playlist['name'].' is not editable');
		}

		return $playlist;
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws CoreException
	 * @throws Exception
	 */
	private function collectDataForInsert(array $postData): array
	{
		if (array_key_exists('UID', $postData))
			$saveData['UID'] = $postData['UID'];
		else
			$saveData['UID'] = $this->UID;

		$saveData['playlist_mode'] = $postData['playlist_mode'];

		return $this->collectCommon($postData, $saveData);
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws CoreException
	 * @throws Exception
	 */
	private function collectDataForUpdate(array $postData): array
	{
		$saveData = [];
		// only moduleadmin are allowed to change UID
		if (array_key_exists('UID', $postData))
			$saveData['UID'] = $postData['UID'];

		return $this->collectCommon($postData, $saveData);
	}

	/**
	 * @throws CoreException
	 * @throws Exception
	 * @throws PhpfastcacheSimpleCacheException
	 */
	private function collectCommon(array $postData, array $saveData): array
	{
		$saveData['playlist_name'] = $postData['playlist_name'];
		if (array_key_exists('time_limit', $postData))
			$saveData['time_limit'] = $postData['time_limit'];

		if (array_key_exists('multizone', $postData))
			$saveData['multizone'] = $postData['multizone'];

		return $saveData;
	}
}