<?php

namespace App\Modules\Auth\Controller;

use App\Framework\Exceptions\UserException;
use App\Modules\Auth\Repositories\UserMain;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use SlimSession\Helper;

class LoginController
{
	private UserMain $userMain;
	private LoggerInterface $logger;

	/**
	 * @param UserMain $userMain
	 */
	public function __construct(UserMain $userMain, LoggerInterface $logger)
	{
		$this->userMain = $userMain;
		$this->logger   = $logger;
	}

	public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		/** @var Helper $session */
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
		try
		{
			$params   = (array) $request->getParsedBody();
			// no need to sanitize here, as we are escuse prepared statements in DB
			$username = $params['username'] ?? null;
			$password = $params['password'] ?? null;
			$session  = $request->getAttribute('session');
			$flash    = $request->getAttribute('flash');

			$user = $this->userMain->loadUserByIdentifier($username);

			if (!password_verify($password, $user->getPassword()))
				throw new UserException('Invalid credentials.');

			/** @var Helper $session */
			$session->set('user', [
				'UID' => $user->getUID(),
				'username' => $user->getUsername(),
				'locale' => $user->getLocale(),
				'company_id' => $user->getCompanyId(),
				'status' => $user->getStatus(),
			]);
		}
		catch (UserException $e)
		{
			$flash->addMessage('error', $e->getMessage());
			$this->logger->error($e->getMessage());
			return $this->redirect($response, '/login');
		}
		catch (Exception $e)
		{
			// Not tested because of overengineered dbal bullshit
			$this->logger->error($e->getMessage());
		}

		return $this->redirect($response);
	}

	public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$session = $request->getAttribute('session');
		$session->delete('user');
		return $this->redirect($response, '/login');
	}

	private function renderForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$flash    = $request->getAttribute('flash');
		$messages = $flash->getMessages(); // Flash-Nachrichten abholen
		$error	  = [];
		if (array_key_exists('error', $messages))
			$error = $messages['error'];

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
					'LANG_SUBMIT' => 'Login',
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
