<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class MatrixSOAPClient {
	protected $wsdlUrl;

	protected $user;
	protected $pass;

	protected $client;

	/**
	 * Have we connected yet? 
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return $this->client != null;
	}

	/**
	 * Connect to Matrix
	 *
	 * @param array $config
	 */
	public function connect($config) {
		$this->wsdlUrl = $config['apiUrl'];
		$this->user = isset($config['username']) ? $config['username'] : null;
		$this->pass = isset($config['password']) ? $config['password'] : null;

		$login = $this->user ? array('login' => $this->user, 'password' => $this->pass) : null;

		if ($this->wsdlUrl) {
			$this->client = new SoapClient($this->wsdlUrl, $login);
		}
	}

	/**
	 * Disconnect from the source
	 */
	public function disconnect() {
		$this->client = null;
	}

	/**
	 * get general information about an asset
	 * 
	 * @param array $args
	 */
	public function getAsset($args) {
		$id = (string) isset($args['id']) ? $args['id'] : 0;

		$details = $this->client->GetAsset(array('AssetID' => $id));

		$content = new stdClass;
		if ($details) {
			// need this bit of hack magic to fix XML tags that are just digits instead of legal tags
			$details->GetAssetResult = preg_replace('/<(\/?)(\d+)>/', '<\\1index_\\2>', $details->GetAssetResult);
			$xml = new SimpleXMLElement($details->GetAssetResult);
			$content = $this->xmlToObject($xml);
			$content->type_code = mb_strtolower($xml->getName());

			// now need to iterate all the vars to ensure they're loaded too
			if (isset($content->vars)) {
				foreach ($content->vars as $k => $deats) {
					$content->$k = isset($deats->value) ? $deats->value : '';
				}
			}

			// finally - we need to figure out if this is meant to be menu accessible or not
			$details = $this->client->GetLinks(array('AssetID' => $id, 'SideOfLink' => 'minor', 'LinkType' => 3));
			if ($details && $details->GetLinksResult) {
				if (!is_array($details->GetLinksResult)) {
					$details->GetLinksResult = array($details->GetLinksResult);
				}
				foreach ($details->GetLinksResult as $link) {
					if ((int) $link->LinkType === 1) {
						$content->ShowInMenus = true;
						break;
					}
				}
			}
		}

		return $content;
	}

	/**
	 * We don't need an explicit attributes thing because it's handled by the
	 * GetAsset call already for SOAP
	 *
	 * @return stdClass
	 */
	public function getAttributes() {
		return new stdClass();
	}

	/**
	 * Get all the children of a particular asset.
	 *
	 * Will return in normal link sort order, and the asset will be marked as to whether they should be
	 * in menus or not
	 *
	 * @param array $args
	 *			Args for the getChildren call. Must have 'id' set
	 */
	public function getChildren($args) {
		$id = (string) isset($args['id']) ? $args['id'] : 0;
		$details = $this->client->GetLinks(array('AssetID' => $id, 'SideOfLink' => 'major', 'LinkType' => 3 /* TYPE_1 or TYPE_2 */));
		$links = array();
		if ($details && isset($details->GetLinksResult)) {
			if (!is_array($details->GetLinksResult)) {
				$details->GetLinksResult = array($details->GetLinksResult);
			}
			foreach ($details->GetLinksResult as $link) {
				$link->id = $link->MinorID;
				$links[] = $link;
			}
		}

		return $links;
	}

	/**
	 * Converts a SimpleXMLElement to an array representation
	 *
	 * @param SimpleXMLElement $xml
	 * @return SimpleXMLElement
	 */
	protected function xmlToObject(SimpleXMLElement $xml) {
		$props = new stdClass();
		foreach ($xml as $k => $v) {
			if ($v instanceof SimpleXMLElement) {
				if ($v->children() && count($v->children())) {
					$props->{$v->getName()} = $this->xmlToObject($v);
				} else {
					$props->$k = (string) $v;
				}
			} else {
				$props->$k = $v;
			}
		}

		return $props;
	}
}