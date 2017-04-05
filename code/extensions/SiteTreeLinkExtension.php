<?php

/**
 * Fixes duplicate link in SiteTree
 *
 * @package silverstripe
 * @subpackage mysite
 */
class SiteTreeLinkExtension extends DataExtension
{
    /**
     * Event handler called before duplicating a sitetree object.
     */
    public function onBeforeDuplicate()
    {
        $owner = $this->owner;
        //loop through has_one relationships and reset any Link fields
        if($hasOne = Config::inst()->get($owner->ClassName, 'has_one')){
            foreach ($hasOne as $field => $fieldType) {
                if ($fieldType === 'Link') {
                    $owner->{$field.'ID'} = 0;
                }
            }
        }
    }
}
