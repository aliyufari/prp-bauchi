<?php

namespace App\Traits;

use App\Support\Jurisdiction;
use App\Support\RoleHierarchy;
use Illuminate\Validation\Validator;

trait ValidatesAssignedLevel
{
    protected function validateAssignedLevel(Validator $v, string $role): void
    {
        $level = Jurisdiction::levelFor($this->input('type'));

        if (!$level) {
            return; // the enum rule already reports an invalid type
        }

        $actor = $this->user();

        if (!RoleHierarchy::canAssign($actor, $role, $level)) {
            $v->errors()->add('type', 'You cannot assign a level equal to or above your own.');

            return;
        }

        $id = $this->input("{$level}_id");

        if (!$id) {
            $v->errors()->add("{$level}_id", "Select the {$level} this person will cover.");

            return;
        }

        // Check ONLY the node they will own, not the chain completed beneath it.
        if (!Jurisdiction::for($actor)->covers(["{$level}_id" => $id])) {
            $v->errors()->add("{$level}_id", "That {$level} is outside your jurisdiction.");
        }
    }
}