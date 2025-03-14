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


namespace Tests\Unit\Framework\Utils\FilteredList\Paginator;

use App\Framework\Exceptions\ModuleException;
use App\Framework\Utils\FilteredList\Paginator\Renderer;
use App\Framework\Utils\FormParameters\BaseFilterParameters;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
	private Renderer $renderer;
	private BaseFilterParameters $baseFilterMock;

	protected function setUp(): void
	{
		$this->baseFilterMock = $this->createMock(BaseFilterParameters::class);
		$this->renderer = new Renderer();
	}

	#[Group('units')]
	public function testRenderLinks(): void
	{
		// Arrange
		$pageLinks = [
			['page' => 1, 'name' => 'Page 1'],
			['page' => 2, 'name' => 'Page 2']
		];
		$site = 'example-site';

		// Erwartete BaseFilterParameter-Rückgabewerte
		$this->baseFilterMock
			->method('getValueOfParameter')
			->willReturnMap([
				[BaseFilterParameters::PARAMETER_SORT_COLUMN, 'name'],
				[BaseFilterParameters::PARAMETER_SORT_ORDER, 'asc'],
				[BaseFilterParameters::PARAMETER_ELEMENTS_PER_PAGE, '10'],
			]);

		$expectedResult = [
			[
				'ELEMENTS_PAGELINK' => '/example-site?elements_page=1&sort_column=name&sort_order=asc&elements_per_page=10',
				'ELEMENTS_PAGENAME' => 'Page 1',
				'ELEMENTS_PAGENUMBER' => 1,
			],
			[
				'ELEMENTS_PAGELINK' => '/example-site?elements_page=2&sort_column=name&sort_order=asc&elements_per_page=10',
				'ELEMENTS_PAGENAME' => 'Page 2',
				'ELEMENTS_PAGENUMBER' => 2,
			],
		];

		$this->renderer->setBaseFilter($this->baseFilterMock)->setSite($site);

		$result = $this->renderer->renderLinks($pageLinks);

		$this->assertSame($expectedResult, $result);
	}

	#[Group('units')]
	public function testRenderElementsPerSiteDropDown(): void
	{
		$settings = ['min' => 10, 'max' => 30, 'steps' => 10];
		$elementsPage = 2;
		$site = 'example-site';

		$this->renderer->setBaseFilter($this->baseFilterMock)->setSite($site);

		$this->baseFilterMock
			->method('getValueOfParameter')
			->willReturnMap([
				[BaseFilterParameters::PARAMETER_SORT_COLUMN, 'name'],
				[BaseFilterParameters::PARAMETER_SORT_ORDER, 'asc'],
				[BaseFilterParameters::PARAMETER_ELEMENTS_PAGE, $elementsPage],
				[BaseFilterParameters::PARAMETER_ELEMENTS_PER_PAGE, 20],
			]);

		$expectedResult = [
			[
				'ELEMENTS_PER_PAGE_VALUE' => 10,
				'ELEMENTS_PER_PAGE_DATA_LINK' =>'/example-site?elements_per_page=10&sort_column=name&sort_order=asc&elements_page=2',
				'ELEMENTS_PER_PAGE_NAME' => 10,
				'ELEMENTS_PER_PAGE_SELECTED' => ''
			],
			[
				'ELEMENTS_PER_PAGE_VALUE' => 20,
				'ELEMENTS_PER_PAGE_DATA_LINK' =>'/example-site?elements_per_page=20&sort_column=name&sort_order=asc&elements_page=2',
				'ELEMENTS_PER_PAGE_NAME' => 20,
				'ELEMENTS_PER_PAGE_SELECTED' => 'selected'
			],
			[
				'ELEMENTS_PER_PAGE_VALUE' => 30,
				'ELEMENTS_PER_PAGE_DATA_LINK' =>'/example-site?elements_per_page=30&sort_column=name&sort_order=asc&elements_page=2',
				'ELEMENTS_PER_PAGE_NAME' => 30,
				'ELEMENTS_PER_PAGE_SELECTED' => ''
			]
		];

		// Act
		$result = $this->renderer->renderDropdown($settings);

		// Assert
		$this->assertSame($expectedResult, $result);
	}
}
