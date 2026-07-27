<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportChecklistItemModel extends Model
{
    protected $table         = 'report_checklist_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['report_id', 'section_number', 'section_title', 'item_text', 'is_checked', 'sort_order', 'created_at'];

    public function forReport(int $reportId): array
    {
        return $this->where('report_id', $reportId)->orderBy('sort_order')->findAll();
    }

    public function setChecked(int $reportId, int $sectionNumber, bool $checked): void
    {
        $this->where('report_id', $reportId)->where('section_number', $sectionNumber)->set(['is_checked' => $checked ? 1 : 0])->update();
    }
}
