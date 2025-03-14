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

namespace App\Framework\Utils\Html;

use App\Framework\Core\Session;
use Exception;

class FieldsFactory
{
	public function createTextField(array $attributes): TextField
	{
		return new TextField($attributes);
	}

	public function createNumberField(array $attributes): NumberField
	{
		return new NumberField($attributes);
	}

	public function createAutocompleteField(array $attributes): AutocompleteField
	{
		return new AutocompleteField($attributes);
	}

	public function createDropdownField(array $attributes): DropdownField
	{
		return new DropdownField($attributes);
	}


	public function createEmailField(array $attributes): EmailField
	{
		return new EmailField($attributes);
	}

	public function createPasswordField(array $attributes): PasswordField
	{
		return new PasswordField($attributes);
	}

	/**
	 * @throws Exception
	 */
	public function createCsrfTokenField(array $attributes, $session): CsrfTokenField
	{
		return new CsrfTokenField($attributes, $session);
	}

	public function createHiddenField(array $options): HiddenField
	{
		return new HiddenField($options);
	}

}