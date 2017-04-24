<?php

/**
 * Link
 *
 * @package silverstripe
 * @subpackage silverstripe-links
 */
class Link extends DataObject
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Type' => 'Varchar',
        'URL' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'OpenInNewWindow' => 'Boolean',
        'Template' => 'Varchar(255)'
    );

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = array(
        'File' => 'File',
    );

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = array(
        'Title' => 'Title',
        'LinkType' => 'Type',
        'LinkURL' => 'Link'
    );

    /**
     * A map of templates that are available for rendering
     * Link objects with
     *
     * @var array
     */
    private static $templates = array();

    /**
     * A map of object types that can be linked to
     * Custom dataobjects can be added to this
     *
     * @var array
     **/
    private static $types = array(
        'URL' => 'URL',
        'Email' => 'Email address',
        'Phone' => 'Phone number',
        'File' => 'File on this website',
    );

    /**
     * List the allowed included link types.  If null all are allowed.
     *
     * @var array
     **/
    private static $allowed_types = null;

    /**
     * @var string custom CSS classes for template
     */
    protected $cssClass;

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = $this->scaffoldFormFields(array(
            // Don't allow has_many/many_many relationship editing before the record is first saved
            'includeRelations' => ($this->ID > 0),
            'tabbed' => true,
            'ajaxSafe' => true
        ));

        $fields->removeByName(
            array(
                // seem to need to remove both of these for different SS versions...
                'FileID',
                'File',

                'Template'
            )
        );

        $styles = $this->config()->get('styles');
        if ($styles) {
            $i18nStyles = array();
            foreach ($styles as $key => $label) {
                $i18nStyles[$key] = _t('Links.STYLE'.strtoupper($key), $label);
            }
            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create(
                    'Style',
                    _t('Links.STYLE', 'Style'),
                    $i18nTemplates
                )->setEmptyString('Default')
            );
        }

        $fields->dataFieldByName('Title')
            ->setTitle(_t('Links.TITLE', 'Title'))
            ->setRightTitle(_t('Links.OPTIONALTITLE', 'Optional. Will be auto-generated from link if left blank'));

        $fields->replaceField(
            'Type',
            DropdownField::create(
                'Type',
                _t('Links.LINKTYPE', 'Link Type'),
                $this->Types
            )->setEmptyString(' '),
            'OpenInNewWindow'
        );

        $fields->addFieldsToTab(
            'Root.Main',
            CheckboxField::create(
                'OpenInNewWindow',
                _t('Links.OPENINNEWWINDOW','Open link in a new window')
            )->displayIf('Type')->isEqualTo("URL")
                ->orIf()->isEqualTo("File")
                ->orIf()->isEqualTo("SiteTree")
                ->end()
        );

        $fields->addFieldsToTab(
            'Root.Main',
            array(
                DisplayLogicWrapper::create(
                    TreeDropdownField::create(
                        'FileID',
                        _t('Links.FILE', 'File'),
                        'File',
                        'ID',
                        'Title'
                    )
                )->displayIf("Type")->isEqualTo("File")->end(),
            ),
            'OpenInNewWindow'
        );

        $fields->dataFieldByName('URL')
            ->displayIf("Type")->isEqualTo("URL");

        $fields->dataFieldByName('Email')
            ->setTitle(_t('Links.EMAILADDRESS', 'Email Address'))
            ->displayIf("Type")
            ->isEqualTo("Email");

        $fields->dataFieldByName('Phone')
            ->setTitle(_t('Links.PHONENUMBER', 'Phone Number'))
            ->displayIf("Type")
            ->isEqualTo("Phone");

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }


    /**
     * If the title is empty, set it to getLinkURL()
     *
     * @return string
     **/
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (!$this->Title) {
            switch ($this->Type) {
                case 'URL':
                case 'Email':
                case 'Phone':
                    $this->Title = $this->{$this->Type};
                    break;
                case 'SiteTree':
                    $this->Title = $this->SiteTree()->MenuTitle;
                    break;
                default:
                    if ($this->Type && $component = $this->getComponent($this->Type)) {
                        $this->Title = $component->Title;
                    } else {
                        $this->Title = 'Link-' . $this->ID;
                    }
                    break;
            }

            $this->write();
        }
    }

    /**
     * Add CSS classes.
     *
     * @param string $class CSS classes.
     * @return Link
     **/
    public function setCSSClass($class)
    {
        $this->cssClass = $class;
        return $this;
    }

    /**
     * Sets allowed link types
     *
     * @param array
     * @return Link
     **/
    public function setAllowedTypes($types = null)
    {
        $this->allowed_types = $types;
        return $this;
    }

    /**
     * Returns allowed link types
     *
     * @return array
     */
    public function getTypes()
    {
        $types = $this->config()->get('types');
        $i18nTypes = array();
        $allowed_types = $this->config()->get('allowed_types');

        if ($this->allowed_types) {
            // Prioritise local field over global settings
            $allowed_types = $this->allowed_types;
        }

        if ($allowed_types) {
           foreach ($allowed_types as $type) {
                if (!array_key_exists($type, $types)) {
                    user_error("{$type} is not a valid link type");
                }
            }

            foreach (array_diff_key($types, array_flip($allowed_types)) as $key => $value) {
                unset($types[$key]);
            }
        }

        // Get translatable labels
        foreach ($types as $key => $label) {
            $i18nTypes[$key] = _t('Links.TYPE'.strtoupper($key), $label);
        }
        $this->extend('updateTypes', $i18nTypes);
        return $i18nTypes;
    }

    /**
     * Renders an HTML anchor tag for this link
     *
     * @return string
     **/
    public function forTemplate()
    {
        if ($this->LinkURL) {
            $link = $this->renderWith($this->RenderTemplates);

            $this->extend('updateTemplate', $link);
            return $link;
        }
    }

    /**
     * Returns a list of rendering templates
     *
     * @return array
     **/
    public function getRenderTemplates()
    {
        $class = $this->Classname;
        $templates = array();
        if (is_object($class)) $class = get_class($class);

        if (!is_subclass_of($class, 'DataObject')) {
            throw new InvalidArgumentException("$class is not a subclass of DataObject");
        }

        while ($next = get_parent_class($class)) {
            if ($this->Style) {
                $templates[] = $class . '_' . $this->Style;
            }
            $templates[] = $class;
            if ($next == 'DataObject') {
                return $templates;
            }

            $class = $next;
        }
    }

    /**
     * Works out what the URL for this link should be based on it's Type
     *
     * @return string
     **/
     public function getLinkURL()
     {
         if (!$this->ID) {
             return;
         }
         $type = $this->Type;
         switch ($type) {
             case 'URL':
                 $LinkURL = $this->URL;
                 break;
             case 'Email':
                 $LinkURL = $this->Email ? "mailto:$this->Email" : null;
                 break;
             case 'Phone':
                 $phone = $this->obj('Phone')->PhoneFriendly();
                 $LinkURL = $phone ? "tel:$phone" : null;
                 break;
             default:
                 if ($type && $component = $this->getComponent($type)) {
                     if (!$component->exists()) {
                         $LinkURL = false;
                     }
                     if ($component->hasMethod('Link')) {
                         $LinkURL = $component->Link() . $this->Anchor;
                     } else {
                         $LinkURL = "Please implement a Link() method on your dataobject \"$type\"";
                     }
                 }
                 break;
         }

         $this->extend('updateLinkURL', $LinkURL);
         return $LinkURL;
     }

    /**
     * Gets the classes for this link.
     *
     * @return string
     **/
    public function getClasses()
    {
        $classes = explode(' ', $this->cssClass);
        if ($this->Style) {
            $classes[] = $this->Style;
        }

        $this->extend('updateClasses', $classes);
        return implode(' ', $classes);
    }

    /**
     * Gets the html class attribute for this link.
     *
     * @return string
     **/
    public function getClassAttr()
    {
        $class = $this->Classes ? Convert::raw2att($this->Classes) : '';
        return $class ? " class='$class'" : '';
    }

    /**
     * Gets the html target attribute for the anchor tag
     *
     * @return string
     **/
    public function getTargetAttr()
    {
        return $this->OpenInNewWindow ? " target='_blank'" : '';
    }

    /**
     * Gets the description label of this links type
     *
     * @return string
     **/
    public function getLinkType()
    {
        $types = $this->config()->get('types');
        return isset($types[$this->Type]) ? _t('Links.TYPE'.strtoupper($this->Type), $types[$this->Type]) : null;
    }

    /**
     * Validate
     *
     * @return ValidationResult
     **/
    protected function validate()
    {
        $valid = true;
        $message = null;
        $type = $this->Type;

        // Check if empty strings
        switch ($type) {
            case 'URL':
            case 'Email':
            case 'Phone':
                if ($this->{$type} == '') {
                    $valid = false;
                    $message = _t('Links.VALIDATIONERROR_EMPTY'.strtoupper($type), "You must enter a $type for a link type of \"$this->LinkType\"");
                }
                break;
            default:
                if ($type && empty($this->{$type.'ID'})) {
                    $valid = false;
                    $message = _t('Links.VALIDATIONERROR_OBJECT', "Please select a {value} object to link to", array('value' => $type));
                }
                break;
        }
        // if its already failed don't bother checking the rest
        if ($valid) {
            switch ($type) {
                case 'URL':
                    $allowedFirst = array('#', '/');
                    if (!in_array(substr($this->URL, 0, 1), $allowedFirst) && !filter_var($this->URL, FILTER_VALIDATE_URL)) {
                        $valid = false;
                        $message = _t('Links.VALIDATIONERROR_VALIDURL', 'Please enter a valid URL. Be sure to include http:// for an external URL. Or begin your internal url/anchor with a "/" character');
                    }
                    break;
                case 'Email':
                    if (!filter_var($this->Email, FILTER_VALIDATE_EMAIL)) {
                        $valid = false;
                        $message = _t('Links.VALIDATIONERROR_VALIDEMAIL', 'Please enter a valid Email address');
                    }
                    break;
                case 'Phone':
                    if (!preg_match("/^\+?[0-9a-zA-Z\-\s]*[\,\#]?[0-9\-\s]*$/", $this->Phone)) {
                        $valid = false;
                        $message = _t('Links.VALIDATIONERROR_VALIDPHONE', 'Please enter a valid Phone number');
                    }
                    break;
            }
        }

        $result = ValidationResult::create($valid, $message);

        $this->extend('updateValidate', $result);
        return $result;
    }
}
