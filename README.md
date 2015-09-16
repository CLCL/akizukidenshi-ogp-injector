akizukidenshi-ogp-injector
==========================

$B=)7nEE;RDL>&$N>&IJ%Z!<%8$K(BOGP$B%a%?%G!<%?$rIUM?$9$k(BPHP

## $B@bL@(B

$B>&IJ%Z!<%8$,(B ``http://akizukidenshi.com/catalog/g/gM-08233/``

$B%$%s%8%'%/%?@_CV(BURL$B$,(B ``http://akizukidenshi-ogp-injector.dtpwiki.jp/``

$B$@$H$9$k$H!"(B

[**http://akizukidenshi-ogp-injector.dtpwiki.jp/**http://akizukidenshi.com/catalog/g/gM-08233/](http://akizukidenshi-ogp-injector.dtpwiki.jp/http://akizukidenshi.com/catalog/g/gM-08233)

$B$G!">&IJ%Z!<%8$NFbMF$r%9%/%l%$%T%s%0$7$F!"(BOGP$B%a%?%G!<%?$rKd$a9~$s$@?7$7$$%Z!<%8$,I=<($5$l$^$9!#(B

$B$3$N?7$7$$(BURL$B$O!"(BTwiiter$B$d(BSNS$B$K>&IJ$r>R2p$9$k$H$-!"$h$j>\:Y$J@bL@$H>&IJ<L??$,I=<($5$l$k$N$G!">&IJ$N>R2p$,$7$d$9$/$J$j$^$9!#(B

## $B@_CV(B

CentOS 6$B!&(BApache 2.2.27$B!&(BPHP 5.3.3$B$GF0:n3NG':Q$_(B

$B%I%a%$%s$NMQ0U$d(BApache$B$N(BVirtualHosts$B$N@_Dj$O>JN,(B

### $B%@%&%s%m!<%I(B

```
git clone
cd akizukidenshi-ogp-injector/
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

### AWS S3$B%"%C%W%m!<%IMQ%-!<$N@_Dj(B

root$B%f!<%6$7$+%"%/%;%9$G$-$J$$(B ``/etc/sysconfig/httpd`` $B$K@_Dj$9$kNc(B

```
cat << EOS | sudo tee -a /etc/sysconfig/httpd
export AWS_ACCESS_KEY_ID=xxxxxxxxxxxxxxxxxxxx
export AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
EOS
```

$B0lHL%f!<%6$N(B ``.htaccess`` $B$K@_Dj$9$kNc(B**$B!JHs?d>)!K(B**

```
cat << EOS >> .htaccess
SetEnv AWS_ACCESS_KEY_ID xxxxxxxxxxxxxxxxxxxx
SetEnv AWS_SECRET_ACCESS_KEY xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
EOS
```

### $B@_CV%I%a%$%s!&(BURL$B4XO"@_Dj(B

``config.php`` $B$K@_Dj(B

```
cp -a config.php config.php~
cat << EOS > config.php
<?php
// $BDj?t(B
define('AKIZUKI_BASE_URL', 'http://akizukidenshi.com');
define('S3_BUCKET',        'akizukidenshi-ogp-injector.dtpwiki.jp.tokyo');
define('S3_REGION',        'ap-northeast-1');
define('S3_BASE_URL',      'https://s3-' . S3_REGION . '.amazonaws.com/' . S3_BUCKET);
define('USER_AGENT',       'Mozilla/5.0 (compatible; akizukidenshi-ogp-injector/20150916; Goutte-Guzzle-PHP; +https://github.com/CLCL/akizukidenshi-ogp-injector)');
EOS
```
