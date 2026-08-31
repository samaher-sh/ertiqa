<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table         = 'departments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['parent_id', 'name_ar', 'is_active', 'sort_order'];

    public function mainDepartments(): array
    {
        return $this->where('parent_id', null)->where('is_active', 1)->orderBy('sort_order')->findAll();
    }

    public function subDepartments(int $parentId): array
    {
        return $this->where('parent_id', $parentId)->where('is_active', 1)->orderBy('sort_order')->findAll();
    }

    public function findByNameAr(string $name): ?array
    {
        return $this->where('name_ar', $name)->first() ?: null;
    }

    /**
     * كل الإدارات (رئيسية وفرعية) بترتيب هرمي مسطّح -- كل إدارة رئيسية
     * وبعدها فروعها مباشرة -- تُستخدم لقائمة منسدلة واحدة مسطّحة (زي فورم
     * إضافة مستخدم) بدل قائمتين متتاليتين (رئيسية ثم فرعية)
     */
    public function flatHierarchy(): array
    {
        $mains = $this->mainDepartments();
        $flat = [];
        foreach ($mains as $main) {
            $flat[] = $main;
            foreach ($this->subDepartments((int) $main['id']) as $sub) {
                $flat[] = $sub;
            }
        }
        return $flat;
    }

    /**
     * يقارن إدارتين مع مراعاة الهرمية (إدارة رئيسية + أقسام فرعية تحتها) --
     * لا يكفي تطابق الرقم بالضبط، لأنه منسّق الإدارة الرئيسية (زي الموارد
     * البشرية) لازم يُعتبر بنطاق أي مهمة تستهدف قسمًا فرعيًا تحتها (زي
     * التوظيف) والعكس صحيح. مستخدَمة بكل صلاحية وصول تقارن إدارة مستخدم
     * بإدارة مستهدفة بمهمة (مصفوفة مستوى الخدمة، تحميل المرفقات، ...)
     */
    public function isInScope(int $deptA, int $deptB): bool
    {
        if (!$deptA || !$deptB) {
            return false;
        }
        if ($deptA === $deptB) {
            return true;
        }

        $a = $this->find($deptA);
        if ($a && (int) ($a['parent_id'] ?? 0) === $deptB) {
            return true;
        }

        $b = $this->find($deptB);
        if ($b && (int) ($b['parent_id'] ?? 0) === $deptA) {
            return true;
        }

        return false;
    }
}
