<?php

namespace  A2nt\MemberProfiles\Forms;

use SilverStripe\Forms\Form;
use SilverStripe\Security\Member_Validator;

class MemberRegistration_Validator extends Member_Validator
{
    public function php($data)
    {
        $valid = parent::php($data);

        // Execute the validators on the extensions
        if ($this->extension_instances) {
            foreach ($this->extension_instances as $extension) {
                if (method_exists($extension, 'hasMethod') && $extension->hasMethod('updatePHP')) {
                    $valid &= $extension->updatePHP($data, $this->form);
                }
            }
        }

        return $valid;
    }
}
