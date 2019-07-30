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

// For RabbitMQ connection
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * HUBzero extension of flysystem local adapter
 */
class IdataAdapter extends \League\Flysystem\Adapter\Local
{
	/**
	* Contructor: Save mount path for later use
	*
	**/
	function __construct($path, $mount_path, $amqp)
	{
		$this->mount_path = $mount_path;
		$this->amqp       = $amqp;

		// File actions
		$this->RENAMED    = 'renamed';
		$this->DELETED    = 'deleted';
		$this->CHANGED    = 'opened-file';

		parent::__construct($path);
	}


	/**
	* Get base directory (path prefix with mount path removed)
	*
	* @return  string	base dir with trailing DS
	**/
	public function getBaseDir()
	{
		$baseDir = str_replace($this->mount_path, '', $this->pathPrefix);

		return $baseDir;
	}


	/**
	* @inheritdoc
	*/
	public function write($path, $contents, League\Flysystem\Config $config)
	{
		$path   = $this->adjustName($path);
		$result = parent::write($path, $contents, $config);

		// Set metadata
		if (false != $result)
		{
			$this->iDataMsg($this->CHANGED,$path);
			// TODO Remove? Event::trigger('metadata.captureInitialMetadata',array($this->getBaseDir() . $path));
		}

		return $result;
	}


	/**
	* @inheritdoc
	*/
	public function writeStream($path, $resource, League\Flysystem\Config  $config)
	{
		$path   = $this->adjustName($path);
		$result = parent::writeStream($path,$resource,$config);

		// Set metadata
		if (false != $result)
		{
			$this->iDataMsg($this->CHANGED,$path);
			// TODO Remove? Event::trigger('metadata.captureInitialMetadata',array($this->getBaseDir() . $path));
		}

		return $result;
	}


	/**
	* @inheritdoc
	*/
	public function update($path, $contents, League\Flysystem\Config $config)
	{
		$result = parent::update($path,$contents,$config);

		if (false != $result)
		{
			$this->iDataMsg($this->CHANGED,$path);
		}

		return $result;
	}


	/**
		* @inheritdoc
		*/
	public function copy($path, $newpath)
	{
		$newpath = $this->adjustName($newpath);
		$result  = parent::copy($path,$newpath);

		if (false != $result)
		{
			$this->iDataMsg($this->CHANGED,$newpath);
		}

		return $result;
	}


	/**
		* @inheritdoc
		*/
	public function delete($path)
	{
		$result = parent::delete($path);

		if (false != $result)
		{
			$this->iDataMsg($this->DELETED,$path);
		}

		return $result;
	}


	/**
	* @inheritdoc
	*/
	public function listContents($directory = '', $recursive = false)
	{
		$result = [];
        $location = $this->applyPathPrefix($directory) . $this->pathSeparator;

		if (! is_dir($location))
		{
			return [];
		}

		if ('' == $location)
		{
			return [];
		}

		$cdir = scandir($location);

		foreach ($cdir as $key => $value)
		{
			if (!in_array($value, array(".", "..")))
			{
				$path = $location.$value;

				if ($recursive && is_dir($path))
				{
					$result[$value] = $this->listContents($path,$recursive);
				}
				else
				{
					$file = new SplFileInfo($path);
					$result[] = $this->normalizeFileInfo($file);
				}
			}
		}

		return array_filter($result);
	}


	/**
	* @inheritdoc
	*/
	public function deleteDir($dirname, $recursing=false)
	{
		if (!$recursing)
		{
			$location = $this->applyPathPrefix($dirname);
		}
		else
		{
			$location = $dirname;
		}

		if (!is_dir($location))
		{
			return false;
		}

		if ('' == $location)
		{
			return false;
		}

		// Recursivly delete contents
		$cdir = scandir($location);

		foreach ($cdir as $key => $value)
		{
			if (!in_array($value, array(".", "..")))
			{

				$path = $location.$this->pathSeparator.$value;

				if (is_dir($path))
				{
					if (!$this->deleteDir($path, true))
					{
						return false;
					}
				}
				else
				{
					$result = unlink($path); // covers symlinks, files

					if (false != $result)
					{
						$this->iDataMsg($this->DELETED,$path);
					}
				}
			}
		}

		// Delete given dir
		return rmdir($location);
	}


	/**
	* @inheritdoc
	*/
	public function rename($path, $newpath)
	{
		// Renaming dir
		if (is_dir($this->applyPathPrefix($path)))
		{
			return false; // NOTE: silently doing nothing
		}

		// Renaming file
		else
		{
			$newpath = $this->adjustName($newpath);
			$result  = parent::rename($path,$newpath);

			if (false != $result)
			{
				$this->iDataMsg($this->RENAMED,$path,$newpath);
			}

			return $result;
		}
	}


