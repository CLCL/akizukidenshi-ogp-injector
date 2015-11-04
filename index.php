<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
ini_set('default_charset', 'UTF-8');

// Cache_Lite（ローカルキャッシュ/秋月電子のHTMLを過剰にGETしすぎないために利用）
$cache  = new Cache_Lite( array (
  'cacheDir' => __DIR__ . '/cache_cache_lite/',
  'lifeTime' => 86400,
  'automaticCleaningFactor' => 20,
  'hashedDirectoryLevel'    => 1,
  'hashedDirectoryUmask'    => 02775,
));

// Goutte クラス（Webスクレーパー/秋月電子のウェブサイトのHTMLから情報を抜き出す）
$client = new Goutte\Client;
$client->setHeader('User-Agent', USER_AGENT);

// Silex（ルータ・ミニフレームワーク/URLを解析してコンテンツ出力を振り分ける）
$app = new Silex\Application();
// デバッグモード
//$app['debug'] = true;

// Twig（Twigテンプレートエンジン）
$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__,
  'twig.options' => array(
    'cache'     => __DIR__ .'/cache_twig',
    'debug'     => $app['debug'],
  ),
));

// HTTPCache（HTTPキャッシュ/ブラウザ-HTTPサーバ間のキャッシュ）
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
  'http_cache.cache_dir' => __DIR__.'/cache_http_cache',
  'http_cache.esi'       => null,
));
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//Request::setTrustedProxies(array('127.0.0.1', '::1'));

// エラートラップ/本番運用ではエラー発生後すべてここにトラップする
if (!$app['debug']) {
  $app->error(function (\Exception $e, $code) {
    return new Response('HTTP Status ' . $code . ': 指定されたURLはサポートしていません。');
  });
}

// Rooter
$app->get('/', function() use ($app) {
  $res = array();
  $expires = 5;
  $date_last_modified = gmdate("D, d M Y H:i:s T", time());
  
  //$html = $twig->render('page.twig', $res);
  $html = $app['twig']->render('page.twig', $res);
  $response = new Response( $html, 200, array(
    'Last-Modified' => $date_last_modified,
    'Cache-Control' => 'public, max-age='.$expires,
  ));
  $response->setTtl($expires);
  return $response;
});
$app->get('/http://{url1}/{url2}/{url3}/{url4}/', function($url1, $url2, $url3, $url4) use($app) { 
  $url = "http://$url1/$url2/$url3/$url4/";
  // $urlの異状チェック
  if ( !parse_url($url) ) {
    $app->abort(400, 'URLがおかしい。');
  }
  // $urlの書式チェック
  if ( !preg_match('|^'.AKIZUKI_BASE_URL.'/catalog/\w/[-\w]+/?$|', $url)) {
    $app->abort(400, '指定されたURLはサポートしていません');
  }
  $res = scraper($url);
  if ($res[title] == '') {
    if (( $res[status] != 200 ) && ( $res[status] != 304 )) {
      $app->abort($res[status], $res[status]);
    }
  }
  // 無事にScrape出来た
  $expires = 86400;
  $date_last_modified = $res['date']->format(DateTime::RFC1123);
  $date_last_modified = preg_replace('/ \+0000$/', ' GMT', $date_last_modified);
  $html = $app['twig']->render('product_detail.twig', $res);
  $response = new Response( $html, 200, array(
    'Last-Modified' => $date_last_modified,
    'Cache-Control' => 'public, max-age='.$expires,
  ));
  $response->setTtl($expires);
  return $response;
});
$app['http_cache']->run();

exit(0);

