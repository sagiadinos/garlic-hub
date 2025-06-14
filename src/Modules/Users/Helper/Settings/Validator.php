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

namespace App\Modules\Users\Helper\Settings;

use App\Framework\Core\BaseValidator;
use App\Framework\Core\Translate\Translator;
use App\Framework\Exceptions\CoreException;
use App\Framework\Exceptions\FrameworkException;
use App\Framework\Exceptions\ModuleException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\SimpleCache\InvalidArgumentException;

class Validator extends BaseValidator
{
	private Translator $translator;
	private Parameters $inputEditParameters;

	public function __construct(Translator $translator, Parameters $inputEditParameters)
	{
		$this->translator = $translator;
		$this->inputEditParameters = $inputEditParameters;
	}

	/**
	 * @return string[]
	 * @throws ModuleException
	 * @throws CoreException
	 * @throws FrameworkException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 */
	public function validateUserInput(): array
	{
		$this->inputEditParameters->checkCsrfToken();

		$errors = [];
		if (empty($this->inputEditParameters->getValueOfParameter(Parameters::PARAMETER_USER_NAME)))
			$errors[] = $this->translator->translate('no_username', 'users');

		if (empty($this->inputEditParameters->getValueOfParameter(Parameters::PARAMETER_USER_EMAIL)) ||
			!$this->isEmail($this->inputEditParameters->getValueOfParameter(Parameters::PARAMETER_USER_EMAIL))
		)
			$errors[] = $this->translator->translate('no_email', 'users');

		return $errors;
	}


}