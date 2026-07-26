<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class BlockedDomain implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = substr(strrchr($value, '@'), 1);
        if (in_array($domain, Config::get('blocked-domains.blocked_domains'))) {
            $fail('The provided email address does not appear to be valid, please check and try again.');
        }
    }
}
