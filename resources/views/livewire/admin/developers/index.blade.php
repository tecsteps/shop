<div>
    <div class="mb-6">
        <flux:heading size="xl">Developers</flux:heading>
    </div>

    {{-- API Tokens --}}
    <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <flux:heading size="lg" class="mb-2">API tokens</flux:heading>
        <flux:text class="mb-4 text-sm text-gray-500">Manage personal access tokens for the Admin API.</flux:text>

        @if ($generatedToken)
            <flux:callout variant="warning" class="mb-4">
                <strong>Copy this token now.</strong> It will not be shown again.
                <div class="mt-2 rounded bg-gray-100 p-2 font-mono text-xs dark:bg-gray-800">
                    {{ $generatedToken }}
                </div>
            </flux:callout>
        @endif

        <flux:text class="mb-4 text-gray-500">No tokens generated yet.</flux:text>

        <flux:button variant="ghost" x-on:click="$flux.modal('generate-token').show()">
            Generate new token
        </flux:button>
    </div>

    <flux:separator class="my-8" />

    {{-- Webhooks --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <flux:heading size="lg" class="mb-2">Webhooks</flux:heading>
        <flux:text class="mb-4 text-sm text-gray-500">Manage webhook subscriptions for real-time event notifications.</flux:text>

        <flux:text class="text-gray-500">No webhooks configured yet.</flux:text>
    </div>

    {{-- Generate Token Modal --}}
    <flux:modal name="generate-token" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Generate API token</flux:heading>
            <flux:field>
                <flux:label>Token name</flux:label>
                <flux:input wire:model="newTokenName" placeholder="My integration" />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('generate-token').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="generateToken">Generate</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
