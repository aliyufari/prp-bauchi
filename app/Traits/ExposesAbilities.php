<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

trait ExposesAbilities
{
    /** @return array{view:bool,update:bool,delete:bool} */
    protected function abilities(Request $request, ?string $module = null): array
    {
        $user = $request->user();

        return collect(['view', 'update', 'delete'])->mapWithKeys(fn ($a) => [
            $a => $user && Gate::forUser($user)->allows($module ? "{$module}:{$a}" : $a, $this->resource),
        ])->all();
    }
}