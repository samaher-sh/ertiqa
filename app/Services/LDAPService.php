<?php

namespace App\Services;

use App\Models\LDAPModel;

/**
 * LDAPService
 * طبقة وسيطة فوق LDAPModel -- متخصصة 100% بعمليات LDAP، ما تلمس قاعدة
 * البيانات إطلاقًا. أي Controller أو Service آخر يحتاج بيانات من LDAP
 * يستدعي هذي الطبقة فقط، لا LDAPModel مباشرة.
 */
class LDAPService
{
    private LDAPModel $ldap;

    public function __construct()
    {
        $this->ldap = new LDAPModel();
    }

    /**
     * التحقق من بيانات دخول المستخدم عبر LDAP
     *
     * @param string $username الرقم الوظيفي
     * @param string $password كلمة المرور
     * @return array ['success' => bool, 'username' => string, 'error' => string, 'code' => string]
     */
    public function authenticate(string $username, string $password): array
    {
        return $this->ldap->authenticateUser($username, $password);
    }

    /**
     * البحث عن موظف في LDAP بناءً على اسم المستخدم (username = الرقم الوظيفي)
     *
     * @param string $username اسم المستخدم / الرقم الوظيفي
     * @return array|null بيانات الموظف أو null
     */
    public function getUserByUsername(string $username): ?array
    {
        return $this->ldap->getDataFromLDAPByUsername($username);
    }

    /**
     * بحث عام عن موظف في LDAP (بـ username أو email)
     *
     * @param string $identifier اسم المستخدم أو البريد
     * @return array|null بيانات الموظف أو null
     */
    public function getUserDetails(string $identifier): ?array
    {
        return $this->ldap->getUserDetails($identifier);
    }
}
