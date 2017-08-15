<?php

namespace A2nt\MemberProfiles\Controllers;

use PageController;

class MemberRegistrationPageController extends PageController
{

    use \SilverStripe\Core\Injector\Injectable;
    use \SilverStripe\Core\Config\Configurable;

    private static $allowed_actions = [
        'LoginForm',
        'Form',
    ];
    private static $url_segment = 'register';

    public function Link($action = null)
    {
        return Controller::join_links(
            Director::baseURL(),
            self::config()->url_segment,
            $action
        );
    }

    public function init()
    {
        $backurl = $this->getRequest()->getVar('BackURL');
        if ($backurl) {
            Session::set('BackURL', $backurl);
        }
        parent::init();
    }

    public function Form()
    {
        return MemberRegistrationForm::create($this, Form::class);
    }

    public function LoginForm()
    {
        $form = parent::LoginForm();

        $this->extend('updateLoginForm', $form);

        return $form;
    }
}
