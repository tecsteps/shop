<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DevelopersController extends AdminController
{
    public function index(): View
    {
        $tokenTable = $this->resolveTokenTable();
        $tokenColumns = $tokenTable ? Schema::getColumnListing($tokenTable) : [];

        $webhookTable = $this->resolveWebhookTable();
        $webhookColumns = $webhookTable ? Schema::getColumnListing($webhookTable) : [];

        return view('admin.developers.index', [
            'installationOptions' => $this->installationOptions(),
            'tokenTable' => $tokenTable,
            'tokens' => $tokenTable ? $this->loadTokens($tokenTable, $tokenColumns) : collect(),
            'tokenCrudAvailable' => $tokenTable !== null && in_array('id', $tokenColumns, true),
            'webhookTable' => $webhookTable,
            'webhooks' => $webhookTable ? $this->loadWebhooks($webhookTable, $webhookColumns) : collect(),
            'webhookCrudAvailable' => $webhookTable !== null && in_array('id', $webhookColumns, true),
            'newToken' => (string) session('new_token', ''),
        ]);
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $table = $this->resolveTokenTable();

        if ($table === null) {
            return back()->withErrors([
                'token' => 'No token table is available in this build.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
            'installation_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $columns = Schema::getColumnListing($table);

        if (! in_array('id', $columns, true)) {
            return back()->withErrors([
                'token' => 'The token table does not support basic CRUD by ID.',
            ]);
        }

        $installationId = $this->resolveInstallationId(
            isset($validated['installation_id']) ? (int) $validated['installation_id'] : null
        );

        if (in_array('installation_id', $columns, true) && $installationId === null) {
            return back()->withErrors([
                'token' => 'No app installation is available for token creation.',
            ])->withInput();
        }

        [$payload, $plainToken] = $this->buildTokenPayload($validated, $columns, $installationId);

        if ($payload === []) {
            return back()->withErrors([
                'token' => 'Token payload could not be mapped to the current schema.',
            ]);
        }

        try {
            DB::table($table)->insert($payload);
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'token' => sprintf('Token could not be created: %s', $exception->getMessage()),
            ])->withInput();
        }

        return redirect()->route('admin.developers.index')
            ->with('status', 'Developer token created. Copy it now; it will not be shown again.')
            ->with('new_token', $plainToken);
    }

    public function destroyToken(int $tokenId): RedirectResponse
    {
        $table = $this->resolveTokenTable();

        if ($table === null) {
            return back()->withErrors([
                'token' => 'No token table is available in this build.',
            ]);
        }

        $columns = Schema::getColumnListing($table);

        if (! in_array('id', $columns, true)) {
            return back()->withErrors([
                'token' => 'The token table does not support basic CRUD by ID.',
            ]);
        }

        $query = $this->scopedTokenQuery($table, $columns)
            ->where('id', $tokenId);

        if (! $query->exists()) {
            return back()->withErrors([
                'token' => 'Token not found for the current scope.',
            ]);
        }

        if (in_array('revoked_at', $columns, true)) {
            $payload = ['revoked_at' => now()];
            $this->withTimestamps($payload, $columns, true);
            $query->update($payload);
        } elseif (in_array('revoked', $columns, true)) {
            $payload = ['revoked' => true];
            $this->withTimestamps($payload, $columns, true);
            $query->update($payload);
        } else {
            $query->delete();
        }

        return redirect()->route('admin.developers.index')
            ->with('status', 'Developer token revoked.');
    }

    public function storeWebhook(Request $request): RedirectResponse
    {
        $table = $this->resolveWebhookTable();

        if ($table === null) {
            return back()->withErrors([
                'webhook' => 'No webhook subscription table is available in this build.',
            ]);
        }

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:500'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'installation_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $columns = Schema::getColumnListing($table);

        if (! in_array('id', $columns, true)) {
            return back()->withErrors([
                'webhook' => 'The webhook table does not support basic CRUD by ID.',
            ]);
        }

        $requestedInstallationId = isset($validated['installation_id']) ? (int) $validated['installation_id'] : null;
        $installationId = $this->resolveInstallationId($requestedInstallationId);

        if ($requestedInstallationId !== null && $installationId === null) {
            return back()->withErrors([
                'webhook' => 'Selected app installation was not found for this store.',
            ])->withInput();
        }

        $payload = $this->buildWebhookPayload($validated, $columns, false, $installationId);

        if ($payload === []) {
            return back()->withErrors([
                'webhook' => 'Webhook payload could not be mapped to the current schema.',
            ]);
        }

        try {
            DB::table($table)->insert($payload);
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'webhook' => sprintf('Webhook could not be created: %s', $exception->getMessage()),
            ])->withInput();
        }

        return redirect()->route('admin.developers.index')
            ->with('status', 'Webhook subscription created.');
    }

    public function updateWebhook(Request $request, int $webhookId): RedirectResponse
    {
        $table = $this->resolveWebhookTable();

        if ($table === null) {
            return back()->withErrors([
                'webhook' => 'No webhook subscription table is available in this build.',
            ]);
        }

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:500'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'installation_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $columns = Schema::getColumnListing($table);

        if (! in_array('id', $columns, true)) {
            return back()->withErrors([
                'webhook' => 'The webhook table does not support basic CRUD by ID.',
            ]);
        }

        $query = $this->scopedWebhookQuery($table, $columns)
            ->where('id', $webhookId);

        if (! $query->exists()) {
            return back()->withErrors([
                'webhook' => 'Webhook not found for the current scope.',
            ]);
        }

        $requestedInstallationId = isset($validated['installation_id']) ? (int) $validated['installation_id'] : null;
        $installationId = $this->resolveInstallationId($requestedInstallationId);

        if ($requestedInstallationId !== null && $installationId === null) {
            return back()->withErrors([
                'webhook' => 'Selected app installation was not found for this store.',
            ])->withInput();
        }

        $payload = $this->buildWebhookPayload($validated, $columns, true, $installationId);

        if ($payload === []) {
            return back()->withErrors([
                'webhook' => 'Webhook payload could not be mapped to the current schema.',
            ]);
        }

        try {
            $query->update($payload);
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'webhook' => sprintf('Webhook could not be updated: %s', $exception->getMessage()),
            ])->withInput();
        }

        return redirect()->route('admin.developers.index')
            ->with('status', 'Webhook subscription updated.');
    }

    public function destroyWebhook(int $webhookId): RedirectResponse
    {
        $table = $this->resolveWebhookTable();

        if ($table === null) {
            return back()->withErrors([
                'webhook' => 'No webhook subscription table is available in this build.',
            ]);
        }

        $columns = Schema::getColumnListing($table);

        if (! in_array('id', $columns, true)) {
            return back()->withErrors([
                'webhook' => 'The webhook table does not support basic CRUD by ID.',
            ]);
        }

        $deleted = $this->scopedWebhookQuery($table, $columns)
            ->where('id', $webhookId)
            ->delete();

        if ($deleted === 0) {
            return back()->withErrors([
                'webhook' => 'Webhook not found for the current scope.',
            ]);
        }

        return redirect()->route('admin.developers.index')
            ->with('status', 'Webhook subscription deleted.');
    }

    protected function resolveTokenTable(): ?string
    {
        foreach (['oauth_tokens', 'personal_access_tokens'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function resolveWebhookTable(): ?string
    {
        return Schema::hasTable('webhook_subscriptions')
            ? 'webhook_subscriptions'
            : null;
    }

    /**
     * @return Collection<int, object>
     */
    protected function installationOptions(): Collection
    {
        if (! Schema::hasTable('app_installations')) {
            return collect();
        }

        $columns = Schema::getColumnListing('app_installations');
        $query = DB::table('app_installations');

        if (in_array('store_id', $columns, true)) {
            $query->where('app_installations.store_id', $this->currentStore()->id);
        }

        $selects = ['app_installations.id'];

        if (Schema::hasTable('apps') && in_array('app_id', $columns, true)) {
            $query->leftJoin('apps', 'apps.id', '=', 'app_installations.app_id');

            if (in_array('name', Schema::getColumnListing('apps'), true)) {
                $selects[] = 'apps.name as app_name';
            }
        }

        if (in_array('status', $columns, true)) {
            $selects[] = 'app_installations.status';
        }

        return $query->select($selects)
            ->orderByDesc('app_installations.id')
            ->limit(100)
            ->get()
            ->map(static function (object $row): object {
                return (object) [
                    'id' => (int) $row->id,
                    'label' => isset($row->app_name) && is_string($row->app_name) && $row->app_name !== ''
                        ? $row->app_name
                        : sprintf('Installation #%d', $row->id),
                    'status' => isset($row->status) ? (string) $row->status : null,
                ];
            });
    }

    protected function resolveInstallationId(?int $requestedId = null): ?int
    {
        if (! Schema::hasTable('app_installations')) {
            return null;
        }

        $columns = Schema::getColumnListing('app_installations');
        $query = DB::table('app_installations');

        if (in_array('store_id', $columns, true)) {
            $query->where('store_id', $this->currentStore()->id);
        }

        if ($requestedId !== null) {
            $query->where('id', $requestedId);

            $id = $query->value('id');

            return $id === null ? null : (int) $id;
        }

        $id = $query->orderByDesc('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string>  $columns
     * @return Collection<int, object>
     */
    protected function loadTokens(string $table, array $columns): Collection
    {
        $query = $this->scopedTokenQuery($table, $columns);

        $selects = ['id'];

        $nameColumn = $this->firstExistingColumn($columns, ['name', 'label']);
        if ($nameColumn !== null) {
            $selects[] = "{$nameColumn} as name";
        }

        $scopesJsonColumn = $this->firstExistingColumn($columns, ['scopes_json', 'abilities']);
        if ($scopesJsonColumn !== null) {
            $selects[] = "{$scopesJsonColumn} as scopes_json";
        }

        if (in_array('scopes', $columns, true)) {
            $selects[] = 'scopes';
        }

        if (in_array('revoked_at', $columns, true)) {
            $selects[] = 'revoked_at';
        }

        if (in_array('revoked', $columns, true)) {
            $selects[] = 'revoked';
        }

        if (in_array('expires_at', $columns, true)) {
            $selects[] = 'expires_at';
        }

        if (in_array('last_used_at', $columns, true)) {
            $selects[] = 'last_used_at';
        }

        if (in_array('created_at', $columns, true)) {
            $selects[] = 'created_at';
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        return $query->select($selects)
            ->limit(50)
            ->get()
            ->map(function (object $row): object {
                $scopes = [];

                if (property_exists($row, 'scopes_json') && is_string($row->scopes_json)) {
                    $decoded = json_decode($row->scopes_json, true);

                    if (is_array($decoded)) {
                        $scopes = array_values(array_filter(array_map(static fn ($item): string => (string) $item, $decoded)));
                    }
                } elseif (property_exists($row, 'scopes') && is_string($row->scopes)) {
                    $segments = array_map('trim', explode(',', $row->scopes));
                    $scopes = array_values(array_filter($segments, static fn (string $value): bool => $value !== ''));
                }

                $revoked = false;

                if (property_exists($row, 'revoked_at')) {
                    $revoked = ! empty($row->revoked_at);
                }

                if (property_exists($row, 'revoked')) {
                    $revoked = $revoked || (bool) $row->revoked;
                }

                return (object) [
                    'id' => (int) $row->id,
                    'name' => property_exists($row, 'name') && is_string($row->name) && $row->name !== ''
                        ? $row->name
                        : sprintf('Token #%d', $row->id),
                    'scopes' => $scopes,
                    'revoked' => $revoked,
                    'expires_at' => property_exists($row, 'expires_at') ? $row->expires_at : null,
                    'last_used_at' => property_exists($row, 'last_used_at') ? $row->last_used_at : null,
                    'created_at' => property_exists($row, 'created_at') ? $row->created_at : null,
                ];
            });
    }

    /**
     * @param  array<string>  $columns
     * @return Collection<int, object>
     */
    protected function loadWebhooks(string $table, array $columns): Collection
    {
        $query = $this->scopedWebhookQuery($table, $columns);

        $topicColumn = $this->firstExistingColumn($columns, ['topic', 'event', 'event_name', 'event_type']);
        $urlColumn = $this->firstExistingColumn($columns, ['target_url', 'url', 'endpoint']);
        $activeColumn = $this->firstExistingColumn($columns, ['is_active', 'active', 'status']);

        $selects = ['id'];

        if ($topicColumn !== null) {
            $selects[] = "{$topicColumn} as topic";
        }

        if ($urlColumn !== null) {
            $selects[] = "{$urlColumn} as endpoint_url";
        }

        if ($activeColumn !== null) {
            $selects[] = "{$activeColumn} as active_state";
        }

        if (in_array('created_at', $columns, true)) {
            $selects[] = 'created_at';
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        return $query->select($selects)
            ->limit(50)
            ->get()
            ->map(function (object $row): object {
                $active = true;

                if (property_exists($row, 'active_state')) {
                    if (is_string($row->active_state)) {
                        $active = ! in_array(strtolower($row->active_state), ['inactive', 'disabled', 'off'], true);
                    } else {
                        $active = (bool) $row->active_state;
                    }
                }

                return (object) [
                    'id' => (int) $row->id,
                    'topic' => property_exists($row, 'topic') ? (string) $row->topic : 'unknown',
                    'url' => property_exists($row, 'endpoint_url') ? (string) $row->endpoint_url : '-',
                    'is_active' => $active,
                    'created_at' => property_exists($row, 'created_at') ? $row->created_at : null,
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string>  $columns
     * @return array{0: array<string, mixed>, 1: string}
     */
    protected function buildTokenPayload(array $validated, array $columns, ?int $installationId = null): array
    {
        $token = Str::random(40);
        $tokenHash = hash('sha256', $token);
        $scopes = $this->parseScopes((string) ($validated['scopes'] ?? ''));

        $payload = [];

        if (in_array('store_id', $columns, true)) {
            $payload['store_id'] = $this->currentStore()->id;
        }

        if (in_array('user_id', $columns, true) && auth()->id()) {
            $payload['user_id'] = auth()->id();
        }

        if (in_array('tokenable_id', $columns, true) && auth()->id()) {
            $payload['tokenable_id'] = auth()->id();
        }

        if (in_array('tokenable_type', $columns, true) && auth()->user()) {
            $payload['tokenable_type'] = auth()->user()::class;
        }

        if (in_array('installation_id', $columns, true) && $installationId !== null) {
            $payload['installation_id'] = $installationId;
        }

        $nameColumn = $this->firstExistingColumn($columns, ['name', 'label']);

        if ($nameColumn !== null) {
            $payload[$nameColumn] = $validated['name'];
        }

        if (in_array('token', $columns, true)) {
            $payload['token'] = $tokenHash;
        }

        if (in_array('token_hash', $columns, true)) {
            $payload['token_hash'] = $tokenHash;
        }

        if (in_array('access_token_hash', $columns, true)) {
            $payload['access_token_hash'] = $tokenHash;
        }

        if (in_array('refresh_token_hash', $columns, true)) {
            $payload['refresh_token_hash'] = hash('sha256', Str::random(40));
        }

        if (in_array('token_prefix', $columns, true)) {
            $payload['token_prefix'] = substr($token, 0, 8);
        }

        if (in_array('scopes_json', $columns, true)) {
            $payload['scopes_json'] = json_encode($scopes, JSON_THROW_ON_ERROR);
        }

        if (in_array('scopes', $columns, true)) {
            $payload['scopes'] = implode(',', $scopes);
        }

        if (in_array('abilities', $columns, true)) {
            $payload['abilities'] = json_encode($scopes, JSON_THROW_ON_ERROR);
        }

        if (in_array('revoked_at', $columns, true)) {
            $payload['revoked_at'] = null;
        }

        if (in_array('revoked', $columns, true)) {
            $payload['revoked'] = false;
        }

        if (in_array('expires_at', $columns, true)) {
            $payload['expires_at'] = ! empty($validated['expires_at'])
                ? $validated['expires_at']
                : now()->addDays(30);
        }

        $this->withTimestamps($payload, $columns, false);

        return [$payload, $token];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string>  $columns
     * @return array<string, mixed>
     */
    protected function buildWebhookPayload(array $validated, array $columns, bool $isUpdate, ?int $installationId = null): array
    {
        $payload = [];
        $isActive = (bool) ($validated['is_active'] ?? false);

        if (in_array('store_id', $columns, true)) {
            $payload['store_id'] = $this->currentStore()->id;
        }

        $topicColumn = $this->firstExistingColumn($columns, ['topic', 'event', 'event_name', 'event_type']);
        if ($topicColumn !== null) {
            $payload[$topicColumn] = $validated['topic'];
        }

        $urlColumn = $this->firstExistingColumn($columns, ['target_url', 'url', 'endpoint']);
        if ($urlColumn !== null) {
            $payload[$urlColumn] = $validated['url'];
        }

        if (in_array('app_installation_id', $columns, true) && $installationId !== null) {
            $payload['app_installation_id'] = $installationId;
        }

        $secret = ! empty($validated['secret'])
            ? (string) $validated['secret']
            : Str::random(40);

        if (in_array('signing_secret_encrypted', $columns, true)) {
            $payload['signing_secret_encrypted'] = encrypt($secret);
        } elseif (in_array('secret_encrypted', $columns, true)) {
            $payload['secret_encrypted'] = encrypt($secret);
        } elseif (in_array('secret', $columns, true)) {
            $payload['secret'] = $secret;
        }

        if (in_array('is_active', $columns, true)) {
            $payload['is_active'] = $isActive;
        } elseif (in_array('active', $columns, true)) {
            $payload['active'] = $isActive;
        } elseif (in_array('status', $columns, true)) {
            $payload['status'] = $isActive ? 'active' : 'inactive';
        }

        $this->withTimestamps($payload, $columns, $isUpdate);

        return $payload;
    }

    /**
     * @param  array<string>  $columns
     */
    protected function scopedTokenQuery(string $table, array $columns)
    {
        $query = DB::table($table);

        if (in_array('store_id', $columns, true)) {
            return $query->where('store_id', $this->currentStore()->id);
        }

        if (in_array('installation_id', $columns, true) && Schema::hasTable('app_installations')) {
            $installationColumns = Schema::getColumnListing('app_installations');

            if (in_array('store_id', $installationColumns, true)) {
                $installationIds = DB::table('app_installations')
                    ->where('store_id', $this->currentStore()->id)
                    ->pluck('id')
                    ->all();

                return $query->whereIn('installation_id', $installationIds === [] ? [-1] : $installationIds);
            }
        }

        if (in_array('tokenable_id', $columns, true) && in_array('tokenable_type', $columns, true) && auth()->user()) {
            return $query
                ->where('tokenable_id', auth()->id())
                ->where('tokenable_type', auth()->user()::class);
        }

        if (in_array('user_id', $columns, true) && auth()->id()) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }

    /**
     * @param  array<string>  $columns
     */
    protected function scopedWebhookQuery(string $table, array $columns)
    {
        $query = DB::table($table);

        if (in_array('store_id', $columns, true)) {
            return $query->where('store_id', $this->currentStore()->id);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string>  $columns
     */
    protected function withTimestamps(array &$payload, array $columns, bool $isUpdate): void
    {
        if (in_array('updated_at', $columns, true)) {
            $payload['updated_at'] = now();
        }

        if (! $isUpdate && in_array('created_at', $columns, true)) {
            $payload['created_at'] = now();
        }
    }

    /**
     * @param  array<string>  $columns
     * @param  list<string>  $candidates
     */
    protected function firstExistingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function parseScopes(string $input): array
    {
        $parts = preg_split('/[\n\r,]+/', $input) ?: [];
        $parts = array_map(static fn (string $value): string => trim($value), $parts);
        $parts = array_values(array_filter($parts, static fn (string $value): bool => $value !== ''));

        return array_values(array_unique($parts));
    }
}
