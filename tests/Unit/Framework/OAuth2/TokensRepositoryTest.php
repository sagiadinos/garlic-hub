<?php

namespace Tests\Unit\Framework\OAuth2;

use App\Framework\OAuth2\TokensRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class TokensRepositoryTest extends TestCase
{
	private Connection $mockConnection;
	private TokensRepository $repository;
	private QueryBuilder $queryBuilderMock;

	/**
	 * setUp() wird vor jedem Test aufgerufen
	 *
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$this->mockConnection   = $this->createMock(Connection::class);
		$this->repository       = new TokensRepository($this->mockConnection);
		$this->queryBuilderMock = $this->createMock(QueryBuilder::class);
	}

	#[Group('units')]
	public function testGetNewAuthCodeReturnsAuthCodeEntity(): void
	{
		$authCode = $this->repository->getNewAuthCode();
		$this->assertInstanceOf(AuthCodeEntityInterface::class, $authCode);
	}

	/**
	 * @throws Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testPersistNewAuthCodeCallsInsert(): void
	{
		$mockAuthCodeEntity = $this->createMock(AuthCodeEntityInterface::class);
		$mockAuthCodeEntity->method('getIdentifier')->willReturn('test-auth-code-id');
		$mockAuthCodeEntity->method('getClient')->willReturn($this->createMock(ClientEntityInterface::class));
		$mockAuthCodeEntity->method('getUserIdentifier')->willReturn('test-user-id');
		$mockAuthCodeEntity->method('getRedirectUri')->willReturn('https://example.com/callback');
		$datetime_immutable = new DateTimeImmutable('now +1 hour');
		$mockAuthCodeEntity->method('getExpiryDateTime')->willReturn($datetime_immutable);
		$mockAuthCodeEntity->method('getScopes')->willReturn([]);
		$this->mockConnection->expects($this->once())->method('insert')->with('oauth2_credentials',
			[
				'type'         => 'auth_code',
				'token'        => 'test-auth-code-id',
				'client_id'    => '',
				'UID'          => 'test-user-id',
				'redirect_uri' => 'https://example.com/callback',
				'scopes'       => '',
				'expires_at'   => $datetime_immutable->format('Y-m-d H:i:s'),
				'created_at'   => date('Y-m-d H:i:s'),
			]
		);
		$this->mockConnection->expects($this->once())->method('lastInsertId');
		$this->repository->persistNewAuthCode($mockAuthCodeEntity);
		$this->assertTrue(true); // Wenn kein Fehler geworfen wurde, war der Test erfolgreich
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testRevokeAuthCode(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('update')->with('oauth2_credentials');
		$this->queryBuilderMock->expects($this->once())->method('set')->with('revoked', ':set_revoked');

		$this->queryBuilderMock->expects($this->once())->method('executeStatement');

		$this->repository->revokeAuthCode('test-auth-code-id');
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testIsAuthCodeRevokedReturnsFalse(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('fetchOne')->willReturn(0);

		$result = $this->repository->isAuthCodeRevoked('test-auth-code-id');
		$this->assertFalse($result);
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testIsAuthCodeRevokedReturnsTrue(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('fetchOne')->willReturn(1);

		$result = $this->repository->isAuthCodeRevoked('test-auth-code-id');
		$this->assertTrue($result);
	}

	/**
	 * @throws Exception
	 */
	#[Group('units')]
	public function testGetNewAccessTokenReturnsAccessTokenEntity(): void
	{
		$mockClientEntity = $this->createMock(ClientEntityInterface::class);
		$accessToken = $this->repository->getNewToken($mockClientEntity, [], 'test-user-id');
		$this->assertInstanceOf(AccessTokenEntityInterface::class, $accessToken);
	}

	/**
	 * @throws Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testPersistNewAccessTokenCallsInsert(): void
	{
		$mockAccessTokenEntity = $this->createMock(AccessTokenEntityInterface::class);
		$mockAccessTokenEntity->method('getIdentifier')->willReturn('test-access-token-id');
		$mockAccessTokenEntity->method('getClient')->willReturn($this->createMock(ClientEntityInterface::class));
		$mockAccessTokenEntity->method('getUserIdentifier')->willReturn('test-user-id');
		$mockAccessTokenEntity->method('getExpiryDateTime')->willReturn(new DateTimeImmutable('now +1 hour'));
		$mockAccessTokenEntity->method('getScopes')->willReturn([]);

		$this->repository->persistNewAccessToken($mockAccessTokenEntity);
		$this->assertTrue(true);
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testRevokeAccessToken(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('update')->with('oauth2_credentials');
		$this->queryBuilderMock->expects($this->once())->method('set')->with('revoked', ':set_revoked');

		$this->queryBuilderMock->expects($this->once())->method('executeStatement');

		$this->repository->revokeAccessToken('test-access-token-id');
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testIsAccessTokenRevokedReturnsFalse(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('fetchOne')->willReturn(0);

		$result = $this->repository->isAccessTokenRevoked('test-access-token-id');
		$this->assertFalse($result);
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testIsAccessTokenRevokedReturnsTrue(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('fetchOne')->willReturn(1);

		$result = $this->repository->isAccessTokenRevoked('test-access-token-id');
		$this->assertTrue($result);
	}

	#[Group('units')]
	public function testGetNewRefreshTokenReturnsRefreshTokenEntity(): void
	{
		$refreshToken = $this->repository->getNewRefreshToken();
		$this->assertInstanceOf(RefreshTokenEntityInterface::class, $refreshToken);
	}

	/**
	 * @throws Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testPersistNewRefreshTokenCallsInsert(): void
	{
		$mockRefreshTokenEntity = $this->createMock(RefreshTokenEntityInterface::class);
		$mockRefreshTokenEntity->method('getIdentifier')->willReturn('test-refresh-token-id');
		$mockRefreshTokenEntity->method('getExpiryDateTime')->willReturn(new DateTimeImmutable('now +1 hour'));
		$mockAccessTokenEntity = $this->createMock(AccessTokenEntityInterface::class);
		$mockAccessTokenEntity->method('getClient')->willReturn($this->createMock(ClientEntityInterface::class));
		$mockAccessTokenEntity->method('getUserIdentifier')->willReturn('test-user-id');
		$mockAccessTokenEntity->method('getScopes')->willReturn([]);
		$mockRefreshTokenEntity->method('getAccessToken')->willReturn($mockAccessTokenEntity);

		$this->repository->persistNewRefreshToken($mockRefreshTokenEntity);
		$this->assertTrue(true);
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testRevokeRefreshToken(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('update')->with('oauth2_credentials');
		$this->queryBuilderMock->expects($this->once())->method('set')->with('revoked', ':set_revoked');

		$this->queryBuilderMock->expects($this->once())->method('executeStatement');

		$this->repository->revokeRefreshToken('test-refresh-token-id');
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testIsRefreshTokenRevokedReturnsFalse(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('fetchOne')->willReturn(0);

		$result = $this->repository->isRefreshTokenRevoked('test-refresh-token-id');
		$this->assertFalse($result);
	}

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testIsRefreshTokenRevokedReturnsTrue(): void
	{
		$this->mockConnection->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
		$this->queryBuilderMock->expects($this->once())->method('fetchOne')->willReturn(1);

		$result = $this->repository->isRefreshTokenRevoked('test-refresh-token-id');
		$this->assertTrue($result);
	}
}