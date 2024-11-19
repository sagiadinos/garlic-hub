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

namespace App\Framework\Core\Locales;

class UrlLocaleExtractor implements LocaleExtractorInterface
{
	private string $defaultLocale;

	public function __construct(string $defaultLocale = 'en_US')
	{
		$this->defaultLocale = $defaultLocale;
	}

	public function extractLocale(array $whiteList): string
	{
		$locale = $_GET['locale'] ?? $this->defaultLocale;

		// converts "en-us" in "en_US"
		$locale = str_replace('-', '_', strtolower($locale));

		return in_array($locale, $whiteList, true) ? $locale : $this->defaultLocale;
	}
}