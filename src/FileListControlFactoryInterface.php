<?php

namespace TomasKarlik\FileList;


interface FileListControlFactoryInterface
{

	/**
	 * @param string $directory
	 * @return FileListControl
	 */
	function create($directory);

}
