<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\LDAPService;

class AuthController extends BaseController
{
    /**
     * GET / — يعرض صفحة تسجيل الدخول
     * لو المستخدم مسجّل دخول أصلًا، نوجّهه للوحته مباشرة بدل ما يشوف صفحة الدخول من جديد
     */
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to($this->destinationForRole(session()->get('role_code')));
        }

        return view('auth/login');
    }

    /**
     * POST /auth/login — يستقبل JSON من صفحة الدخول (fetch)، يتحقق، ويرجّع JSON
     */
    public function login()
    {
        $data = $this->request->getJSON(true);

        $nationalId = trim($data['national_id'] ?? '');
        $password   = (string) ($data['password'] ?? '');

        if ($nationalId === '' || $password === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'يرجى إدخال اسم المستخدم وكلمة المرور.',
            ]);
        }

        $userModel = new UserModel();
        $user = $userModel->findByNationalIdWithRole($nationalId);

        // نفس رسالة الخطأ سواء المستخدم مو موجود أو كلمة المرور غلط
        // (عشان ما نعطي أي طرف معلومة تساعده يخمّن حسابات صحيحة - ممارسة أمان قياسية)
        $genericError = [
            'success' => false,
            'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
        ];

        if ($user && $user['auth_source'] === 'local') {
            // حساب محلي صراحة (حسابات اختبار/طوارئ) -- ما نكلّم AD إطلاقًا
            if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                return $this->response->setStatusCode(401)->setJSON($genericError);
            }
        } else {
            // أي حالة ثانية (صف محلي بحالة auth_source='ldap'، أو ما فيه صف
            // محلي إطلاقًا) — نتحقق مباشرة عند خادم Active Directory بدون
            // اشتراط وجود صف محلي مسبقًا. لو صحّت بيانات AD لكن الموظف ما
            // له حساب مفعّل بنظامنا (ما فيه دور/إدارة مُسندة)، نرفض برسالة
            // واضحة (مو رسالة الخطأ العامة) عشان يعرف يتواصل مع الإدارة
            $ldapResult = (new LDAPService())->authenticate($nationalId, $password);
            if (empty($ldapResult['success'])) {
                return $this->response->setStatusCode(401)->setJSON($genericError);
            }
            if (!$user) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'حسابك موثّق بالدليل الموحّد (LDAP)، لكنه غير مُفعّل بنظام ارتقاء بعد. يرجى التواصل مع إدارة المراجعة الداخلية.',
                ]);
            }
        }

        // نجح الدخول — نحفظ بيانات الجلسة
        session()->set([
            'isLoggedIn'      => true,
            'user_id'         => $user['id'],
            'national_id'     => $user['national_id'],
            'full_name'       => $user['full_name'],
            'email'           => $user['email'],
            'phone'           => $user['phone'],
            'role_id'         => $user['role_id'],
            'role_code'       => $user['role_code'],
            'role_name'       => $user['role_name'],
            'department_id'   => $user['department_id'],
            'department_name' => $user['department_name'],
        ]);

        $userModel->touchLastLogin($user['id']);

        return $this->response->setJSON([
            'success'  => true,
            'redirect' => $this->destinationForRole($user['role_code']),
        ]);
    }

    /**
     * GET /auth/logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }

    /**
     * يحدد وين يروح المستخدم بعد الدخول حسب دوره.
     * ملاحظة: هذا افتراض مبدئي قابل للتعديل بسهولة من مكان واحد فقط.
     */
    private function destinationForRole(string $roleCode): string
    {
        return match ($roleCode) {
            'dept_manager', 'specialized_manager' => '/client',
            default                               => '/dashboard',
        };
    }
}
