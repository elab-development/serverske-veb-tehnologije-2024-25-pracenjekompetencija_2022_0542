<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'credential_id' => $this->credential_id,
            'status'        => $this->status,
            'notes'         => $this->notes,
            'credential'    => new CredentialResource($this->whenLoaded('credential')),
        ];
    }
}
