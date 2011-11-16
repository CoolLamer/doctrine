<?php
/**
 * This file is part of the Nella Framework.
 *
 * Copyright (c) 2006, 2011 Patrik Votoček (http://patrik.votocek.cz)
 *
 * This source file is subject to the GNU Lesser General Public License. For more information please see http://nella-project.org
 */

namespace Nella\Media;

/**
 * Media presenter
 *
 * @author	Patrik Votoček
 */
class MediaPresenter extends \Nella\Application\UI\MicroPresenter
{
	/**
	 * @param \Nette\Application\Request
	 * @return \Nette\Application\IResponse
	 */
	public function run(\Nette\Application\Request $request)
	{
		$params = $request->params;
		if (isset($params['file'])) {
			return callback($this, 'actionFile')->invokeArgs(array('file' => $params['file']));
		} elseif (isset($params['image']) && isset($params['format'])) {
			return callback($this, 'actionImage')->invokeArgs(array(
				'image' => $params['image'],
				'format' => $params['format'],
				'path' => $params['path'],
				'type' => isset($params['type']) ? $params['type'] : NULL
			));
		} else {
			return parent::run($request);
		}
	}

	/**
	 * @param IFile
	 */
	public function actionFile(IFile $file)
	{
		return new \Nette\Application\Responses\FileResponse(
			$file->getFileinfo()->getRealPath(), //$file->getContent(),
			$file->getFilename(),
			$file->getMimeType()
		);
	}

	/**
	 * @param IImage
	 * @param IFormat
	 * @param string
	 * @param int
	 */
	public function actionImage($image, $format, $path, $type = NULL)
	{
		$service = $this->getContext()->doctrineContainer->getService('Nella\Media\ImageEntity');
		$image = $service->repository->find($image);
		
		$service = $this->getContext()->doctrineContainer->getService('Nella\Media\ImageFormatEntity');
		$format = $service->repository->find($format);
			
		$image = $format->process($image);
		$context = $this->getContext();

		$path = $context->expand($context->params['wwwDir']) . $path;
		$dir = pathinfo($path, PATHINFO_DIRNAME);
		if (!file_exists($dir)) {
			mkdir($dir, 0777, TRUE);
		}

		$image->save($path);
		if (!$type) {
			$image->send();
		} else {
			switch ($type) {
				case 'gif':
					$type = \Nette\Image::GIF;
					break;
				case 'png':
					$type = \Nette\Image::PNG;
					break;
				default:
					$type = \Nette\Image::JPEG;
					break;
			}
			$image->send($type);
		}

		$this->terminate();
	}
}