	/**
	 * Change filename to be valid for use with our systems: legal chars: Aa-Zz0-9.-_
	 *
	 * @param   string  $path      Original path including file name
	 * @return  string             Path with unsupported chars removed from file name
	 */
	public function adjustName($path)
	{
		// Separate containing dir's path, prep it for reattachment

		$dir  = dirname($path);

		if ($dir == '.')
		{
			$dir = '';
		}

		if ($dir != '' && substr($dir, -1) != DS)
		{
			$dir .= DS;
		}

		// Replace whitespace with underscores
		$without_whitespace = preg_replace('/\s+/', '_', basename($path));

		// Remove unsupported chars in file name
		$name = preg_replace('/[^A-Za-z0-9\.\-\_]/', '', $without_whitespace);

		return $dir . $name;
	}


	/**
	* @param SplFileInfo $file
	*
	* @return array
	*/
	protected function mapFileInfo(SplFileInfo $file)
	{
		$normalized = [
			'type' => $file->getType(),
			'path' => $this->getFilePath($file),
		];

		$normalized['timestamp'] = $file->getMTime();

		$owner  = '';

		// TODO get owner's user id from metadata

		if ($owner != '')
		{
			// Get owner's user ID number
			$user = User::getInstance($owner);
			$id   = $user->get('id');
			$normalized['owner'] = $id;
		}
		else
		{
			// For now, get current user TODO Change as needed
			$user = User::getInstance();
			$id   = $user->get('id');
			$normalized['owner'] = $id;
		}

		if ($normalized['type'] === 'file') {
			$normalized['size'] = $file->getSize();
		}

		return $normalized;
	}


	/**
	* Send iData msg to support tracking metadata
	*
	* @param string $action		Description of file event (required, will not be validated)
	* @param string $path1		Full, un-prefixed path of file (valid path required)
	* @param string $path2		Full, un-prefixed path of file, optional
	*
	* @return none
	*/
	protected function iDataMsg($action,$path1,$path2 = '')
	{
		$full1 = $this->applyPathPrefix($path1); // Fixup paths
		$full2 = $this->applyPathPrefix($path2);

		$msg = array(
			'actor'       => User::getInstance()->get('username') // Who?   Hub user
			,'action'     => $action                              // What?  File changed in specified way
			,'cwd'        => dirname($full1)                      // Where? In specified directory
			,'@timestamp' => date('c')                            // When?  Now ('c' = ISO 8601 timestamp)
			,'sequence'   => 0                                    // Why?   Must use correct user ID ('0' = from web session)
		);

		$paths = array();

		switch ($action)
		{
			case $this->CHANGED: // created or modified
				$paths[] = $this->iDataMsgPath('NORMAL',basename($full1),"0");
				break;

			case $this->RENAMED: // renamed or moved
				$paths[] = $this->iDataMsgPath('PARENT',dirname( $full1),"0");
				$paths[] = $this->iDataMsgPath('PARENT',dirname( $full2),"1");
				$paths[] = $this->iDataMsgPath('DELETE',basename($full1),"2");
				$paths[] = $this->iDataMsgPath('CREATE',basename($full2),"3");
				break;

			case $this->DELETED: // deleted specifically or as part of directory deletion
				$paths[] = $this->iDataMsgPath('PARENT',dirname( $full1),"0");
				$paths[] = $this->iDataMsgPath('DELETE',basename($full1),"1");
				break;
		}

		$msg["paths"] = $paths;
		$msg = json_encode($msg,JSON_UNESCAPED_SLASHES);

		// Send to RabbitMQ, NOTE: hardcoded vhost & delivery mode
		try
		{
			$connection = new AMQPStreamConnection($this->amqp["host"],$this->amqp["port"],$this->amqp["user"],$this->amqp["password"],"/");
			$channel = $connection->channel();
			$channel->queue_bind($this->amqp["queue"],$this->amqp["exchange"]);
			$msg = new AMQPMessage($msg,array('content_type' => 'text/plain','delivery_mode' => 2));
			$channel->basic_publish($msg,$this->amqp["exchange"]);
			$channel->close();
			$connection->close();
		}
		catch (Exception $e)
		{
			error_log('IdataAdapter->iDataMsg(): AMQP exception "'.print_r($e->getMessage(),true).'"');
		}
	}


	/**
	* Build a path array for an iData msg
	*
	* @param string $name		Absolute or relative path to file or directory
	* @param string $item		Integer, as string, between "1" and "4"
	* @param string $objtype	"NORMAL", "CREATE", "DELETE", "PARENT"
	*
	* @return array
	*/
	protected function iDataMsgPath($objtype,$name,$item)
	{
		return array(
			"name"     => $name
			,"item"    => $item
			,"objtype" => $objtype
			);
	}
}
