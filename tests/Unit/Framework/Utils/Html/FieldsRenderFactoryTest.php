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

namespace Tests\Unit\Framework\Utils\Html;

use App\Framework\Utils\Html\FieldInterface;
use App\Framework\Utils\Html\FieldsRenderFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class FieldsRenderFactoryTest extends TestCase
{
	private FieldsRenderFactory $fieldsRenderFactory;

	protected function setUp(): void
	{
		$this->fieldsRenderFactory = new FieldsRenderFactory();
	}

	/**
	 * @throws Exception
	 */
	#[Group('units')]
	public function testGetRendererForUnsupportedField(): void
	{
		$fieldMock = $this->createMock(FieldInterface::class);

		$this->expectException(InvalidArgumentException::class);

		$this->fieldsRenderFactory->getRenderer($fieldMock);
	}
}
