<?php

namespace App\Traits;

use App\Support\Jurisdiction;
use App\Support\LocationChain;
use Illuminate\Validation\Validator;

trait ValidatesLocation
{
    protected function locationRules(): array
    {
        return [
            'state_id' => ['nullable', 'uuid', 'exists:states,id'],
            'zone_id'  => ['nullable', 'uuid', 'exists:zones,id'],
            'lga_id'   => ['nullable', 'uuid', 'exists:lgas,id'],
            'ward_id'  => ['nullable', 'uuid', 'exists:wards,id'],
            'pu_id'    => ['nullable', 'uuid', 'exists:pus,id'],
        ];
    }

    /** Pin the levels the actor is fixed to, so they can't be omitted or altered. */
    protected function prepareForValidation(): void
    {
        $locked = Jurisdiction::for($this->user())->lockedIds();

        if ($locked) {
            $this->merge($locked);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $submitted = $this->only(array_map(fn ($l) => "{$l}_id", LocationChain::LEVELS));

            $key = 'state_id';
            foreach (array_reverse(LocationChain::LEVELS) as $l) {
                if (!empty($submitted["{$l}_id"])) {
                    $key = "{$l}_id";
                    break;
                }
            }

            if (!LocationChain::isConsistent($submitted)) {
                $v->errors()->add($key, 'The selected locations do not belong together.');

                return;
            }

            if (!Jurisdiction::for($this->user())->covers($submitted)) {
                $v->errors()->add($key, 'You can only assign locations within your jurisdiction.');
            }
        });
    }
}