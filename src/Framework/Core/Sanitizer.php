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

namespace App\Framework\Core;

class Sanitizer
{
	private string $allowedTags;

	public function __construct(string $allowedTags = null)
	{
		$this->allowedTags = $allowedTags;
	}

	public function string(?string $value, string $default = ''): string
	{
		return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
	}

	public function html(?string $value, string $default = ''): string
	{
		return strip_tags($value ?? $default, $this->allowedTags);
	}

	public function int(?string $value, int $default = 0): int
	{
		return (int)($value ?? $default);
	}

	public function float(?string $value, float $default = 0.0): float
	{
		return (float)($value ?? $default); // Simple cast for sanitization
	}

	public function bool(?string $value, bool $default = false): bool
	{
		return (bool)($value ?? $default);
	}


	public function stringArray(?array $values, array $default = []): array
	{
		if (!is_array($values))
			return $default;

		return array_map(function ($s) {
			return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
		}, $values);
	}

	public function intArray(?array $values, array $default = []): array
	{
		if (!is_array($values))
			return $default;

		return array_map(function ($i) {
			return (int)$i;
		}, $values);
	}

	public function floatArray(?array $values, array $default = []): array
	{
		if (!is_array($values))
			return $default;

		return array_map(function ($f) {
			return (float)$f;
		}, $values);
	}

	public function jsonArray(?string $jsonString, array $default = []): array
	{
		if ($jsonString === null)
			return $default;

		$data = json_decode($jsonString, true);

		if (json_last_error() !== JSON_ERROR_NONE || !is_array($data))
			return $default;

		return $data;
	}
}