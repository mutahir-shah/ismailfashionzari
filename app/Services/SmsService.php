<?php

namespace App\Services;

use App\SMSProviders\BdBulkSms;
use App\SMSProviders\ReveSms;
use App\SMSProviders\TonkraSms;
use App\SMSProviders\ZirconSms;
use App\SMSProviders\CustomHttpSms;

class SmsService
{
    private $_tonkraSms;
    private $_reveSms;
    private $_bdbulkSms;
    private $_zirconSms;
    private $_customHttpSms;

    public function __construct(TonkraSms $tonkraSms, ReveSms $reveSms, BdBulkSms $bdBulkSms, ZirconSms $zirconSms, CustomHttpSms $customHttpSms)
    {
        $this->_tonkraSms = $tonkraSms;
        $this->_reveSms = $reveSms;
        $this->_bdbulkSms = $bdBulkSms;
        $this->_zirconSms = $zirconSms;
        $this->_customHttpSms = $customHttpSms;
    }

    public function initialize($data)
    {
        $smsServiceProviderName = $data['sms_provider_name'];
        
        try {
            switch ($smsServiceProviderName) {
                case 'tonkra':
                    return $this->_tonkraSms->send($data);
                case 'revesms':
                    return $this->_reveSms->send($data);
                case 'bdbulksms':
                    return $this->_bdbulkSms->send($data);
                case 'zircon':
                    return $this->_zirconSms->send($data);
                case 'custom_http':
                    return $this->_customHttpSms->send($data);
                default:
                    break;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
