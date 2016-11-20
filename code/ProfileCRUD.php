<?php
/**
 * Object management forms and
 *
 */

class ProfileCRUD extends ProfileController
{
    private static $hide_ancestor = true;

    private static $managed_models = [
    ];

    private static $allowed_actions = [
        'newitem',
        'view',
        'edit',
        'delete',
        'ItemForm',
    ];

    private static $url_handlers = [
        '$ModelClass!/ItemForm' => 'ItemForm',
        '$ModelClass!/$Action!/$ID/$OtherID' => 'handleAction',
    ];

    /**
     * @var DataObject
     */
    private $item;

    /**
     * @var String
     */
    protected $modelClass;

    public function setupVariables()
    {
        parent::setupVariables();
        $this->extend('setupVariables');

        $modelClass = $this->request->param('ModelClass');
        $req = $this->request->requestVar('ModelClass');
        $modelClass = $req ? $req : $modelClass;

        if ($modelClass) {
            if (!in_array($modelClass, $this->stat('managed_models'))) {
                $this->httpError(404, 'Model ' . $modelClass . ' isn\'t available.');
                return false;
            }
            $this->modelClass = $modelClass;

            // allow new/$ID if you need to add object to a specific ID
            $action = $this->request->param('Action');
            switch ($action) {
                case 'view':
                case 'edit':
                case 'delete':
                    $ID = $this->request->param('ID');
                    $req = $this->request->requestVar('ModelClass');
                    $ID = $req ? $req : $ID;

                    $item = $modelClass::get()->byID($ID);
                    if (!$item) {
                        $this->httpError(404, 'Model ' . $modelClass . ' isn\'t available.');
                        return false;
                    }
                    $this->setItem($item);
                break;
            }
        }


        return true;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setItem($item)
    {
        $this->item = $item;
    }

    public function newitem()
    {
        if (!Permission::check('CREATE_'.$this->modelClass)) {
            return Security::permissionFailure();
        }

        return $this->render();
    }

    public function view()
    {
        if (!Permission::check('VIEW_'.$this->modelClass)) {
            return Security::permissionFailure();
        }

        return $this->render();
    }

    public function edit()
    {
        if (!$this->getItem()->canEdit()) {
            return Security::permissionFailure();
        }

        return $this->render();
    }

    public function delete()
    {
        $item = $this->getItem();
        if ($item->canEdit()) {
            $item->delete();

            $this->extend('updateItemRemoved', $item);

            return $this->redirect($this->Link());
        } else {
            $this->extend('updateItemRemoveDenied', $item);

            return $this->redirect($this->Link());
        }

        //return $this->httpError(404);
    }

    public function ItemForm()
    {
        $model = $this->modelClass;
        $item = ($this->item) ? $this->item : singleton($model);
        $btn_content = '<i class="fa fa-plus"></i> '._t('ProfileCRUD.NEWITEM', 'Make New');

        $fields = $item->getFrontEndFields();
        if ($this->item) {
            $fields->push(HiddenField::create('ID'));
            $btn_content = '<i class="fa fa-pencil"></i> '._t('ProfileCRUD.EDITITEM', 'Save');
            if (!$this->item->canEdit()) {
                return Security::permissionFailure();
            }
        }

        $fields->push(HiddenField::create('ModelClass', '', $model));

        $form = Form::create(
            $this,
            'ItemForm',
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
            RequiredFields::create($item->config()->required_fields)
        )
            ->loadDataFrom($item);


        if ($this->item) {
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
                $member = $this->getMember();
                $val = $member->{$field->getName()};
                if (!is_object($val) && $val !== null) {
                    $field->setValue($val);
                }
            }
        }

        $this->extend('updateItemForm', $form);

        return $form;
    }

    public function FormObjectLink($name)
    {
        return Controller::join_links($this->Link(), $this->modelClass, $name);
    }

    public function doEdit(array $data, Form $form)
    {
        $new = false;
        $ID = isset($data['ID']) ? $data['ID'] : null;
        $modelClass = $this->modelClass;

        if (!Permission::check('CREATE_'.$modelClass)) {
            return Security::permissionFailure();
        }

        if ($ID) {
            if (!$item = $modelClass::get()->byID($ID)) {
                return $this->httpError(404);
            }
            $new = true;
        } else {
            $item = singleton($modelClass);
        }

        if (!$item->canEdit()) {
            $this->extend('updateItemEditDenied', $item);

            return $this->redirect($this->Link());
        }

        $form->saveInto($item);
        if (method_exists($item, 'preprocessData')) {
            $item->preprocessData($data, $form);
        }

        $validator = $item->validate();
        if ($validator->valid()) {
            $item->write();

            $this->extend('updateItemEditSuccess', $item, $data, $new);

            return $this->redirect($item->getViewLink());
        } else {
            Page_Controller::setSiteMessage(nl2br($validator->starredList()), 'danger');

            return $this->redirectBack();
        }
    }

    public function doDelete()
    {
        $item = $this->getItem();
        if ($item->ID && $item->canEdit()) {
            $item->delete();

            $this->extend('updateItemRemoved', $item);
            return $this->redirect($this->Link());
        }

        return $this->httpError(404);
    }
}
