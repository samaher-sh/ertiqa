<?php

namespace App\Controllers;

use App\Services\LDAPService;

/**
 * نقطة البحث الفوري عن موظف بالدليل الموحّد (LDAP) من الواجهة -- تُستخدم
 * للتحقق من وجود الموظف قبل إرسال نموذج (زي إضافة مستخدم جديد) بدل ما
 * يُكتشف الخطأ بعد الإرسال. محمية بفلتر "auth" (راجع Routes.php) --
 * ما تُستدعى إلا من جلسة مسجّلة دخول أصلًا.
 */
class LdapController extends BaseController
{
    private LDAPService $ldapService;

    public function __construct()
    {
        $this->ldapService = new LDAPService();
    }

    /**
     * GET dashboard/ldap/search?q=... — يستقبل الرقم الوظيفي، يرجّع JSON ببيانات الموظف
     */
    public function search()
    {
        $username = trim((string) $this->request->getGet('q'));

        if ($username === '') {
            return $this->response->setJSON([]);
        }

        try {
            $result = $this->ldapService->getUserByUsername($username);
        } catch (\Throwable $e) {
            log_message('error', 'LDAP search failed: ' . $e->getMessage());
            return $this->response->setStatusCode(502)->setJSON(['error' => 'تعذّر الاتصال بخادم الدليل الموحّد (LDAP).']);
        }

        if (empty($result) || empty($result['username'])) {
            return $this->response->setJSON([]);
        }

        return $this->response->setJSON([[
            'id'        => $result['username'],
            'username'  => $result['username'],
            'full_name' => $result['fullname'],
            'email'     => $result['email'],
            'dept'      => $result['dept'],
        ]]);
    }
}
