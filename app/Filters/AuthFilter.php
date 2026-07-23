<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * يمنع أي شخص غير مسجّل دخول من فتح صفحات محمية (زي /dashboard و /client)
 * يُطبَّق كمجموعة Route بملف Routes.php — راجع ROUTES_TO_ADD.txt
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/')->with('error', 'يرجى تسجيل الدخول أولاً.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ما نحتاج نسوي شي بعد الطلب
    }
}
