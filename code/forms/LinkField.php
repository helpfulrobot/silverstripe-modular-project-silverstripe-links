<?php

/**
 * LinkField
 *
 * @package silverstripe
 * @subpackage silverstripe-links
 */
class LinkField extends TextField
{
    /**
     * @var Boolean
     **/
    protected $isFrontend = false;

    /**
     * @var Link
     **/
    protected $linkObject;

    /**
     * List the allowed included link types.  If null all are allowed.
     *
     * @var array
     **/
    protected $allowed_types = null;

    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = array(
        'LinkForm' => true,
        'LinkFormHTML' => true,
        'doSaveLink' => true,
        'doRemoveLink' => true
    );

    public function Field($properties = array())
    {
        Requirements::javascript(LINKS_PATH . '/javascript/linkfield.js');
        return parent::Field();
    }

    /**
     * The LinkForm for the dialog window
     *
     * @return Form
     **/
    public function LinkForm()
    {
        $link = $this->getLinkObject();

        $action = FormAction::create(
            'doSaveLink',
            _t('.SAVE', 'Save')
        )->setUseButtonTag('true');

        if (!$this->isFrontend) {
            $action->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept');
        }

        $link = null;
        if ($linkID = (int) $this->request->getVar('LinkID')) {
            $link = Link::get()->byID($linkID);
        }
        $link = $link ? $link : singleton('Link');
        $link->setAllowedTypes($this->getAllowedTypes());
        $fields = $link->getCMSFields();

        $title = $link ? _t('links.EDITLINK', 'Edit Link') : _t('links.ADDLINK', 'Add Link');
        $fields->insertBefore(HeaderField::create('LinkHeader', $title), _t('links.TITLE', 'Title'));
        $actions = FieldList::create($action);
        $form = Form::create($this, 'LinkForm', $fields, $actions);

        if ($link) {
            $form->loadDataFrom($link);
            $fields->push(HiddenField::create('LinkID', 'LinkID', $link->ID));
        }

        $this->extend('updateLinkForm', $form);

        return $form;
    }


    /**
     * Either updates the current link or creates a new one
     * Returns field template to update the interface
     * @return string
     **/
    public function doSaveLink($data, $form)
    {
        $link = $this->getLinkObject() ? $this->getLinkObject() : Link::create();
        $form->saveInto($link);
        try {
            $link->write();
        } catch (ValidationException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $form->forTemplate();
        }
        $this->setValue($link->ID);
        $this->setForm($form);
        return $this->FieldHolder();
    }


    /**
     * Delete link action - TODO
     *
     * @return string
     **/
    public function doRemoveLink()
    {
        $this->setValue('');
        return $this->FieldHolder();
    }


    /**
     * Returns the current link object
     *
     * @return Link
     **/
    public function getLinkObject()
    {
        $requestID = Controller::curr()->request->requestVar('LinkID');

        if ($requestID == '0') {
            return;
        }

        if (!$this->linkObject) {
            $id = $this->Value() ? $this->Value() : $requestID;
            if ((int) $id) {
                $this->linkObject = Link::get()->byID($id);
            }
        }
        return $this->linkObject;
    }

    /**
     * Returns the HTML of the LinkForm for the dialog
     *
     * @return string
     **/
    public function LinkFormHTML()
    {
        return $this->LinkForm()->forTemplate();
    }

    public function getIsFrontend()
    {
        return $this->isFrontend;
    }

    public function setIsFrontend($bool)
    {
        $this->isFrontend = $bool;
        return $this->this;
    }

    /**
     * Sets allowed link types
     *
     * @param string $type type name
     * @param string,... $types Additional type names
     * @return Link
     **/
    public function setAllowedTypes()
    {
        $this->allowed_types = func_get_args();
        return $this;
    }

    public function getAllowedTypes()
    {
        return $this->allowed_types;
    }
}
