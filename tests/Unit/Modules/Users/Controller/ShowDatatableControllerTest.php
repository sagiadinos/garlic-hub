<?php

namespace Tests\Unit\Modules\Users\Controller;

use App\Framework\Core\Session;
use App\Framework\Core\Translate\Translator;
use App\Framework\Exceptions\CoreException;
use App\Framework\Exceptions\FrameworkException;
use App\Framework\Utils\Datatable\DatatableFacadeInterface;
use App\Framework\Utils\Datatable\DatatableTemplatePreparer;
use App\Modules\Users\Controller\ShowDatatableController;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\SimpleCache\InvalidArgumentException;

class ShowDatatableControllerTest extends TestCase
{
	private ShowDatatableController $controller;
	private DatatableFacadeInterface&MockObject $facadeMock;
	private DatatableTemplatePreparer&MockObject $templatePreparerMock;
	private ServerRequestInterface&MockObject $requestMock;
	private ResponseInterface&MockObject $responseMock;
	private Translator&MockObject $translatorMock;
	private Session&MockObject $sessionMock;
	private StreamInterface&MockObject $streamInterfaceMock;


	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$this->facadeMock           = $this->createMock(DatatableFacadeInterface::class);
		$this->templatePreparerMock = $this->createMock(DatatableTemplatePreparer::class);
		$this->requestMock          = $this->createMock(ServerRequestInterface::class);
		$this->responseMock         = $this->createMock(ResponseInterface::class);
		$this->translatorMock       = $this->createMock(Translator::class);
		$this->sessionMock          = $this->createMock(Session::class);
		$this->streamInterfaceMock  = $this->createMock(StreamInterface::class);

		$this->controller = new ShowDatatableController($this->facadeMock, $this->templatePreparerMock);
	}

	/**
	 * @throws CoreException
	 * @throws FrameworkException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 */
	#[Group('units')]
	public function testShowMethodReturnsResponseWithSerializedTemplateData(): void
	{
		$templateData = ['key' => 'value'];

		$this->requestMock->expects($this->exactly(2))->method('getAttribute')
			->willReturnMap([
				['translator', null, $this->translatorMock],
				['session', null, $this->sessionMock],
			]);

		$this->facadeMock->expects($this->once())->method('configure')
			->with($this->translatorMock, $this->sessionMock);

		$this->facadeMock->expects($this->once())->method('processSubmittedUserInput');
		$this->facadeMock->expects($this->once())->method('prepareDataGrid');
		$this->facadeMock->expects($this->once())->method('prepareUITemplate')
			->willReturn(['dataGrid' => 'value']);

		$this->templatePreparerMock->expects($this->once())->method('preparerUITemplate')
			->with(['dataGrid' => 'value'])
			->willReturn($templateData);

		$this->responseMock->method('getBody')->willReturn($this->streamInterfaceMock);

		$this->streamInterfaceMock->expects($this->once())->method('write')
			->with(serialize($templateData));

		$this->responseMock
			->expects($this->once())
			->method('withHeader')
			->with('Content-Type', 'text/html')
			->willReturnSelf();

		$result = $this->controller->show($this->requestMock, $this->responseMock);

		$this->assertSame($this->responseMock, $result);
	}
}