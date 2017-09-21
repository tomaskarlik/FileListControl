<?php

namespace TomasKarlik\FileList;

use Nette\Utils\Finder;
use Nette\Utils\Strings;
use TomasKarlik\FileList\Exception\FileDeleteException;
use TomasKarlik\FileList\Exception\FileIsNotWritableException;
use TomasKarlik\FileList\Exception\FileNotFoundException;
use TomasKarlik\FileList\Exception\InvalidPathException;


final class FileFacade
{

	/**
	 * @param string $file
	 * @throws FileIsNotWritableException
	 * @throws FileDeleteException
	 * @throws FileNotFoundException
	 */
	public function deleteFile($file)
	{
		if ( ! file_exists($file)) {
			throw new FileNotFoundException(sprintf('File "%s" not exists!', $file));
		}

		if ( ! is_writable($file)) {
			throw new FileIsNotWritableException(sprintf('File "%s" is not writable!', $file));
		}

		if ( ! unlink($file)) {
			throw new FileDeleteException(sprintf('Error delete file "%s"!', $file));
		}
	}


	/**
	 * @param string $basePath
	 * @param string $currentPath
	 * @throws InvalidPathException
	 * @throws FileNotFoundException
	 * @return string
	 */
	public function getRelativePath($basePath, $currentPath)
	{
		$basePath = realpath($basePath);
		$currentPath = realpath($currentPath);

		$this->verifyPath($basePath, $currentPath);

		$currentPath = Strings::replace($currentPath, sprintf('#^%s#', preg_quote($basePath, '#')), '');
		return ltrim($currentPath, DIRECTORY_SEPARATOR);
	}


	/**
	 * @return array
	 */
	public function getDirectories($path)
	{
		$directories = Finder::findDirectories()->in($path);
		$directories = iterator_to_array($directories);
		ksort($directories);

		return $directories;
	}


	/**
	 * @return array
	 */
	public function getFiles($path)
	{
		$files = Finder::findFiles()->in($path);
		$files = iterator_to_array($files);
		ksort($files);

		return $files;
	}


	/**
	 * @param string $basePath
	 * @param string $currentPath
	 * @return bool
	 */
	public function isRootDir($basePath, $currentPath)
	{
		$basePath = realpath($basePath);
		$currentPath = realpath($currentPath);

		return ($basePath === $currentPath);
	}


	/**
	 * @param string $basePath
	 * @param string $currentPath
	 * @throws InvalidPathException
	 * @throws FileNotFoundException
	 */
	public function verifyPath($basePath, $currentPath)
	{
		$basePath = realpath($basePath);
		$currentPath = realpath($currentPath);

		if ( ! Strings::startsWith($currentPath, $basePath)) {
			throw new InvalidPathException(sprintf('File "%s" is not accessible!', $currentPath));
		}

		if ( ! file_exists($currentPath)) {
			throw new FileNotFoundException(sprintf('File "%s" not exists!', $currentPath));
		}
	}

}
