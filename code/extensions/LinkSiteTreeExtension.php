<?php

/**
 * Add sitetree type to link field
 *
 * @package silverstripe
 * @subpackage mysite
 */
class LinkSiteTreeExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = array(
        'Anchor' => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'SiteTree' => 'SiteTree',
    );

    /**
     * A map of object types that can be linked to
     * Custom dataobjects can be added to this
     *
     * @var array
     **/
    private static $types = array(
        'SiteTree' => 'Page on this website',
    );

    /**
     * Update Fields
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;

        // Insert site tree field after the file selection field
        $fields->insertAfter(
            'Type',
            DisplayLogicWrapper::create(
                TreeDropdownField::create(
                    'SiteTreeID',
                    _t('Links.PAGE', 'Page'),
                    'SiteTree'
                ),
                TextField::create(
                    'Anchor',
                    _t('Links.ANCHOR', 'Anchor/Querystring')
                )->setRightTitle(_t('Links.ANCHORINFO', 'Include # at the start of your anchor name or, ? at the start of your querystring'))
            )->displayIf("Type")->isEqualTo("SiteTree")->end()
        );

        // Display warning if the selected page is deleted or unpublished
        if ($owner->SiteTreeID && !$owner->SiteTree()->isPublished()) {
            $fields
                ->dataFieldByName('SiteTreeID')
                ->setRightTitle(_t('Links.DELETEDWARNING', 'Warning: The selected page appears to have been deleted or unpublished. This link may not appear or may be broken in the frontend'));
        }
    }
}
