<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * تعريف كل عناصر القائمة الجانبية الممكنة.
     * icon هنا اسم أيقونة Lucide (نفس مكتبة الأيقونات المستخدمة بواجهة shell.php الجديدة)
     */
    protected function allNavItems(): array
    {
        return [
            'home'           => ['label' => 'الرئيسية',          'desc' => 'Dashboard',        'url' => base_url('dashboard'),               'icon' => 'home'],
            'newTask'        => ['label' => 'بدء مهمة',           'desc' => 'New Audit Task',   'url' => base_url('dashboard/new-task'),      'icon' => 'plus'],
            'riskMatrix'     => ['label' => 'مصفوفة المخاطر',     'desc' => 'Risk Matrix',      'url' => base_url('dashboard/risk-matrix'),   'icon' => 'bar-chart-2'],
            'meetingSummary' => ['label' => 'ملخص اجتماع',        'desc' => 'Meeting Summary',  'url' => base_url('dashboard/meetings'),      'icon' => 'users'],
            'observations'   => ['label' => 'الملاحظات',          'desc' => 'Observations',     'url' => base_url('dashboard/observations'),  'icon' => 'book-open'],
            'finalReports'   => ['label' => 'تقرير نهائي',        'desc' => 'Final Reports',    'url' => base_url('dashboard/reports'),       'icon' => 'file-text'],
            'sentTasks'      => ['label' => 'المراسلات المشتركة', 'desc' => 'Sent Tasks',       'url' => base_url('dashboard/sent-tasks'),    'icon' => 'send'],
        ];
    }

    /**
     * يفلتر عناصر القائمة حسب الدور — نفس منطق MAIN_NAV.filter() بالواجهة الأصلية بالضبط
     */
    protected function navItemsForRole(bool $isPresident, bool $isHrDept, bool $isAuditHead): array
    {
        $all  = $this->allNavItems();
        $keys = array_keys($all);

        if ($isPresident) {
            $keys = ['home', 'finalReports'];
        } elseif ($isHrDept) {
            $keys = ['home', 'meetingSummary', 'observations', 'finalReports', 'sentTasks'];
        } elseif ($isAuditHead) {
            $keys = ['home', 'finalReports'];
        }

        $result = [];
        foreach ($keys as $k) {
            if (isset($all[$k])) {
                $result[] = array_merge(['key' => $k], $all[$k]);
            }
        }
        return $result;
    }

    /**
     * يبني عناصر القائمة الجانبية حسب دور الجلسة الحالية
     */
    protected function navItemsForCurrentSession(): array
    {
        $roleCode = session()->get('role_code');

        return $this->navItemsForRole(
            $roleCode === 'top_management',
            in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true),
            $roleCode === 'audit_head'
        );
    }
}
