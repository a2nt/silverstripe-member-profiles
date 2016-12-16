<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 12/16/16
 * Time: 1:59 PM
 */

class ProfileItemForm extends Form
{
    public function __construct(Controller $controller, $name)
    {
        $model = $controller->getModel();
        $item = $controller->getItem();

        if ($item) {
            if (!$item->canEdit()) {
                return Security::permissionFailure();
            }
            $btn_content = '<i class="fa fa-pencil"></i> '._t('ProfileCRUD.EDITITEM', 'Save');
        } else {
            $item = $item ? $item : singleton($model);
            $btn_content = '<i class="fa fa-plus"></i> '._t('ProfileCRUD.NEWITEM', 'Make New');
        }

        $fields = $item->getFrontEndFields();

        if ($item->isInDB()) {
            $fields->push(HiddenField::create('ID'));
        }
        $fields->push(HiddenField::create('ModelClass', '', $model));

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
            RequiredFields::create($item::config()->get('required_fields'))
        );

        $this->loadDataFrom($item);

        if ($item->getField('ID')) {
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

        $this->extend('updateForm');

        return $this;
    }

    public function doEdit(array $data)
    {
        $modelClass = $this->getController()->getModel();
        $item = $this->getController()->getItem();

        if (!Permission::check('CREATE_'.$modelClass)) {
            return Security::permissionFailure();
        }

        if ($item) {
            if (!$item->canEdit()) {
                $this->extend('updateItemEditDenied', $item);

                return $this->getController()->redirect($this->getController()->Link());
            }
        } else {
            $item = singleton($modelClass);
        }


        $this->saveInto($item);
        if (method_exists($item, 'preprocessData')) {
            $item->preprocessData($data, $this);
        }

        $validator = $item->validate();
        if ($validator->valid()) {
            $new = $item->getField('ID') ? true : false;
            $item->write();
            $this->extend('updateItemEditSuccess', $item, $data, $new);

            return $this->getController()->redirect($item->getViewLink());
        } else {
            $this->setMessage(nl2br($validator->starredList()), 'bad', false);
            $this->extend('updateItemEditError', $validator);

            return $this->getController()->redirectBack();
        }
    }

    public function doDelete()
    {
        $item = $this->getController()->getItem();
        if ($item->getField('ID') && $item->canEdit()) {
            $item->delete();

            $this->extend('updateItemRemoved', $item);
            return $this->getController()->redirect($this->getController()->Link());
        }

        return $this->getController()->httpError(404);
    }
}
