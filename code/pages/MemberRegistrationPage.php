<?php

/*
 * Member Registration Page
 * @author Anton Fedianin aka Tony Air <tony@twma.pro>
 * https://tony.twma.pro/
 *
*/

//namespace Members\Accounts;

class MemberRegistrationPage extends Page
{
    private static $defaults = [
        'URLSegment' => 'register',
        'ShowInMenus' => 0,
        'ShowInSearch' => 0,
    ];

    public function canCreate($member = null)
    {
        if (self::get()->count() > 0) {
            return false;
        }

        return true;
    }

    public function requireDefaultRecords()
    {
        if ($this->canCreate()) {
            $className = get_class($this);
            $page = new $className();
            $page->setField('Title', _t($className.'DEFAULTTITLE', 'Register'));
            $page->setField('Content', _t($className.'DEFAULTCONTENT', '<p>Default registration page content. You can change it in the <a href="/admin/">CMS</a></p>'));
            $page->write();
            $page->publish('Stage', 'Live');
            $page->flushCache();
            DB::alteration_message($className.' page created', 'created');
        }
    }

    /**
     * Returns the link or the URLSegment to the account page on this site.
     *
     * @param bool $urlSegment Return the URLSegment only
     *
     * @return string Page Link
     */
    public static function find_link($urlSegment = false)
    {
        $page = self::get_if_account_page_exists();

        return ($urlSegment) ? $page->URLSegment : $page->Link();
    }

    /**
     * Returns the title of the account page on this site.
     *
     * @param bool $urlSegment Return the URLSegment only
     *
     * @return string page Title
     */
    public static function find_title()
    {
        $page = self::get_if_account_page_exists();

        return $page->Title;
    }

    protected static function get_if_account_page_exists()
    {
        $page = self::get();
        if ($page) {
            return $page->first();
        }
        user_error('No Member Registration Page was found. Please create one in the CMS!', E_USER_ERROR);
    }
}

class MemberRegistrationPage_Controller extends Page_Controller
{
    private static $allowed_actions = [
        'LoginForm',
        'Form',
    ];
    private static $url_segment = 'register';

    public function Link($action = null)
    {
        return Controller::join_links(
            Director::baseURL(), self::config()->url_segment, $action
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
        return MemberRegistrationForm::create($this, 'Form');
    }

    public function LoginForm(){
        $form = parent::LoginForm();

        $this->extend('updateLoginForm',$form);

        return $form;
    }
}
