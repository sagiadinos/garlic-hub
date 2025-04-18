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

namespace App\Modules\Playlists\Helper\ExportSmil\items;

use App\Framework\Core\Config\Config;
use App\Framework\Exceptions\CoreException;
use App\Framework\Exceptions\ModuleException;
use App\Modules\Playlists\Helper\ExportSmil\Utils\Conditional;
use App\Modules\Playlists\Helper\ExportSmil\Utils\Properties;
use App\Modules\Playlists\Helper\ExportSmil\Utils\Trigger;
use App\Modules\Playlists\Helper\ItemType;

class ItemsFactory
{
	const string MEDIA_TYPE_IMAGE       = 'image';
	const string MEDIA_TYPE_VIDEO       = 'video';
	const string MEDIA_TYPE_AUDIO       = 'audio';
	const string MEDIA_TYPE_WIDGET      = 'widget';
	const string MEDIA_TYPE_DOWNLOAD    = 'download';
	const string MEDIA_TYPE_APPLICATION = 'application';
	const string MEDIA_TYPE_TEXT        = 'text';

	private Config $config;

	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	/**
	 * @throws CoreException
	 */
	public function createItem($item): ItemInterface | null
	{
		switch ($item['item_type'])
		{
			case ItemType::MEDIAPOOL->value:
				return $this->createMedia($item);
			case ItemType::PLAYLIST->value:
				return new SeqContainer(
					$this->config,
					$item,
					new Properties($this->config, $item['properties']),
					new Trigger($item['begin_trigger']),
					new Trigger($item['end_trigger']),
					new Conditional($item['conditional'])
				);

			/*			case ItemType::TEMPLATE->value:
							return new Template($this->config, $item, $item['properties']);
						case ItemType::CHANNEL->value:
							return new Channel($this->config, $item, $item['properties']);
			*/			default:
				new ModuleException('playlists_items', 'Unsupported item type '. $item['item_type'].'.');
		}
		return null;
	}

	/**
	 * @throws CoreException
	 */
	private function createMedia($item): Media | null
	{
		$mediaType = explode('/', $item['mimetype'], 2)[0];

		switch($mediaType)
		{
			case self::MEDIA_TYPE_IMAGE:
				return new Image(
					$this->config,
					$item,
					new Properties($this->config, $item['properties']),
					new Trigger($item['begin_trigger']),
					new Trigger($item['end_trigger']),
					new Conditional($item['conditional'])
				);

			case self::MEDIA_TYPE_VIDEO:
				return new Video(
					$this->config,
					$item,
					new Properties($this->config, $item['properties']),
					new Trigger($item['begin_trigger']),
					new Trigger($item['end_trigger']),
					new Conditional($item['conditional'])
				);

			case self::MEDIA_TYPE_AUDIO:
				return new Audio(
					$this->config,
					$item,
					new Properties($this->config, $item['properties']),
					new Trigger($item['begin_trigger']),
					new Trigger($item['end_trigger']),
					new Conditional($item['conditional'])
				);

			case self::MEDIA_TYPE_WIDGET:
			case self::MEDIA_TYPE_DOWNLOAD:
			case self::MEDIA_TYPE_APPLICATION:
				return new Widget(
					$this->config,
					$item,
					new Properties($this->config, $item['properties']),
					new Trigger($item['begin_trigger']),
					new Trigger($item['end_trigger']),
					new Conditional($item['conditional'])
				);

			case self::MEDIA_TYPE_TEXT:
				return new Text(
					$this->config,
					$item,
					new Properties($this->config, $item['properties']),
					new Trigger($item['begin_trigger']),
					new Trigger($item['end_trigger']),
					new Conditional($item['conditional'])
				);
			default:
				new ModuleException('playlists_items', 'Unsupported media type '. $mediaType);
		}
		return null;
	}
}