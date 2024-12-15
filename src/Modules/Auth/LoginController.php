<?php

namespace App\Modules\Auth;

use App\Framework\Exceptions\UserException;
use Doctrine\DBAL\Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use SlimSession\Helper;

class LoginController
{
	private AuthService $authService;
	private LoggerInterface $logger;

	/**
	 * @param AuthService $authService
	 * @param LoggerInterface $logger
	 */
	public function __construct(AuthService $authService, LoggerInterface $logger)
	{
		$this->authService = $authService;
		$this->logger      = $logger;
	}

	/**
	 * @throws \Exception
	 */
	public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$session  = $request->getAttribute('session');
		if ($session->exists('user'))
			return $this->redirect($response);

		return $this->renderForm($request, $response);
	}

	/**
	 * @throws UserException
	 */
	public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		/** @var Helper $session */
		$session  = $request->getAttribute('session');
		$flash    = $request->getAttribute('flash');
		try
		{
			$params   = (array) $request->getParsedBody();
			// no need to sanitize here, as we are executing prepared statements in DB
			$username = $params['username'] ?? null;
			$password = $params['password'] ?? null;

			$csrfToken = $params['csrf_token'] ?? null;
			if(!$session->exists('csrf_token') || $session->get('csrf_token') !== $csrfToken)
				throw new UserException('CSRF Token mismatch');

			$userEntity = $this->authService->login($username, $password);
			$main_data = $userEntity->getMain();
			$session->set('user', $main_data);
			$session->set('locale', $main_data['locale']);
		}
		catch (\Exception | InvalidArgumentException | Exception $e)
		{
			// dbal exception not tested because overengineered bullshit make mocking a pain in ass
			$flash->addMessage('error', $e->getMessage());
			$this->logger->error($e->getMessage());
			return $this->redirect($response, '/login');
		}

		if (!$session->exists('oauth_redirect_params'))
			return $this->redirect($response);

		$oauthParams = $session->get('oauth_redirect_params', []);
		$session->delete('oauth_redirect_params');

		return $this->redirect($response, '/api/authorize?' . http_build_query($oauthParams));
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 */
	public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$session = $request->getAttribute('session');
		$user    = $session->get('user');
		$this->authService->logout($user);
		$session->delete('user');
		return $this->redirect($response, '/login');
	}

	/**
	 * @throws \Exception
	 */
	private function renderForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$flash    = $request->getAttribute('flash');
		$messages = $flash->getMessages(); // Flash-Nachrichten abholen
		$error	  = [];
		if (array_key_exists('error', $messages))
			$error = $messages['error'];

		$csrfToken = bin2hex(random_bytes(32));
		$session = $request->getAttribute('session');
		$session->set('csrf_token', $csrfToken);

		$data = [
			'main_layout' => [
				'LANG_PAGE_TITLE' => 'Garlic Hub - Login',
				'error_messages' => $error,
				'ADDITIONAL_CSS' => ['/css/user/login.css']
			],
			'this_layout' => [
				'template' => 'auth/login', // Template-name
				'data' => [
					'LANG_PAGE_HEADER' => 'Login',
					'LANG_USERNAME' => 'Username / Email',
					'LANG_PASSWORD' => 'Password',
					'CSRF_TOKEN' => $csrfToken,
					'LANG_SUBMIT' => 'Login'

				]
			]
		];
		$response->getBody()->write(serialize($data));

		return $response->withHeader('Content-Type', 'text/html');
	}

	private function redirect(ResponseInterface $response, string $route = '/'): ResponseInterface
	{
		return $response->withHeader('Location', $route)->withStatus(302);
	}

}
