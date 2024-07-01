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

        $this->config = [
            'token' => $irnic_token,
            'password' => $irnic_password,
            'handle' => str_replace('-irnic', '', $reseller_nic_handle),
            'reseller' => $reseller_nic_handle
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
     * Determines the type of IP address provided.
     *
     * @param string $ip The IP address to check.
     * @return string|bool Returns 'v4' if the IP address is a IPv4 address, 'v6' if it is a IPv6 address, or false if it is not a valid IP address.
     */
    protected function getTypeIP($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (filter_var($ip, FILTER_FLAG_IPV6)) {
            return 'v6';
        } else {
            return 'v4';
        }
    }


    /**
     * Call the IRNIC API with the provided XML and return the response as an array.
     *
     * @param string $action The action to be performed.
     * @param array $data The data to send to the API.
     * @param bool $reternXml Whether to return the XML directly. Default is false.
     * @return array The response from the IRNIC API as an array.
     */
    private function callApi(string $action, array $data, $returnXML = false)
    {
        $xml = $this->renderTemplate($action . '.xml', $data);
        if ($returnXML) {
            return $xml;
        }
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

        if ($response->getStatusCode() != 200) {
            throw new \Exception('IRNIC API error: ' . $response->getBody()->getContents());
        }

        $xml = $response->getBody()->getContents();
        $array = Xml2Array::convert($xml)->toArray();


        $flat = Arrays::flatten($array);
        if (self::$debug) {
            $debug = print_r(['flat' => $flat, 'xml' => $xml, 'array' => $array], true);
            $action = str_replace('/', '-', $action);
            file_put_contents('debug' . $action . '.log', $debug, FILE_APPEND);
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
     * Checks the permissibility of contacts based on certain criteria.
     *
     * @param array $contacts The array of contacts to be checked.
     * @throws \Exception If an error occurs during the contact check process.
     * @return array An array containing the errors for contacts that do not meet the criteria.
     */
    private function _checkPermissibleContacts(array $contacts)
    {
        $positions = [];
        $nichandles = array_unique($contacts);
        $err = [];
        foreach ($nichandles as $key => $value) {
            if (trim($value) === $this->config['reseller']) {
                continue;
            }
            $info = $this->contentInfo($value);
            if ($info['meta']['code'] != 1000) {
                $positions[$value] = false;
            } else {
                $positions[$value] = $info['data']['position'];
            }
        }

        foreach ($contacts as $key => $value) {
            if (trim($value) === $this->config['reseller']) {
                continue;
            }
            if (!is_array($positions[$value])) {
                $err[$value] = 'This contact is not correct';
                continue;
            }
            if ($positions[$value][$key] != true) {
                $err[$value] = 'You are not allowed to set the `' . $key . '` for this contact';
            }
        }
        return $err;
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
     * Retrieves the response metadata from the given response.
     *
     * @param array $response The response from which to extract the metadata.
     * @return array The response metadata, containing the code, message, errors,
     *               client transaction ID, and server transaction ID.
     */
    private function getResponseMeta($response)
    {
        $meta = [];
        $meta['code'] = $response['epp.response.result.@attributes.code'];
        $meta['massage'] = $response['epp.response.result.msg'];
        $meta['errors'] = Arrays::get('epp.response.result.value', $response);
        if ($meta['errors'] === null) {
            $meta['errors'] = Arrays::get('epp.response.result.extValue.*', $response);
        }
        $meta['clTRID'] = $response['epp.response.trID.clTRID'];
        $meta['svTRID'] = $response['epp.response.trID.svTRID'];

        return $meta;
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
        $out['meta'] = $this->getResponseMeta($response);

        if (!Arrays::has_key('epp.response.resData', $response)) {
            $out['data'] = [];
            return $out;
        }

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



    /**
     * Retrieves information about a contact based on the provided NIC handle.
     *
     * @param string|null $nic_handle The NIC handle of the contact to retrieve information for.
     * @throws \Exception If the NIC handle or email is not provided.
     * @return array The information about the contact including meta data, contact details, and extension details.
     */
    public function contentInfo(string $nic_handle = null)
    {
        if (null === $nic_handle) {
            throw new \Exception('irnic-handle or emailis not provided');
        }
        $response = $this->callApi('contact/info', ['irnic_handle' => $nic_handle]);
        $out['meta'] = $this->getResponseMeta($response);

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
     */
    public function domainCheck(array $domains)
    {
        $response = $this->callApi('domain/check', ['domains' => $domains]);
        $out['meta'] = $this->getResponseMeta($response);

        if (!Arrays::has_key('epp.response.resData', $response)) {
            $out['data'] = [];
            return $out;
        }

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


    /**
     * Retrieves information about a domain.
     *
     * @param string $domain The domain name to retrieve information for.
     * @throws \Exception If the API call fails.
     * @return array An array containing the meta information and the domain information.
     * The array structure is as follows:
     */
    public function domainInfo(string $domain)
    {
        $response = $this->callApi('domain/info', ['domain' => $domain]);
        $out['meta'] = $this->getResponseMeta($response);

        if (!Arrays::has_key('epp.response.resData', $response)) {
            $out['data'] = [];
            return $out;
        }

        $out['data']['domain'] = $response['epp.response.resData.domain:infData.domain:name'];
        $out['data']['roid'] =  Arrays::get('epp.response.resData.domain:infData.domain:roid', $response);
        $out['data']['crDate'] =  Arrays::get('epp.response.resData.domain:infData.domain:crDate', $response);
        $out['data']['upDate'] =  Arrays::get('epp.response.resData.domain:infData.domain:upDate', $response);
        $out['data']['exDate'] =  Arrays::get('epp.response.resData.domain:infData.domain:exDate', $response);

        foreach ($response['epp.response.resData.domain:infData.domain:status'] as $status) {
            $out['data']['status'][] = $status['@attributes.s'];
        }
        foreach ($response['epp.response.resData.domain:infData.domain:contact'] as $contact) {
            $out['data']['contact'][$contact['@attributes.type']] = $contact['@value'];
        }

        if (is_array($response['epp.response.resData.domain:infData.domain:ns.domain:hostAttr'])) {
            foreach ($response['epp.response.resData.domain:infData.domain:ns.domain:hostAttr'] as $key =>  $ns) {
                $out['data']['ns'][$key]['hostName'] = $ns['domain:hostName'];
                $out['data']['ns'][$key]['hostAddr'] = Arrays::get('domain:hostAddr.@value', $ns);
                $out['data']['ns'][$key]['ip'] = Arrays::get('domain:hostAddr.@attributes.ip', $ns);
            }
        } else {
            $out['data']['ns'][0]['hostName'] = $response['epp.response.resData.domain:infData.domain:ns.domain:hostAttr.domain:hostName'];
            $out['data']['ns'][0]['hostAddr'] = null;
            $out['data']['ns'][0]['ip'] = null;
        }
        return $out;
    }

    /**
     * Creates a new domain with the given parameters.
     *
     * @param string $domain The domain name to create.
     * @param int $period The period of the domain in months. Must be 12 or 60.
     * @param array $contacts An array of contact details for the domain.
     * @param array $ns An array of nameservers for the domain.
     * @throws \Exception If the domain period is not 12 or 60.
     * @return void
     */
    public function domainCreate(string $domain, int $period, array $contacts, array $ns)
    {
        $_periods = [12, 60];
        $_contacts = ['holder', 'admin', 'tech', 'bill'];
        $_ns = ['ns1', 'ns2', 'ns3', 'ns4'];

        if (!in_array($period, $_periods)) {
            throw new \Exception('Domain period is must be 12 or 60');
        }

        foreach ($_contacts as $key => $name) {
            if (!array_key_exists($name, $contacts)) {
                throw new \Exception('$contacts[\'' . $name . '\'] is not set');
            }
            if (empty($contacts[$name])) {
                throw new \Exception('$contacts[\'' . $name . '\'] is empty');
            }
        }

        $checkHandles = $this->_checkPermissibleContacts($contacts);
        if (!empty($checkHandles)) {
            throw new \Exception(var_export($checkHandles, true));
        }

        $_ns = count($ns);
        if ($_ns < 2 || $_ns > 4) {
            throw new \Exception('$ns must be between 2 and 4 nameservers');
        }

        $data = [
            'domain' => $domain,
            'period' => $period,
            'contacts' => $contacts,
        ];

        foreach ($ns as $key => $val) {
            if (is_numeric($key)) {
                $key = $val;
                $val = false;
            }
            $data['ns'][] = [
                'hostName' => $key,
                'hostAddr' => $val,
                'type' => $this->getTypeIP($val)
            ];
        }

        $response = $this->callApi('domain/create', $data);
        $out['meta'] = $this->getResponseMeta($response);

        if ($out['meta']['code'] != 1000) {
            $out['data'] = [];
            return $out;
        }

        $out['data'] = [
            'domain' => $response['epp.response.resData.domain:creData.domain:name'],
            'crDate' => $response['epp.response.resData.domain:creData.domain:crDate'],
            'exDate' => $response['epp.response.resData.domain:creData.domain:exDate'],
        ];

        return $out;
    }


    /**
     * Renews a domain with the given parameters.
     *
     * @param string $domain The domain to renew.
     * @param int $period The renewal period (must be 12 or 60).
     * @param mixed $curExpDate The current expiration date of the domain.
     * @throws \Exception Domain period must be 12 or 60.
     * @return array Information about the renewal including meta and data.
     */
    public function domainRenew(string $domain, int $period, $curExpDate = null)
    {
        $_periods = [12, 60];

        if (!in_array($period, $_periods)) {
            throw new \Exception('Domain period is must be 12 or 60');
        }
        if ($curExpDate === null) {
            $domainExpDate = $this->domainInfo($domain);
            if ((int)$domainExpDate['meta']['code'] !== 1000) {
                return $domainExpDate;
            }
            $domainExpDate = explode('T', $domainExpDate['data']['exDate']);
            $curExpDate = $domainExpDate[0];
        }
        $data = [
            'domain' => $domain,
            'period' => $period,
            'curExpDate' => $curExpDate,
        ];

        $response = $this->callApi('domain/renew', $data, false);
        $out['meta'] = $this->getResponseMeta($response);

        if ($out['meta']['code'] != 1001) {
            $out['data'] = [];
            return $out;
        }
        $out['data']['domain'] = $response['epp.response.resData.domain:renData.domain:name'];

        return $out;
    }

    /**
     * Updates the contact details for a domain.
     *
     * @param string $domain The domain to update the contacts for.
     * @param array $contacts An array containing contact details for the domain.
     * @throws \Exception If the handler only supports specific contact types or if a contact detail is empty.
     * @return array|null The response meta information and data after updating the contacts.
     */
    public function domainUpdateContact(string $domain, array $contacts)
    {
        $_contacts = ['holder', 'admin', 'tech', 'bill', 'reseller'];
        foreach ($contacts as $key => $name) {
            if (!array_search($key, $_contacts)) {
                throw new \Exception('this handler only supports ' . implode(', ', $_contacts));
            }
            if (empty($contacts[$key])) {
                throw new \Exception('$contacts[\'' . $key . '\'] is empty');
            }
        }
        $data = [
            'domain' => $domain,
            'contacts' => $contacts
        ];

        $response = $this->callApi('domain/updatecontact', $data, false);
        $out['meta'] = $this->getResponseMeta($response);
        $out['data'] = null;

        return $out;
    }


    /**
     * Updates the nameservers for a given domain.
     *
     * @param string $domain The domain name to update.
     * @param array $ns An array of nameservers for the domain. Each nameserver can be either a string or an associative array with the keys 'hostName' and 'hostAddr'.
     * @throws \Exception If the number of nameservers is not between 2 and 4, or if the domain is not found.
     * @return array An array with the following keys: 'meta' (an array with the response metadata), 'data' (null).
     */
    public function domainUpdateNS(string $domain, array $ns)
    {
        $_ns = count($ns);
        if ($_ns < 2 || $_ns > 4) {
            throw new \Exception('$ns must be between 2 and 4 nameservers');
        }
        foreach ($ns as $key => $val) {
            if (is_numeric($key)) {
                $key = $val;
                $val = false;
            }
            $newNS[] = [
                'hostName' => $key,
                'hostAddr' => $val,
                'type' => $this->getTypeIP($val)
            ];
        }

        $domainInfo = $this->domainInfo($domain);

        if ($domainInfo['meta']['code'] != 1000) {
            throw new \Exception('Domain not found');
        }

        $domainInfo = $domainInfo['data']['ns'];
        $data = [
            'domain' => $domain,
            'old' => $domainInfo,
            'new' => $newNS
        ];
        $response = $this->callApi('domain/nameserver', $data, false);
        $out['meta'] = $this->getResponseMeta($response);
        $out['data'] = null;

        return $out;
    }

    public function domainTransfer()
    {
        //TODO: تکمیل نشده! پیچیدگی دارد.
    }
}
