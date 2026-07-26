<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'attachment',
        'status',
    ];

    protected $appends = ['attachment_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment
            ? get_media_url($this->attachment)
            : null;
    }
}