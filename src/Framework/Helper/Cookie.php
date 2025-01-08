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

namespace App\Framework\Helper;

use App\Framework\Exceptions\FrameworkException;

class Cookie
{
	private Crypt $crypt;

	public function __construct(Crypt $crypt)
	{
		$this->crypt = $crypt;
	}

	/**
	 * @throws  FrameworkException
	 */
	public function getCookie($cookie_name): bool|array|null
	{
		if (!array_key_exists($cookie_name, $_COOKIE))
			return null;

		$payload = $_COOKIE[$cookie_name];
		return $this->validateAndUnpackContent($payload);
	}


	/**
	 * @throws FrameworkException
	 */
	public function createCookie(string $name, array $contents, \DateTime $expire): void
	{
		$expire  = $expire->getTimestamp();
		$content = $this->hashContent($contents);
		$result  = setcookie($name, $content, $expire, '/', '', true, true);

		if ($result === false)
			throw new FrameworkException('Cookie failed to set.');
	}

	public function deleteCookie(string $name): void
	{
		// cheap way to delete a cookie without knowing its details
		setcookie($name, '', 1);
	}

	public function hasCookie(string $name): bool
	{
		return array_key_exists($name, $_COOKIE);
	}


	private function hashContent(array $payload): string
	{
		$content  = serialize($payload);
		$checksum = $this->crypt->createSha256Hash($content);
		return serialize([$content, $checksum]);
	}

	/**
	 * @throws  FrameworkException
	 */
	private function validateAndUnpackContent(string $raw_content):array | bool
	{
		[$content, $checksum] = unserialize($raw_content);

		if ($content === false || $checksum === false)
			throw new FrameworkException('Failed to unserialize content.');

		if (!hash_equals($checksum, $this->crypt->createSha256Hash($content)))
			throw new FrameworkException('Possible cookie manipulation detected. Checksum does of ' . $checksum . ' does not match');

		return unserialize($content);
	}
}