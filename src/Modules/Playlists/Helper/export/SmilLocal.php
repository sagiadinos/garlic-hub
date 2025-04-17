<?php
namespace Thymian\modules\playlists\export;

use Symfony\Component\Yaml\Exception\RuntimeException;
use Thymian\framework\core\Config;
use Thymian\framework\core\Directory;
use Thymian\framework\exceptions\BaseException;
use Thymian\framework\exceptions\ModuleException;
use Thymian\framework\logging\Logger;
use Thymian\framework\model\File;
use Thymian\framework\model\GenericFactory;
use Thymian\modules\player\PlayerModel;
use Thymian\modules\playlists\Content;
use Thymian\modules\playlists\ItemsModel;
use Thymian\modules\playlists\Model;

class SmilLocal extends Base
{
	/**
	 * @var Directory
	 */
	protected $Directory;

	/**
	 * @var GenericFactory
	 */
	protected $GenericFactory;

	/**
	 * @param string            $module_name
	 * @param Config            $Config
	 * @param Model             $PlaylistModel
	 * @param ItemsModel        $itemsModel
	 * @param GenericFactory    $genericFactory
	 */
	public function  __construct($module_name, Config $Config, Model $PlaylistModel, ItemsModel $itemsModel, GenericFactory $genericFactory)
	{
		$this->setGenericFactory($genericFactory);
		parent::__construct($module_name, $Config, $PlaylistModel, $itemsModel);
	}

	/**
	 * @return GenericFactory
	 */
	public function getGenericFactory()
	{
		return $this->GenericFactory;
	}

	/**
	 * @param GenericFactory $GenericFactory
	 * @return $this
	 */
	public function setGenericFactory($GenericFactory)
	{
		$this->GenericFactory = $GenericFactory;
		return $this;
	}

	/**
	 * some thing we need to initialise first
	 *
	 * @param int $playlist_id
	 *
	 * @return $this
	 * @throws ModuleException
	 * @throws \Thymian\framework\exceptions\CoreException
	 */
	public function initExport($playlist_id)
	{
		$base_path          = $this->getConfig()->getConfigValue('path_smil_playlists', 'smil_playlists');
		$media_pool_path    = _SystemPath . $this->getConfig()->getConfigValue('_mm_media_path', 'mediapool');
		$templates_path     = _SystemPath . $this->getConfig()->getConfigValue('templates_export_path', 'templates');

		$this->getDirectoryHelper()->createDirectoryIfNotExist($base_path);

		$this->setPlaylistBasePath($base_path)
			 ->setMediaPoolPath($media_pool_path)
			 ->setTemplatesPath($templates_path)
			 ->setPlaylistId($playlist_id);

		// check paths
		$this->getDirectoryHelper()->createDirectoryIfNotExist($this->buildLocalPath());
		return $this;
	}

	/**
	 * @param Directory $file
	 * @return $this
	 */
	public function setDirectoryHelper(Directory $file)
	{
		$this->Directory = $file;
		return $this;
	}

	/**
	 * @return Directory
	 * @throws ModuleException
	 */
	public function getDirectoryHelper()
	{
		if (empty($this->Directory))
		{
			throw new ModuleException($this->module_name, 'Directory helper is not instantiated.');
		}
		return $this->Directory;
	}

	/**
	 * @param Content $Content
	 * @return $this
	 * @throws ModuleException
	 */
	public function writeSMILFiles(Content $Content)
	{
		$fix_permissions = \Thymian::isUserRoot();

		try
		{
			// prefetch.smil
			$File = $this->getGenericFactory()->createFileInstance($this->buildLocalPath() . 'prefetch.smil');
			$File->setData($Content->getPrefetchContent())->writeData();

			if ($fix_permissions === true)
			{
				$this->fixFilePermission($File);
			}

			// items.smil
			$File = $this->getGenericFactory()->createFileInstance($this->buildLocalPath() . 'items.smil');
			$File->setData($Content->getElementsContent())->writeData();

			if ($fix_permissions === true)
			{
				$this->fixFilePermission($File);
			}

			// exclusive.smil
			$File = $this->getGenericFactory()->createFileInstance($this->buildLocalPath() . 'exclusive.smil');
			$File->setData($Content->getExclusiveContent())->writeData();

			if ($fix_permissions === true)
			{
				$this->fixFilePermission($File);
			}

			// preview.smil
			$File = $this->getGenericFactory()->createFileInstance($this->buildLocalPath() . 'preview.smil');
			$File->setData($Content->getPreviewContent())->writeData();

			if ($fix_permissions === true)
			{
				$this->fixFilePermission($File);
			}
		}
		catch(\RuntimeException $re)
		{
			Logger::Error($this->module_name, $re->getMessage(), $re->getCode(), '', $re->getFile(), $re->getLine());
			throw new ModuleException($this->module_name, 'Can not create smil files on local server. Path: ' . $this->buildLocalPath());
		}
		catch(BaseException $e)
		{
			$e->log();
			throw new ModuleException($this->module_name, 'Can not create smil files on local server. Path: ' . $this->buildLocalPath(), 0, $e);
		}

		return $this;
	}

