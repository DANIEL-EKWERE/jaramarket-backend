<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'subject'   => $this->subject,
            'message'   => $this->message,
            'status'    => $this->status,
            'attachment'=> $this->attachment_url,

            'created_at'=> $this->created_at->diffForHumans(),
        ];
    }
}
