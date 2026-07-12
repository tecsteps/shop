<?php

namespace App\Livewire\Admin\Developers;

use App\Livewire\Admin\AdminComponent;
use App\Models\WebhookSubscription;
use App\Services\AuditLogger;
use App\Services\WebhookTargetValidator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Index extends AdminComponent
{
    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public ?WebhookSubscription $editingWebhook = null;

    public function mount(): void
    {
        $this->authorizeDevelopers();
    }

    public function generateToken(): void
    {
        $this->authorizeDevelopers();
        $data = $this->validate(['newTokenName' => ['required', 'string', 'max:255']]);
        $storeId = $this->currentStore()->id;
        $token = $this->adminUser()->createToken(
            'store:'.$storeId.':'.trim($data['newTokenName']),
            [
                'store:'.$storeId,
                'read-products', 'write-products',
                'read-collections', 'write-collections',
                'read-orders', 'write-orders',
                'read-customers', 'write-customers',
                'read-discounts', 'write-discounts',
                'read-settings', 'write-settings',
                'read-content', 'write-content',
                'read-themes', 'write-themes',
                'read-analytics',
            ],
        );
        $this->generatedToken = Str::after($token->plainTextToken, '|');
        app(AuditLogger::class)->log(
            'api_token.created',
            (int) $this->adminUser()->getAuthIdentifier(),
            $storeId,
            'personal_access_token',
            (int) $token->accessToken->id,
            ['token_name' => $token->accessToken->name, 'abilities' => $token->accessToken->abilities],
        );
        $this->reset('newTokenName');
        unset($this->tokens);
        $this->dispatch('modal-close', name: 'generate-token');
        $this->toast('API token generated');
    }

    public function revokeToken(int $tokenId): void
    {
        $this->authorizeDevelopers();
        $token = $this->adminUser()->tokens()->where('name', 'like', $this->tokenPrefix().'%')->findOrFail($tokenId);
        app(AuditLogger::class)->log(
            'api_token.revoked',
            (int) $this->adminUser()->getAuthIdentifier(),
            (int) $this->currentStore()->id,
            'personal_access_token',
            (int) $token->id,
            ['token_name' => $token->name],
        );
        $token->delete();
        unset($this->tokens);
        $this->toast('API token revoked');
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        $this->authorizeDevelopers();
        $this->editingWebhook = $webhookId ? $this->webhook($webhookId) : null;
        $this->webhookEventType = (string) ($this->editingWebhook?->event_type ?? 'order.created');
        $this->webhookUrl = (string) ($this->editingWebhook?->target_url ?? '');
        $this->resetValidation();
        $this->dispatch('modal-show', name: 'webhook-form');
    }

    public function saveWebhook(): void
    {
        $this->authorizeDevelopers();
        $data = $this->validate([
            'webhookEventType' => ['required', Rule::in($this->eventTypes())],
            'webhookUrl' => [
                'required', 'url:https', 'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        app(WebhookTargetValidator::class)->validate((string) $value);
                    } catch (\Throwable $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],
        ]);
        $target = app(WebhookTargetValidator::class)->validate($data['webhookUrl']);
        if ($this->editingWebhook) {
            $webhook = $this->webhook($this->editingWebhook->id);
            $webhook->update(['event_type' => $data['webhookEventType'], 'target_url' => $target->url]);
        } else {
            WebhookSubscription::withoutGlobalScopes()->create([
                'store_id' => $this->currentStore()->id,
                'app_installation_id' => null,
                'event_type' => $data['webhookEventType'],
                'target_url' => $target->url,
                'signing_secret_encrypted' => Str::random(64),
                'status' => 'active',
            ]);
        }
        $this->editingWebhook = null;
        unset($this->webhooks);
        $this->dispatch('modal-close', name: 'webhook-form');
        $this->toast('Webhook saved');
    }

    public function deleteWebhook(int $webhookId): void
    {
        $this->authorizeDevelopers();
        $this->webhook($webhookId)->delete();
        unset($this->webhooks);
        $this->toast('Webhook deleted');
    }

    #[Computed]
    public function tokens(): mixed
    {
        return $this->adminUser()->tokens()->where('name', 'like', $this->tokenPrefix().'%')->latest()->get();
    }

    #[Computed]
    public function webhooks(): mixed
    {
        return WebhookSubscription::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)
            ->with(['deliveries', 'installation.app'])->orderBy('event_type')->get();
    }

    public function render(): View
    {
        return $this->admin(view('admin.developers.index'), 'Developers', [['label' => 'Developers']]);
    }

    /** @return list<string> */
    public function eventTypes(): array
    {
        return ['order.created', 'order.updated', 'order.cancelled', 'product.created', 'product.updated', 'product.deleted', 'customer.created', 'checkout.completed', 'fulfillment.created', 'refund.created'];
    }

    private function authorizeDevelopers(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }

    private function tokenPrefix(): string
    {
        return 'store:'.$this->currentStore()->id.':';
    }

    private function webhook(int $id): WebhookSubscription
    {
        return WebhookSubscription::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->findOrFail($id);
    }
}
