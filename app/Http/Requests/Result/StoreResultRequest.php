<?php

namespace App\Http\Requests\Result;

use App\Enums\ElectionParty;
use App\Models\Result;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreResultRequest extends FormRequest
{
    // Permission only. The jurisdiction check runs after validation so the user gets a
    // field error on the PU instead of a bare 403.
    public function authorize(): bool
    {
        return $this->user()->checkPermissionTo('results.upload');
    }

    public function rules(): array
    {
        return ['pu_id' => ['required', 'uuid', 'exists:pus,id']] + $this->commonRules();
    }

    protected function commonRules(): array
    {
        return [
            'election_id' => ['required', 'uuid', 'exists:elections,id'],
            'office' => ['required', Rule::in((array) config('elections.active_offices', ['governor']))],
            'registered_voters' => ['nullable', 'integer', 'min:0'],
            'accredited_voters' => ['required', 'integer', 'min:0'],
            'rejected_votes' => ['required', 'integer', 'min:0'],
            'party_votes' => ['required', 'array', 'min:1'],
            'party_votes.*' => ['required', 'integer', 'min:0'],
            'result_sheet_url' => ['nullable', 'string', 'max:2048'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->checkVotes($v);
            $this->checkUnique($v);
            $this->checkJurisdiction($v);
        });
    }

    protected function checkVotes(Validator $v): void
    {
        $votes = (array) $this->input('party_votes', []);
        $valid = array_column(ElectionParty::cases(), 'value');

        if (array_diff(array_keys($votes), $valid)) {
            $v->errors()->add('party_votes', 'Unknown party in the vote breakdown.');

            return;
        }

        if ((array_sum($votes) + (int) $this->input('rejected_votes', 0)) > (int) $this->input('accredited_voters', 0)) {
            $v->errors()->add('party_votes', 'Total votes (valid + rejected) cannot exceed accredited voters.');
        }
    }

    protected function checkUnique(Validator $v): void
    {
        if ($v->errors()->hasAny(['pu_id', 'election_id', 'office'])) {
            return;
        }

        $current = $this->route('result');

        $exists = Result::query()
            ->where('election_id', $this->input('election_id'))
            ->where('pu_id', $current?->pu_id ?? $this->input('pu_id'))
            ->where('office', $this->input('office'))
            ->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->exists();

        if ($exists) {
            $v->errors()->add('office', 'A result for this office has already been uploaded for this polling unit and election.');
        }
    }

    protected function checkJurisdiction(Validator $v): void
    {
        if ($v->errors()->has('pu_id')) {
            return;
        }

        if (!Gate::forUser($this->user())->allows('upload', [Result::class, ['pu_id' => $this->input('pu_id')]])) {
            $v->errors()->add('pu_id', 'You can only upload results for polling units inside your jurisdiction.');
        }
    }
}