<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'national_id', 'full_name', 'email', 'phone',
        'auth_source', 'password_hash', 'role_id', 'department_id',
        'is_active', 'last_login_at',
    ];

    /**
     * يجيب المستخدم مع اسم الدور وكود الدور واسم القسم بجوين واحد
     * (يستخدم بالـ AuthController وقت التحقق من الدخول)
     */
    public function findByNationalIdWithRole(string $nationalId): ?array
    {
        $user = $this->select('users.*, roles.code as role_code, roles.name_ar as role_name, departments.name_ar as department_name')
            ->join('roles', 'roles.id = users.role_id')
            ->join('departments', 'departments.id = users.department_id', 'left')
            ->where('users.national_id', $nationalId)
            ->where('users.is_active', 1)
            ->first();

        return $user ?: null;
    }

    public function touchLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }
}
