<?php
/*

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
 * An external content item sourced from a MySource Matrix system
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class MatrixContentItem extends ExternalContentItem
{
	/**
	 * Use the same icon for everything
	 * 
	 * @var String
	 */
	public static $icon = array("matrix-connector/images/matrix/matrix-item", "folder");

	/**
	 * Holds all the information about the links this object has. 
	 *
	 * @var Object
	 */
	protected $assetLinks;

	/**
	 * On creation, bind to the cmisobj if provided
	 * 
	 * @param ExternalContentSource $source
	 * 					Where this item was loaded from
	 * @param Int $id
	 * 					The Alfresco ID of this object
	 */
	public function __construct($source=null, $id=null)
	{
		parent::__construct($source, $id);
		if ($this->source) {
			$repo = $this->source->getRemoteRepository();
			// lets load the object from the Alfresco repository and populate the 'remoteProperties' field
			$data = $repo->call('getGeneral', array('id' => $id));
			foreach ($data as $key => $value) {
				$this->remoteProperties[$key] = $value;
			}

			$data = $repo->call('getAttributes', array('id'=>$id));
			if ($data) {
				foreach ($data as $key => $value) {
					if ($key == 'asset_links') {
						$this->assetLinks = $value;
					} else {
						$this->remoteProperties[$key] = $value;
					}
				}
			}

			$this->Title = isset($this->remoteProperties['name']) ? $this->remoteProperties['name'] : 'No name';
			$this->MenuTitle = isset($this->remoteProperties['short_name']) ? $this->remoteProperties['short_name'] : $this->Title;
		}
	}

	/**
	 * Overridden to pass the content through as its downloaded (if it's not cached locally)
	 */
	public function streamContent()
	{
		
	}
	
	/**
	 * Return the asset type
	 * @see external-content/code/model/ExternalContentItem#getType()
	 */
	public function getType()
	{
		return $this->type_code;
	}
	
	protected $objChildren = null;

	/**
	 * Overridden to load all children from Matrix. For now we're ignoring
	 * the $showAll param - we have a separate 'dependentChildren' method
	 * to specifically handle dependent and non-dependent children
	 * 
	 * @param boolean $showAll
	 * @return DataObjectSet
	 */
	public function stageChildren($showAll = false) {
		if (!$this->ID) {
			return DataObject::get('MatrixContentSource');
		}

		if (!$this->objChildren) {
			$this->objChildren = new DataObjectSet();
			// For the first batch, just get all the immediate children of the
			// top level 
			$repo = $this->source->getRemoteRepository();
			if ($repo->isConnected()) {
				if(isset($_GET['debug_profile'])) Profiler::mark("MatrixContentItem", "getChildren");
				if (!isset($this->remoteProperties['id'])) {
					// for some reason the call failed!
					return $this->objChildren;
				}

				$childItems = $repo->call('getChildren', array('id'=>$this->remoteProperties['id'], 'depth' => 1));
				if(isset($_GET['debug_profile'])) Profiler::unmark("MatrixContentItem", "getChildren");
				// make sure that there's no errors!!
				if (!isset($childItems->error)) {
					if(isset($_GET['debug_profile'])) Profiler::mark("MatrixContentItem", "loadChildren");
					// means there weren't any children of this asset
					foreach ($childItems as $childId => $properties) {
						$item = $this->source->getObject($childId);
						$this->objChildren->push($item);
					}
					if(isset($_GET['debug_profile'])) Profiler::unmark("MatrixContentItem", "loadChildren");
				}
			}
		}

		return $this->objChildren;
	}

	/**
	 * Gets the children of this item as a UL that is acceptable for use in a tree.
	 *
	 * We override this method because the base SilverStripe implementation does a bunch of stuff that's really
	 * not useful for us when working with a purely ajax environment
	 */
	public function getChildrenAsUL($attributes = "", $titleEval = '"<li>" . $child->Title', $extraArg = null, $limitToMarked = false, $childrenMethod = "AllChildrenIncludingDeleted", $numChildrenMethod = "numChildren", $rootCall = true, $minNodeCount = 30) {
		return $this->source->getChildrenOfNodeAsUL($attributes, $titleEval, $extraArg, $this);
	}

	/**
	 * Check the object type; if it's a Document, return 0, otherwise 
	 * return one as we don't know whether this type has children or not
	 * 
	 * @return int
	 */
	public function numChildren()
	{
		// we don't know how many there might be, so we'll just say that there are for now
		// This is only used when drawing the tree so it's not critical to be accurate
		return 1;
	}

	/**
	 * Matrix has the concept of dependent children whereby some asset types have children that only have
	 * relevance in the context of 'this' asset. This method returns just those children
	 * 
	 * @return DataObjectSource
	 */
	public function DependentChildren() {
		return new DataObjectSource();
	}

	/**
	 * Return all children that are NOT dependent children in matrix.
	 *
	 * @return DataObjectSource
	 */
	public function Children() {
		return $this->stageChildren();
	}

	/**
	 * Converts arbitrary matrix based content into something that can be understood in SS
	 *
	 * Converts ./?a=xx links, @todo convert %keywords% also
	 */
	public function convertContent($content) {
		if (preg_match_all('|\./\\?a=(\d+)|', $content, $matches)) {
			if (isset($matches[1])) {
				foreach ($matches[1] as $assetid) {
					if ($item = $this->source->getObject($assetid)) {
						$content = str_replace('./?a='.$assetid, $item->Link(), $content);
					}
				}
			}
		}

		return $content;
	}
}

?>
