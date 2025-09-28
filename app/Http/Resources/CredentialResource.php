<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CredentialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'title'       => $this->title,
            'category'    => new CategoryResource($this->whenLoaded('category')),
            'category_id' => $this->category_id,
            'issuer_name' => $this->issuer_name,
            'issued_at'   => $this->issued_at,
            'expires_at'  => $this->expires_at,
            'is_verified' => (bool) $this->is_verified,
            'verification' => $this->whenLoaded('verification', fn() => [
                'id'     => $this->verification->id,
                'status' => $this->verification->status,
                'notes'  => $this->verification->notes,
            ]),
        ];
    }
}
