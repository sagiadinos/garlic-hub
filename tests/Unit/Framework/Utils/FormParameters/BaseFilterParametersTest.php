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


namespace Tests\Unit\Framework\Utils\FormParameters;

use App\Framework\Core\Sanitizer;
use App\Framework\Core\Session;
use App\Framework\Exceptions\ModuleException;
use App\Framework\Utils\FormParameters\BaseFilterParameters;
use App\Framework\Utils\FormParameters\BaseFilterParametersInterface;
use App\Framework\Utils\FormParameters\ScalarType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ConcreteFilterBaseParameters extends BaseFilterParameters
{
	public function __construct(Sanitizer $sanitizer, Session $session, string $keyStore)
	{
		parent::__construct('testModule', $sanitizer, $session, $keyStore);
		$this->currentParameters = $this->defaultParameters;
	}

	public function addDefaultParameters(): void
	{
		$this->currentParameters = $this->defaultParameters;
	}
}

class BaseFilterParametersTest extends TestCase
{
	private readonly Session $sessionMock;
	private readonly Sanitizer $sanitizerMock;
	private BaseFilterParameters $baseFilterParameters;

	/**
	 * @throws Exception
	 */
	public function setUp(): void
	{
		$this->sanitizerMock = $this->createMock(Sanitizer::class);
		$this->sessionMock = $this->createMock(Session::class);

		$this->baseFilterParameters = new ConcreteFilterBaseParameters($this->sanitizerMock, $this->sessionMock, 'testKeyStore');
	}

	#[Group('units')]
	public function testSetParameterDefaultValuesSuccessfullySetsDefault()
	{
		$this->baseFilterParameters->setParameterDefaultValues('column_name');
		$this->assertSame('column_name', $this->baseFilterParameters->getDefaultValueOfParameter(BaseFilterParameters::PARAMETER_SORT_COLUMN));
	}


	/**
	 * @throws ModuleException
	 */
	#[Group('units')]
	public function testSetElementsParametersToNullSetsValuesToZero()
	{
		$this->baseFilterParameters->setValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PAGE, 10);
		$this->baseFilterParameters->setValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PER_PAGE, 10);

		$this->assertSame(
			10,
			$this->baseFilterParameters->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PAGE)
		);
		$this->assertSame(
			10,
			$this->baseFilterParameters->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PER_PAGE)
		);

		$this->baseFilterParameters->setElementsParametersToNull();

		$this->assertSame(
			0,
			$this->baseFilterParameters->getValueOfParameter(BaseFilterParameters::PARAMETER_ELEMENTS_PAGE)
		);
		$this->assertSame(
			0,
			$this->baseFilterParameters->getValueOfParameter(BaseFilterParameters::PARAMETER_ELEMENTS_PER_PAGE)
		);
	}

	#[Group('units')]
	public function testAddCompany()
	{
		$this->baseFilterParameters->addCompany();

		$this->assertTrue($this->baseFilterParameters->hasParameter(BaseFilterParameters::PARAMETER_COMPANY_ID));
	}

	#[Group('units')]
	public function testParseInputFilterAllUsersUsesStoredSessionParameters()
	{
		$this->baseFilterParameters->setUserInputs([]);
		$sessionStored = [
			BaseFilterParametersInterface::PARAMETER_ELEMENTS_PER_PAGE  =>
				['scalar_type'  => ScalarType::INT, 'default_value' => 10, 'parsed' => true, 'value' => 15],
			BaseFilterParametersInterface::PARAMETER_ELEMENTS_PAGE =>
				['scalar_type'  => ScalarType::INT, 'default_value' => 1, 'parsed' => true, 'value' => 1],
			BaseFilterParametersInterface::PARAMETER_SORT_COLUMN =>
				['scalar_type'  => ScalarType::STRING, 'default_value' => 'default_colum', 'parsed' => true, 'value' => 'name'],
			BaseFilterParametersInterface::PARAMETER_SORT_ORDER         =>
				['scalar_type'  => ScalarType::STRING, 'default_value' => 'ASC', 'parsed' => true, 'value' => 'DESC'],
		];

		$this->sessionMock->method('exists')->with('testKeyStore')->willReturn(true);
		$this->sessionMock->method('get')->with('testKeyStore')->willReturn($sessionStored);

		$this->baseFilterParameters->parseInputFilterAllUsers();

		$this->assertSame(15, $this->baseFilterParameters->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PER_PAGE));
		$this->assertSame('name', $this->baseFilterParameters->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_SORT_COLUMN));
		$this->assertSame('DESC', $this->baseFilterParameters->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_SORT_ORDER));
	}

	/**
	 * @throws ModuleException
	 */
	#[Group('units')]
	public function testParseInputFilterAllUsersParsesAllParameters()
	{
		$this->baseFilterParameters->setUserInputs([]);

		$this->sanitizerMock->expects($this->exactly(2))
			->method('int')
			->willReturnMap([['10', 10], ['1',1]]);

		$this->baseFilterParameters->parseInputFilterAllUsers();

		$this->assertSame(10, $this->baseFilterParameters->getValueOfParameter(BaseFilterParameters::PARAMETER_ELEMENTS_PER_PAGE));
		$this->assertSame(1, $this->baseFilterParameters->getValueOfParameter(BaseFilterParameters::PARAMETER_ELEMENTS_PAGE));
	}

	#[Group('units')]
	public function testParseInputFilterAllUsersStoresParametersInSession()
	{
		$baseFilterParameters1 = new ConcreteFilterBaseParameters($this->sanitizerMock, $this->sessionMock, '');

		$baseFilterParameters1->addDefaultParameters();
		$baseFilterParameters1->setUserInputs([]);

		$this->sanitizerMock->expects($this->exactly(2))
			->method('int')
			->willReturnMap([['10', 10], ['1',1]]);

		$baseFilterParameters1->parseInputFilterAllUsers();

		$this->assertSame(10, $baseFilterParameters1->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PER_PAGE));
		$this->assertSame(1, $baseFilterParameters1->getValueOfParameter(BaseFilterParametersInterface::PARAMETER_ELEMENTS_PAGE));
	}

}
