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


use App\Framework\Core\Cookie;
use Exception;

class CsrfTokenField extends AbstractInputField
{
	const string CSRF_TOKEN_COOKIE_NAME = 'csrf_token';
	const string CSRF_TOKEN_COOKIE_EXPIRE = '+1 hours';
	private Cookie $cookie;

	/**
	 * @throws Exception
	 */
	public function __construct(array $attributes, Cookie $cookie)
	{
		parent::__construct($attributes);
		$this->cookie = $cookie;
		$this->setValue($this->generateToken());
	}

	/**
	 * @throws Exception
	 */
	private function generateToken(): string
	{
		$token = bin2hex(random_bytes(32));
		$this->cookie->createCookie(self::CSRF_TOKEN_COOKIE_NAME, $token, new \DateTime(self::CSRF_TOKEN_COOKIE_EXPIRE));
		return $token;
	}

}