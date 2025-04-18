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
use App\Framework\Services\AbstractBaseService;
use App\Modules\Mediapool\Services\MediaService;
use App\Modules\Playlists\Helper\ItemType;
use App\Modules\Playlists\Repositories\ItemsRepository;
use Doctrine\DBAL\Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Log\LoggerInterface;

class ItemsService extends AbstractBaseService
{
	private readonly ItemsRepository $itemsRepository;
	private readonly PlaylistsService $playlistsService;
	private readonly MediaService $mediaService;#
	private readonly DurationCalculatorService $durationCalculatorService;


	public function __construct(ItemsRepository $itemsRepository,
								MediaService $mediaService,
								PlaylistsService $playlistsService,
								DurationCalculatorService $durationCalculatorService,
								LoggerInterface $logger)
	{
		$this->itemsRepository  = $itemsRepository;
		$this->playlistsService = $playlistsService;
		$this->mediaService     = $mediaService;
		$this->durationCalculatorService = $durationCalculatorService;

		parent::__construct($logger);
	}

	/**
	 * @throws Exception
	 */
	public function loadItemsByPlaylistId(int $playlistId): array
	{
		$this->playlistsService->setUID($this->UID);

		$items = [];
		$thumbnailPath  = $this->mediaService->getPathTumbnails();
		foreach($this->itemsRepository->findAllByPlaylistId($playlistId) as $value)
		{
			switch ($value['item_type'])
			{
				case ItemType::MEDIAPOOL->value:
					$tmp = $value;
					if (str_starts_with($value['mimetype'], 'image/'))
						$ext = str_replace('jpeg', 'jpg', substr(strrchr($value['mimetype'], '/'), 1));
					else
						$ext = 'jpg';

					$tmp['paths']['thumbnail'] = $thumbnailPath.'/'.$value['file_resource'].'.'.$ext;
					$items[] = $tmp;
					break;
				case ItemType::PLAYLIST->value:
					$tmp = $value;
					$tmp['paths']['thumbnail'] = 'public/images/icons/playlist.svg';
					$items[] = $tmp;
					break;

			}
		}
		$playlist = $this->playlistsService->loadPlaylistForEdit($playlistId);
		$playlist['count_items'] = count($items);

		return ['playlist' =>  $playlist, 'items' => $items];
	}


	/**
	 * @throws Exception
	 */
	public function insertMedia(int $playlistId, string $id, int $position): array
	{
		try
		{
			$this->itemsRepository->beginTransaction();
			$this->mediaService->setUID($this->UID);
			$this->playlistsService->setUID($this->UID);
			$this->durationCalculatorService->setUID($this->UID);

			$media = $this->mediaService->fetchMedia($id); // checks rights, too
			if (empty($media))
				throw new ModuleException('items', 'Media is not accessible');

			$playlistData = $this->playlistsService->loadPlaylistForEdit($playlistId); // also checks rights
			if (empty($playlistData))
				throw new ModuleException('items', 'Playlist is not accessible');

			if (!$this->allowedByTimeLimit($playlistId, $playlistData['time_limit']))
				throw new ModuleException('items', 'Playlist time limit exceeds');

			$itemDuration =  $this->durationCalculatorService->calculateRemainingMediaDuration($playlistData, $media);
			$this->itemsRepository->updatePositionsWhenInserted($playlistId, $position);

			$saveItem = [
				'playlist_id'   => $playlistId,
				'datasource'    => 'file',
				'item_duration' => $itemDuration,
				'item_filesize' => $media['metadata']['size'],
				'item_order'    => $position,
				'item_name'     => $media['filename'],
				'item_type'     => ItemType::MEDIAPOOL->value,
				'file_resource' => $media['checksum'],
				'mimetype'      => $media['mimetype'],
			];
			$id = $this->itemsRepository->insert($saveItem);
			if ($id === 0)
				throw new ModuleException('items', 'Playlist item could not inserted.');

			$saveItem['item_id'] = $id;
			$saveItem['paths'] = $media['paths'];

			$this->updatePlaylistDurationAndFileSize($playlistData);

			$this->calculateDurations($playlistData); // one time, because of the recursive calls in updatePlaylistDurationAndFileSize
			$this->durationCalculatorService->determineTotalPlaylistProperties($playlistId);

			$playlist = [
				'count_items'       => $this->durationCalculatorService->getTotalEntries(),
				'filesize'          => $this->durationCalculatorService->getFileSize(),
				'duration'          => $this->durationCalculatorService->getDuration(),
				'owner_duration'    => $this->durationCalculatorService->getOwnerDuration()
			];

			$this->itemsRepository->commitTransaction();

			return ['playlist' => $playlist, 'item' => $saveItem];
		}
		catch (Exception | ModuleException | CoreException | PhpfastcacheSimpleCacheException $e)
		{
			$this->itemsRepository->rollBackTransaction();
			$this->logger->error('Error insert media: ' . $e->getMessage());
			return [];
		}
	}

