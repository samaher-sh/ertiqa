<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * إعدادات الاتصال بخادم Active Directory عبر LDAP -- كل القيم الفعلية تُقرأ
 * من .env (غير مرفوع لـ git، بنفس آلية Config\Database مع بيانات قاعدة
 * البيانات: مفاتيح "ldap.<اسم الخاصية>"). لا تُوضع أي قيمة حقيقية هنا بالكود
 * مباشرة -- راجعي env.example للمفاتيح المطلوبة.
 */
class Ldap extends BaseConfig
{
    /** عنوان/IP خادم LDAP (ldap.host بملف .env) */
    public string $host = '';

    /** المنفذ (389 لـ LDAP العادي، 636 لـ LDAPS) */
    public int $port = 389;

    /** حساب الخدمة (Bind DN) المستخدَم للبحث عن الموظفين -- مو حساب مستخدم عادي */
    public string $bindDn = '';

    /** كلمة مرور حساب الخدمة */
    public string $bindPassword = '';

    /** قاعدة البحث التنظيمية (Base DN)، مثال: OU=Users,DC=kamc,DC=med,DC=sa */
    public string $baseDn = '';
}
