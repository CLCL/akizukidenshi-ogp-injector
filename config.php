<?php
// 定数
define('AKIZUKI_BASE_URL', 'http://akizukidenshi.com');
define('S3_BUCKET',        'akizukidenshi-ogp-injector.dtpwiki.jp.tokyo');
define('S3_REGION',        'ap-northeast-1');
define('S3_BASE_URL',      'https://s3-' . S3_REGION . '.amazonaws.com/' . S3_BUCKET);
define('USER_AGENT',       'Mozilla/5.0 (compatible; akizukidenshi-ogp-injector/20150916; Goutte-Guzzle-PHP; +https://github.com/CLCL/akizukidenshi-ogp-injector)');
