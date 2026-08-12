<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #152c33; direction: rtl; }
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .header-table td { vertical-align: middle; }
    .header-right { text-align: right; font-size: 12px; font-weight: bold; color: #196b7f; }
    .header-left { text-align: left; font-size: 11px; color: #6b7280; }
    .divider { border-top: 2px solid #3185b3; margin: 10px 0 16px; }
    .addr { font-weight: bold; font-size: 14px; margin-bottom: 8px; }
    .greet { font-weight: bold; margin-bottom: 12px; }
    p.body-p { line-height: 1.9; text-align: right; margin: 0 0 10px; }
    mark { background: #f0f7fa; color: #196b7f; padding: 1px 5px; font-weight: bold; }
    .procedure-box { border: 1px solid #b3d4e5; border-radius: 6px; margin-bottom: 10px; }
    .procedure-head { background: #f0f7fa; color: #196b7f; padding: 6px 10px; font-size: 11px; font-weight: bold; }
    .procedure-body { padding: 8px 10px; font-size: 13px; }
    .contacts { margin: 8px 0; }
    .contacts div { margin-bottom: 4px; }
    .bar { height: 5px; background: #3185b3; margin-top: 20px; border-radius: 3px; }
</style>
</head>
<body>
    <table class="header-table">
        <tr>
            <!-- kamc-pdf-logo.png مقصوص لحجمه الفعلي بالضبط (32×30) بدون سمة width --
                 mPDF يفشل بصمت بتصغير شعار kamc.png الأصلي (1005×944) داخل جدول مهما
                 كانت نسبة التصغير، حتى 1.5x؛ العرض الطبيعي 1:1 فقط يشتغل بثبات -->
            <td width="34"><img src="<?= FCPATH ?>assets/images/kamc-pdf-logo.png"></td>
            <td class="header-right">إدارة المراجعة الداخلية</td>
            <td class="header-left">
                التاريخ: <?= date('d/m/Y') ?><br>
                الرقم: م.م / <?= esc($mission['year']) ?> / <?= esc(substr($mission['mission_code'], -3)) ?>
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    <p class="addr">سعادة المدير التنفيذي لـ<mark><?= esc($mainDept['name_ar'] ?? '') ?></mark> المحترم</p>
    <p class="greet">السلام عليكم ورحمة الله وبركاته،،،</p>

    <p class="body-p">نود الإفادة بأن إدارة المراجعة الداخلية بصدد القيام بزيارة <mark><?= esc($targetDept['name_ar'] ?? '') ?></mark>، للقيام بعملية المراجعة الداخلية، وذلك وفق خطة المراجعة لعام <mark><?= esc($mission['year']) ?></mark>م المعتمدة من قبل المدير العام التنفيذي.</p>

    <p class="body-p">عليه نأمل تلطف سعادتكم بتوجيه من يلزم للعمل على التنسيق - خلال مدة لا تتجاوز <strong>(7) أيام عمل</strong> من تاريخه - لعقد اجتماع افتتاحي لفريق المراجعة مع سعادتكم أو من ترونه مناسباً:</p>

    <?php if (!empty($mission['procedure_note'])): ?>
    <div class="procedure-box">
        <div class="procedure-head">المراد مناقشته في الاجتماع</div>
        <div class="procedure-body"><?= nl2br(esc($mission['procedure_note'])) ?></div>
    </div>
    <?php endif; ?>

    <p class="body-p">كما نأمل التكرم بتوجيه المختصين لتزويدنا بالمتطلبات الأولية (مرفق 1) والاطلاع والموافقة على اتفاقية مستوى الخدمة من قبل ممثل الإدارة (مرفق 2) حتى يتسنى لنا البدء بعملية المراجعة. إن تحضير هذه المتطلبات والموافقة على الاتفاقية مسبقاً سوف يساهم في سرعة وسهولة عملية المراجعة الداخلية ويقلل من إرباك أو مقاطعة موظفي الإدارة، هذه القائمة مبدئية ومن المحتمل أن نقوم بطلب وثائق ومستندات أخرى خلال عملية المراجعة.</p>
    <p class="body-p">حرصاً على وقتكم نأمل بتكليف مسؤول اتصال / منسق لمساعدة فريق العمل خلال فترة المراجعة.</p>
    <p class="body-p">علماً بأن المراجع الرئيسي لهذه العملية الأستاذ / <mark><?= esc($mission['reviewer_name']) ?></mark></p>
    <p class="body-p">والذي يمكن التواصل معه عبر القنوات التالية:</p>

    <div class="contacts">
        <div>البريد الإلكتروني: <strong><?= esc($mission['reviewer_email']) ?></strong></div>
        <div>رقم الجوال: <strong><?= esc($mission['reviewer_phone']) ?></strong></div>
    </div>

    <p class="body-p" style="margin-top:16px;">وتقبلوا وافر تحياتي وتقديري،،،</p>
    <p class="body-p">مدير إدارة المراجعة الداخلية</p>
    <?php if (!empty($mission['director_name'])): ?>
        <p class="body-p" style="font-weight:bold;color:#196b7f;"><?= esc($mission['director_name']) ?></p>
    <?php endif; ?>

    <div class="bar"></div>
</body>
</html>
