<?php

/**
 * 
 */


namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\MissionModel;
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
            'home'              => ['label' => 'الرئيسية',          'url' => base_url('dashboard'),                  'icon' => 'home'],
            'newTask'           => ['label' => 'بدء مهمة',           'url' => base_url('dashboard/new-task'),         'icon' => 'plus'],
            'documentRequests'  => ['label' => 'قائمة المستندات',    'url' => base_url('dashboard/document-requests'), 'icon' => 'folder-check'],
            'riskMatrix'        => ['label' => 'مصفوفة المخاطر',     'url' => base_url('dashboard/risk-matrix'),      'icon' => 'bar-chart-2'],
            'meetingSchedule'   => ['label' => 'جدولة اجتماع',       'url' => base_url('dashboard/meeting-schedule'), 'icon' => 'calendar-plus'],
            'meetingSummary'    => ['label' => 'ملخص اجتماع',        'url' => base_url('dashboard/meetings'),         'icon' => 'users'],
            'observations'      => ['label' => 'الملاحظات',          'url' => base_url('dashboard/observations'),     'icon' => 'book-open'],
            'finalReports'      => ['label' => 'تقرير نهائي',        'url' => base_url('dashboard/reports'),          'icon' => 'file-text'],
            'sentTasks'         => ['label' => 'المراسلات المشتركة', 'url' => base_url('dashboard/sent-tasks'),       'icon' => 'send'],
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
            $keys = ['home', 'meetingSchedule', 'sentTasks', 'finalReports'];
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

    /**
     * مفاتيح صفحات القائمة الجانبية اللي عندها View حقيقي مُهاجَر مِن الـ SPA
     * فعليًا (Server-Rendered). باقي المفاتيح لسا تُخدَّم من shell.php (SPA) —
     * روابطها بالقائمة تُوجَّه لـ dashboard/ (الرئيسية) مؤقتًا لحد ما تُهاجر.
     */
    protected function migratedPageKeys(): array
    {
        return ['observations', 'riskMatrix', 'meetingSummary', 'documentRequests', 'meetingSchedule', 'sentTasks', 'finalReports', 'newTask', 'home'];
    }

    /**
     * نفس منطق فروع isHrDept/audit_head/افتراضي المستخدَم بـ
     * DashboardController::targetMissions()/activeMissions() ودالة
     * loadMissionsForSelector() بالجافاسكربت بالضبط — قائمة "المهمة المرتبطة"
     * الموحّدة المستخدَمة بكل صفحة فيها منتقي مهمة (مصفوفة المخاطر، الملاحظات، ...)
     */
    protected function missionsForCurrentSession(): array
    {
        $session      = session();
        $roleCode     = $session->get('role_code');
        $isHrDept     = in_array($roleCode, ['dept_coordinator', 'dept_manager', 'specialized_manager'], true);
        $missionModel = new MissionModel();

        if ($isHrDept) {
            $departmentId = $session->get('department_id');
            $missions     = $departmentId ? $missionModel->missionsForTargetDepartment((int) $departmentId) : [];
        } elseif ($roleCode === 'audit_head') {
            $departmentId = $session->get('department_id');
            $missions     = $departmentId ? $missionModel->activeMissionsForAuditDepartment((int) $departmentId) : [];
        } else {
            $userId   = (int) $session->get('user_id');
            $missions = $missionModel->activeMissionsForUser($userId);
        }

        foreach ($missions as &$m) {
            $m['next_stage'] = $missionModel->computeRealNextStage((int) $m['id']);
        }

        return $missions;
    }

    /**
     * نفس شكل استجابة ApiController::session() بالضبط، لكن كمصفوفة PHP جاهزة
     * للتمرير مباشرة لـ View بدل استدعاء JSON من الجافاسكربت — يُرجع null لو
     * ما فيه جلسة دخول نشطة (المفروض ما يصير أصلًا خلف فلتر auth، لكن للأمان)
     */
    protected function sessionUserSummary(): ?array
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return null;
        }

        $departmentParentName = null;
        $departmentId         = $session->get('department_id');
        if ($departmentId) {
            $dept = (new DepartmentModel())->find((int) $departmentId);
            if ($dept && !empty($dept['parent_id'])) {
                $parent                = (new DepartmentModel())->find((int) $dept['parent_id']);
                $departmentParentName = $parent['name_ar'] ?? null;
            }
        }

        return [
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
        ];
    }
}
