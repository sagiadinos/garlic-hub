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

namespace App\Framework\User;

use App\Framework\Exceptions\UserException;
use App\Framework\User\Edge\UserMainRepository;
use Doctrine\DBAL\Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Cache\InvalidArgumentException;

class UserService
{
	private UserEntityFactory $userEntityFactory;
	private UserRepositoryFactory $userRepositoryFactory;
	private array $userRepositories;
	private Psr16Adapter $cache;

	public function __construct(UserRepositoryFactory $userRepositoryFactory, UserEntityFactory $userEntityFactory, Psr16Adapter
	$cache)
	{
		$this->userRepositoryFactory = $userRepositoryFactory;
		$this->userEntityFactory     = $userEntityFactory;
		$this->cache                 = $cache;
		$this->userRepositories      = $this->userRepositoryFactory->create();
	}

	public function getUserRepositories(): array
	{
		return $this->userRepositories;
	}

	/**
	 * @throws UserException
	 * @throws Exception
	 */
	public function findUser($identifier)
	{
		/** @var UserMainRepository $usrMainRepository */
		$usrMainRepository = $this->getUserRepositories()['main'];

		return $usrMainRepository->findByIdentifier($identifier);
	}

	/**
	 * Get the current user from cache or database.
	 *
	 * @param int $UID
	 * @return UserEntity
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function getCurrentUser(int $UID): UserEntity
	{
		$cacheKey   = $this->getCacheKey($UID);
		$cachedData = $this->cache->get($cacheKey);

		if ($cachedData)
			return $this->userEntityFactory->create($cachedData);

		$userData = $this->collectUserData($UID);

		// Cache the user data
		$this->cache->set($cacheKey, $userData, 3600 * 24); // Cache for 1 day

		return $this->userEntityFactory->create($userData);
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws \InvalidArgumentException
	 * @throws InvalidArgumentException
	 */
	public function invalidateCache(int $UID): void
	{
		$cacheKey = $this->getCacheKey($UID);
		$this->cache->delete($cacheKey);
	}

	/**
	 * @throws Exception
	 */
	private function collectUserData(int $UID): array
	{
		$userData = [];
		foreach ($this->userRepositories as $key => $repository)
		{
			$userData[$key] = $repository->findById($UID);
		}

		return $userData;
	}

	private function getCacheKey(int $UID): string
	{
		return "user_$UID";
	}
}