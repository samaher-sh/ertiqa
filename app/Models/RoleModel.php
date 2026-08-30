<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['code', 'name_ar', 'is_active'];

    public function activeRoles(): array
    {
        return $this->where('is_active', 1)->orderBy('name_ar')->findAll();
    }
}
