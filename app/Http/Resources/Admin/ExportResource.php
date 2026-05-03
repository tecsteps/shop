<?php

namespace App\Http\Resources\Admin;

use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportResource extends JsonResource
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
            'status' => $this->status,
            'format' => $this->format,
            'row_count' => $this->row_count,
            'download_url' => $this->downloadUrl(),
            'download_expires_at' => $this->download_expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'failure_message' => $this->failure_message,
        ];
    }

    private function downloadUrl(): ?string
    {
        if ($this->status !== Export::StatusCompleted || ! $this->storage_key) {
            return null;
        }

        try {
            return Storage::disk('local')->temporaryUrl(
                $this->storage_key,
                $this->download_expires_at ?? now()->addHour(),
            );
        } catch (Throwable) {
            return Storage::disk('local')->url($this->storage_key);
        }
    }
}
