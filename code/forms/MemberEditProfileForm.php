<?php

/*
 * Member Profile Editor Form
 * @author Anton Fedianin aka Tony Air <tony@twma.pro>
 * https://tony.twma.pro/
 *
*/

class MemberEditProfileForm extends Form
{
    protected $member;

    public function __construct($controller, $name, Member $member)
    {
        $this->member = $member;

        $fields = $this->member->getMemberFormFields();

        $fields->push(HiddenField::create('ID', 'ID', $this->member->ID));
        $fields->removeByName('Password');
        $actions = FieldList::create(
            FormAction::create(
                'updatedetails',
                _t('MemberEditProfileForm.UPDATE', 'Update')
            )
        );

        $validator = Member_Validator::create(
            'FirstName',
            'Surname',
            'Email'
        );
        parent::__construct($controller, $name, $fields, $actions, $validator);

        $passwordfield = $this->getChangePasswordField();
        if ($passwordfield) {
            $fields->push($passwordfield);
        }

        $this->extend('updateMemberEditProfileForm');

        // use it if you need to add specific member fields for different types of members for an instance
        $this->member->extend('updateMemberEditProfileForm', $this);

        $this->loadDataFrom($this->member);
    }

    public function updatedetails($data, $form)
    {
        $form->saveInto($this->member);
        if (Member::config()->send_frontend_update_notifications) {
            $this->sendUpdateNotification($data);
        }
        $this->member->write();
        $form->sessionMessage(_t('MemberEditProfileForm.UPDATED', 'Your member details have been updated.'), 'good');

        return $this->controller->redirectBack();
    }

    public function sendUpdateNotification($data)
    {
        $body = _t(
            'MemberEditProfileForm.UPDATEDMSGCONTENT',
            '{name} has updated their details via the website. Here is the new information:<br/>',
            ['name' => $this->member->getName()]
        );

        $notifyOnFields = Member::config()
            ->frontend_update_notification_fields ?: DataObject::database_fields('Member');

        $changedFields = $this->member->getChangedFields(true, 2);
        $send = false;

        foreach ($changedFields as $key => $field) {
            if (in_array($key, $notifyOnFields)) {
                $body .= "<br/><strong>$key:</strong><br/>".
                    "<b style='color:red;'>".$field['before'].'</b><br/>'.
                    "<span style='color:green;'>".$field['after'].'</span><br/>';
                $send = true;
            }
        }

        if ($send) {
            $email = StyledHtmlEmail::create(
                Email::config()->admin_email,
                Email::config()->admin_email,
                _t(
                    'MemberEditProfileForm.UPDATEDMSGTITLE',
                    'Member details update: {name}',
                    ['name' => $this->member->getName()]
                ),
                $body
            );
            $email->send();
        }
    }

    protected function getChangePasswordField()
    {
        if ($this->member->ID != Member::currentUserID()) {
            return false;
        }

        return LiteralField::create(
            'ChangePasswordLink',
            '<div class="field"><p>
                    <a href="Security/changepassword?BackURL='.$this->controller->Link().'">'
                        ._t('MemberEditProfileForm.CHANGEPASSWORD', 'Сhange password')
                    .'</a>
                </p>
            </div>'
        );
    }
}
