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

namespace App\Modules\Users\Controller;

use App\Framework\Utils\DataGrid\BaseDataGridTemplateFormatter;
use App\Framework\Utils\DataGrid\DataGridFacadeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;

class ShowOverviewController
{
	private DataGridFacadeInterface $facade;
	private BaseDataGridTemplateFormatter $templateFormatter;
	public function __construct(DataGridFacadeInterface $facade, BaseDataGridTemplateFormatter $templateFormatter)
	{
		$this->facade            = $facade;
		$this->templateFormatter = $templateFormatter;
	}

	public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$this->facade->configure($request->getAttribute('translator'), $request->getAttribute('session'));
		$this->facade->handleUserInput($_GET);

		$this->facade->prepareDataGrid();
		$dataGrid = $this->facade->prepareTemplate();

		$templateData = $this->templateFormatter->formatUITemplate($dataGrid);

		$response->getBody()->write(serialize($templateData));

		return $response->withHeader('Content-Type', 'text/html');
	}


}