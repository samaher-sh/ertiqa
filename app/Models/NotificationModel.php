<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // created_at فقط، الجدول ما فيه updated_at

    protected $allowedFields = [
        'user_id', 'mission_id', 'type', 'title', 'body', 'channel',
        'is_read', 'read_at', 'sent_at', 'created_at',
    ];
}
