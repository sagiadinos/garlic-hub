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

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;

class ShowController
{
	/**
	 * @throws Exception|InvalidArgumentException
	 */
	public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$translator = $request->getAttribute('translator');

		$data = [
			'main_layout' => [
				'LANG_PAGE_TITLE' => $translator->translate('mediapool', 'menu'),
				'additional_css' => [
					'/css/external/bootstrap-icons.min.css',
					'/css/external/wunderbaum.min.css',
					'/css/external/glightbox.min.css',
					'/css/mediapool/overview.css',
					'/css/mediapool/uploads.css'
				],
				'footer_scripts' => [
					'/js/external/wunderbaum.umd.min.js',
					'/js/external/glightbox.min.js',
					'/js/external/jszip.min.js'
				],
				'footer_modules' => [
					'/js/mediapool/init.js'
				]
			],
			'this_layout' => [
				'template' => 'mediapool/overview', // Template-name
				'data' => [
					'LANG_DRAG_AND_DROP' => $translator->translate('drag_and_drop', 'mediapool'),
					'LANG_INSERT_FILES_HERE' => $translator->translate('insert_files_here', 'mediapool'),
					'LANG_START_UPLOAD' => $translator->translate('start_upload', 'mediapool'),
					'LANG_SAVE' => $translator->translate('save', 'main'),
					'LANG_CANCEL' => $translator->translate('cancel', 'main'),
					'LANG_CLOSE' => $translator->translate('close', 'main'),
					'LANG_FOLDER_NAME' => $translator->translate('name', 'main'),
					'LANG_IS_PUBLIC' => $translator->translate('is_public', 'main'),
					'LANG_EDIT' => $translator->translate('edit', 'main'),
					'LANG_CLONE' => $translator->translate('clone', 'main'),
					'LANG_DOWNLOAD' => $translator->translate('download', 'main'),
					'LANG_ADD_ROOT_FOLDER' => $translator->translate('add_root_folder', 'mediapool'),
					'LANG_ADD_SUB_FOLDER' => $translator->translate('add_sub_folder', 'mediapool'),
					'LANG_EDIT_FOLDER' => $translator->translate('edit_folder', 'mediapool'),
					'LANG_REMOVE' => $translator->translate('remove', 'main'),
					'LANG_OWNER' => $translator->translate('owner', 'main'),
					'LANG_FILENAME' => $translator->translate('filename', 'mediapool'),
					'LANG_DESCRIPTION' => $translator->translate('description', 'mediapool'),
					'LANG_MIMETYPE' => $translator->translate('mimetype', 'mediapool'),
					'LANG_FILESIZE' => $translator->translate('filesize', 'mediapool'),
					'LANG_DIMENSIONS' => $translator->translate('dimensions', 'mediapool'),
					'LANG_MEDIA_DURATION' => $translator->translate('media_duration', 'mediapool'),
				]
			]
		];
		$response->getBody()->write(serialize($data));

		return $response->withHeader('Content-Type', 'text/html');
	}

}