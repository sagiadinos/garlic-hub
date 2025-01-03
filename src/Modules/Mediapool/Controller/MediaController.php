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


namespace App\Modules\Mediapool\Controller;

use App\Modules\Mediapool\Services\MediaService;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper;

class MediaController
{
	private MediaService $mediaService;
	private int $UID;

	public function __construct(MediaService $mediaService)
	{
		$this->mediaService = $mediaService;
	}

	public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
	{
		if (!$this->hasRights($request->getAttribute('session')))
		{
			$response->getBody()->write(json_encode([]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
		}

		$node_id = (array_key_exists('node_id', $args)) ? (int) $args['node_id'] : 0;
		if ($node_id === 0)
		{
			$response->getBody()->write(json_encode(['success' => false, 'error_message' => 'node is missing']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
		}

		$media_list = $this->mediaService->listMediaBy($node_id);
		$response->getBody()->write(json_encode(['success' => true, 'media_list' => $media_list]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	private function hasRights(Helper $session): bool
	{
		$ret = $session->exists('user');
		if ($ret)
			$this->UID = $session->get('user')['UID'];

		return $ret;
	}


}