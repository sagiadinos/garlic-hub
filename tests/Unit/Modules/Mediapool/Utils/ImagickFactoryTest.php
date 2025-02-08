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

namespace Tests\Unit\Modules\Mediapool\Utils;

use App\Modules\Mediapool\Utils\ImagickFactory;
use Imagick;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class ImagickFactoryTest extends TestCase
{
	#[Group('units')]
	public function testCreateImagickReturnsImagickInstance()
	{
		$factory = new ImagickFactory();
		$imagick = $factory->createImagick();
		$this->assertInstanceOf(Imagick::class, $imagick);
	}

	#[Group('units')]
	public function testCreateImagickCreatesNewInstanceEachTime()
	{
		$factory = new ImagickFactory();
		$imagick1 = $factory->createImagick();
		$imagick2 = $factory->createImagick();
		$this->assertNotSame($imagick1, $imagick2);
	}
}
