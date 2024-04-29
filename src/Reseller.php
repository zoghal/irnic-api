<?php

declare(strict_types=1);

namespace Zoghal\IrnicApi;


class Reseller
{
    private static $ERROR_CODES = [
        1000 => [
            "en" =>  "Command completed successfully",
            "fa" =>  "درخواست به درستی دریافت و عملیات مربوط به آن با موفقیت انجام شد."
        ],
        1001 => [
            "en" =>  "Command completed successfully; action pending",
            "fa" =>  "درخواست به درستی دریافت و عملیات مربوط به آن پس از بررسی و طی مراحل مربوطه انجام می‌شود."
        ],
        1300 => [
            "en" =>  "Command completed successfully; no messages",
            "fa" =>  "درخواست دریافت پیغام به درستی دریافت شد. پیغام جدیدی موجود نیست."
        ],
        1301 => [
            "en" =>  "Command completed successfully; ack to dequeue",
            "fa" =>  "درخواست دریافت پیغام به درستی دریافت و پیغام شما ارسال شد. به ایرنیک برای خارج کردن آن از فهرست پیغامهای جدید اطلاع دهید."
        ],
        2000 => [
            "en" =>  "Unknown command",
            "fa" =>  "فرمت درخواست ارسال شده درست نیست."
        ],
        2001 => [
            "en" =>  "Command syntax error",
            "fa" =>  "درخواست دارای دستور اشتباه است."
        ],
        2003 => [
            "en" =>  "Required parameter missing",
            "fa" =>  "یکی از موارد اجباری در درخواست موجود نیست."
        ],
        2004 => [
            "en" =>  "Parameter value range error",
            "fa" =>  "مقدار ارسال شده در درخواست خارج از محدوده مورد قبول است."
        ],
        2005 => [
            "en" =>  "Parameter value syntax error",
            "fa" =>  "مقدار ارسال شده در درخواست دارای فرمت اشتباه است."
        ],
        2101 => [
            "en" =>  "Unimplemented command",
            "fa" =>  "درخواست ارسال شده اشتباه است."
        ],
        2104 => [
            "en" =>  "Billing failure",
            "fa" =>  "وقوع مشکل در هنگام انجام عملیات مالی."
        ],
        2105 => [
            "en" =>  "Object is not eligible for renewal",
            "fa" =>  "دامنه برای تمدید واجد شرایط لازم نمی‌باشد."
        ],
        2200 => [
            "en" =>  "Authentication error",
            "fa" =>  "تشخیص هویت برای درخواست دهنده انجام نشده است."
        ],
        2201 => [
            "en" =>  "Authorization error",
            "fa" =>  "دسترسی به این خدمت برای شما مقدور نمی‌باشد."
        ],
        2202 => [
            "en" =>  "Invalid authorization information",
            "fa" =>  "اطلاعات حق دسترسی شما صحیح نمی‌باشد."
        ],
        2302 => [
            "en" =>  "Object exists",
            "fa" =>  "مورد درخواست برای ایجاد قبلا در سامانه ثبت شده است."
        ],
        2303 => [
            "en" =>  "Object does not exist",
            "fa" =>  "مورد درخواست شده موجود نیست. در سامانه وجود ندارد."
        ],

        2304 => [
            "en" =>  "Object status prohibits operation",
            "fa" =>  "تغییر «مورد درخواست» امکانپذیر نمی‌باشد."
        ],

        2306 => [
            "en" =>  "Parameter value policy error",
            "fa" =>  "یکی از مقادیر داده شده مغایر با مقررات ثبت دامنه است."
        ],

        2308 => [
            "en" =>  "Data management policy violation",
            "fa" =>  "مدیریت داده‌ها اشتباه است."
        ],

        2400 => [
            "en" =>  "Command failed",
            "fa" =>  "درخواست داده شده انجام نگردید."
        ]
    ];

    public function __construct()
    {
    }


    /**
     * Retrieves the error message corresponding to the given error code.
     *
     * @param int $errCode The error code to retrieve the error message for.
     * @return string The error message.
     */
    public static function getError(int $errCode): string
    {
        return self::$ERROR_CODES[$errCode]['en'];
    }
}
