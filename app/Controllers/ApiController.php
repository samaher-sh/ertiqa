<?php

/**
 * 
 */

namespace App\Controllers;

use App\Models\DepartmentModel;

class ApiController extends BaseController
{
    /**
     * GET /api/session — بيانات المستخدم المسجّل دخوله فعليًا من الجلسة (مو من localStorage)
     */
    public function session()
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'لا توجد جلسة دخول نشطة.',
            ]);
        }

        // الإدارة الأم (لو وجدت) عشان نعرض "الإدارة > الإدارة الفرعية" بنفس شكل الواجهة
        $departmentParentName = null;
        $departmentId = $session->get('department_id');
        if ($departmentId) {
            $dept = (new DepartmentModel())->find((int) $departmentId);
            if ($dept && !empty($dept['parent_id'])) {
                $parent = (new DepartmentModel())->find((int) $dept['parent_id']);
                $departmentParentName = $parent['name_ar'] ?? null;
            }
        }

        return $this->response->setJSON([
            'success'                => true,
            'user_id'                => $session->get('user_id'),
            'full_name'              => $session->get('full_name'),
            'national_id'            => $session->get('national_id'),
            'email'                  => $session->get('email'),
            'phone'                  => $session->get('phone'),
            'role_code'              => $session->get('role_code'),
            'role_name'              => $session->get('role_name'),
            'department_id'          => $departmentId,
            'department_name'        => $session->get('department_name'),
            'department_parent_name' => $departmentParentName,
        ]);
    }

    /**
     * GET /api/nav-items — عناصر القائمة الجانبية الصحيحة حسب دور الجلسة الحالية
     */
    public function navItems()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        return $this->response->setJSON([
            'success' => true,
            'items'   => $this->navItemsForCurrentSession(),
        ]);
    }

    /**
     * GET /api/departments — الإدارات الرئيسية وفروعها كـ JSON
     * {main: [...], subs_by_parent: {...}}
     */
    public function departments()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $deptModel = new DepartmentModel();
        $mainDepts = $deptModel->mainDepartments();

        $subsByParent = [];
        foreach ($mainDepts as $main) {
            $subsByParent[$main['id']] = $deptModel->subDepartments((int) $main['id']);
        }

        return $this->response->setJSON([
            'success'        => true,
            'main'           => $mainDepts,
            'subs_by_parent' => $subsByParent,
        ]);
    }
}
