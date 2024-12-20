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


namespace App\Modules\User;

use App\Framework\Core\Locales\Locales;
use App\Framework\User\UserService;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper;

class EditLocalesController
{
	private UserService $userService;

	public function __construct(UserService $userService)
	{
		$this->userService = $userService;
	}

	/**
	 * @throws Exception
	 */
	public function setLocales(ServerRequestInterface $request, ResponseInterface $response, array $args):
	ResponseInterface
	{
		$locale  = htmlentities($args['locale'], ENT_QUOTES);

		// set locale into session
		/** @var  Helper $session */
		$session = $request->getAttribute('session');
		$session->set('locale', $locale);

		if ($session->exists('user'))
		{
			$user = $session->get('user');
			$user['locale'] = $locale;
			$session->set('user', $user);
			$this->userService->updateUser($user['UID'], ['locale' => $locale]);
		}

		// determine current locale secure because it checks a whitelist
		// of available locales
		/** @var  Locales $locales */
		$locales    = $request->getAttribute('locales');
		$locales->determineCurrentLocale();
		$previousUrl = $request->getHeaderLine('Referer') ?: '/';

		return $response
			->withHeader('Location', $previousUrl)
			->withStatus(302); // 302: forwarding

	}

}