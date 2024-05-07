<?php

declare(strict_types=1);

namespace Zoghal\IrnicApi;

use GuzzleHttp\Client;
use Jackiedo\XmlArray\Xml2Array;
use Zoghal\IrnicApi\Templates\Template;

class IrNic
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

    public static $irnicApiUrl = 'https://epp.nic.ir/submit';
    public static $resselerUniqueId = 'abs-ssss';
    public static $template_cache = false;
    protected static $irnicToken;
    protected static $irnicDeposit;

    /**
     * Sets the IRNIC token and deposit for the reseller.
     *
     * @param string $token The IRNIC token to set.
     * @param string $deposit The deposit value to set.
     */
    public static function reseller(string $token, string $deposit)
    {
        self::$irnicToken = $token;
        self::$irnicDeposit = $deposit;
        Template::$cache_enabled = self::$template_cache;
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

    /**
     * Call the IRNIC API with the provided XML and return the response as an array.
     *
     * @param mixed $xml The XML data to send to the API.
     * @return array The response from the IRNIC API as an array.
     */
    public static function callApi($xml)
    {
        $client = new Client();
        $response = $client->post(
            self::$irnicApiUrl,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::$irnicToken,
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml
            ]
        );
        $response = $response->getBody()->getContents();
        $response = Xml2Array::convert($response)->toArray();
        return $response;
    }


    /**
     * Checks the content of the given contacts and returns the result.
     *
     * @param array $contacts The contacts to be checked.
     * @throws \Exception If no contacts are provided.
     * @return array An array containing the XML response and the result of the content check.
     */
    public static function contentCheck(array $contacts)
    {
        if (empty($contacts)) {
            throw new \Exception('No contacts provided');
        }
        $data = [
            'contacts' => $contacts,
            'disposit' => self::$irnicDeposit,
            'UniqueId' => self::$resselerUniqueId
        ];

        $xml = Template::view('contact/check.xml', $data);
        $xml = self::callApi($xml);
        $xml = $xml["epp"]["response"];
        $out = [
            'code' => $xml['result']['@attributes']['code'],
            'clTRID' => $xml['trID']['clTRID'],
            'svTRID' => $xml['trID']['svTRID'],
            'massage' => $xml['result']['msg'],
            'result' => []
        ];
        $xml = $xml['resData']['contact:chkData']['contact:cd'];
        foreach ($xml as $key => $value) {
            $contact = $value['contact:id']['@value'];
            if (!isset($value['contact:position'])) {
                $out['result'][$contact] = 0;
            } else {
                $pos = [];
                foreach ($value['contact:position'] as $position) {
                    $pos[$position['@attributes']['type']] = $position['@attributes']['allowed'];
                }
                $out['result'][$contact] = $pos;
            }
        }
        return $out;
    }
}
