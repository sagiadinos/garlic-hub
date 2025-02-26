<?php
/*
 garlic-hub: Digital Signage Management Platform

 Copyright (C) 2025 Nikolaos Sagiadinos <garlic@saghiadinos.de>
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

use App\Framework\Core\Config\Config;
use App\Framework\Core\Sanitizer;
use App\Framework\Core\Session;
use App\Framework\Core\Translate\Translator;
use App\Framework\User\UserService;
use App\Framework\Utils\Html\FormBuilder;
use App\Modules\Playlists\Controller\OverviewController;
use App\Modules\Playlists\Controller\SettingsController;
use App\Modules\Playlists\FormHelper\FilterFormBuilder;
use App\Modules\Playlists\FormHelper\FilterParameters;
use App\Modules\Playlists\FormHelper\SettingsParameters;
use App\Modules\Playlists\FormHelper\SettingsFormBuilder;
use App\Modules\Playlists\FormHelper\SettingsValidator;
use App\Modules\Playlists\Repositories\PlaylistsRepository;
use App\Modules\Playlists\Services\AclValidator;
use App\Modules\Playlists\Services\PlaylistsEditService;
use App\Modules\Playlists\Services\PlaylistsOverviewService;
use Psr\Container\ContainerInterface;

$dependencies = [];

$dependencies[PlaylistsRepository::class] = DI\factory(function (ContainerInterface $container)
{
	return new PlaylistsRepository($container->get('SqlConnection'));
});

$dependencies[AclValidator::class] = DI\factory(function (ContainerInterface $container)
{
	return new AclValidator(
		'playlists',
		$container->get(UserService::class),
		$container->get(Config::class),
	);
});

$dependencies[PlaylistsEditService::class] = DI\factory(function (ContainerInterface $container)
{
	return new PlaylistsEditService(
		$container->get(PlaylistsRepository::class),
		$container->get(AclValidator::class),
		$container->get('ModuleLogger')
	);
});
$dependencies[SettingsParameters::class] = DI\factory(function (ContainerInterface $container)
{
	return new SettingsParameters(
		$container->get(Sanitizer::class),
		$container->get(Session::class)
	);
});
$dependencies[SettingsValidator::class] = DI\factory(function (ContainerInterface $container)
{
	return new SettingsValidator(
		$container->get(Translator::class),
		$container->get(SettingsParameters::class),
	);
});
$dependencies[SettingsFormBuilder::class] = DI\factory(function (ContainerInterface $container)
{
	return new SettingsFormBuilder(
		$container->get(AclValidator::class),
		$container->get(SettingsParameters::class),
		$container->get(SettingsValidator::class),
		$container->get(FormBuilder::class)
	);
});
$dependencies[SettingsController::class] = DI\factory(function (ContainerInterface $container)
{
	return new SettingsController(
		$container->get(SettingsFormBuilder::class),
		$container->get(SettingsParameters::class),
		$container->get(PlaylistsEditService::class)
	);
});
$dependencies[PlaylistsOverviewService::class] = DI\factory(function (ContainerInterface $container)
{
	return new PlaylistsOverviewService(
		$container->get(PlaylistsRepository::class),
		$container->get(AclValidator::class),
		$container->get('ModuleLogger')
	);
});
$dependencies[FilterParameters::class] = DI\factory(function (ContainerInterface $container)
{
	return new FilterParameters(
		$container->get(Sanitizer::class),
		$container->get(Session::class)
	);
});

$dependencies[FilterFormBuilder::class] = DI\factory(function (ContainerInterface $container)
{
	return new FilterFormBuilder(
		$container->get(AclValidator::class),
		$container->get(FilterParameters::class),
		$container->get(FormBuilder::class)
	);
});

$dependencies[OverviewController::class] = DI\factory(function (ContainerInterface $container)
{
	return new OverviewController(
		$container->get(FilterFormBuilder::class),
		$container->get(FilterParameters::class),
		$container->get(PlaylistsOverviewService::class)
	);
});
return $dependencies;
