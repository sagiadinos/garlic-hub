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

use App\Controller\HomeController;
use App\Framework\Middleware\FinalRenderMiddleware;
use App\Modules\Auth\LoginController;
use App\Modules\Auth\OAuth2Controller;
use App\Modules\Mediapool\Controller\MediaController;
use App\Modules\Mediapool\Controller\NodesController;
use App\Modules\Mediapool\Controller\ShowController;
use App\Modules\Mediapool\Controller\UploadController;
use App\Modules\Player\Controller\PlayerController;
use App\Modules\Player\Controller\PlayerIndexController;
use App\Modules\Playlists\Controller\ExportController;
use App\Modules\Playlists\Controller\ItemsController;
use App\Modules\Playlists\Controller\PlaylistsController;
use App\Modules\Playlists\Controller\ShowComposeController;
use App\Modules\Playlists\Controller\ShowDatatableController;
use App\Modules\Playlists\Controller\ShowSettingsController;
use App\Modules\Playlists\Controller\WidgetsController;
use App\Modules\Users\Controller\EditLocalesController;
use App\Modules\Users\Controller\EditPasswordController;
use App\Modules\Users\Controller\UsersController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/* @var App $app */
/** @phpstan-ignore-next-line */
assert($app instanceof App);
$container = $app->getContainer();

$app->get('/smil-index', [PlayerIndexController::class, 'index']);

$app->group('', function (RouteCollectorProxy $group)
{
	$group->get('/', [HomeController::class, 'index']);
	$group->get('/legals', [HomeController::class, 'legals']);
	$group->get('/login', [LoginController::class, 'showLogin']);
	$group->post('/login', [LoginController::class, 'login']);
	$group->get('/logout', [LoginController::class, 'logout']);
	$group->get('/set-locales/{locale}', [EditLocalesController::class, 'setLocales']);

	$group->get('/users', [\App\Modules\Users\Controller\ShowDatatableController::class, 'show']);
	$group->get('/users/edit', [EditPasswordController::class, 'showForm']);
	$group->post('/users/edit/password', [EditPasswordController::class, 'editPassword']);

	$group->get('/mediapool', [ShowController::class, 'show']);

	$group->get('/playlists', [ShowDatatableController::class, 'show']);
	$group->get('/playlists/settings/{playlist_mode:master|internal|external|multizone|channel}', [ShowSettingsController::class, 'newPlaylistForm']);
	$group->get('/playlists/settings/{playlist_id:\d+}', [ShowSettingsController::class, 'editPlaylistForm']);
	$group->delete('/playlists/settings/{playlist_id:\d+}', [ShowSettingsController::class, 'delete']);
	$group->post('/playlists/settings', [ShowSettingsController::class, 'store']);
	$group->get('/playlists/compose/{playlist_id}', [ShowComposeController::class, 'show']);

	$group->get('/player', [\App\Modules\Player\Controller\ShowDatatableController::class, 'show']);
})->add($container->get(FinalRenderMiddleware::class));

$app->group('/api', function (RouteCollectorProxy $group)
{
	$group->get('/authorize', [OAuth2Controller::class, 'authorize']);
	$group->post('/token', [OAuth2Controller::class, 'token']);
})->add(function ($request, $handler) {return $handler->handle($request)->withHeader('Content-Type', 'text/html');});

$app->group('/async', function (RouteCollectorProxy $group)
{
	$group->get('/users/find/{username}', [UsersController::class, 'findByName']);

	$group->get('/mediapool/node[/{parent_id:\d+}]', [NodesController::class, 'list']); // parent_id is optional with []
	$group->post('/mediapool/node', [NodesController::class, 'add']);
	$group->delete('/mediapool/node', [NodesController::class, 'delete']);
	$group->patch('/mediapool/node', [NodesController::class, 'edit']);
	$group->post('/mediapool/node/move', [NodesController::class, 'move']);
	$group->post('/mediapool/uploadLocalFile', [UploadController::class, 'uploadLocalFile']);
	$group->post('/mediapool/uploadFromUrl', [UploadController::class, 'uploadFromUrl']);
	$group->post('/mediapool/searchStockImages', [UploadController::class, 'searchStockImages']);
	$group->get('/mediapool/media/list/{node_id:\d+}', [MediaController::class, 'list']);
	$group->get('/mediapool/media/{media_id}', [MediaController::class, 'getInfo']);
	$group->post('/mediapool/media', [MediaController::class, 'add']);
	$group->delete('/mediapool/media', [MediaController::class, 'delete']);
	$group->post('/mediapool/media/edit', [MediaController::class, 'edit']);
	$group->post('/mediapool/media/move', [MediaController::class, 'move']);
	$group->post('/mediapool/media/clone', [MediaController::class, 'clone']);

	$group->get('/playlists/find/{playlist_mode:master|internal|external|multizone|channel}[/{playlist_name}]', [PlaylistsController::class, 'findByName']);
	$group->get('/playlists/find/for-player[/{playlist_name}]', [PlaylistsController::class, 'findForPlayerAssignment']);
	$group->delete('/playlists', [PlaylistsController::class, 'delete']);
	$group->put('/playlists', [ExportController::class, 'export']);
	$group->get('/playlists/find/{playlist_id:\d+}', [PlaylistsController::class, 'findById']);
	$group->get('/playlists/multizone/{playlist_id:\d+}', [PlaylistsController::class, 'loadZone']);
	$group->post('/playlists/multizone/{playlist_id:\d+}', [PlaylistsController::class, 'saveZone']);
	$group->patch('/playlists/shuffle', [PlaylistsController::class, 'toggleShuffle']);
	$group->patch('/playlists/picking', [PlaylistsController::class, 'shufflePicking']);

	$group->get('/playlists/items/load/{playlist_id:\d+}', [ItemsController::class, 'loadItems']);
	$group->post('/playlists/items/insert', [ItemsController::class, 'insert']);
	$group->delete('/playlists/items', [ItemsController::class, 'delete']);
	$group->patch('/playlists/items', [ItemsController::class, 'updateItemOrders']);
	$group->get('/playlists/item/{item_id:\d+}', [ItemsController::class, 'fetch']);
	$group->patch('/playlists/item', [ItemsController::class, 'edit']);
	$group->get('/playlists/widget/fetch/{item_id:\d+}', [WidgetsController::class, 'fetch']);
	$group->patch('/playlists/widget/save', [WidgetsController::class, 'save']);

	$group->patch('/player/playlist', [PlayerController::class, 'replacePlaylist']);


})->add(function ($request, $handler) {return $handler->handle($request)->withHeader('Content-Type', 'text/html');});
