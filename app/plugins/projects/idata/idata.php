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

require_once PATH_CORE . DS . 'components' . DS . 'com_projects' . DS . 'models' . DS . 'orm' . DS . 'provider.php';
require_once PATH_CORE . DS . 'components' . DS . 'com_projects' . DS . 'models' . DS . 'orm' . DS . 'connection.php';
//require_once PATH_APP . DS . 'plugins' . DS . 'filesystem' . DS . 'idata' . DS . 'idata.php';

use Components\Projects\Models\Orm\Provider as Provider;
use Components\Projects\Models\Orm\Connection as Connection;
use Hubzero\Plugin\Plugin;

/**
 * Plugin class for idata hooks into projects
 */
class plgProjectsIdata extends Plugin
{
	/**
	 * Handles project creation event
	 *
	 * @param   object  $project  The project being created
	 * @return  void
	 **/
	public function onProjectCreate($project)
	{
		// TODO Exclude project short name (and, therefore, dir.) 'external' throughout code!

		$provider = Provider::oneByAlias('idata'); // plgFilesystemIdata::$PROVIDER_ALIAS);

		// Create connection - incl. params for filesystem plugin's init() (as JSON)
		// path parameter is used to prevent request to specify the directory to share with the connection
		$connection = Connection::blank();
		$connection->set('name'        , $this->params->get('repo_desc'));
		$connection->set('project_id'  , $project->get('id'));
		$connection->set('provider_id' , $provider->id);
		$connection->set('params'      , json_encode(array('alias' => $project->get('alias'), 'path' => '/')));
		$connection->save();
	}

	/**
	 * Adds callback that fires only when new Connection is created
	 * in order to add the project Alias to the Connection params.
	 *
	 * @param   string $alias The alias of the project.
	 * @return  void
	 **/
	public function onProjectAreas($alias)
	{
		Event::addListener(function($event) use($alias){
			$collection = $event->getArgument('model');
			if ($collection->provider->get('alias') == 'idata')
			{
				$projectAlias = json_encode(array('alias' => $alias));
				$collection->set('params', $projectAlias);
				$collection->save();
			}
		}, '#__projects_connections_new');
	}
}
