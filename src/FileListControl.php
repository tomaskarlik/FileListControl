<?php

namespace TomasKarlik\FileList;

use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\ITemplate;
use Nette\Utils\Finder;
use Nette\Utils\Strings;


final class FileListControl extends Control
{

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @var string
	 */
	private $templateFile;


	/**
	 * @param string $directory
	 */
	public function __construct($directory)
	{
		parent::__construct();

		$this->directory = realpath($directory);
		$this->templateFile = __DIR__ . '/templates/default.latte';
	}


	public function render()
	{
		$path = $this->getParameter('path', NULL);
		$path = $this->directory . ($path ? '/' . $path : '');

		if ( ! $this->verifyLogPath($path)) {
			$this->flashMessage('Zadaná cesta neexistuje!', 'alert-danger');
			$this->template->directories = [];
			$this->template->files = [];
			$this->template->breadcrumbs = [];
			$this->template->parent = NULL;

		} else {
			$this->template->directories = $this->getDirectories($path);
			$this->template->files = $this->getFiles($path);
			$this->template->breadcrumbs = $this->getBreadcrumbParts($path);
			$this->template->parent = $this->isRootDir($path) ? NULL : $this->getRelativePath(dirname($path));
		}

		$this->template->setFile($this->templateFile);
		$this->template->render();
	}


	/**
	 * @param string $templateFile
	 */
	public function setTemplate($templateFile)
	{
		$this->templateFile = $templateFile;
	}


	/**
	 * @param string $file
	 * @param int $download
	 */
	public function handleShowFile($file, $download = 0)
	{
		$file = $this->directory . '/' . $file;
		if ( ! $this->verifyLogPath($file)) {
			$this->flashMessage('Soubor neexistuje!', 'alert-danger');
			$this->redirect('this');
		}

		$download = (bool) $download;
		$this->presenter->sendResponse(
			new FileResponse($file, NULL, mime_content_type($file), $download)
		);
	}


	/**
	 * @param string $file
	 */
	public function handleDeleteFile($file)
	{
		$file = $this->directory . '/' . $file;
		$path = NULL;

		if ( ! $this->verifyLogPath($file)) {
			$this->flashMessage('Soubor neexistuje!', 'alert-danger');

		} else {
			if ( ! is_writable($file)) {
				$this->flashMessage('Soubor nelze smazat (přístupová práva)!', 'alert-warning');

			} elseif (@unlink($file)) {
				$this->flashMessage('Soubor byl smazán!', 'alert-info');

			} else {
				$this->flashMessage('Chyba!', 'alert-danger');
			}
			$path = $this->getRelativePath(dirname($file));
		}
		$this->redirect('this', ['path' => $path]);
	}


	/**
	 * @param string $path
	 * @return string
	 */
	public function getRelativePath($path)
	{
		$path = realpath($path);
		$path = Strings::replace($path, sprintf('#^%s#', preg_quote($this->directory, '#')), '');
		return ltrim($path, DIRECTORY_SEPARATOR);
	}


	/**
	 * @return ITemplate
	 */
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->addFilter('relative', [$this, 'getRelativePath']);
		return $template;
	}


	/**
	 * @param string $path
	 * @return array
	 */
	private function getBreadcrumbParts($path)
	{
		$relative = $this->getRelativePath($path);
		if (empty($relative)) {
			return [];
		}

		$breadcrumbs = [];
		$relativePath = '';

		$parts = explode(DIRECTORY_SEPARATOR, $relative);
		foreach ($parts as $part) {
			$relativePath .= ($relativePath ? DIRECTORY_SEPARATOR . $part : $part);
			$breadcrumbs[$relativePath] = $part;
		}

		return $breadcrumbs;
	}


	/**
	 * @return array
	 */
	private function getDirectories($path)
	{
		$directories = Finder::findDirectories()->in($path);
		$directories = iterator_to_array($directories);
		ksort($directories);

		return $directories;
	}


	/**
	 * @return array
	 */
	private function getFiles($path)
	{
		$files = Finder::findFiles()->in($path);
		$files = iterator_to_array($files);
		ksort($files);

		return $files;
	}


	/**
	 * @param string $path
	 * @return bool
	 */
	private function isRootDir($path)
	{
		$path = realpath($path);
		return ($path === $this->directory);
	}


	/**
	 * @param string $path
	 * @return bool
	 */
	private function verifyLogPath($path)
	{
		$path = realpath($path);
		if ( ! Strings::startsWith($path, $this->directory)) {
			return FALSE;
		}
		return file_exists($path);
	}

}
