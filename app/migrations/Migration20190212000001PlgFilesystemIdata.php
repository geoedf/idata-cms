<?php

use Hubzero\Content\Migration\Base;

// No direct access
defined('_HZEXEC_') or die();

/**
 * Migration script for "idata" plugins with dummy values for installation specific config
 **/
class Migration20190212000001PlgFilesystemIdata extends Base
{
	public function up()
	{
		if ($this->db->tableExists('#__projects_connection_providers'))
		{
			$query = "SELECT * FROM `#__projects_connection_providers` WHERE `alias` = 'idata';";
			$this->db->setQuery($query);
			$this->db->query();
			if (!$this->db->getNumRows())
			{
				$query = "INSERT INTO `#__projects_connection_providers` (`alias`, `name`) VALUES ('idata', 'iData Storage');";
				$this->db->setQuery($query);
				$this->db->query();
			}

			$this->addPluginEntry('filesystem', 'idata');
			$this->addPluginEntry('handlers'  , 'idata');
			$this->addPluginEntry('metadata'  , 'idata');
			$this->addPluginEntry('projects'  , 'idata');
			//$this->addPluginEntry('search'    , 'idata');
			//$this->addComponentEntry(           'idata');
		}

		// Config for plugin 'filesystem' 'idata'
		$config = array(
			'mount_path' => '/srv/idata/',
		);
		$this->saveParams('plg_filesystem_idata',$config);

		// Config for official HZ plugin 'projects' 'files' - NOTE: Not reset in down()
		$config = array(
			'default_action'          => 'connections',
			'handler_base_path'       => '/srv/idata/',
			'default_connection_name' => 'Default Storage',
		);
		$this->saveParams('plg_projects_files',$config);

		// Config for plugins 'metadata'/'projects' 'idata'
		$this->saveParams('plg_metadata_idata',array('publisher' => 'iData@myHub'));
		$this->saveParams('plg_projects_idata',array('repo_desc' => 'iData Storage'));
	}

	public function down()
	{
		if ($this->db->tableExists('#__projects_connection_providers'))
		{
			$query = "DELETE FROM `#__projects_connection_providers` WHERE `alias` = 'idata'";
			$this->db->setQuery($query);
			$this->db->query();

			$this->deletePluginEntry('filesystem', 'idata');
			$this->deletePluginEntry('handlers'  , 'idata');
			$this->deletePluginEntry('metadata'  , 'idata');
			$this->deletePluginEntry('projects'  , 'idata');
			//$this->deletePluginEntry('search'    , 'idata');
			//$this->deleteComponentEntry(           'idata');
		}
	}
}
