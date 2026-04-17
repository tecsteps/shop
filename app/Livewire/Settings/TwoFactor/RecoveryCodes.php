<?php

namespace App\Livewire\Settings\TwoFactor;

use Exception;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecoveryCodes extends Component
{
    /** @var list<string> */
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $generateNewRecoveryCodes($user);

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                /** @var string $recoveryCodes */
                $recoveryCodes = $user->two_factor_recovery_codes;
                /** @var string $decryptedCodes */
                $decryptedCodes = decrypt($recoveryCodes);
                /** @var list<string> $decoded */
                $decoded = json_decode($decryptedCodes, true);
                $this->recoveryCodes = $decoded;
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}
