<?php

/*
 * Member Profile Extension adds profile area link functionality
 * @author Anton Fedianin aka Tony Air <tony@twma.pro>
 * https://tony.twma.pro/
 *
*/

class MemberProfileExtension extends DataExtension
{
    public function getProfileLink($action = null)
    {
        return MemberProfilePage::get()->First()->Link($action);
    }
}
