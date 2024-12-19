<?php

namespace Tests\Unit\Framework\User;

use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use App\Framework\User\UserService;
use App\Framework\User\UserEntityFactory;
use App\Framework\User\UserRepositoryFactory;
use App\Framework\User\UserEntity;
use App\Framework\User\Edge\UserMainRepository;
use Phpfastcache\Helper\Psr16Adapter;
use App\Framework\Exceptions\UserException;

class UserServiceTest extends TestCase
{
	private UserService $userService;
	private UserRepositoryFactory $repositoryFactoryMock;
	private UserEntityFactory $entityFactoryMock;
	private Psr16Adapter $cacheMock;
	private UserMainRepository $userMainRepositoryMMockk;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$this->repositoryFactoryMock = $this->createMock(UserRepositoryFactory::class);
		$this->entityFactoryMock     = $this->createMock(UserEntityFactory::class);
		$this->cacheMock             = $this->createMock(Psr16Adapter::class);
		$this->userMainRepositoryMMockk = $this->createMock(UserMainRepository::class);
		$this->repositoryFactoryMock->method('create')
			->willReturn(['main' => $this->userMainRepositoryMMockk]);

		$this->userService = new UserService(
			$this->repositoryFactoryMock,
			$this->entityFactoryMock,
			$this->cacheMock
		);
	}

	#[Group('units')]
	public function testFindUserSuccess(): void
	{
		$identifier = 'test@example.com';
		$mockUserData = ['UID' => 1, 'username' => 'testuser'];

		// Repository simuliert Rückgabe von User-Daten
		$this->userMainRepositoryMMockk
			->method('findByIdentifier')
			->with($identifier)
			->willReturn($mockUserData);

		$result = $this->userService->findUser($identifier);

		$this->assertEquals($mockUserData, $result);
	}

	#[Group('units')]
	public function testFindUserNotFound(): void
	{
		$identifier = 'unknown@example.com';

		// Repository simuliert, dass kein Benutzer gefunden wurde
		$this->userMainRepositoryMMockk->method('findByIdentifier')
			->with($identifier)
			->willReturn([]);

		$this->assertEmpty($this->userService->findUser($identifier));
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testGetCurrentUserFromCache(): void
	{
		$UID = 1;
		$cachedData = ['UID' => 1, 'username' => 'testuser'];

		$this->cacheMock->method('get')->with("user_$UID")
			->willReturn($cachedData);

		$this->userMainRepositoryMMockk->expects($this->never())->method('findById');

		$mockUserEntity = $this->createMock(UserEntity::class);
		$this->entityFactoryMock->method('create')
			->with($cachedData)
			->willReturn($mockUserEntity);

		$result = $this->userService->getCurrentUser($UID);
		$this->assertEquals($mockUserEntity, $result);
	}

	#[Group('units')]
	public function testGetCurrentUserFromDatabase(): void
	{
		$UID = 1;
		$userData = ['UID' => 1, 'username' => 'testuser'];

		$this->cacheMock->method('get')->with("user_$UID")
			->willReturn(null);

		$this->userMainRepositoryMMockk->expects($this->once())->method('findById')->with($UID)
			->willReturn($userData);

		$mockUserEntity = $this->createMock(UserEntity::class);
		$this->entityFactoryMock->method('create')
			->with(['main' => $userData])
			->willReturn($mockUserEntity);

		$result = $this->userService->getCurrentUser($UID);

		$this->assertEquals($mockUserEntity, $result);
	}

	#[Group('units')]
	public function testInvalidCache(): void
	{
		$UID = 14;

		$this->cacheMock->expects($this->once())->method('delete')
			->with('user_'.$UID)
		;
		$this->userService->invalidateCache($UID);

	}


}
