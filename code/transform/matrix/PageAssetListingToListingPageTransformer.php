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
 * Transform an asset listing page
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class PageAssetListingToListingPageTransformer implements ExternalContentTransformer
{
	public function transform($item, $parentObject, $duplicateStrategy)
	{
		$pageChildren = $item->stageChildren();
		// okay, first we'll create the new page item, 
		// and map a bunch of child information across
		$newPage = new MatrixListingPage();
		
		$newPage->Title = $item->Title;
		$newPage->MenuTitle = $item->MenuTitle;
		
		// what else should we map across?
		$filteredChildren = $item->Children();
		$newPage->Content = $item->Content();
		$newPage->NoResultsContent = $item->Content('NoResults');
		
		$newPage->MatrixId = $item->id;
		$newPage->OriginalProperties = json_encode($item->getRemoteProperties());
		$newPage->ParentID = $parentObject->ID;
		$newPage->Sort = 0;

		$newPage->write();
		return new TransformResult($newPage, $filteredChildren);
		
	}
}

?>