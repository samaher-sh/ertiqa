<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * UsersService
 * تنسيق عملية إضافة مستخدم جديد بحساب auth_source='ldap': يتحقق من وجوده
 * فعليًا بالدليل الموحّد عبر LDAPService أولًا (لا يُضاف أي حد ما هو موظف
 * حقيقي مسجّل بـ AD)، يجلب اسمه وبريده منه، ثم يحفظه بجدول users.
 *
 * الدور (role_id) والإدارة (department_id) يحددهما المستدعي صراحةً -- ما
 * تُشتق تلقائيًا من LDAP، لأن هيكلة الأدوار والإدارات داخلية للنظام (جدولا
 * roles/departments)، مو جزء من AD.
 */
class UsersService
{
    private LDAPService $ldap;
    private UserModel $userModel;

    public function __construct()
    {
        $this->ldap = new LDAPService();
        $this->userModel = new UserModel();
    }

    /**
     * @return array{success: bool, message: string, user_id: int|null}
     */
    public function createFromLdap(string $employeeNumber, int $roleId, int $departmentId): array
    {
        $employeeNumber = trim($employeeNumber);
        if ($employeeNumber === '' || !$roleId || !$departmentId) {
            return ['success' => false, 'message' => 'بيانات غير مكتملة.', 'user_id' => null];
        }

        if ($this->userModel->where('national_id', $employeeNumber)->first()) {
            return ['success' => false, 'message' => 'يوجد مستخدم مسجّل مسبقًا بنفس الرقم الوظيفي.', 'user_id' => null];
        }

        $employee = $this->ldap->getUserByUsername($employeeNumber);
        if (empty($employee) || empty($employee['username'])) {
            return ['success' => false, 'message' => 'الرقم الوظيفي غير موجود بالدليل الموحّد (LDAP).', 'user_id' => null];
        }

        $userId = $this->userModel->insert([
            'national_id'   => $employeeNumber,
            'full_name'     => $employee['fullname'] ?: $employeeNumber,
            'email'         => $employee['email'] ?: null,
            'auth_source'   => 'ldap',
            'password_hash' => null,
            'role_id'       => $roleId,
            'department_id' => $departmentId,
            'is_active'     => 1,
        ], true);

        if (!$userId) {
            return ['success' => false, 'message' => 'تعذّر حفظ المستخدم.', 'user_id' => null];
        }

        return ['success' => true, 'message' => 'تمت إضافة المستخدم بنجاح.', 'user_id' => (int) $userId];
    }
}
