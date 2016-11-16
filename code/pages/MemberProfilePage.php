<?php

/*
 * Member Profile Page
 * @author Anton Fedianin aka Tony Air <tony@twma.pro>
 * https://tony.twma.pro/
 *
*/

class MemberProfilePage extends Page
{
    private static $defaults = [
        'URLSegment' => 'profile',
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

    /**
     * Returns the link or the URLSegment to the account page on this site.
     *
     * @param bool $urlSegment Return the URLSegment only
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
     */
    public static function find_title()
    {
        $page = self::get_if_account_page_exists();

        return $page->Title;
    }

    protected static function get_if_account_page_exists()
    {
        if ($page = self::get()) {
            return $page->First();
        }
        user_error('No Profile Page was found. Please create one in the CMS!', E_USER_ERROR);
    }

    public function requireDefaultRecords()
    {
        if ($this->canCreate()) {
            $className = get_class($this);
            $page = new $className();
            $page->setField('Title', _t($className.'DEFAULTTITLE', 'Profile'));
            $page->setField('Content', _t($className.'DEFAULTCONTENT', '<p>Default page content. You can change it in the <a href="/admin/">CMS</a></p>'));
            $page->write();
            $page->publish('Stage', 'Live');
            $page->flushCache();
            DB::alteration_message($className.' page created', 'created');
        }
    }
}

class MemberProfilePage_Controller extends Page_Controller
{
    private static $allowed_actions = [
        'index' => true,
        'edit' => true,
        'MemberEditProfileForm' => '->canEditProfile',
        'updatedetails' => '->canEditProfile',
        'sendpassword' => '->canEditProfile',
    ];

    private static $url_segment = 'profile';
    private static $action_template = 'ProfileMembership';

    protected $member = null;

    public function init()
    {
        parent::init();
        if (!$this->member) {
            $currentuser = Member::currentUser();
            if (!$currentuser) {
                return Security::permissionFailure(
                    $this,
                    _t('MemberProfilePage.MUSTBELOGGEDIN', 'You must log in to view your profile.')
                );
            } else {
                $this->member = $currentuser;
            }
        }
    }

    public function getMember()
    {
        return $this->member;
    }

    public function setMember($member)
    {
        $this->member = $member;

        return $this;
    }

    public static function getStaticTitle()
    {
        $class = get_called_class();

        $lang_var = ($class === 'MemberProfilePage_Controller')
            ? 'MemberProfilePage.Title'
            : str_replace('_Controller', '', $class).'.Title';

        return _t(
            $lang_var,
            trim(preg_replace('/[A-Z]/', ' $0', str_replace('_Controller', '', $class)))
        );
    }

    public function getTitle()
    {
        return self::getStaticTitle();
    }

    public function Link($params = null)
    {
        $class = get_class($this);
        $action = ($class === 'MemberProfilePage_Controller') ? '' : $class;

        return self::join_links(MemberProfilePage::find_link(), $action, $params, '/');
    }

    public function getViewer($action)
    {
        return new SSViewer(['MemberProfilePage', 'Page']);
    }

    public function edit()
    {
        if (!$this->canEditProfile()) {
            return Security::permissionFailure(
                $this,
                _t('MemberProfilePage.NOPERMISSIONS', 'You do not have permission to edit this profile.')
            );
        }

        return [
            'Title' => _t('MemberProfilePage.EDITPROFILE', 'Edit Profile'),
            'Content' => '',
            'Form' => $this->MemberEditProfileForm(),
        ];
    }

    public function MemberEditProfileForm()
    {
        return MemberEditProfileForm::create($this, 'MemberEditProfileForm', $this->member);
    }

    public function canEditProfile()
    {
        return (bool) $this->member && $this->member->canEdit(
            Member::currentUser()
        );
    }

    public function ProfileArea()
    {
        $class = get_class($this);
        $template = (Config::inst()->get($class, 'action_template'))
            ? Config::inst()->get($class, 'action_template')
            : $class;

        $action = $this->request->param('Action');
        $template .= ($action) ? '_'.$action : '';

        return $this->renderWith($template);
    }

    public static function ProfileMenu()
    {
        $curr = get_class(Controller::curr());
        $classes = self::get_type_classes();

        $menu = [];
        $menu[] = [
            'Link' => self::join_links(MemberProfilePage::find_link()),
            'Title' => _t('MemberProfilePage.Title', 'Profile'),
            'Status' => ($curr === 'MemberProfilePage_Controller') ? 'active' : '',
        ];

        // if child classes exists make links
        if (is_array($classes)) {
            foreach ($classes as $class) {
                if ($class::canView()) {
                    $menu[] = [
                        'Link' => self::join_links(MemberProfilePage::find_link(), $class),
                        'Title' => $class::getStaticTitle(),
                        'Status' => ($curr === $class) ? 'active' : '',
                    ];
                }
            }
        }

        return new ArrayList($menu);
    }

    public function MetaTags($includeTitle = true)
    {
        return parent::MetaTags($includeTitle)
            .'<meta name="robots" content="noindex" />';
    }

    public function handleRequest(SS_HTTPRequest $request, DataModel $model = null)
    {
        $response = parent::handleRequest($request, $model);

        $action = $request->param('Action');
        if ($this->hasProfileController($action)) {
            $request->shiftAllParams();
            $controller = Injector::inst()->create($action, $this->dataRecord);
            $this->pushCurrent();

            return $controller->handleRequest($request, $this->model);
        }

        return $response;
    }

    public function hasProfileController($controller)
    {
        $classes = $this->get_type_classes();
        if (is_array($classes)) {
            return in_array($controller, $classes);
        }

        return false;
    }

    /* gets MemberProfilePage_Controller classes and removes by hide_ancestor */
    private static function get_type_classes()
    {
        $classes = ClassInfo::subclassesFor('MemberProfilePage_Controller');
        if (count($classes) === 1) {
            return false;
        }

        $baseClassIndex = array_search('MemberProfilePage_Controller', $classes);
        if ($baseClassIndex !== false) {
            unset($classes[$baseClassIndex]);
        }

        $kill_ancestors = [];
        // figure out if there are any classes we don't want to appear
        foreach ($classes as $class) {
            $instance = singleton($class);
            // do any of the progeny want to hide an ancestor?
            if ($ancestor_to_hide = $instance->stat('hide_ancestor')) {
                // note for killing later
                $kill_ancestors[] = $ancestor_to_hide;
            }
        }

        // If any of the descendents don't want any of the elders to show up, cruelly render the elders surplus to requirements.
        if ($kill_ancestors) {
            $kill_ancestors = array_unique($kill_ancestors);
            foreach ($kill_ancestors as $mark) {
                // unset from $classes
                $idx = array_search($mark, $classes);
                unset($classes[$idx]);
            }
        }

        return $classes;
    }
}
