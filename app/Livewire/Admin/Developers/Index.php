<?php

namespace App\Livewire\Admin\Developers;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Support\TokenAbilities;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Developer settings (spec 03 section 16): personal access tokens for the
 * Admin REST API. Tokens are created with a name and a set of abilities;
 * the plain-text token is displayed exactly once after generation.
 */
#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts;

    public string $newTokenName = '';

    /** @var list<string> */
    public array $newTokenAbilities = [];

    public ?string $generatedToken = null;

    public function mount(): void
    {
        $this->authorize('manageDevelopers', $this->store());
    }

    public function generateToken(): void
    {
        $this->authorize('manageDevelopers', $this->store());

        $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
            'newTokenAbilities' => ['required', 'array', 'min:1'],
            'newTokenAbilities.*' => [Rule::in(TokenAbilities::names())],
        ], [], [
            'newTokenName' => __('token name'),
            'newTokenAbilities' => __('abilities'),
        ]);

        $token = auth()->user()->createToken($this->newTokenName, $this->newTokenAbilities);

        $this->generatedToken = $token->plainTextToken;

        Flux::modal('generate-token')->close();

        $this->reset('newTokenName', 'newTokenAbilities');
        unset($this->tokens);

        $this->toast(__('API token created.'));
    }

    public function revokeToken(int $tokenId): void
    {
        $this->authorize('manageDevelopers', $this->store());

        auth()->user()->tokens()->whereKey($tokenId)->delete();

        unset($this->tokens);

        $this->toast(__('API token revoked.'));
    }

    /**
     * @return Collection<int, \Laravel\Sanctum\PersonalAccessToken>
     */
    #[Computed]
    public function tokens(): Collection
    {
        return auth()->user()->tokens()->latest('id')->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableAbilities(): array
    {
        return TokenAbilities::all();
    }

    public function render(): View
    {
        return view('livewire.admin.developers.index')->title(__('Developers'));
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
