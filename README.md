# FileList

Install
-------
Download package
```
composer require tomaskarlik/filelist
````

Register extension in config.neon
```
extensions:
	fileList: TomasKarlik\FileList\FileListControlExtension
```

Usage
-----
```php
<?php

namespace App\Module\SomeModule\Presenters;

use Nette\Application\UI\Presenter;
use TomasKarlik\FileList\FileListControl;
use TomasKarlik\FileList\FileListControlFactoryInterface;


final class ListPresenter extends Presenter
{

	private $factory;


	public function __construct(FileListControlFactoryInterface $factory)
	{
		$this->factory = $factory;
	}


	/**
	 * @return FileListControl
	 */
	protected function createComponentLogFileList()
	{
		$someDir = __DIR__;
		return $this->factory->create($someDir);
	}

}
```
```latte
{control logFileList}
````