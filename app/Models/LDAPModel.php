<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * الملف الوحيد اللي يتصل فعليًا بخادم Active Directory عبر بروتوكول LDAP.
 * كل العمليات الحقيقية (الاتصال، التحقق من كلمة المرور، البحث عن بيانات
 * الموظف) تحدث هنا فقط -- ما فيه أي كود آخر بالمشروع يستدعي دوال ldap_*
 * مباشرة، الكل يمر عبر LDAPService.
 *
 * بيانات الاتصال (host/port/bindDn/bindPassword/baseDn) تُقرأ من Config\Ldap
 * (بدورها من .env، غير مرفوع لـ git) -- لا تُكتب هنا كقيم ثابتة إطلاقًا.
 */
class LDAPModel extends Model
{
    private string $ldapHost;
    private int    $ldapPort;
    private string $ldapUser;
    private string $ldapPass;
    private string $ldapBaseDn;

    public function __construct()
    {
        parent::__construct();

        $config = config('Ldap');
        $this->ldapHost   = $config->host;
        $this->ldapPort   = $config->port;
        $this->ldapUser   = $config->bindDn;
        $this->ldapPass   = $config->bindPassword;
        $this->ldapBaseDn = $config->baseDn;
    }

    /** يفتح اتصال بخادم LDAP ويربط ببروتوكول v3 -- يرجّع null لو الامتداد غير مفعّل أو تعذّر الاتصال */
    private function connect()
    {
        if (!function_exists('ldap_connect')) {
            return null;
        }

        $conn = @ldap_connect($this->ldapHost, $this->ldapPort);
        if (!$conn) {
            return null;
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        return $conn;
    }

    /**
     * التحقق من صحة بيانات دخول موظف عبر LDAP (search-then-bind):
     * 1) نتصل بحساب الخدمة عشان نبحث عن DN الحقيقي للموظف برقمه الوظيفي.
     * 2) نحاول Bind بنفس DN الموظف + كلمة المرور المُدخلة -- هذا هو التحقق
     *    الفعلي (LDAP ما يوفّر طريقة تانية للتحقق من كلمة مرور مستخدم غير
     *    Bind فعلي بحسابه هو نفسه).
     *
     * @return array{success: bool, username: string, error: string, code: string}
     */
    public function authenticateUser(string $username, string $password): array
    {
        $invalid = ['success' => false, 'username' => $username, 'error' => 'بيانات الدخول غير صحيحة.', 'code' => 'invalid_credentials'];

        if ($username === '' || $password === '') {
            return $invalid;
        }

        $conn = $this->connect();
        if (!$conn) {
            return ['success' => false, 'username' => $username, 'error' => 'تعذّر الاتصال بخادم الدليل الموحّد (LDAP).', 'code' => 'connection_failed'];
        }

        // خطوة 1: بحث بحساب الخدمة عشان نجيب DN الموظف الحقيقي
        if (!@ldap_bind($conn, $this->ldapUser, $this->ldapPass)) {
            ldap_unbind($conn);
            return ['success' => false, 'username' => $username, 'error' => 'تعذّر الاتصال بخادم الدليل الموحّد (LDAP).', 'code' => 'service_bind_failed'];
        }

        $escaped = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $search = @ldap_search($conn, $this->ldapBaseDn, "(sAMAccountName={$escaped})", ['dn']);
        if (!$search) {
            ldap_unbind($conn);
            return $invalid;
        }

        $entries = ldap_get_entries($conn, $search);
        if ($entries['count'] === 0) {
            ldap_unbind($conn);
            return $invalid;
        }

        $userDn = $entries[0]['dn'];

        // خطوة 2: Bind فعلي بحساب الموظف نفسه -- ينجح فقط لو كلمة المرور صحيحة
        $verified = @ldap_bind($conn, $userDn, $password);
        ldap_unbind($conn);

        if (!$verified) {
            return $invalid;
        }

        return ['success' => true, 'username' => $username, 'error' => '', 'code' => ''];
    }

    /** بحث عام عن موظف بـ username أو email (تطابق جزئي) */
    public function getUserDetails(?string $identifier): ?array
    {
        if (empty($identifier)) {
            return null;
        }

        $conn = $this->connect();
        if (!$conn) {
            return null;
        }

        if (!@ldap_bind($conn, $this->ldapUser, $this->ldapPass)) {
            ldap_unbind($conn);
            return null;
        }

        $escaped = ldap_escape($identifier, '', LDAP_ESCAPE_FILTER);
        $filter = "(|(sAMAccountName=*{$escaped}*)(mail=*{$escaped}*))";
        $attributes = ['displayname', 'mail', 'samaccountname', 'userprincipalname', 'department'];

        $search = @ldap_search($conn, $this->ldapBaseDn, $filter, $attributes);
        if (!$search) {
            ldap_unbind($conn);
            return null;
        }

        $entries = ldap_get_entries($conn, $search);
        ldap_unbind($conn);

        if ($entries['count'] === 0) {
            return null;
        }

        $entry = $entries[0];

        return [
            'username' => $entry['samaccountname'][0] ?? $identifier,
            'fullname' => $entry['displayname'][0] ?? $identifier,
            'email'    => $entry['mail'][0] ?? '',
            'dept'     => $entry['department'][0] ?? '',
        ];
    }

    /** بحث بالرقم الوظيفي (sAMAccountName) بالضبط -- يُستخدم لجلب بيانات موظف قبل إضافته */
    public function getDataFromLDAPByUsername(?string $username): ?array
    {
        if (empty($username)) {
            return null;
        }

        $conn = $this->connect();
        if (!$conn) {
            return null;
        }

        if (!@ldap_bind($conn, $this->ldapUser, $this->ldapPass)) {
            ldap_unbind($conn);
            return null;
        }

        $escaped = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $filter = "(sAMAccountName={$escaped})";

        $search = @ldap_search($conn, $this->ldapBaseDn, $filter, ['displayname', 'mail', 'samaccountname', 'department']);
        if (!$search) {
            ldap_unbind($conn);
            return null;
        }

        $entries = ldap_get_entries($conn, $search);
        ldap_unbind($conn);

        if ($entries['count'] === 0) {
            return null;
        }

        $entry = $entries[0];

        return [
            'username' => $entry['samaccountname'][0] ?? $username,
            'fullname' => $entry['displayname'][0] ?? $username,
            'email'    => $entry['mail'][0] ?? '',
            'dept'     => $entry['department'][0] ?? '',
        ];
    }
}
