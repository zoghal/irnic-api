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

    protected $config = [];
    public static $irnicApiUrl = 'https://epp.nic.ir/submit';
    public static $templateCache = false;
    public static $debug = false;
    /**
     * Constructs a new instance of the class.
     *
     * @param string $irnic_token The IRNIC token.
     * @param string $irnic_password The IRNIC password.
     * @param string $reseller_nic_handle The reseller NIC handle. Default is an empty string.
     * @throws \Exception If the reseller NIC handle does not end with '-irnic'.
     * @return void
     */
    private function __construct($irnic_token, $irnic_password, $reseller_nic_handle = '')
    {
        if (false === strpos($reseller_nic_handle, '-irnic')) {
            throw new \Exception('resseler_nic_handle must end with -irnic');
        }
        $reseller_nic_handle = str_replace('-irnic', '', $reseller_nic_handle);
        $this->config = [
            'token' => $irnic_token,
            'password' => $irnic_password,
            'handle' => $reseller_nic_handle
        ];
    }

    /**
     * Creates a new instance of the class with the given reseller credentials.
     *
     * @param string $token The IRNIC token.
     * @param string $deposit The IRNIC deposit.
     * @param mixed $reseller_nic_handle The reseller NIC handle.
     * @return static The newly created instance of the class.
     */
    public static function reseller(string $token, string $deposit, $reseller_nic_handle)
    {
        return new static($token, $deposit, $reseller_nic_handle);
    }


    /**
     * Retrieves the value at the specified path in the given nested array.
     *
     * @param array &$data The nested array to search in.
     * @param string $path The path to the desired value, using dot notation.
     * @param mixed $def The default value to return if the path is not found. Default is null.
     * @return mixed The value at the specified path, or the default value if the path is not found.
     */
    protected function getValue(array &$data, $path, $def = null)
    {
        $keys = explode('.', $path);
        foreach ($keys as $k) {
            if (isset($data[$k])) {
                $data = &$data[$k];
            } else {
                return $def;
            }
        }
        return $data;
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
    private function callApi($action, $data)
    {
        $xml = $this->renderTemplate($action . '.xml', $data);
        $client = new Client();
        $response = $client->post(
            self::$irnicApiUrl,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['token'],
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml
            ]
        );
        $xml = $response->getBody()->getContents();
        $array = Xml2Array::convert($xml)->toArray();
        $flat = Arrays::flatten($array);
        if (self::$debug) {
            $debug = print_r(['flat' => $flat, 'xml' => $xml, 'array' => $array], true);
            $action = str_replace('/', '-', $action);
            file_put_contents('debug' . $action . '.log', $debug);
        }
        return $flat;
    }

    /**
     * Generates a unique identifier by concatenating the handle from the configuration
     * with the current time.
     *
     * @return string The generated unique identifier.
     */
    private function generateUniqueId()
    {
        return $this->config['handle'] . '-' . time();
    }

    /**
     * Renders a template with the given name and data.
     *
     * @param string $templateName The name of the template to render.
     * @param array $data The data to pass to the template.
     * @return string The rendered template.
     */
    private function renderTemplate($templateName, array $data)
    {
        $data = array_merge($data, [
            'disposit' => $this->config['password'],
            'UniqueId' => $this->generateUniqueId()
        ]);
        return Template::view($templateName, $data);
    }

    /**
     * Checks the content of the given contacts and returns the result.
     *
     * @param array $contacts The nic-handels to be checked.
     * @throws \Exception If no contacts are provided.
     * @return array An array containing the XML response and the result of the content check.
     */
    public function contentCheck(array $contacts)
    {
        if (empty($contacts)) {
            throw new \Exception('No contacts provided');
        }
        $response = $this->callApi('contact/check', ['contacts' => $contacts]);

        $out['meta'] = [
            'code' => $response['epp.response.result.@attributes.code'],
            'massage' => $response['epp.response.result.msg'],
            'clTRID' => $response['epp.response.trID.clTRID'],
            'svTRID' => $response['epp.response.trID.svTRID'],
        ];

        $response = $response['epp.response.resData.contact:chkData.contact:cd'];

        foreach ($response as $item) {
            $contact = $item['contact:id.@value'];
            if (!isset($item['contact:position'])) {
                $out['data'][$contact] = false;
                continue;
            }
            $pos = [];
            foreach ($item['contact:position'] as $position) {
                $pos[$position['@attributes.type']] = (bool) $position['@attributes.allowed'];
            }
            $out['data'][$contact] = $pos;
        }
        return $out;
    }



    public function contentInfo(string $nic_handle = null)
    {
        if (null === $nic_handle) {
            throw new \Exception('irnic-handle or emailis not provided');
        }
        $response = $this->callApi('contact/info', ['irnic_handle' => $nic_handle]);




        $out['meta'] = [
            'code' => $response['epp.response.result.@attributes.code'],
            'massage' => $response['epp.response.result.msg'],
            'clTRID' => $response['epp.response.trID.clTRID'],
            'svTRID' => $response['epp.response.trID.svTRID'],
        ];

        if (!Arrays::has_key('epp.response.resData', $response)) {
            $out['data'] = [];
            return $out;
        }

        $out['data']['id'] = $response['epp.response.resData.contact:infData.contact:id'];
        $out['data']['roid'] = $response['epp.response.resData.contact:infData.contact:roid'];

        foreach ($response['epp.response.resData.contact:infData.contact:status'] as $key => $value) {
            $out['data']['status'][] = $value['@attributes.s'];
        }

        foreach ($response['epp.response.resData.contact:infData.contact:position'] as $key => $value) {
            $out['data']['position'][$value['@attributes.type']] = (bool) $value['@attributes.allowed'];
        }

        $out['data']['contact'] = [
            'voice' => Arrays::get('epp.response.resData.contact:infData.contact:voice', $response),
            'fax' => Arrays::get('epp.response.resData.contact:infData.contact:fax', $response),
            'ident' => Arrays::get('epp.response.resData.contact:infData.contact:ident', $response),
            'email' => Arrays::get('epp.response.resData.contact:infData.contact:email', $response),
            'crDate' => Arrays::get('epp.response.resData.contact:infData.contact:crDate', $response)
        ];

        $out['data']['contact']['postalInfo'] = [
            'firstname' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:firstname', $response),
            'lastname' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:lastname', $response),
            'street' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:addr.contact:street', $response),
            'city' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:addr.contact:city', $response),
            'sp' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:addr.contact:sp', $response),
            'pc' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:addr.contact:pc', $response),
            'cc' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.contact:addr.contact:cc', $response),
            'type' => Arrays::get('epp.response.resData.contact:infData.contact:postalInfo.@attributes.type', $response),
        ];

        if (Arrays::has_key('epp.response.extension', $response)) {
            $out['extension'] = [
                'recieved' => $response['epp.response.extension.contractInfo.recieved'],
                'spent' => $response['epp.response.extension.contractInfo.spent'],
                'balance' =>  $response['epp.response.extension.contractInfo.balance'],
                'number' => $response['epp.response.extension.contractInfo.@attributes.number'],
            ];
        }

        return $out;
    }



    public function contactUpdate(string $nic_handle, array $data)
    {
        //TODO: بعد از ثبت یه دامنه تکمیل گردد.
    }



    /**
     * Checks the availability of the given domains and returns the result.
     *
     * @param array $domains An array of domain names to be checked.
     * @return array An array containing the meta information and the availability status of each domain.
     * The array structure is as follows:
     * [
     *     'meta' => [
     *         'code' => The response code,
     *         'massage' => The response message,
     *         'clTRID' => The client transaction ID,
     *         'svTRID' => The server transaction ID,
     *     ],
     *     'data' => [
     *         'domain_name' => [
     *             'normalized_name' => The normalized domain name,
     *             'canonized_name' => The canonized domain name,
     *             'tld' => The top-level domain (TLD),
     *             'available' => A boolean indicating whether the domain is available or not,
     *         ],
     *         ...
     *     ],
     * ]
     */
    public function domainCheck(array $domains)
    {
        $response = $this->callApi('domain/check', ['domains' => $domains]);

        $out['meta'] = [
            'code' => $response['epp.response.result.@attributes.code'],
            'massage' => $response['epp.response.result.msg'],
            'clTRID' => $response['epp.response.trID.clTRID'],
            'svTRID' => $response['epp.response.trID.svTRID'],
        ];

        foreach ($response['epp.response.resData.domain:chkData.domain:cd'] as $domain) {
            $out['data'][] = [
                'domain' => $domain['domain:name.@value'],
                'normalized_name' => $domain['domain:name.@attributes.normalized_name'],
                'canonized_name' => $domain['domain:name.@attributes.canonized_name'],
                'tld' => $domain['domain:name.@attributes.tld'],
                'available' => (bool)$domain['domain:name.@attributes.avail'],
                'reason' => Arrays::get('domain:reason', $domain),
            ];
        }
        return $out;
    }


    public function domainInfo(string $domain)
    {
        $response = $this->callApi('domain/info', ['domain' => $domain]);
        print_r($response);
        $out['meta'] = [
            'code' => $response['epp.response.result.@attributes.code'],
            'massage' => $response['epp.response.result.msg'],
            'clTRID' => $response['epp.response.trID.clTRID'],
            'svTRID' => $response['epp.response.trID.svTRID'],
        ];
        $out['data']['domain'] = $response['epp.response.resData.domain:infData.domain:name'];
        $out['data']['roid'] =  Arrays::get('epp.response.resData.domain:infData.domain:roid', $response);
        $out['data']['upDate'] =  Arrays::get('epp.response.resData.domain:infData.domain:upDate', $response);
        $out['data']['exDate'] =  Arrays::get('epp.response.resData.domain:infData.domain:exDate', $response);
        foreach ($response['epp.response.resData.domain:infData.domain:status'] as $status) {
            $out['data']['status'][] = $status['@attributes.s'];
        }
        foreach ($response['epp.response.resData.domain:infData.domain:contact'] as $contact) {
            $out['data']['contact'][$contact['@attributes.type']] = $contact['@value'];
        }
        foreach ($response['epp.response.resData.domain:infData.domain:ns.domain:hostAttr'] as $key =>  $ns) {
            $out['data']['ns'][$key]['hostName'] = $ns['domain:hostName'];
            $out['data']['ns'][$key]['hostAddr'] = Arrays::get('domain:hostAddr.@value', $ns);
            $out['data']['ns'][$key]['ip'] = Arrays::get('domain:hostAddr.@attributes.ip', $ns);
        }
        return $out;
    }
}
