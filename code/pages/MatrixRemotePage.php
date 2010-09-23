<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class MatrixRemotePage extends ExternalContentPage {

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		// $fields->addFieldToTab('Root.Content.Main', )
//		$fields->addFieldToTab('Root.Content.Main', new ExternalTreeDropdownField('ExternalContentRoot', _t('ExternalContentPage.CONTENT_SOURCE', 'External Content Source'), 'ExternalContentSource'));$fields->addFieldToTab('Root.Content.Main', new ExternalTreeDropdownField('ExternalContentRoot', _t('ExternalContentPage.CONTENT_SOURCE', 'External Content Source'), 'ExternalContentSource'));
		

		return $fields;
	}
}


class MatrixRemotePage_Controller extends ExternalContentPage_Controller {

}