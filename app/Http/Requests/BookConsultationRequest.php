<?php

namespace App\Http\Requests;

use App\Consultation\Calendar;
use App\Models\Country;
use App\Models\Region;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public booking form. Every field is checked against a fixed list or a strict pattern; free text may not
 * contain HTML. `form_token` is an encrypted timestamp issued with the page (time trap against bots).
 */
class BookConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trim = fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v;
        $this->merge([
            'name' => $trim($this->input('name')),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
            'phone' => $trim($this->input('phone')),
            'company' => $trim($this->input('company')) ?: null,
            'message' => is_string($this->input('message')) ? trim($this->input('message')) ?: null : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $calendar = app(Calendar::class);
        $noHtml = function (string $attribute, mixed $value, Closure $fail) {
            if (is_string($value) && ($value !== strip_tags($value) || preg_match('/[<>]/', $value))) {
                $fail('no_html');
            }
        };

        return [
            'service' => ['nullable', 'string', Rule::in(Service::query()->where('status', 'published')->pluck('slug'))],
            'region' => ['nullable', 'string', Rule::in(Region::query()->where('status', 'published')->pluck('slug'))],
            'stage' => ['required', Rule::in(config('consultation.stages'))],
            'name' => ['required', 'string', 'min:2', 'max:120', $noHtml, 'not_regex:/https?:|www\./i'],
            'email' => ['required', 'string', 'max:190', 'email:rfc,strict'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9\s\-()]{6,20}$/'],
            'company' => ['nullable', 'string', 'max:160', $noHtml],
            'country' => ['required', 'string', Rule::in([...Country::query()->pluck('code')->all(), 'xx'])],
            'meeting_mode' => ['required', Rule::in(config('consultation.meeting_modes'))],
            'preferred_date' => ['required', 'date_format:Y-m-d', Rule::in($calendar->dates())],
            'preferred_slot' => ['required', Rule::in($calendar->slots())],
            'message' => ['nullable', 'string', 'max:2000', $noHtml, function (string $a, mixed $v, Closure $fail) {
                if (is_string($v) && preg_match_all('/https?:\/\//i', $v) > 2) {
                    $fail('too_many_links');
                }
            }],
            'consent' => ['accepted'],
            'form_token' => ['required', 'string', function (string $a, mixed $v, Closure $fail) {
                try {
                    $issued = (int) decrypt($v);
                } catch (DecryptException) {
                    $fail('expired');

                    return;
                }
                $elapsed = time() - $issued;
                if ($elapsed < config('consultation.min_fill_seconds') || $elapsed > 3 * 3600) {
                    $fail('expired');
                }
            }],
        ];
    }

    /** @return array<string, mixed> data stored on the booking */
    public function booking(): array
    {
        return $this->safe()->except(['consent', 'form_token']);
    }
}
