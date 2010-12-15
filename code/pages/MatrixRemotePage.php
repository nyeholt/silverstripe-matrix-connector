<?php

/**
 * A Matrix page that uses the REMOTE urls of a matrix asset when displaying
 * content, meaning that links in this content will actually point
 * at the Matrix served pages. 
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class MatrixRemotePage extends ExternalContentPage {

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		return $fields;
	}
}


class MatrixRemotePage_Controller extends ExternalContentPage_Controller {

}