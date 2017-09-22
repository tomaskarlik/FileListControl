<?php

namespace TomasKarlik\FileList;

use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\ITemplate;
use TomasKarlik\FileList\Exception\FileDeleteException;
use TomasKarlik\FileList\Exception\FileIsNotWritableException;
use TomasKarlik\FileList\Exception\FileNotFoundException;
use TomasKarlik\FileList\Exception\InvalidPathException;


final class FileListControl extends Control
{

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @var FileFacade
	 */
	private $fileFacade;

	/**
	 * @var string
	 */
	private $templateFile;


	/**
	 * @param string $directory
	 * @param FileFacade $fileFacade
	 */
	public function __construct($directory, FileFacade $fileFacade)
	{
		parent::__construct();

		$this->directory = $directory;
		$this->templateFile = __DIR__ . '/templates/default.latte';

		$this->fileFacade = $fileFacade;
	}


	public function render()
	{
		$path = $this->getParameter('path', NULL);
		$path = $this->directory . ($path ? '/' . $path : '');

		$this->template->directories = [];
		$this->template->files = [];
		$this->template->breadcrumbs = [];
		$this->template->parent = NULL;

		try {
			$this->fileFacade->verifyPath($this->directory, $path);
			$this->template->directories = $this->fileFacade->getDirectories($path);
			$this->template->files = $this->fileFacade->getFiles($path);

			if ( ! $this->fileFacade->isRootDir($this->directory, $path)) {
				$this->template->breadcrumbs = $this->getBreadcrumbParts($path);
				$this->template->parent = $this->getRelativePath(dirname($path));
			}

		} catch (InvalidPathException $exception) {
			$this->flashMessage('Zadaná cesta není přístupná!', 'alert-danger');

		} catch (FileNotFoundException $exception) {
			$this->flashMessage('Zadaná cesta neexistuje!', 'alert-danger');
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
		try {
			$this->fileFacade->verifyPath($this->directory, $file);

		} catch (InvalidPathException $exception) {
			$this->flashMessage('Zadaná cesta není přístupná!', 'alert-danger');
			$this->redirect('this');

		} catch (FileNotFoundException $exception) {
			$this->flashMessage('Zadaná cesta neexistuje!', 'alert-danger');
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

		try {
			$this->fileFacade->verifyPath($this->directory, $file);
			$path = $this->getRelativePath(dirname($file)); //path to return
			$this->fileFacade->deleteFile($file);

		} catch (FileIsNotWritableException $exception) {
			$this->flashMessage('Soubor nelze smazat (přístupová práva)!', 'alert-warning');

		} catch (InvalidPathException $exception) {
			$this->flashMessage('Zadaná cesta není přístupná!', 'alert-danger');

		} catch (FileNotFoundException $exception) {
			$this->flashMessage('Soubor neexistuje!', 'alert-danger');

		} catch (FileDeleteException $exception) {
			$this->flashMessage('Chyba!', 'alert-danger');
		}

		$this->redirect('this', ['path' => $path]);
	}


	/**
	 * @param string $path
	 * @return string
	 */
	public function getRelativePath($path)
	{
		return $this->fileFacade->getRelativePath($this->directory, $path);
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

}
