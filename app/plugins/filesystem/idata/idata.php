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

require_once __DIR__ . DS .  'IdataAdapter.php';

/**
 * Plugin class for idata filesystem connectivity
 */
class plgFilesystemIdata extends \Hubzero\Plugin\Plugin
{
	static $PROVIDER_ALIAS = 'idata';
	protected $_event_latch = false;

	/**
	 * Initializes the idata filesystem connection
	 *
	 * @param   array   $params  Any connection params needed
	 * @return  object
	 **/
	public static function init($params = [])
	{
		if (!isset($params['alias']))
		{
			Lang::load('plg_filesystem_idata',PATH_APP . DS . 'plugins' . DS . 'filesystem' . DS . 'idata');
			throw new Exception(Lang::txt('PLG_FILESYSTEM_IDATA_ERROR_NO_ALIAS'));
		}

		$config = Plugin::params('filesystem', 'idata');
		$mount_path = trim($config->get('mount_path','/srv/idata'));
		$alias      = trim($params['alias'], '/');
		$path       = rtrim($mount_path . $alias);

		$amqp       = array(
			'host'      => trim($config->get('amqp_host'    ,'127.0.0.1' ))
			,'port'     => trim($config->get('amqp_port'    ,'5672'      ))
			,'user'     => trim($config->get('amqp_user'    ,'rabbitmq'  ))
			,'password' => trim($config->get('amqp_password','rabbitmq'  ))
			,'exchange' => trim($config->get('amqp_exchange','geoedf-all'))
			,'queue'    => trim($config->get('amqp_queue'   ,'geoedf-all'))
		);

		$adapter = new IdataAdapter($path,$mount_path,$amqp);

		return $adapter;
	}

	/**
	 * Handle for event indicating file(s) were attached to a publication - to incl. of ext. metadata
	 *
	 * @param   string   $identifier  Full path name for file attached to publication (has "." in first position)
	 * @param   object   $pub         Publication object
	 **/
	public function onAfterSaveFileAttachments($pub, $configs, $elementId, $element)
	{
		// Prevent recursion
		if (!$this->_event_latch)
		{
			$this->_event_latch = true;

			$metafile_base   = 'METADATA-PUB-' . strval($pub->identifier()) . '-' . $pub->get('version_number') . '.TXT';
			$metafile_full   = PATH_APP . DS . 'tmp' . DS . $metafile_base;
			$metafile_handle = fopen($metafile_full,'w');

			// Recreate entire metadata file. Write output for all of pub's current iData-based attachements
			$attachments = $pub->attachments(true); // TODO Correct value?

			foreach ($attachments as $entry)
			{
				foreach ($entry as $item)  // TODO Why array of arrays?
				{
					if (isset($item->path))
					{
						try
						{
							// TODO Check if file if from iData repo? How?

							// Get metadata from idata
							$prodsf = Prods::prodsFileConnect($pub->project()->get('alias') . DS . $item->path);
							$rodsmetas = $prodsf->getMeta();

							// Copy data out of rodsmeta items to simple set of key/value pairs
							$metadata = [];
							foreach ($rodsmetas as $item)
							{
								$metadata[$item->name] = $item->value;
							}

							// Translate to XML & write to file
							fwrite($metafile_handle, $this->generateDcXml($metadata));
						}
						catch (Exception $e) // Error may occur if file is not from iData repo (?)
						{
						}
					}
				}
			}

			// Add metadata file to master repo
			$result = $pub->_project->repo()->insert([
				'path'     => NULL,
				'dirPath'  => NULL,
				'dataPath' => $metafile_full,
				'update'   => false // TODO correct value?
			]);

			// Attach metadata file to publication
			//  - Can't call addAttachment(): Must call save() so _git will be initialized.
			//  - Must have valid File() parent as save() uses it (1115: "...$this->_parent->_db"). (If not, error: "Call...on a non-object" at ...com_publications/tables/attachment.php:470)
			//  - "Attachments" class calls save(). Its loadAttach() shows that it can be parent of File.
			$db       = App::get('db');
			$atts_obj = new \Components\Publications\Models\Attachments($db);
			$att      = new \Components\Publications\Models\Attachment\File($atts_obj);
			$result   = $att->save($element, $elementId, $pub, null, array($metafile_base));

			// Clean up tmp file
			fclose($metafile_handle);
			unlink($metafile_full);
		}
	}

	/**
	 * Encode text for XML output
	 *
	 * @param   array   $text  xml input
	 * @return  array          encode text
	 **/
	protected function encode($text)
	{
		return htmlspecialchars($text,ENT_XML1,'UTF-8');
	}

