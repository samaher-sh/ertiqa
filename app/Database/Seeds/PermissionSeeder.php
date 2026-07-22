<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $permissions = [
            ['code' => 'missions.view',              'module' => 'missions',            'stage_number' => null, 'action' => 'view',    'name_ar' => 'عرض المهام الرقابية'],
            ['code' => 'missions.create',            'module' => 'missions',            'stage_number' => null, 'action' => 'create',  'name_ar' => 'إنشاء مهمة رقابية جديدة'],
            ['code' => 'missions.edit',              'module' => 'missions',            'stage_number' => null, 'action' => 'edit',    'name_ar' => 'تعديل بيانات المهمة'],

            ['code' => 'sla.view',                   'module' => 'sla',                 'stage_number' => 1,    'action' => 'view',    'name_ar' => 'عرض اتفاقية مستوى الخدمة'],
            ['code' => 'sla.create',                 'module' => 'sla',                 'stage_number' => 1,    'action' => 'create',  'name_ar' => 'إنشاء طلب اتفاقية مستوى الخدمة'],
            ['code' => 'sla.respond',                'module' => 'sla',                 'stage_number' => 1,    'action' => 'edit',    'name_ar' => 'الرد على بنود اتفاقية مستوى الخدمة'],

            ['code' => 'documents.view',             'module' => 'documents',           'stage_number' => 1,    'action' => 'view',    'name_ar' => 'عرض طلبات المستندات'],
            ['code' => 'documents.request',          'module' => 'documents',           'stage_number' => 1,    'action' => 'create',  'name_ar' => 'إنشاء طلب مستندات'],
            ['code' => 'documents.respond',          'module' => 'documents',           'stage_number' => 1,    'action' => 'edit',    'name_ar' => 'الرد على طلب المستندات ورفعها'],

            ['code' => 'risk_matrix.view',           'module' => 'risk_matrix',         'stage_number' => 2,    'action' => 'view',    'name_ar' => 'عرض مصفوفة المخاطر'],
            ['code' => 'risk_matrix.edit',           'module' => 'risk_matrix',         'stage_number' => 2,    'action' => 'edit',    'name_ar' => 'تعديل مصفوفة المخاطر'],

            ['code' => 'meetings.view',              'module' => 'meetings',            'stage_number' => 2,    'action' => 'view',    'name_ar' => 'عرض الاجتماعات'],
            ['code' => 'meetings.create',            'module' => 'meetings',            'stage_number' => 2,    'action' => 'create',  'name_ar' => 'إنشاء اجتماع'],
            ['code' => 'meetings.edit',              'module' => 'meetings',            'stage_number' => 2,    'action' => 'edit',    'name_ar' => 'تعديل بيانات/محضر الاجتماع'],

            ['code' => 'audit_notes.view',           'module' => 'audit_notes',         'stage_number' => 3,    'action' => 'view',    'name_ar' => 'عرض الملاحظات الرقابية'],
            ['code' => 'audit_notes.create',         'module' => 'audit_notes',         'stage_number' => 3,    'action' => 'create',  'name_ar' => 'إنشاء ملاحظة رقابية'],
            ['code' => 'audit_notes.edit',           'module' => 'audit_notes',         'stage_number' => 3,    'action' => 'edit',    'name_ar' => 'تعديل الملاحظة الرقابية'],
            ['code' => 'audit_notes.sign',           'module' => 'audit_notes',         'stage_number' => 3,    'action' => 'sign',    'name_ar' => 'التوقيع على الملاحظة الرقابية'],
            ['code' => 'audit_notes.approve',        'module' => 'audit_notes',         'stage_number' => 3,    'action' => 'approve', 'name_ar' => 'اعتماد إدراج الملاحظة بالتقرير'],

            ['code' => 'reports.view',               'module' => 'reports',             'stage_number' => 4,    'action' => 'view',    'name_ar' => 'عرض التقرير الرقابي النهائي'],
            ['code' => 'reports.edit_checklist',     'module' => 'reports',             'stage_number' => 4,    'action' => 'edit',    'name_ar' => 'تعبئة تشيك ليست التقرير (يشمل إنشاءه تلقائيًا)'],

            ['code' => 'signatures.sign',            'module' => 'signatures',          'stage_number' => 6,    'action' => 'sign',    'name_ar' => 'التوقيع الإلكتروني التسلسلي'],

            ['code' => 'reports.send',               'module' => 'reports',             'stage_number' => 7,    'action' => 'approve', 'name_ar' => 'اعتماد إرسال التقرير النهائي'],

            ['code' => 'corrective_actions.view',    'module' => 'corrective_actions',  'stage_number' => 7,    'action' => 'view',    'name_ar' => 'عرض الإجراءات التصحيحية'],
            ['code' => 'corrective_actions.edit',    'module' => 'corrective_actions',  'stage_number' => 7,    'action' => 'edit',    'name_ar' => 'تحديث حالة الإجراء التصحيحي'],
            ['code' => 'corrective_actions.verify',  'module' => 'corrective_actions',  'stage_number' => 7,    'action' => 'approve', 'name_ar' => 'اعتماد إغلاق الإجراء التصحيحي'],

            ['code' => 'admin.manage_users',         'module' => 'admin',               'stage_number' => null, 'action' => 'edit',    'name_ar' => 'إدارة المستخدمين'],
            ['code' => 'admin.manage_permissions',   'module' => 'admin',               'stage_number' => null, 'action' => 'edit',    'name_ar' => 'إدارة الأدوار والصلاحيات'],
        ];

        foreach ($permissions as &$p) {
            $p['created_at'] = $now;
        }

        $this->db->table('permissions')->insertBatch($permissions);
    }
}
