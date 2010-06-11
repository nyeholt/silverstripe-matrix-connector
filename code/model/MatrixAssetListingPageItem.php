<?php
/*

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
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
 * A standard page content item
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class MatrixAssetListingPageItem extends MatrixContentItem
{
	/**
	 * Use the same icon for everything
	 *
	 * @var String
	 */
	public static $icon = array("matrix-connector/images/matrix/matrix-asset-listing-page", "file");

	/**
	 * Cache the computed content value
	 *
	 * @var String
	 */
	private $content;

	/**
	 * The content if there's no results to display
	 *
	 * @var String
	 */
	private $noResultsContent;

    public function Content($mode=null)
	{
		if (!$this->content) {
			$this->content = '';
			$this->noResultsContent = '';

			$pageChildren = $this->DependentChildren();

			foreach ($pageChildren as $child) {
				// if it's a folder and titled 'type formats', contine
				if ($child->type_code == 'bodycopy') {
					if ($child->Title == 'Page Contents (No Results)') {
						$this->noResultsContent = BodycopyContentExtractor::extractContent($child);
					} else if ($child->Title = 'Page Contents') {
						$this->content = BodycopyContentExtractor::extractContent($child);
					}
				}
			}
		}

		if ($mode == 'NoResults') {
			return $this->noResultsContent;
		}

		return $this->content;
	}


	/**
	 * Matrix has the concept of dependent children whereby some asset types have children that only have
	 * relevance in the context of 'this' asset. This method returns just those children
	 *
	 * @return DataObjectSource
	 */
	public function DependentChildren() {
		$all = $this->stageChildren();
		$children = new DataObjectSet();
		foreach ($all as $child) {
			// see if it's dependent
			if ($child->type_code == 'bodycopy') {
				$children->push($child);
			}

			if ($child->type_code == 'folder') {
				if ($child->Title == 'Type Formats' || $child->Title == 'Position Formats' || $child->Title == 'Group Formats') {
					$children->push($child);
				}
			}
		}
		return $children;
	}

	/**
	 * Return all children that are NOT dependent children in matrix.
	 *
	 * @return DataObjectSource
	 */
	public function Children() {
		$all = $this->stageChildren();
		$children = new DataObjectSet();
		
		foreach ($all as $child) {
			// see if it's dependent
			if ($child->type_code == 'bodycopy') {
				continue;
			}

			if ($child->type_code == 'folder') {
				if ($child->Title == 'Type Formats' || $child->Title == 'Position Formats' || $child->Title == 'Group Formats') {
					continue;
				}
			}
			$children->push($child);
		}

		return $children;
	}
}
?>