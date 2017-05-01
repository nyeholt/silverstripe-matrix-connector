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
class MatrixStandardPageItem extends MatrixContentItem
{
	/**
	 * Use the same icon for everything
	 *
	 * @var String
	 */
	private static $icon = array("matrix-connector/images/matrix/matrix-standard-page", "file");

	/**
	 * Cache the computed content value
	 *
	 * @var String
	 */
	private $content;

	/**
	 * Converts all contained bodycopies into content
	 *
	 * @param $linkTo
	 *			Whether to resolve links to local or remote page objects
	 *
	 * @return String
	 */
    public function Content($linkTo='local', $convertLinks = false)
	{
		if (!$this->content) {
			$this->content = '';

			$children = $this->DependentChildren();
			foreach ($children as $child) {
				if ($child->type_code == 'bodycopy') {
					$this->content .= BodycopyContentExtractor::extractContent($child);
				}
			}

            if ($convertLinks) {
                $this->content = $this->convertContent($this->content, $linkTo);
            }
		}
		return $this->content;
	}

	/**
	 * Matrix has the concept of dependent children whereby some asset types have children that only have
	 * relevance in the context of 'this' asset. This method returns just those children
	 *
	 * @return ArrayList
	 */
	public function DependentChildren() {
		$all = $this->stageChildren();
		$children = new ArrayList();

		foreach ($all as $child) {
			// see if it's dependent
			if ($child->type_code == 'bodycopy') {
				$children->push($child);
			}
		}
		return $children;
	}

	/**
	 * Return all children that are NOT dependent children in matrix.
	 *
	 * @return ArrayList
	 */
	public function Children() {
		$all = $this->stageChildren();
		$children = new ArrayList();
		
		foreach ($all as $child) {
			// see if it's dependent
			if ($child->type_code == 'bodycopy') {
				continue;
			}
			$children->push($child);
		}
		return $children;
	}
}
?>