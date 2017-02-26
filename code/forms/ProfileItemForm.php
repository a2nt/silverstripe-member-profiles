<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 12/16/16
 * Time: 1:59 PM
 */

class ProfileItemForm extends Form
{
    private $item;
    private $itemClass;

    public function __construct(Controller $controller, $name, $model = null, $item = null)
    {
        $this->itemClass = $model ? $model : $controller->getModel();
        $this->item = $item ? $item : $controller->getItem();

        if ($this->item && $this->item->getField('ID')) {
            if (!$this->item->canEdit()) {
                return Security::permissionFailure();
            }
            $btn_content = '<i class="fa fa-pencil"></i> '._t('ProfileCRUD.EDITITEM', 'Save');
        } else {
            $this->item = $this->item ? $this->item : singleton($this->itemClass);
            $btn_content = '<i class="fa fa-plus"></i> '._t('ProfileCRUD.NEWITEM', 'Make New');
        }

        $fields = $this->item->getFrontEndFields();

        $fields->unshift(LiteralField::create(
            'ItemFormNote',
            '<div class="item-form-note">'._t(get_class($this->item).'.ItemFormNote').'</div>'
        ));

        if ($this->item->isInDB()) {
            $fields->push(HiddenField::create('ID'));
        }

        $fields->push(HiddenField::create('ModelClass', '', $this->itemClass));

        $model = $this->itemClass;
        parent::__construct(
            $controller,
            $name,
            $fields,
            $actions = FieldList::create(
                FormAction::create(
                    'doEdit',
                    _t('Page.SubscribeEmailSubmit', 'Submit')
                )
                    ->setAttribute(
                        'title',
                        _t('Page.SubscribeEmailSubmit', 'Submit')
                    )
                    ->addExtraClass('btn '.Page_Controller::getBtnClass())
                    ->setButtonContent($btn_content)
            ),
            RequiredFields::create($model::config()->get('required_fields'))
        );

        $this->loadDataFrom($this->item);
        $this->addExtraClass('item-'.$this->itemClass);

        if ($this->item->getField('ID')) {
            $actions->push(FormAction::create(
                'doDelete',
                _t('Profile_Controller.DELETEITEM', 'Delete')
            )
                ->setAttribute(
                    'title',
                    _t('ProfileCRUD.DELETEITEM', 'Delete')
                )
                ->addExtraClass('btn btn-danger')
                ->setButtonContent(
                    '<i class="fa fa-times"></i> '
                    ._t('ProfileCRUD.DELETEITEM', 'Delete')
                )
            );
        }

        // preset member info
        foreach ($fields as $field) {
            $val = $field->Value();
            if (
                $field->Name != 'Title'
                && !is_object($val)
                && $val === null
            ) {
                $member = $controller->getMember();
                $val = $member->getField($field->getName());
                if (!is_object($val) && $val !== null) {
                    $field->setValue($val);
                }
            }
        }

        // let's u create rewrite fields values
        if (method_exists($this->item, 'customDataFields')) {
            $this->item->customDataFields($this);
        }

        $this->extend('updateForm');

        return $this;
    }

    public function doEdit(array $data)
    {
        if (!Permission::check('CREATE_'.$this->itemClass)) {
            return Security::permissionFailure();
        }

        if ($this->item && $this->item->getField('ID')) {
            if (!$this->item->canEdit()) {
                $this->extend('updateItemEditDenied', $this->item);

                return $this->getController()->redirect($this->getController()->Link());
            }
        } else {
            $this->item = singleton($this->itemClass);
        }


        $this->saveInto($this->item);

        if (method_exists($this->item, 'preprocessData')) {
            $this->item->preprocessData($data, $this);
        }

        $validator = $this->item->validate();
        if ($validator->valid()) {
            $new = $this->item->getField('ID') ? false : true;
            $this->item->write();
            $this->extend('updateItemEditSuccess', $this->item, $data, $new);

            return $this->getController()->redirect($this->item->getViewLink());
        } else {
            $this->setMessage(nl2br($validator->starredList()), 'bad', false);
            $this->extend('updateItemEditError', $validator);

            return $this->getController()->redirectBack();
        }
    }

    public function doDelete()
    {
        if ($this->item->getField('ID') && $this->item->canEdit()) {
            $this->item->delete();

            $this->extend('updateItemRemoved', $this->item);
            return $this->getController()->redirect($this->getController()->Link());
        }

        return $this->getController()->httpError(404);
    }
}
