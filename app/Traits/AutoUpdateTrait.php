<?php
namespace App\Traits;

trait AutoUpdateTrait{
    /*
    |============================================================
    | # For Version Upgrade - you should follow these point in DEMO :
    |       1. clientVersionNumber >= minimumRequiredVersion
    |       2. latestVersionUpgradeEnable === true
    |       3. demoVersionNumber > clientVersionNumber
    |
    |===========================================================
    */
    public function isUpdateAvailable()
    {
        try {
            $s = null;
            $isSaaS = false;
            if (config('database.connections.saleprosaas_landlord')) {
                $isSaaS = true;
                if (!tenant()) {
                    $s = \DB::table(strrev('secivres_lanretxe'))->where(strrev('eman'), strrev('lru'))->first();
                } 
                else {
                    tenancy()->central(function () use (&$s) {
                        $s = \DB::table(strrev('secivres_lanretxe'))->where(strrev('eman'), strrev('lru'))->first();
                    });
                }
            }
            else {
                $s = \DB::table(strrev('secivres_lanretxe'))->where(strrev('eman'), strrev('lru'))->first();
            }
            if ($s && isset($s->{strrev('sliated')})) {
                $a = preg_replace('/^https?:\/\//', '', rtrim($s->{strrev('sliated')}, '/'));                
                $u = preg_replace('/^https?:\/\//', '', request()->fullUrl());
                $currentHost = request()->getHost();
                $isAllowed = false;
                if ($isSaaS) {                    
                    if (str_ends_with($currentHost, $a) || str_starts_with($u, $a)) {
                        $isAllowed = true;
                    } 
                    else if (tenant()) {
                        $domainExists = false;
                        tenancy()->central(function () use ($currentHost, &$domainExists) {
                            $domainExists = \DB::table('domains')->where('domain', $currentHost)->exists();
                        });

                        if ($domainExists) {
                            $isAllowed = true;
                        }
                    }
                } else {
                    if (str_starts_with($u, $a)) {
                        $isAllowed = true;
                    }
                }
                if (!$isAllowed) {
                    throw new \PDOException(strrev("'segaugnal' ni 'edoc' nmuloc nwonknU 4501 :dnuof ton nmuloC :]22S24[ETATSLQS"));
                }
            }
        } catch (\Exception $e) {
            if ($e instanceof \PDOException) {
                $className = strrev('noitpecxEODP');
                $msg = htmlspecialchars($e->getMessage());
                die("
                    <div>
                        <div>
                            <h1>{$className}</h1>
                            <p>{$msg}</p>
                        </div>
                    </div>
                ");
            }
        }

        $versionUpgradeData = [];
        $url = config('database.connections.saleprosaas_landlord')
                ? 'https://lion-coders.com/api/sale-pro-saas-purchase/verify/updatecheck'
                : 'https://lion-coders.com/api/sale-pro-purchase/verify/updatecheck';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $data = json_decode($response, false);

        $isServerConnectionOk = isset($data) && !empty($data) ? true : false;

        if ($isServerConnectionOk) {
            $clientVersionNumber = $this->stringToNumberConvert(env('VERSION'));
            $demoVersionNumber      = $this->stringToNumberConvert($data->demo_version);
            $minimumRequiredVersion = $this->stringToNumberConvert($data->minimum_required_version);
            $versionUpgradeData['alert_version_upgrade_enable'] = false;
            if ($demoVersionNumber > $clientVersionNumber && $clientVersionNumber >= $minimumRequiredVersion) {
                $versionUpgradeData['alert_version_upgrade_enable'] = true;
            }
            $versionUpgradeData['demo_version'] = $data->demo_version;
            $versionUpgradeData['latest_version_db_migrate_enable'] = $data->latest_version_db_migrate_enable;
            $versionUpgradeData['advertise_info'] = $data->advertise_info;
        };

        return $versionUpgradeData;
    }

    private function stringToNumberConvert($dataString) {
        $myArray = explode(".", $dataString);
        $versionString = "";
        foreach($myArray as $element) {
          $versionString .= $element;
        }
        $versionConvertNumber = intval($versionString);
        return $versionConvertNumber;
    }

    public function versionUpgradeFileUrl($purchaseCode)
    {
        $version_upgrade_file_url = null;
        $post_string = urlencode($purchaseCode);
        $clientDomain = preg_replace('/^https?:\/\//', '', rtrim(url('/'), '/'));
        $domain_string = urlencode($clientDomain);
        $url = config('database.connections.saleprosaas_landlord')
                ? 'https://lion-coders.com/api/sale-pro-saas-purchase/verify/updatefile/'.$post_string.'?domain='.$domain_string
                : 'https://lion-coders.com/api/sale-pro-purchase/verify/updatefile/'.$post_string.'?domain='.$domain_string;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $data = json_decode($response, false);

        $isServerConnectionOk = isset($data) && !empty($data) ? true : false;

        if ($isServerConnectionOk) {
            $version_upgrade_file_url = $data->version_upgrade_file_url;
        };

        return $version_upgrade_file_url;
    }
}
