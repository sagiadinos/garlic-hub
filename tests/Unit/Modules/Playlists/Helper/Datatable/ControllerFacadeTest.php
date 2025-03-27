<?php

namespace Tests\Unit\Modules\Playlists\Helper\Datatable;

use App\Framework\Core\Session;
use App\Framework\Core\Translate\Translator;
use App\Framework\Exceptions\CoreException;
use App\Framework\Exceptions\FrameworkException;
use App\Framework\Exceptions\ModuleException;
use App\Modules\Playlists\Helper\Datatable\ControllerFacade;
use App\Modules\Playlists\Helper\Datatable\DatatableBuilder;
use App\Modules\Playlists\Helper\Datatable\DatatablePreparer;
use App\Modules\Playlists\Services\PlaylistsDatatableService;
use App\Modules\Playlists\Services\PlaylistsService;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class ControllerFacadeTest extends TestCase
{
	private readonly ControllerFacade $controllerFacade;
	private readonly DatatableBuilder $datatableBuilderMock;
	private readonly DatatablePreparer $datatablePreparerMock;
	private readonly PlaylistsDatatableService $playlistsServiceMock;
	private readonly Translator $translatorMock;
	private readonly Session $sessionMock;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$this->datatableBuilderMock = $this->createMock(DatatableBuilder::class);
		$this->datatablePreparerMock = $this->createMock(DatatablePreparer::class);
		$this->playlistsServiceMock = $this->createMock(PlaylistsDatatableService::class);
		$this->translatorMock = $this->createMock(Translator::class);
		$this->sessionMock = $this->createMock(Session::class);

		$this->controllerFacade = new ControllerFacade(
			$this->datatableBuilderMock,
			$this->datatablePreparerMock,
			$this->playlistsServiceMock
		);

		$this->playlistsServiceMock->method('getCurrentTotalResult')->willReturn(42);
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws CoreException
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testConfigure(): void
	{
		$mockUID = 12345;
		$mockUserData = ['UID' => $mockUID];

		$this->sessionMock->expects($this->once())->method('get')
			->with('user')
			->willReturn($mockUserData);

		$this->playlistsServiceMock->expects($this->once())->method('setUID')
			->with($mockUID);

		$this->datatableBuilderMock->expects($this->once())->method('configureParameters')
			->with($mockUID);

		$this->datatableBuilderMock->expects($this->once())->method('setTranslator')
			->with($this->translatorMock);

		$this->datatablePreparerMock->expects($this->once())->method('setTranslator')
			->with($this->translatorMock);

		$this->controllerFacade->configure($this->translatorMock, $this->sessionMock);
	}

	/**
	 * @throws ModuleException
	 */
	#[Group('units')]
	public function testProcessSubmittedUserInput(): void
	{
		$this->datatableBuilderMock->expects($this->once())->method('determineParameters');

		$this->playlistsServiceMock->expects($this->once())->method('loadDatatable');

		$this->controllerFacade->processSubmittedUserInput();
	}

	/**
	 * @throws ModuleException
	 * @throws CoreException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 * @throws FrameworkException
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testPrepareDataGrid(): void
	{
		// Arrange
		$this->datatableBuilderMock->expects($this->once())->method('buildTitle');
		$this->datatableBuilderMock->expects($this->once())->method('collectFormElements');
		$this->datatableBuilderMock->expects($this->once())
			->method('createPagination')
			->with(42);
		$this->datatableBuilderMock->expects($this->once())->method('createDropDown');
		$this->datatableBuilderMock->expects($this->once())->method('createTableFields');

		// Act
		$result = $this->controllerFacade->prepareDataGrid();

		// Assert
		$this->assertSame($this->controllerFacade, $result);
	}

	/**
	 * @throws ModuleException
	 * @throws CoreException
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 * @throws FrameworkException
	 * @throws \Doctrine\DBAL\Exception
	 */
	#[Group('units')]
	public function testPrepareUITemplate(): void
	{
		$mockUID = 12345;
		$mockUserData = ['UID' => $mockUID];

		$this->sessionMock->expects($this->once())->method('get')
			->with('user')
			->willReturn($mockUserData);
		$this->controllerFacade->configure($this->translatorMock, $this->sessionMock);

		$mockDatatableStructure = [
			'pager' => ['page_1', 'page_2'],
			'dropdown' => ['option_1', 'option_2'],
			'form' => ['field_1' => 'value_1'],
			'header' => ['header_1', 'header_2'],
			'title' => 'Mock Title'
		];

		$mockPagination = [
			'dropdown' => 'mock_dropdown',
			'links' => 'mock_links',
		];

		$mockFormattedList = ['row_1', 'row_2'];
		$currentTotalResult = 42;

		$this->datatableBuilderMock->expects($this->once())
			->method('getDatatableStructure')
			->willReturn($mockDatatableStructure);

		$this->datatablePreparerMock->expects($this->once())
			->method('preparePagination')
			->with($mockDatatableStructure['pager'], $mockDatatableStructure['dropdown'])
			->willReturn($mockPagination);

		$this->datatablePreparerMock->expects($this->once())
			->method('prepareFilterForm')
			->with($mockDatatableStructure['form'])
			->willReturn(['prepared_filter_form']);

		$this->datatablePreparerMock->expects($this->once())
			->method('prepareAdd')
			->with('folder-plus')
			->willReturn(['prepared_add']);

		$this->datatablePreparerMock->expects($this->once())
			->method('prepareTableHeader')
			->with($mockDatatableStructure['header'], ['playlists', 'main'])
			->willReturn(['prepared_header']);

		$this->datatablePreparerMock->expects($this->once())
			->method('prepareSort')
			->willReturn(['prepared_sort']);

		$this->datatablePreparerMock->expects($this->once())
			->method('preparePage')
			->willReturn(['prepared_page']);

		$this->playlistsServiceMock->expects($this->once())
			->method('getCurrentTotalResult')
			->willReturn($currentTotalResult);

		$currentFilterResults = [
			['playlist_id' => 1, 'playlist_name' => 'playlist1', 'description' => 'description1'],
			['playlist_id' => 2, 'playlist_name' => 'playlist2', 'description' => 'description2'],
			['playlist_id' => 3, 'playlist_name' => 'playlist3', 'description' => 'description3'],
			['playlist_id' => 4, 'playlist_name' => 'playlist4', 'description' => 'description4'],
		];
		$this->playlistsServiceMock->expects($this->exactly(2))
			->method('getCurrentFilterResults')
			->willReturn($currentFilterResults);

		$showerIds = array_column($currentFilterResults, 'playlist_id');
		$this->playlistsServiceMock->expects($this->once())
			->method('getPlaylistsInUse')->with($showerIds)
			->willReturn([]);

		$this->datatablePreparerMock->expects($this->once())->method('setUsedPlaylists')->with([]);

		$this->datatablePreparerMock->expects($this->once())
			->method('prepareTableBody')
			->with($currentFilterResults, $mockDatatableStructure['header'], $this->anything())
			->willReturn($mockFormattedList);

		$result = $this->controllerFacade->prepareUITemplate();

		$this->assertEquals([
			'filter_elements' => ['prepared_filter_form'],
			'pagination_dropdown' => 'mock_dropdown',
			'pagination_links' => 'mock_links',
			'has_add' => ['prepared_add'],
			'results_header' => ['prepared_header'],
			'results_list' => $mockFormattedList,
			'results_count' => $currentTotalResult,
			'title' => 'Mock Title',
			'template_name' => 'playlists/datatable',
			'module_name' => 'playlists',
			'additional_css' => ['/css/playlists/overview.css'],
			'footer_modules' => ['/js/playlists/overview/init.js'],
			'sort' => ['prepared_sort'],
			'page' => ['prepared_page']
		], $result);
	}

	/**
	 * @throws PhpfastcacheSimpleCacheException
	 * @throws InvalidArgumentException
	 * @throws CoreException
	 */
	#[Group('units')]
	public function testPrepareContextMenu(): void
	{
		$mockContextMenu = [
			['name' => 'Option1', 'action' => 'action1'],
			['name' => 'Option2', 'action' => 'action2']
		];

		$this->datatablePreparerMock->expects($this->once())
			->method('formatPlaylistContextMenu')
			->willReturn($mockContextMenu);

		$result = $this->controllerFacade->prepareContextMenu();

		$this->assertSame($mockContextMenu, $result);
	}
}