	public function insertPlaylist(int $playlistId, string $id, int $position): array
	{
		try
		{
			$this->itemsRepository->beginTransaction();
			$this->mediaService->setUID($this->UID);
			$this->playlistsService->setUID($this->UID);
			$this->durationCalculatorService->setUID($this->UID);

			$playlistData = $this->playlistsService->loadPlaylistForEdit($playlistId); // checks rights
			if (empty($playlistData))
				throw new ModuleException('items', 'Target playlist is not accessible');

			$playlistSourceData = $this->playlistsService->loadPlaylistForEdit($id); // also checks rights
			if (empty($playlistSourceData))
				throw new ModuleException('items', 'Source playlist is not accessible');

			if (!$this->allowedByTimeLimit($playlistId, $playlistData['time_limit']))
				throw new ModuleException('items', 'Playlist time limit exceeds');

			$itemDuration = $playlistSourceData['duration'];
			$this->itemsRepository->updatePositionsWhenInserted($playlistId, $position);

			$saveItem = [
				'playlist_id'   => $playlistId,
				'datasource'    => 'file',
				'item_duration' => $playlistSourceData['duration'],
				'item_filesize' => $playlistSourceData['filesize'],
				'item_order'    => $position,
				'item_name'     => $playlistSourceData['playlist_name'],
				'item_type'     => ItemType::PLAYLIST->value,
				'file_resource' => $id,
				'mimetype'      => ''
			];
			$id = $this->itemsRepository->insert($saveItem);
			if ($id === 0)
				throw new ModuleException('items', 'Playlist item could not inserted.');

			$saveItem['item_id'] = $id;
			$saveItem['paths']['thumbnail'] = 'public/images/icons/playlist.svg';

			$this->updatePlaylistDurationAndFileSize($playlistData);

			$this->calculateDurations($playlistData); // one time, because of the recursive calls in updatePlaylistDurationAndFileSize
			$this->durationCalculatorService->determineTotalPlaylistProperties($playlistId);

			$playlist = [
				'count_items'       => $this->durationCalculatorService->getTotalEntries(),
				'filesize'          => $this->durationCalculatorService->getFileSize(),
				'duration'          => $this->durationCalculatorService->getDuration(),
				'owner_duration'    => $this->durationCalculatorService->getOwnerDuration()
			];

			$this->itemsRepository->commitTransaction();

			return ['playlist' => $playlist, 'item' => $saveItem];
		}
		catch (Exception | ModuleException | CoreException | PhpfastcacheSimpleCacheException $e)
		{
			$this->itemsRepository->rollBackTransaction();
			$this->logger->error('Error insert media: ' . $e->getMessage());
			return [];
		}
	}


	/**
	 * @throws Exception
	 */
	public function updateItemOrder(mixed $playlist_id, array $itemsOrder): void
	{
		$this->playlistsService->setUID($this->UID);
		$this->playlistsService->loadPlaylistForEdit($playlist_id); // will check for rights

		foreach ($itemsOrder as $key => $itemId)
		{
			$this->itemsRepository->updateItemOrder($itemId, $key);
		}
	}

