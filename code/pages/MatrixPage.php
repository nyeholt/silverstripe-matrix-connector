<?php

/**
 * A class mapped into the system from a Matrix page
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license http://silverstripe.org/bsd-license/
 */
class MatrixPage extends Page
{
	private static $db = array(
		'MatrixId' => 'Varchar(64)',
		'OriginalProperties' => 'Text',
	);
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Matrix', new TextField('MatrixId', _t('MatrixPage.MATRIX_ID', 'Matrix ID')));
		$fields->addFieldToTab('Root.Matrix', new ReadonlyField('OriginalProperties', _t('MatrixPage.PROPERTIES', 'Original Matrix Properties')));
		return $fields;
	}
}

class MatrixPage_Controller extends Page_Controller {}

?>