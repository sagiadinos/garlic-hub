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

namespace Tests\Unit\Framework\Exceptions;

use App\Framework\Exceptions\BaseException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class BaseExceptionTest extends TestCase
{
	#[Group('units')]
	public function testSetAndGetModuleName(): void
	{
		$exception = new BaseException('Test Exception');
		$exception->setModuleName('TestModule');

		$this->assertSame('TestModule', $exception->getModuleName());
	}

	#[Group('units')]
	public function testGetDetails(): void
	{
		$exception = new BaseException('Test Exception', 123);
		$exception->setModuleName('TestModule');

		$details = $exception->getDetails();

		$this->assertArrayHasKey('module_name', $details);
		$this->assertArrayHasKey('message', $details);
		$this->assertArrayHasKey('code', $details);
		$this->assertArrayHasKey('file', $details);
		$this->assertArrayHasKey('line', $details);
		$this->assertArrayHasKey('trace', $details);

		$this->assertSame('TestModule', $details['module_name']);
		$this->assertSame('Test Exception', $details['message']);
		$this->assertSame(123, $details['code']);
	}

	#[Group('units')]
	public function testGetDetailsAsString(): void
	{
		$exception = new BaseException('Test Exception', 123);
		$exception->setModuleName('TestModule');

		$detailsString = $exception->getDetailsAsString();

		$this->assertStringContainsString('Module: TestModule', $detailsString);
		$this->assertStringContainsString('Message: Test Exception', $detailsString);
		$this->assertStringContainsString('Code: 123', $detailsString);
		$this->assertStringContainsString('File:', $detailsString);
		$this->assertStringContainsString('Trace:', $detailsString);
	}
}
