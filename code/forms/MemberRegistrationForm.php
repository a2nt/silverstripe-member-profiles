<?php

namespace  A2nt\MemberProfiles\Forms;

use SilverStripe\Security\Member;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Session;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\Form;
use MemberProfiles\Forms\MemberRegistration_Validator;

/*
 * Member Registration Form
 * @author Anton Fedianin aka Tony Air <tony@twma.pro>
 * https://tony.twma.pro/
 *
*/

class MemberRegistrationForm extends Form
{
    private static $allowed_actions = [
        'register',
    ];

    public function __construct($controller, $name = 'MemberRegistrationForm', $fields = null)
    {
        if (!$fields) {
            $restrictFields = [
                Member::config()->unique_identifier_field,
                'FirstName',
                'Surname',
            ];
            $fields = singleton(Member::class)->scaffoldFormFields([
                'restrictFields' => $restrictFields,
                'fieldClasses' => [
                    'Email' => EmailField::class,
                ],
            ]);
        }
        $fields->push(ConfirmedPasswordField::create('Password'));

        $actions = FieldList::create(
            $register = FormAction::create('register', _t('MemberRegistrationForm.REGISTER', 'Register'))
        );
        $validator = MemberRegistration_Validator::create(
            Member::config()->unique_identifier_field,
            'FirstName',
            'Surname'
        );
        parent::__construct($controller, $name, $fields, $actions, $validator);

        if (class_exists('SpamProtectorManager')) {
            $this->enableSpamProtection([
                'name' => 'Math',
            ]);
            $required[] = 'Math';
        }

        $this->extend('updateMemberRegistrationForm');
    }

    public function register($data, $form)
    {

        // log out existing user
        $member = Member::currentUser();
        if ($member) {
            $member->logOut();
        }

        $member = Member::create();
        $form->saveInto($member);
        $member->write();
        $this->extend('onRegister', $data, $form, $member);

        $result = $member->canLogIn();
        if ($result->valid()) {
            $member->logIn();
        } else {
            return $this->controller->redirectBack();
        }

        $back = Session::get('BackURL');
        if ($back) {
            Session::clear('BackURL');

            return Controller::curr()->redirect($back);
        }

        $link = $member->getProfileLink();
        if ($link) {
            return $this->controller->redirect($link);
        }

        return $this->controller->redirect($this->controller->Link());
    }
}