	/**
	 * deletes the Media-symlinks, if set
	 *
	 * @return $this
	 */
	public function deleteItemsSymlinks()
	{
		$this->getDirectoryHelper()->deleteSymlinksInDirectory($this->buildLocalPath());
		return $this;
	}

	/**
	 * deletes the Template-Symlinks, if set
	 *
	 * @return $this
	 * @throws ModuleException
	 */
	public function deleteTemplatesSymlinks()
	{
		// useless, cause in $this->getTemplatesPath() are no symlinks to delete
		$this->getDirectoryHelper()->deleteSymlinksInDirectory($this->getTemplatesPath());
		return $this;
	}

	/**
	 * @param Content $Content
	 * @return $this
	 * @throws ModuleException
	 */
	public function createMediaSymlinks(Content $Content)
	{
		$this->deleteItemsSymlinks();

		foreach ($Content->getMediaSymlinks() as $value)
		{
			$obfuscated_link = $value['obfuscated'];

			if (!is_link($obfuscated_link))
			{
				if (!@symlink($this->getMediaPoolPath() . $value['original'], $obfuscated_link))
				{
					throw new ModuleException($this->module_name, 'Creation of mediapool symlink for '.$value['original'].' to ' . $obfuscated_link . ' failed');
				}
			}
			$md5_file = $this->getMediaPoolPath() . $value['original'] . '.md5';
			if (file_exists($md5_file))
			{
				if (!@symlink($md5_file, $obfuscated_link.'.md5'))
				{
					throw new ModuleException($this->module_name, 'Creation of mediapool symlink for '.$md5_file.' to ' . $obfuscated_link . ' failed');
				}
			}


		}
		return $this;
	}

	/**
	 * @param Content $Content
	 * @return $this
	 * @throws ModuleException
	 */
	public function createTemplatesSymlinks(Content $Content)
	{
		// Todo: Fix this idiotic bug

		// this is useless => have look in corresponding method
		// $this->deleteTemplatesSymlinks();

		// Method $this->deleteItemsSymlinks() deletes all symlinks
		// when we call createMediaSymlinks first and createTemplatesSymlinks all is fine
		// if there is no createMediaSymlinks to call nothing will be deleted. (see useless method)
		// Problem:
		// we cannot delete all symlinks here again, cause it would delete normal items, too which is not creeted in this method
		// currently we had to live with a workaround (look at
		//
		// Solution 1:
		// delete only templates based items here
		// pros: no call changes neccessary on productive system
		// cons: complicated changes needed
		//
		// Solution 2:
		// delete not here, but before the export begins
		// pros: less code to write
		//       method name is create and the first thing we do is delete. Thats silly
		// cons: calls changes need on the other servers
		//
		// currently I prefer solution 2, cause it would make the code clearer
		foreach ($Content->getTemplatesSymlinks() as $value)
		{
			$obfuscated_link = $value['obfuscated'];

			if (is_link($obfuscated_link))
			{
				unlink($obfuscated_link);
			}

			if (!@symlink($this->getTemplatesPath() . $value['original'], $obfuscated_link))
			{
					throw new ModuleException($this->module_name, 'Creation of template symlink for '.$value['original'].' to ' . $obfuscated_link . ' failed');
			}
			$md5_file = $this->getTemplatesPath() . $value['original'] . '.md5';
			if (file_exists($md5_file) && !file_exists($obfuscated_link.'.md5')) // workaround => look description above
			{
				if (!@symlink($md5_file, $obfuscated_link.'.md5'))
				{
					throw new ModuleException($this->module_name, 'Creation of template symlink for '.$md5_file.' to ' . $obfuscated_link . ' failed');
				}
			}
		}
		return $this;
	}

	/**
	 * returns the playlist path with playlist id in directory
	 *
	 * @return string
	 */
	protected function buildLocalPath()
	{
		$path =  $this->getPlaylistBasePath() . $this->getPlaylistId() . DIRECTORY_SEPARATOR;
		return $path;
	}

	/**
	 * @param File $File
	 * @return $this
	 */
	protected function fixFilePermission(File $File)
	{
		try
		{
			$File->chown('www-data')->chgrp('www-data');
		}
		catch(BaseException $e)
		{
			$e->log($File->getRealPath());
		}

		return $this;
	}
}