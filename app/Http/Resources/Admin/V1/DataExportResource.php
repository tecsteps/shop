<?php

namespace App\Http\Resources\Admin\V1;

use App\Enums\ExportStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DataExportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'type' => $this->type,
            'status' => $this->status?->value,
            'format' => $this->format,
            'filters' => $this->filters_json ?? [],
            'row_count' => $this->row_count,
            'download_url' => $this->downloadUrl(),
            'download_expires_at' => $this->download_expires_at?->toIso8601String(),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
        ];
    }

    private function downloadUrl(): ?string
    {
        if ($this->status !== ExportStatus::Completed || ! is_string($this->storage_key)) {
            return null;
        }

        if (! Storage::disk('local')->exists($this->storage_key)) {
            return null;
        }

        return 'data:text/csv;charset=utf-8,'.rawurlencode(Storage::disk('local')->get($this->storage_key));
    }
}