	public function delete(int $playlistId, int $itemId): array
	{
		try
		{
			$this->itemsRepository->beginTransaction();
			$this->playlistsService->setUID($this->UID);
			$this->durationCalculatorService->setUID($this->UID);

			$playlistData = $this->playlistsService->loadPlaylistForEdit($playlistId); // also checks rights
			if (empty($playlistData))
				throw new ModuleException('items', 'Playlist is not accessible');

			$item = $this->itemsRepository->findFirstBy(['item_id' => $itemId]);

			// todo for Core / Enterprise: Check if item belongs to an admin

			if (empty($item))
				throw new ModuleException('items', 'Item not found');

			$deleteId = $this->itemsRepository->delete($itemId);
			if ($deleteId === 0)
				throw new ModuleException('items', 'Item could not deleted');

			$this->itemsRepository->updatePositionsWhenDeleted($playlistId, $item['item_order']);

			$this->updatePlaylistDurationAndFileSize($playlistData);
			$this->calculateDurations($playlistData); // one time, because of the recursive calls in updatePlaylistDurationAndFileSize
			$this->durationCalculatorService->determineTotalPlaylistProperties($playlistId);

			$this->itemsRepository->commitTransaction();

			$playlist = ['count_items'       => $this->durationCalculatorService->getTotalEntries(),
				'filesize'          => $this->durationCalculatorService->getFileSize(),
				'duration'          => $this->durationCalculatorService->getDuration(),
				'owner_duration'    => $this->durationCalculatorService->getOwnerDuration()
			];


			return ['playlist' => $playlist, 'delete_id' => $deleteId];
		}
		catch (Exception | ModuleException | CoreException | PhpfastcacheSimpleCacheException $e)
		{
			$this->itemsRepository->rollBackTransaction();
			$this->logger->error('Error insert media: ' . $e->getMessage());
			return [];
		}

	}

	/**
	 * @throws CoreException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws Exception
	 */
	private function updatePlaylistDurationAndFileSize(array $playlistData)
	{
		if (empty($playlistData) || !isset($playlistData['playlist_id']))
			return $this;

		$this->calculateDurations($playlistData);

		$savePlaylist = [
			'filesize'          => $this->durationCalculatorService->getFileSize(),
			'duration'          => $this->durationCalculatorService->getDuration(),
			'owner_duration'    => $this->durationCalculatorService->getOwnerDuration()
		];
		$this->playlistsService->update($playlistData['playlist_id'], $savePlaylist); // update playlist durations in table

		//now update all higher level playlists
		$saveItem = [
			'item_duration'     => $this->durationCalculatorService->getDuration(),
			'item_filesize'     => $this->durationCalculatorService->getFileSize()
		];
		$this->itemsRepository->update($playlistData['playlist_id'], $saveItem);

		// find all playlist which have inserted this playlist
		$tmp = $this->playlistsService->findAllByItemsAsPlaylistAndMediaId($playlistData['playlist_id']);
		foreach($tmp as $values) // recurse all playlist which have this playlist as item for updating durations
		{
			$this->updatePlaylistDurationAndFileSize($values);
		}
		return $this;
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws CoreException
	 * @throws Exception
	 */
	private function calculateDurations(array $playlistData): void
	{
		$this->durationCalculatorService->calculatePlaylistDurationFromItems($playlistData);
		$this->durationCalculatorService->determineTotalPlaylistProperties($playlistData['playlist_id']);
	}

	/**
	 * @throws Exception
	 */
	private function allowedByTimeLimit(int $playlistId, int $timeLimit): bool
	{
		if ($timeLimit > 0)
			return ($this->itemsRepository->sumDurationOfItemsByUIDAndPlaylistId($this->UID, $playlistId) <= $timeLimit);

		return true;
	}

}