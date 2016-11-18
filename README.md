# SilverStripe Profile Area Module

A simplified light-weight alternative for frontend member profile areas.

 * Registration page
 * Profile page for updating details.
 * Extendable profile area

## Registration Page

Create member Registration Page at the CMS or run /dev/build?flush after module instalation

## Member Profile Page

Create member Profile Page at the CMS or run /dev/build?flush after module instalation

## Profile Area

By default profile area has only profile information and profile editing form controller to add extra profile areas use this example:

```php
class MyProfileArea extends MemberProfilePage_Controller {
    /* ... your code
     * private static $allowed_actions = [
     *    'MyAction'
     * ];
     *
     * public function MyAction() {}
     *
    */
}
```

Profile information will use /profile URL, sub-controllers will use sub-URLs of this page for example:
/profile/myprofilearea

New area will be automatically added to frontend member profile area navigation menu, but you can add hide ancestor to keep it hidden:
```php
class MyProfileArea extends ProfileController {
    private static $hide_ancestor = false; // it's optional if you want to hide this controller set to true
    private static $menu_icon = '<i class="fa fa-pencil-square-o"></i>'; // optional icon
    private static $menu_title = 'My Profile Area'; // optional title otherwise My Profile Area title will be used
}
```

```php
class MyProfileObjectsController extends ProfileCRUD {
    /*
     * This controller will let you create, view, edit and delete objects
     */
    private static $hide_ancestor = false; // it's optional if you want to hide this controller set to true
    private static $menu_icon = '<i class="fa fa-pencil-square-o"></i>'; // optional icon
    private static $menu_title = 'My Profile Area'; // optional title otherwise My Profile Area title will be used

    private static $managed_models = [
        'MyObject',
        'MyObject2',
    ];

    private static $allowed_actions = [
        'ExtraAction'
    ];

    public function ExtraAction() {}
}
```

* to make "MyProfileArea" template create templates/profile/controllers/MyProfileArea.ss
* it will be used as sub-template of MemberProfilePage.ss by using $ProfileArea variable just like $Layout requires sub-template of Page.ss

* to create a specific action template of "MyProfileArea" create templates/profile/controllers/MyProfileArea_MyAction.ss

Use following code to customize MemberRegistrationForm:

```yml
MemberRegistrationForm:
  extensions:
    - MyMemberRegistrationFormExtension
```

```php
class MyMemberRegistrationFormExtension extends Extension
{
    public function updateMemberRegistrationForm()
    {
        /* your code, ex:
         * $fields = $this->owner->Fields();
         * $fields->push(TextField::create('MyField'));
         * BootstrapForm::apply_bootstrap_to_fieldlist($fields);
         * BootstrapForm::apply_bootstrap_to_fieldlist($this->owner->Actions());
        */
    }

    public function onRegister($member)
    {
        /* your code to execute on register for an instance extra notifications */
    }
}
```

Use following code to customize MemberEditProfileForm:

```yml
MemberEditProfileForm:
  extensions:
    - MyMemberEditProfileFormExtension
```

```php
class MyMemberEditProfileFormExtension extends Extension
{
    public function updateMemberEditProfileForm()
    {
        /* your code, ex:
         * $fields = $this->owner->Fields();
         * $fields->push(TextField::create('MyField'));
         * BootstrapForm::apply_bootstrap_to_fieldlist($fields);
         * BootstrapForm::apply_bootstrap_to_fieldlist($this->owner->Actions());
        */
    }
}
```

Use following code to extend Profile CRUD item forms:
```yml
ProfileCRUD:
  extensions:
    - BootstrapItemEditForm
```

```php
class BootstrapItemEditForm extends Extension
{
    public function permissionDenied()
    {
        // your code, for example:
        Page_Controller::setSiteMessage('You must log in to view your profile.', 'warning');
    }

    public function updateItemRemoved(DataObject $item)
    {
        // your code
    }

    public function updateItemRemoveDenied()
    {
        // your code
    }
    public function updateItemEditDenied()
    {
        // your code
    }

    public function updateItemEditSuccess(DataObject $item, array $data, $new = false)
    {
        if ($new) {
            $success_msg = 'New Item Created';
        } else {
            $success_msg = 'Item was updated';
        }

        // your code
    }

    public function updateItemForm($form)
    {
        $fields = $form->Fields();
        // setup bootstrap classes
        BootstrapForm::apply_bootstrap_to_fieldlist($fields);

        $fields = $form->Actions();
        // setup bootstrap classes
        BootstrapForm::apply_bootstrap_to_fieldlist($fields);
    }
}
```
