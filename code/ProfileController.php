<?php

/**
 * Basic Profile Area Controller.
 */
class ProfileController
    extends Controller
    implements PermissionProvider
{
    private static $menu_icon = '<i class="fa fa-user"></i>';
    private static $menu_title = 'Profile';
    private static $url_segment = 'profile';
    private static $template_main = 'ProfileController';

    private static $url_handlers = [
        '' => 'index',
        '$ProfileController!/$Action/$ID/$OtherID' => 'handleController',
    ];

    private static $allowed_actions = [
        'index' => true,
        'handleController' => true,
    ];

    private static $requirements_css = [];
    private static $requirements_javascript = [];

    protected $member;
    protected $response_controller;
    protected static $profile_classes;

    public function init()
    {
        parent::init();

        if (!$this->member) {
            $currentUser = Member::currentUser();
            if (!$currentUser) {
                return Security::permissionFailure(
                    $this,
                    _t('ProfileController.MUSTBELOGGEDIN', 'You must log in to view your profile.')
                );
            } else {
                $this->setMember($currentUser);
            }
        }

        Requirements::combine_files(get_class($this).'.css', $this->stat('requirements_css'));
        Requirements::combine_files(get_class($this).'.js', $this->stat('requirements_javascript'));

        $this->extend('init');
    }

    public function providePermissions()
    {
        $class = get_class($this);
        if (!self::config()->get('hide_ancestor', Config::UNINHERITED)) {
            return [
                'VIEW_'.$class => 'Access to '.$this->Title(),
            ];
        }
    }

    public static function canView()
    {
        return Permission::check('VIEW_'.get_called_class());
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

    public function Link($params = null)
    {
        $class = get_class($this);
        $action = ($class === get_class($this)) ? '' : $class;

        return self::join_links(
            $action,
            $params
        );
    }

    public static function join_links()
    {
        $class = get_called_class();

        $url_segment = self::config()->get('url_segment', Config::UNINHERITED);
        $url_segment = $url_segment ? $url_segment : $class;

        $args = array_merge([
            $url_segment,
        ], func_get_args());

        $arg = implode('/', $args);

        return ($class !== 'ProfileController')
            ? ProfileController::join_links($arg)
            : Controller::join_links($arg);
    }

    public function Title($class = null)
    {
        $class = $class ? $class : get_class($this);

        $configTitle = Config::inst()->get($class, 'menu_title', Config::UNINHERITED);

        $fallback_title = $configTitle
            ? $configTitle
            : trim(preg_replace('/[A-Z]/', ' $0', $class));

        return _t(
            $class.'.TITLE',
            $fallback_title
        );
    }

    public function Icon($class = null)
    {
        $class = $class ? $class : get_class($this);

        return Config::inst()->get($class, 'menu_icon', Config::UNINHERITED);
    }

    /**
     * Gets Profile Area Layout.
     *
     * @param $action string
     *
     * @return HTMLText
     */
    public function ProfileArea($action = null)
    {
        $class = $this->getProfileClass();
        $template = $class;
        $action = $action ? $action : $this->request->param('Action');
        $template .= ($action) ? '_'.$action : '';

        if ($template === 'ProfileController') {
            $template .= '_index';
        }

        return $this->renderWith($template);
    }

    public function getProfileClass()
    {
        return get_class($this);
    }

    /**
     * Gets Profile Menu.
     *
     * @return ArrayList
     */
    public static function ProfileMenu()
    {
        if(!Member::currentUser()){
            return false;
        }

        $curr = get_class(Controller::curr());
        $classes = self::get_profile_classes();
        $profile_controller = singleton('ProfileController');
        $config = Config::inst();

        $menu = ArrayList::create([]);
        $menu->push(ArrayData::create([
            'ClassName' => 'ProfileController',
            'Link' => $profile_controller->Link(),
            'Icon' => $profile_controller->Icon(),
            'Title' => $profile_controller->Title('ProfileController'),
            'Status' => ($curr === 'ProfileController') ? 'active link' : 'link',
        ]));

        // if child classes exists make links
        if (is_array($classes)) {
            foreach ($classes as $class) {
                if ($class::canView() && !$config->get($class, 'hide_menu', Config::UNINHERITED)) {
                    $menu->push(ArrayData::create([
                        'ClassName' => $class,
                        'Link' => $profile_controller->Link($class),
                        'Icon' => $profile_controller->Icon($class),
                        'Title' => $profile_controller->Title($class),
                        'Status' => ($curr === $class) ? 'link active' : 'link',
                    ]));
                }
            }
        }

        return $menu;
    }

    /**
     * Blocks indexing of profile areas.
     *
     * @param $includeTitle boolean
     *
     * @return string
     */
    public function MetaTags($includeTitle = true)
    {
        $tags = $this->getResponseController()
            ->MetaTags($includeTitle);
        $tags .= '<meta name="robots" content="noindex" />';

        return $tags;
    }

    /**
     * Handles requests by Profile Controller.
     *
     * @param $request SS_HTTPRequest
     *
     * @return SS_HTTPRequest
     */
    public function handleController(SS_HTTPRequest $request)
    {
        $controller_class = $request->param('ProfileController');

        if (
            get_class($this) !== $controller_class
            && $this->hasProfileController($controller_class)
        ) {
            $controller = Injector::inst()->create($controller_class, DataModel::inst());

            // remove first 2 pieces of URL and process request
            $request->setUrl(implode('/', array_slice(explode('/', $request->getURL()), 2)));

            return $controller->handleRequest($request, DataModel::inst());
        }

        $action = $controller_class;
        if (!$this->hasAction($action)) {
            return $this->httpError(404, 'Action '.$action.' isn\'t available.');
        }
        if (!$this->checkAccessAction($action) || in_array(strtolower($action), ['run', 'init'])) {
            return $this->httpError(403, 'Action '.$action.' isn\'t allowed.');
        }

        $result = $this->handleAction($request, $action);

        return $result;
    }

    /**
     * Let's you check params and the other variables
     * for example ProfileCRUD checks managed_models and IDs being set
     * if request has ModelClass param it shall be manageable
     * if it's edit or view request item with specified ID shall exist.
     *
     * @return bool
     */
    public function setupVariables()
    {
        return true;
    }

    protected function handleAction($request, $action)
    {
        if (!$this->setupVariables()) {
            return $this->httpError(404, 'Not available.');
        }

        return parent::handleAction($request, $action);
    }

    /**
     * Gets ProfileController sub-classes and except some of them hidden by hide_ancestor.
     *
     * @return array
     */
    private static function get_profile_classes()
    {
        if (self::$profile_classes) {
            return self::$profile_classes;
        }

        $classes = ClassInfo::subclassesFor('ProfileController');
        if (count($classes) === 1) {
            return [];
        }

        $baseClassIndex = array_search('ProfileController', $classes);
        if ($baseClassIndex !== false) {
            unset($classes[$baseClassIndex]);
        }

        $kill_ancestors = [];
        $config = Config::inst();
        // figure out if there are any classes we don't want to appear
        foreach ($classes as $class) {
            // do any of the progeny want to hide an ancestor?
            $ancestor_to_hide = $config->get($class, 'hide_ancestor', Config::UNINHERITED);
            if ($ancestor_to_hide) {
                // note for killing later
                $kill_ancestors[] = $class;
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

        self::$profile_classes = $classes;

        return self::$profile_classes;
    }

    /**
     * Checks if Profile Controller exists.
     *
     * @param string $controller name
     *
     * @return bool
     */
    protected function hasProfileController($controller)
    {
        $classes = self::get_profile_classes();
        if (
            is_array($classes)
            && is_subclass_of($controller, get_class($this))
        ) {
            return in_array($controller, $classes);
        }

        return false;
    }

    /**
     * Prepare the controller for handling the response to this request.
     *
     * @return Page_Controller
     */
    public function getResponseController()
    {
        if ($this->response_controller) {
            return $this->response_controller;
        }

        // Use sitetree pages to render the page
        $tmpPage = Page::create();
        $tmpPage->Title = $this->Title();
        // Disable ID-based caching  of the log-in page by making it a random number
        $tmpPage->ID = -1 * rand(1, 10000000);
        $tmpPage->ProfileClass = $this->getProfileClass();

        $controller = Page_Controller::create($tmpPage);
        $controller->setDataModel($this->model);
        Config::inst()->update('Member', 'log_last_visited', false);
        $controller->init();
        $this->response_controller = $controller;

        return $this->response_controller;
    }

    public function LayoutAjax()
    {
        $template = $this->stat('template_main');

        return $this->renderWith($template);
    }

    public function index()
    {
        $controller = $this->getResponseController();

        // if the controller calls Director::redirect(), this will break early
        if (($response = $controller->getResponse()) && $response->isFinished()) {
            return $response;
        }

        if ($this->hasExtension('AjaxControllerExtension') && Director::is_ajax()) {
            //$link = $action === 'index' ? $this->Link() : $this->Link($action);
            $getVars = $this->request->getVars();

            // TODO: generate link using $this->Link() method
            $link = $getVars['url'];

            unset($getVars['url']);
            unset($getVars['_']);

            if (count($getVars)) {
                $link .= '?';
                foreach ($getVars as $k => $v) {
                    $link .= substr($link, -1) !== '?' ? '&'.$k.'='.$v : $k.'='.$v;
                }
            }

            $title = $this->Title();

            $response = $this->getAjaxResponse();

            $response->addHeader('X-Title', $title);
            $response->addHeader('X-Link', $link);

            $response->triggerEvent('layoutRefresh', [
                'Title' => $title,
                'Link' => $link,
            ]);
            $response->pushRegion('LayoutAjax');

            return $response;
        }

        return $controller
            ->customise($this)
            ->renderWith([
                $this->stat('template_main'),
                'Page',
            ]);
    }

    /**
     * Render the current controller with the templates determined
     * by {@link getViewer()}.
     *
     * @param array $params Key-value array for custom template variables (Optional)
     *
     * @return string Parsed template content
     */
    public function render($params = null)
    {
        $controller = $this->getResponseController();

        if ($this->hasExtension('AjaxControllerExtension') && Director::is_ajax()) {
            //$link = $action === 'index' ? $this->Link() : $this->Link($action);
            $getVars = $this->request->getVars();

            // TODO: generate link using $this->Link() method
            $link = $getVars['url'];

            unset($getVars['url']);
            unset($getVars['_']);

            if (count($getVars)) {
                $link .= '?';
                foreach ($getVars as $k => $v) {
                    $link .= substr($link, -1) !== '?' ? '&'.$k.'='.$v : $k.'='.$v;
                }
            }

            $title = $this->Title();

            $response = $this->getAjaxResponse();

            $response->addHeader('X-Title', $title);
            $response->addHeader('X-Link', $link);

            $response->triggerEvent('layoutRefresh', [
                'Title' => $title,
                'Link' => $link,
            ]);
            $response->pushRegion('LayoutAjax');

            return $response;
        }

        return $controller
            ->customise($this)
            ->renderWith([
                $this->stat('template_main'),
                'Page',
            ]);
    }

    /**
     * Throws a HTTP error response encased in a {@link SS_HTTPResponse_Exception}, which is later caught in
     * {@link RequestHandler::handleAction()} and returned to the user.
     *
     * @param int    $errorCode
     * @param string $errorMessage Plaintext error message
     *
     * @uses SS_HTTPResponse_Exception
     */
    public function httpError($errorCode, $errorMessage = null)
    {
        $response = ErrorPage::response_for($errorCode);

        return parent::httpError($errorCode, $response ? $response : $errorMessage);
    }
}
