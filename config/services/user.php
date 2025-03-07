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


use App\Framework\User\UserService;
use App\Framework\Utils\Html\FormBuilder;
use App\Modules\User\EditLocalesController;
use App\Modules\User\EditPasswordController;
use Psr\Container\ContainerInterface;
$dependencies = [];

$dependencies[EditPasswordController::class] = DI\factory(function (ContainerInterface $container)
{
	return new EditPasswordController(
		$container->get(FormBuilder::class),
		$container->get(UserService::class)
	);
});

$dependencies[EditLocalesController::class] = DI\factory(function (ContainerInterface $container)
{
	return new EditLocalesController($container->get(UserService::class));
});

return $dependencies;