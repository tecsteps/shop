<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AppsController extends AdminController
{
    public function index(): View
    {
        $table = $this->resolveInstallationsTable();

        if ($table === null) {
            return view('admin.apps.index', [
                'installedApps' => collect(),
                'sourceAvailable' => false,
                'sourceTable' => null,
            ]);
        }

        return view('admin.apps.index', [
            'installedApps' => $this->loadInstalledApps($table),
            'sourceAvailable' => true,
            'sourceTable' => $table,
        ]);
    }

    protected function resolveInstallationsTable(): ?string
    {
        return Schema::hasTable('app_installations')
            ? 'app_installations'
            : null;
    }

    /**
     * @return Collection<int, object>
     */
    protected function loadInstalledApps(string $table): Collection
    {
        $installationColumns = Schema::getColumnListing($table);
        $appsTableAvailable = Schema::hasTable('apps');
        $appsColumns = $appsTableAvailable ? Schema::getColumnListing('apps') : [];

        $query = DB::table($table);

        if (in_array('store_id', $installationColumns, true)) {
            $query->where("{$table}.store_id", $this->currentStore()->id);
        }

        if ($appsTableAvailable && in_array('app_id', $installationColumns, true)) {
            $query->leftJoin('apps', 'apps.id', '=', "{$table}.app_id");
        }

        $selects = ["{$table}.id"];

        if ($appsTableAvailable && in_array('name', $appsColumns, true)) {
            $selects[] = 'apps.name as app_name';
        }

        if (in_array('name', $installationColumns, true)) {
            $selects[] = "{$table}.name as installation_name";
        }

        if (in_array('status', $installationColumns, true)) {
            $selects[] = "{$table}.status as installation_status";
        }

        if ($appsTableAvailable && in_array('status', $appsColumns, true)) {
            $selects[] = 'apps.status as app_status';
        }

        if (in_array('installed_at', $installationColumns, true)) {
            $selects[] = "{$table}.installed_at";
        }

        if (in_array('created_at', $installationColumns, true)) {
            $selects[] = "{$table}.created_at";
        }

        if (in_array('scopes_json', $installationColumns, true)) {
            $selects[] = "{$table}.scopes_json";
        }

        $query->select($selects);

        if (in_array('installed_at', $installationColumns, true)) {
            $query->orderByDesc("{$table}.installed_at");
        } elseif (in_array('created_at', $installationColumns, true)) {
            $query->orderByDesc("{$table}.created_at");
        } else {
            $query->orderByDesc("{$table}.id");
        }

        return $query->get()->map(function (object $row): object {
            $scopes = [];

            if (property_exists($row, 'scopes_json') && is_string($row->scopes_json)) {
                $decoded = json_decode($row->scopes_json, true);

                if (is_array($decoded)) {
                    $scopes = array_values(array_filter(array_map(static fn ($value): string => (string) $value, $decoded)));
                }
            }

            return (object) [
                'id' => (int) $row->id,
                'name' => $row->app_name ?? $row->installation_name ?? sprintf('App #%d', $row->id),
                'status' => $row->installation_status ?? $row->app_status ?? 'unknown',
                'installed_at' => $row->installed_at ?? $row->created_at ?? null,
                'scopes' => $scopes,
            ];
        });
    }
}
