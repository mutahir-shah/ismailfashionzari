<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ✅ Demo env switching — Laravel boot এর আগে
$demoMap = [
    'ecommerce-front'   => ['env' => '.env.ecom',  'page' => 'ecom_front'],
    'ecommerce-backend' => ['env' => '.env.ecom',  'page' => 'back_admin'],
    'woocommerce'       => ['env' => '.env.wcom',  'page' => 'back_admin'],
    'gym'       		=> ['env' => '.env.gym',  'page' => 'back_admin'],
    'project'       	=> ['env' => '.env.project',  'page' => 'back_admin'],
    'restaurant'        => ['env' => '.env.restaurant',   'page' => 'back_admin'],
];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/([a-z0-9_-]+)$#i', $uri, $matches)) {
    $slug = strtolower($matches[1]);
    if (isset($demoMap[$slug])) {
        $env  = $demoMap[$slug]['env'];
        $page = $demoMap[$slug]['page'];

        setcookie('env_name', $env, time() + 86400, '/');

        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        if ($page === 'ecom_front') {
            header('Location: ' . ($base ?: '/') . '?demo=true');
        } else {
            header('Location: ' . $base . '/login?demo=1&env=' . urlencode($env) . '&page=' . urlencode($page));
        }
        exit;
    }
}

// ✅ বাকি সব আগের মতোই
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';