	/**
	 * Create XML element from data
	 *
	 * @param   array   $tag          input tag data
	 * @param   array   $attribs      input attrib data
	 * @param   array   $payload      input payload data
	 * @param   bool    $single_line  single line element?
	 * @return  array                 XML element as string
	 **/
	protected function buildXmlElement($tag,$attribs,$payload,$single_line=false)
	{
		$nl = "\n";

		// Ensure payload has no new lines at end
		while ($nl == substr($payload, -1))
		{
			$payload = substr($payload, 0, -1);
		}

		// Option: single line tag
		if ($single_line)
		{
			$nl = '';
		}
		else
		{
			// Indent payload, including multiline payloads: Convention: 2 spaces
			$payload = '  ' . str_replace($nl, $nl . '  ', $payload);
		}

		// Add spacer to attribs as needed
		if ('' != trim($attribs))
		{
			$attribs = ' ' . $attribs;
		}

		// Wrap tag around payload
		$ret  = '<' . $tag . $attribs . '>' . $nl;
		$ret .= $payload                    . $nl;
		$ret .= '</'.$tag             .'>'  . $nl;

		return $ret;
	}

	/**
	 * Create XML element from data
	 *
	 * @param   array   $metadata    array of key/value pairs
	 * @return  array                XML string
	 **/
	protected function generateDcXml($metadata)
	{
		// Constants
		$URL           = 'http://' . $_SERVER['HTTP_HOST'];
		$XMLNS_DCTERMS = $this->encode('xmlns:dcterms="http://purl.org/dc/terms/"');
		$XMLNS_RDF     = $this->encode('xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"');
		$XMLNS_DC      = $this->encode('xmlns:dc="http://purl.org/dc/elements/1.1/"');
		$XMLNS_TERMS   = $this->encode('xmlns:terms="http://' . $_SERVER['HTTP_HOST'] . '/terms/"');

		$CORE_FIELDS = array(
			'description'
			,'title'
			,'subject'
			,'contributor'
			,'publisher'
			,'date'
			,'identifier'
			,'format'
			,'type'
			,'source'
			,'creator'
		);

		$GEO_FIELDS = array(
			 'projection'
			,'northlimit'
			,'southlimit'
			,'eastlimit'
			,'westlimit'
		);

		// Inner tags for core fields
		$INNER = array(
			'description'  => array('dcterms:abstract','rdf:Description')
			,'title'       => array()
			,'subject'     => array('rdf:Description')
			,'contributor' => array('rdf:Description')
			,'publisher'   => array('rdf:Description')
			,'date'        => array('rdf:value')
			,'identifier'  => array('rdf:Description')
			,'format'      => array()
			,'type'        => array()
			,'source'      => array()
			,'creator'     => array('rdf:Description')
		);

		$xml = '';

		// Process core fields
		foreach ($CORE_FIELDS as $idx)
		{
			// Extract data
			$data = '';
			if (array_key_exists($idx,$metadata) && (null != $metadata[$idx]))
			{
				$data .= $metadata[$idx];
			}

			// Only output if value exists
			if ('' != $data)
			{
				$data = $this->encode($data);

				// Wrap with inner tags as needed
				$single_line = true;
				foreach ($INNER[$idx] as $i)
				{
					$data = $this->buildXmlElement($i, '', $data, $single_line);
					if ($single_line)
					{
						$single_line = false;
					}
				}

				// Add to output
				$xml .= $this->buildXmlElement('dc:' . $idx, '', $data, $single_line);

				// Special case: no inner tags
				if ($single_line)
				{
					$xml .= "\n";
				}
			}
		}

		// Process geograhic fields
		$xml_geo_data = '';
		foreach ($GEO_FIELDS as $idx)
		{
			// Extract data
			$data = '';
			if (array_key_exists($idx, $metadata) && (null != $metadata[$idx]))
			{
				$data .= $metadata[$idx];
			}

			// Skip if no value
			if ('' != $data)
			{
				if ('' != $xml_geo_data)
				{
					$xml_geo_data .= '; ';
				}
				$xml_geo_data .= $idx . '=' . $data;
			}
		}
		if ('' != $xml_geo_data)
		{
			$data = $this->encode($xml_geo_data);
			$data = $this->buildXmlElement('rdf:value'  , '', $data, true);
			$data = $this->buildXmlElement('dcterms:box', '', $data      );
			$xml .= $this->buildXmlElement('dc:coverage', '', $data      );
		}

		// Process other fields
		foreach ($metadata as $field => $value)
		{
			// Skip core and geo - output previously
			if (array_key_exists($field,$CORE_FIELDS) || array_key_exists($field,$GEO_FIELDS))
			{
				continue;
			}

			// Output every value found
			$data = $this->buildXmlElement('rdf:Description', '', $this->encode($value), true);
			$xml .= $this->buildXmlElement('terms:' . $field, '', $data);
		}

		// Wrap up XML
		$xml = $this->buildXmlElement('rdf:Description', 'rdf:about="' . $this->encode($URL) . '"', $xml);
		$xml = $this->buildXmlElement('rdf:RDF', $XMLNS_DCTERMS . " \n\t\t" . $XMLNS_RDF . " \n\t\t" . $XMLNS_DC . " \n\t\t" . $XMLNS_TERMS, $xml);

		return $xml;
	}
}
