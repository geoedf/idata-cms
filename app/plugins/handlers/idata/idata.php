<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2019 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-idata
 * @author    Rob Campbell <rcampbel@purdue.edu>
 * @copyright Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

/**
 * Plugin class for idata-specific file handling
 */
class plgHandlersIdata extends \Hubzero\Plugin\Plugin
{
	/**
	 * Determines if the given collection can be handled by this plugin
	 *
	 * @param   \Hubzero\Filesystem\Collection  $collection  The file collection to assess
	 * @return  void
	 **/
	public function canHandle(\Hubzero\Filesystem\Collection $collection)
	{
error_log('plgHandlersIdata()');
		// TODO Figure out why previewing non-prj/dbf works? We're returning 'false' for them!

		// Check extension to make sure we can proceed
		if ($collection->hasExtensions(['prj' => 1]) ||
			$collection->hasExtensions(['dbf' => 1])    )
		{
			return true;
		}

		return false;
	}

	/**
	 * Handles view events for idata-specific files
	 *
	 * @param   \Hubzero\Filesystem\Collection $collection  The file collection to view
	 * @return                                 $view        The newly created view
	 **/
	public function onHandleView(\Hubzero\Filesystem\Collection $collection)
	{
error_log('onHandleView()');
		// Note: removed for now: 'asc' => 'map',
		$viewers = [
			'dbf'  => 'dbf'
			,'prj'  => 'prj'
			,'txt'  => 'txt'
			,'gif'  => 'gif'
			,'jpg'  => 'gif'
			,'png'  => 'gif'
			,'zip'  => 'zip'
			,'gz'   => 'zip'
			,'csv'  => 'csv'
			,'tsv'  => 'csv'
			,'tab'  => 'csv'
		];

		// Find and build correct view
		$lowers = array_change_key_case($collection->getFlatListOfExtensions(), CASE_LOWER);
		foreach ($viewers as $ext => $layout)
		{
			if (array_key_exists($ext, $lowers))
			{
				// Create view
				$view = new \Hubzero\Plugin\View([
					'folder'  => 'handlers',
					'element' => 'idata',
					'name'    => 'idata',
					'layout'  => $layout
				]);

				$view->ext   = $ext;
				$view->limit = $this->params->get('linelimit');

				// Find first file with similar extension (regardless of up/low case)
				foreach ($collection->getFlatListOfFiles() as $file)
				{
					if (strtolower($file->getExtension()) == $ext)
					{
						$view->file = $file;
						break;
					}
				}

				return $view;
			}
		}

		return false;
	}
}
