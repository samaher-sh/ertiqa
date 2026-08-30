<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\DepartmentModel;
use App\Services\UsersService;

/**
 * صفحة "إضافة مستخدم" -- تُنشئ حسابًا جديدًا بـ auth_source='ldap' بعد
 * التحقق من وجود الموظف فعليًا بالدليل الموحّد (عبر UsersService/LDAPService)،
 * مع تحديد دوره وإدارته بنظام ارتقاء يدويًا (لا تُشتق من AD).
 *
 * مقصورة حاليًا على رئيس إدارة المراجعة الداخلية وعضو إدارة المراجعة
 * الداخلية -- راجعي assertAccess() أدناه.
 */
class UsersController extends BaseController
{
    private function assertAccess(): void
    {
        if (!in_array(session()->get('role_code'), ['audit_head', 'audit_member'], true)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ليس لديك صلاحية الوصول لهذي الصفحة.');
        }
    }

    /** GET dashboard/users/create */
    public function create()
    {
        $this->assertAccess();

        return view('dashboard/users/create', [
            'navItems'     => $this->navItemsForCurrentSession(),
            'migratedKeys' => $this->migratedPageKeys(),
            'activeNavKey' => 'addUser',
            'currentUser'  => $this->sessionUserSummary(),
            'roles'        => (new RoleModel())->activeRoles(),
            'departments'  => (new DepartmentModel())->flatHierarchy(),
        ]);
    }

    /** POST dashboard/users — يضيف المستخدم فعليًا */
    public function store()
    {
        $this->assertAccess();

        $employeeNumber = trim((string) ($this->request->getPost('employee_number') ?? ''));
        $roleId = (int) ($this->request->getPost('role_id') ?? 0);
        $departmentId = (int) ($this->request->getPost('department_id') ?? 0);

        $result = (new UsersService())->createFromLdap($employeeNumber, $roleId, $departmentId);

        if (!$result['success']) {
            return redirect()->to(base_url('dashboard/users/create'))->withInput()->with('error', $result['message']);
        }

        return redirect()->to(base_url('dashboard/users/create'))->with('success', $result['message']);
    }
}
