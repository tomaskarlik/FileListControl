<?php

namespace TomasKarlik\FileList;

use Nette\DI\CompilerExtension;


class FileListControlExtension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('fileList'))
			->setImplement(FileListControlFactoryInterface::class)
			->setFactory(FileListControl::class);
	}

}
