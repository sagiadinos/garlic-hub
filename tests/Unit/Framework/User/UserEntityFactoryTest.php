<?php

namespace Tests\Unit\Framework\User;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use App\Framework\User\UserEntityFactory;
use App\Framework\User\UserEntity;
use App\Framework\Core\Config\Config;

class UserEntityFactoryTest extends TestCase
{
	private Config $mockConfig;
	private UserEntityFactory $factory;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$this->mockConfig = $this->createMock(Config::class);
		$this->factory = new UserEntityFactory($this->mockConfig);
	}

	#[Group('units')]
	public function testCreateEnterpriseEdition(): void
	{
		$userData = [
			'main' => ['id' => 1, 'name' => 'Enterprise User'],
			'contact' => ['email' => 'enterprise@example.com'],
			'stats' => ['logins' => 5],
			'security' => ['role' => 'admin'],
			'acl' => ['permissions' => ['read', 'write']],
			'vip' => ['status' => 'gold'],
		];

		$this->mockConfig
			->method('getEdition')
			->willReturn(Config::PLATFORM_EDITION_ENTERPRISE);

		$result = $this->factory->create($userData);

		$this->assertInstanceOf(UserEntity::class, $result);
		$this->assertEquals($userData['main'], $result->getMain());
		$this->assertEquals($userData['contact'], $result->getContact());
		$this->assertEquals($userData['stats'], $result->getStats());
		$this->assertEquals($userData['security'], $result->getSecurity());
		$this->assertEquals($userData['acl'], $result->getAcl());
		$this->assertEquals($userData['vip'], $result->getVip());
	}

	#[Group('units')]
	public function testCreateCoreEdition(): void
	{
		$userData = [
			'main' => ['id' => 1, 'name' => 'Core User'],
			'contact' => ['email' => 'core@example.com'],
			'stats' => ['logins' => 3],
			'security' => ['role' => 'user'], // Ignoriert in Core
			'acl' => ['permissions' => ['read']],
			'vip' => ['status' => 'silver'], // Ignoriert in Core
		];

		$this->mockConfig
			->method('getEdition')
			->willReturn(Config::PLATFORM_EDITION_CORE);

		$result = $this->factory->create($userData);

		$this->assertInstanceOf(UserEntity::class, $result);
		$this->assertEquals($userData['main'], $result->getMain());
		$this->assertEquals($userData['contact'], $result->getContact());
		$this->assertEquals($userData['stats'], $result->getStats());
		$this->assertEquals([], $result->getSecurity());
		$this->assertEquals($userData['acl'], $result->getAcl());
		$this->assertEquals([], $result->getVip());
	}

	#[Group('units')]
	public function testCreateEdgeEdition(): void
	{
		$userData = [
			'main' => ['id' => 1, 'name' => 'Edge User'],
			'contact' => ['email' => 'edge@example.com'], // Ignored in Edge
			'stats' => ['logins' => 1], // Ignored in Edge
			'security' => ['role' => 'guest'], // Ignored in Edge
			'acl' => ['permissions' => ['read']], // Ignored in Edge
			'vip' => ['status' => 'none'], // Ignored in Edge
		];

		$this->mockConfig
			->method('getEdition')
			->willReturn(Config::PLATFORM_EDITION_EDGE);

		$result = $this->factory->create($userData);

		$this->assertInstanceOf(UserEntity::class, $result);
		$this->assertEquals($userData['main'], $result->getMain());
		$this->assertEmpty($result->getContact());
		$this->assertEmpty($result->getStats());
		$this->assertEmpty($result->getSecurity());
		$this->assertEmpty($result->getAcl());
		$this->assertEmpty($result->getVip());
	}
}
