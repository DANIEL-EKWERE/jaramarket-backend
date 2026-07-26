<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'message'  => $this->message,
            'status'   => $this->status,
            'user'     => new UserResource($this->whenLoaded('user')),
            'attachment' => $this->attachment ? asset($this->attachment) : null,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
