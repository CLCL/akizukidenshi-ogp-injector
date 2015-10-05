akizukidenshi-ogp-injector
==========================

秋月電子通商の商品ページにOGPメタデータを付与するPHP

## 説明

商品ページが ``http://akizukidenshi.com/catalog/g/gM-08233/``

インジェクタ設置URLが ``http://akizukidenshi-ogp-injector.dtpwiki.jp/``

だとすると、

[**http://akizukidenshi-ogp-injector.dtpwiki.jp/**http://akizukidenshi.com/catalog/g/gM-08233/](http://akizukidenshi-ogp-injector.dtpwiki.jp/http://akizukidenshi.com/catalog/g/gM-08233)

で、商品ページの内容をスクレイピングして、OGPメタデータを埋め込んだ新しいページが表示されます。

この新しいURLは、TwiiterやSNSに商品を紹介するとき、より詳細な説明と商品写真が表示されるので、商品の紹介がしやすくなります。

## 設置

CentOS 6・Apache 2.2.27・PHP 5.3.3/5.6.14で動作確認済み

ドメインの用意やApacheのVirtualHostsの設定は省略

### ダウンロード

```
git clone https://github.com/CLCL/akizukidenshi-ogp-injector.git
cd akizukidenshi-ogp-injector/
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

### AWS S3アップロード用キーの設定

rootユーザしかアクセスできない ``/etc/sysconfig/httpd`` に設定する例

```
cat << EOS | sudo tee -a /etc/sysconfig/httpd
export AWS_ACCESS_KEY_ID=xxxxxxxxxxxxxxxxxxxx
export AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
EOS
```

一般ユーザの ``.htaccess`` に設定する例**（非推奨）**

```
cat << EOS >> .htaccess
SetEnv AWS_ACCESS_KEY_ID xxxxxxxxxxxxxxxxxxxx
SetEnv AWS_SECRET_ACCESS_KEY xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
EOS
```

### 設置ドメイン・URL関連設定

``config.php`` に設定

```
cp -a config.php config.php~
cat << EOS > config.php
<?php
// 定数
define('AKIZUKI_BASE_URL', 'http://akizukidenshi.com');
define('S3_BUCKET',        'akizukidenshi-ogp-injector.dtpwiki.jp.tokyo');
define('S3_REGION',        'ap-northeast-1');
define('S3_BASE_URL',      'https://s3-' . S3_REGION . '.amazonaws.com/' . S3_BUCKET);
define('USER_AGENT',       'Mozilla/5.0 (compatible; akizukidenshi-ogp-injector/20150916; Goutte-Guzzle-PHP; +https://github.com/CLCL/akizukidenshi-ogp-injector)');
EOS
```
