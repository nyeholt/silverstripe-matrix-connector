<?php
/**

Copyright (c) 2009, SilverStripe Australia Limited - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.
 
*/

/**
 * Encapsulates a content source that pulls content through from MySource Matrix
 * 
 * Currently, only content retrieval via the JS api is supported. 
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class MatrixContentSource extends ExternalContentSource implements ExternalContentRepositoryProvider
{
	private static $db = array(
		'ConnectorType' => "Enum('JS API,SOAP API')",
		'ApiUrl' => 'Varchar(128)',
		'Username' => 'Varchar(64)',
		'Password' => 'Varchar(64)',
		'ApiKey' => 'Varchar(64)',
		'CacheTimeout' => 'Int',
		'RootAsset' => 'Int',
	);
	
	private static $icon = array("matrix-connector/images/matrix/matrix", "folder");


	/**
	 * The Matrix server repository we're going to connect to
	 * @var unknown_type
	 */
	protected $repo;

	/**
	 * A mapping of Matrix typecode to MatrixContentItem child type, if any
	 *
	 * @var array
	 */
	private static $type_mapping = array(
		'page_standard' => 'MatrixStandardPageItem',
		'page_asset_listing' => 'MatrixAssetListingPageItem',
		'folder' => 'MatrixFolderItem',
	);

	/**
	 * @return FieldSet
	 */
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		
		$fields->addFieldToTab('Root.Main', new DropdownField('ConnectorType', _t('ExternalContentSource.CONNECTOR_TYPE', 'Connector Type'), $this->dbObject('ConnectorType')->enumValues()));
		$fields->addFieldToTab('Root.Main', new TextField('ApiUrl', _t('ExternalContentSource.API_URL', 'API Url')));
		$fields->addFieldToTab('Root.Main', new TextField('Username', _t('ExternalContentSource.USER', 'Username')));
		$fields->addFieldToTab('Root.Main', new PasswordField('Password', _t('ExternalContentSource.PASS', 'Password')));
		$fields->addFieldToTab('Root.Main', new TextField('ApiKey', _t('MatrixContentSource.API_KEY', 'API Key (get this from your Matrix installation)')));
		$fields->addFieldToTab('Root.Main', new TextField('RootAsset', _t('MatrixContentSource.ROOT_ASSET', 'Asset ID of the root site to browse')));

		$fields->addFieldToTab('Root.Main', new TextField('CacheTimeout', _t('MatrixContentSource.CACHE_TIMEOUT', 'How long should content be cached for (in seconds)?')));

		return $fields;
	}

	/**
	 * Register a type
	 *
	 * @param String $matrixType
	 *			The matrix type_code
	 * @param String $class 
	 *			The type to create to represent this object. Must subclass MatrixContentItem
	 */
	public static function registerType($matrixType, $class)
	{
		self::$type_mapping[$matrixType] = $class;
	}

	/**
	 * Get the alfresco seamistrepository connected 
	 * 
	 * @return SeaMistRepository
	 */
	public function getRemoteRepository()
	{
		if (!$this->repo) {
			$this->repo = $this->createConnector($this->ConnectorType);
		}

		if (!$this->repo->isConnected()) {
			$config = array(
				'apiUrl' => $this->ApiUrl,
				'username' => $this->Username,
				'password' => $this->Password,
				'apiKey' => $this->ApiKey,
			);

			try {
				$this->repo->connect($config);
			} catch (Exception $zue) {
				singleton('ECUtils')->log("Failed connecting to repository: ".$zue->getMessage()."\n");
			}
		}

		return $this->repo;
	}

	public function encodeId($id) { return $id; }
	public function decodeId($id) { return $id; }
	
	/**
	 * Create the repository connector
	 * @param String $type
	 * 				The type of repository connector to create
	 * @return MatrixJSClient
	 * 				A Matrix repository connector
	 */
	protected function createConnector($type)
	{
		switch ($type) {
			case 'SOAP API': {
				return new MatrixSOAPClient();
			}
			case 'JS API': {
				return new MatrixJSClient($this->CacheTimeout);
			}
			default: {
				return new MatrixJSClient($this->CacheTimeout);
			}
		}
	}
	
	/**
	 * Return a new matrix content importer 
	 * @see external-content/code/dataobjects/ExternalContentSource#getContentImporter()
	 */
	public function getContentImporter($target=null)
	{
		return new MatrixImporter();
	}
	
	/**
	 * Matrix content can only be imported into 
	 * the sitetree for now. 
	 * 
	 * @see external-content/code/dataobjects/ExternalContentSource#allowedImportTargets()
	 */
	public function allowedImportTargets()
	{
		return array('sitetree' => true);
	}

	/**
	 * Whenever we save the content source, we want to disconnect 
	 * the repository so that it reconnects with whatever new connection
	 * details are provided
	 * 
	 * @see sapphire/core/model/DataObject#onBeforeWrite()
	 */
	public function onBeforeWrite()
	{
		parent::onBeforeWrite();
		$repo = $this->getRemoteRepository();
		if ($repo->isConnected()) {
			$repo->disconnect();
		}
	}

	/**
	 * Read only cache of objects loaded from Matrix for this request
	 * 
	 * @var array
	 */
	protected $objectCache = array();

	/**
	 * Get the object represented by ID
	 * 
	 * @param String $objectId
	 * @return DataObject
	 */
	public function getObject($objectId)
	{
		if (!isset($this->objectCache[$objectId])) {
			// get the object from the repository
			try {
				$repo = $this->getRemoteRepository();
				// Load the general data so we know what typeocde we're dealing with
				$data = $repo->getAsset(array('id' => $objectId));
				if (!$data) {
					singleton('ECUtils')->log("Failed getting data from repo. Check your API Url setting");
					return;
				}
				$type = isset($data->type_code) ? $data->type_code : null;
				$clazz = 'MatrixContentItem';
				if ($type) {
					$clazz = isset(self::$type_mapping[$type]) ? self::$type_mapping[$type] : 'MatrixContentItem';
				}
				$item = new $clazz($this, $objectId, $data);
				$this->objectCache[$objectId] = $item;
			} catch (Zend_Http_Client_Adapter_Exception $e) {
				singleton('ECUtils')->log("Failed connecting to matrix server: ".$e->getMessage());
				$this->objectCache[$objectId] = null;
			}
		}

		return $this->objectCache[$objectId];
	}
	
	/**
	 * Get the root object that we're listing from 
	 * (non-PHPdoc)
	 * @see external-content/code/model/ExternalContentSource#getRoot()
	 */
	public function getRoot()
	{
		$item = $this->getObject($this->RootAsset);
		return $item;
	}
	
	/**
	 * Override to fool hierarchy.php
	 * 
	 * @param boolean $showAll
	 * @return ArrayList
	 */
	public function stageChildren($showAll = false) {
		// if we don't have an ID directly, we should load and return ALL the external content sources
		if (!$this->ID) {
			return MatrixContentSource::get();
		}

		$children = new ArrayList();
		try {
			if ($this->ApiUrl && $this->RootAsset) {
				$repo = $this->getRemoteRepository();
				if ($repo->isConnected()) {
					$item = $this->getRoot();
					if ($item) {
						$children = $item->stageChildren();
					}
				}
			}
		} catch (Exception $e) {
			singleton('ECUtils')->log(__CLASS__.':'.__LINE__.':: '.$e->getMessage());
		}

		return $children;
	}

	public function Children() {
		$children = $this->stageChildren();
		if ($children) {
			return $children;
		}
	}

}

