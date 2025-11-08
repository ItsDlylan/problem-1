<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\InteractsWithTwoFactorState;

/**
 * Form request for showing FacilityUser two-factor authentication settings.
 * Uses Fortify's two-factor state interaction trait.
 */
final class ShowFacilityTwoFactorAuthenticationRequest extends FormRequest
{
    use InteractsWithTwoFactorState;
}

