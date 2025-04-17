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


namespace App\Modules\Playlists\Services;

use App\Framework\Core\Config\Config;
use App\Framework\Exceptions\CoreException;
use App\Modules\Playlists\Repositories\ItemsRepository;
use Doctrine\DBAL\Exception;

class ExportService
{
	private Config $config;
	private ItemsRepository $itemsRepository;

	/**
	 * @param Config          $config
	 * @param ItemsRepository $itemsRepository
	 */
	public function __construct(Config $config, ItemsRepository $itemsRepository)
	{
		$this->config = $config;
		$this->itemsRepository = $itemsRepository;
	}

	/**
	 * @throws Exception
	 */
	public function export(int $playlistId)
	{
		$results = $this->itemsRepository->findAllByPlaylistIdWithJoins($playlistId, $this->config->getEdition());
		foreach ($results as $item)
		{
			switch ($item->type)
			{
				
			}

		}


	}

	/**
	 * @throws CoreException
	 */
	protected function handleCreateMediaSymlinks(Content $Content)
	{
		if ($this->config->getConfigValue('content_server_url', 'mediapool'))
		{
			$this->getExportRemote()->createMediaSymlinks($Content);
		}
		else
		{
			$this->getExportLocal()->createMediaSymlinks($Content);
		}

		return $this;
	}

	protected function handleCreateTemplateSymlinks(Content $Content)
	{
		if ($this->config->getConfigValue('_template_content_server_url', 'templates') != '' && !\Thymian::isTemplateServer())
		{
			$this->getExportRemote()->createTemplatesSymlinks($Content);
		}
		else
		{
			$this->getExportLocal()->createTemplatesSymlinks($Content);
		}

		return $this;
	}
}