// スクレイピング
function scraper($url) {
  //echo "URL: $url\n";
  global $client;
  global $cache;
  $crawler = '';
  $is_cached = false;
  if ($cache_data = $cache->get($url)) {
    $crawler = $client->getClient();
    $crawler->addHtmlContent($cache_data, 'cp932');
    $is_cached = true;
  }
  else {
    $crawler = $client->request('GET', $url);
    $status = $client->getResponse()->getStatus();
    if (($status != 200) && ($status != 304 )) {
      return array(status => $status);
    }
  }
  
  $title = regulate_text($crawler->filter('title')->text());
  $image = trim($crawler->filter('td.syosai')->filter('#imglink img')->attr('src'));
  $image_abs = '';
  if ( $is_cached ) {
    $image_abs = S3_BASE_URL.$image;
  }
  else {
    $image_abs = AKIZUKI_BASE_URL.$image;
    $s3_image = s3_push($image_abs);
    if ($s3_image) {
      $image_abs = $s3_image;
    }
  }
  $date  = mb_convert_kana( trim($crawler->filter('#maincontents table tr td '
    . 'table tr td:nth-child(2) table tr:nth-child(1) td')->text()), 'rns');
  $date = preg_match('|発売日 (\d\d\d\d/\d\d/\d\d)|', $date, $matches);
  $date = $matches[1];
  $date = DateTime::createFromFormat('Y/m/d H:i:s', "$date 09:00:00", new DateTimeZone('GMT'));
  $desc_node = $crawler->filter('#maincontents table tr td table tr '
    . 'td:nth-child(2) table tr:nth-child(3) td');
  $desc = regulate_text($desc_node->text());
  $desc = preg_replace('/\n|\r|\r\n/', '', $desc);
  if ($desc == '') {
    // IFRAMEの場合
    $iframe_url = AKIZUKI_BASE_URL . $desc_node->filter('iframe')->attr('src');
    if ($cache_data = $cache->get($iframe_url)) {
      $crawler->clear();
      $crawler->addHtmlContent($cache_data, 'cp932');
    }
    else {
      $crawler = $client->request('GET', $iframe_url);
      $cache->save($client->getResponse()->getContent(), $iframe_url);
    }
    $desc = mb_convert_kana( trim($crawler->text()), 'rns');
    $desc = preg_replace('/\n|\r|\r\n/', '', $desc);
    $desc = strip_tags($desc);
  }

  if (!$is_cached) {
    $cache->save($client->getResponse()->getContent(), $url);
  }

  return array(
    url        => $url,
    date       => $date,
    desc       => $desc,
    title      => $title,
    image_abs  => $image_abs,
    status     => $status,
  );
}

// s3にアップロード
function s3_push($url) {
  // Case 1: 環境変数のS3_ACCESS_KEY,S3_SECRET_ACCESS_KEYを利用（/etc/sysconfig/httpd 
  //         で設定、herokuではこちら）
  $s3 = Aws\S3\S3Client::factory(array('region' => S3_REGION));
  /*
  // Case 2: aws-config.phpに書く
  $aws = Aws\Common\Aws::factory(__DIR__ . '/aws-config.php', array('region' => S3_REGION));
  $s3 = $aws->get('s3');
  /*
  */
  /*
  // Case 3: プログラム内に直書き
  $s3 = Aws\S3\S3Client::factory(array(
                'key'    => 'xxxxxxxxxxxxxxxxxxxx',
                'secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                'region' => S3_REGION, 
  ));
  */
  // http://akizukidenshi.com/img/goods/C/M-09024.jpg
  $res = preg_match('|^(http://akizukidenshi.com/)(img)(.+\.jpg)$|i', $url, $regexp);
  if ($res != 1) {
    // マッチしなかった…
    return false;
  }
  $s3_key = $regexp[2].$regexp[3];
  
  // cURLで秋月サイトから画像を取得して$bufferに入れる
  $ch = curl_init();
  curl_setopt_array($ch, array(
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_USERAGENT      => USER_AGENT,
  ));
  $buffer = curl_exec( $ch );
  curl_close($ch);
  
  $result = $s3->putObject(array(
    'Bucket' => S3_BUCKET,
    'Key'    => $s3_key,
    'Body'   => $buffer,
    'ContentType' => 'image/jpeg',
    'CacheControl'=> 'max-age=604800',
  ));
  //https://s3-ap-northeast-1.amazonaws.com/
  //akizukidenshi-ogp-injector.dtpwiki.jp.tokyo/img/goods/C/M-09024.jpg
  return $result['ObjectURL'];
}

// タイトルや本文の書式をレギュレーションする
function regulate_text($str) {
  $str = mb_convert_kana(trim($str), 'rns');
  $str = preg_replace('/([\w])．([\w])/', '$1.$2', $str);
  return $str;
}
