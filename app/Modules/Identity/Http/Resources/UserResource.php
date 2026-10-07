<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'avatar' => $this->avatar,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}