<?php

/**
 * Object management forms and.
 */
class ProfileCRUD extends ProfileController
{
    private static $hide_ancestor = true;

    private static $managed_models = [
    ];

    private static $allowed_actions = [
        'newItem',
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

    /**
     * @var String
     */
    protected $actionParam;

    // Init
    public function setupVariables()
    {
        if (!parent::setupVariables()) {
            return false;
        }

        $this->extend('setupVariables');

        $modelClass = $this->getModel();

        if ($modelClass) {
            if (!in_array($modelClass, $this->stat('managed_models'))) {
                $this->httpError(404, 'Model '.$modelClass.' isn\'t available.');

                return false;
            }

            // allow new/$ID if you need to add object to a specific ID
            $action = $this->getActionParam();
            switch ($action) {
                case 'view':
                case 'edit':
                case 'delete':
                    $item = $this->getItem();
                    if (!$item) {
                        $this->httpError(404, 'Model '.$modelClass.' isn\'t available.');

                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /*
     * Getters and Setters
     */

    /**
     * Lower case action name to do switch-case on action.
     *
     * @return mixed|string
     */
    public function getActionParam()
    {
        if (!$this->actionParam) {
            $this->actionParam = strtolower($this->request->param('Action'));
        }

        return $this->actionParam;
    }

    /**
     * @return mixed|string
     */
    public function getModel()
    {
        if (!$this->modelClass) {
            $modelClass = $this->request->param('ModelClass');
            $req = $this->request->requestVar('ModelClass');
            $modelClass = $req ? $req : $modelClass;

            $this->modelClass = $modelClass;
        }

        return $this->modelClass;
    }

    /**
     * @param string $model
     */
    public function setModel($model)
    {
        $this->modelClass = $model;
    }

    /**
     * @return DataObject
     */
    public function getItem()
    {
        if (!$this->item && $this->modelClass) {
            $modelClass = $this->modelClass;
            $ID = $this->request->param('ID');
            $req = $this->request->requestVar('ID');
            $ID = $req ? $req : $ID;

            if ($this->getActionParam() !== 'newitem') {
                $this->setItem($modelClass::get()->byID($ID));
            }
        }

        return $this->item;
    }

    /**
     * @param DataObject|null $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    public function ListItems($class = null)
    {
        $class = $class ? $class : $this->modelClass;

        return $class::get();
    }

    public static function getNewItemLink($class, $params = null)
    {
        return self::join_links($class, 'newItem', $params);
    }

    /*
     * Permission checks
     */
    public function providePermissions()
    {
        $permissions = parent::providePermissions();

        $models = self::config()->get('managed_models', Config::UNINHERITED);
        foreach ($models as $model) {
            $permissions['CREATE_'.$model] = 'Create '.$model;
        }

        return $permissions;
    }

    public static function canCreate($class)
    {
        return Permission::check('CREATE_'.$class);
    }

    public function newItem()
    {
        if (!self::canCreate($this->getModel())) {
            return Security::permissionFailure();
        }

        return $this->render();
    }

    public function ItemForm()
    {
        return ProfileItemForm::create($this, 'ItemForm');
    }

    public function FormObjectLink($name)
    {
        return Controller::join_links($this->Link(), $this->getModel(), $name);
    }

    /*
     * Actions
     */

    public function view()
    {
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
        if ($item && $item->canEdit()) {
            $item->delete();

            $this->extend('updateItemRemoved', $item);

            return $this->redirect($this->Link());
        } else {
            $this->extend('updateItemRemoveDenied', $item);

            return $this->redirect($this->Link());
        }
    }
}
