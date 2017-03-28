### とりあえずPHPですぐ動かせるようなセット

- ファイルを置いたら動くように
- リクエストを容易に捌けるように
- フレームワークを使い始めると、アレコレ余計な作業が増えるので
- 目の前のことに集中できるように

なるべくなにもないように


## index.php 
index.php を作っておいたらすぐ動くように。


```php
<?php

class MyApp extends SimpleWebApp {
}

// App を初期設定する
$app = new MyApp;
$app->set_template_path( realpath("../templates"));
$app->set_app_temp_dir(  realpath("../var/tmp"));
$app->default_act = "info";


// Action マッピング
// 登録
$app->get("info", "phpinfo");
$app->post("info", "phpinfo");
$app->get("sample", function () use ($app) {
  $app->render("sample.php");
});

// 実行
$app->run();

```

## リクエストを投げて確認する。

```
curl https://[:1]/path/to/app/?action=info
```



## ディレクトリ構成

```
.
├── composer.json
├── composer.lock
├── composer.phar
├── .htaccess
├── index.php -> public/index.php
├── public
│   └── index.php
├── templates
│   └── index.html
└── vendor
```

## インストール実行

```
git clone https://github.com/takuya/simple-app
cd simple-app
curl -sS https://getcomposer.org/installer | php
./composer.phar install
```



### 実行１:パスに登録する場合

サンプル

` /~takuya/debug ` を実行する場合 

#### .htaccess
```
DirectoryIndex public/index.php
```
#### public/index.php
```php
<?php 

use takuya\SimpleWebApp\SimpleRoutedWebApp;

class MyApp extends SimpleRoutedWebApp {
}

//メイン
$app = new MyApp();
$app->document_root = '/~takuya';
//登録
$app->get("/debug" , function() use ($app){  echo 1 ; });
$app->get("/info" , 'phpinfo');
$app->get("/php/info" , 'phpinfo');

// 実行
$app->run();

```
リクエスト
```
curl http://[::1]/~takuya/debug?name=value
1
```

### 実行２:GETに登録する場合

サンプル

` /~takuya/?action=debug ` を実行する場合 

#### .htaccess
```
DirectoryIndex public/index.php
```
#### public/index.php
```php
<?php 

use takuya\SimpleWebApp\SimpleWebApp;

class MyApp extends SimpleWebApp {
  public __construct(){
    parent::__construct();
    $this->act_key_name = "act"; 
    $this->default_act = "index";
  }
}

//メイン
$app = new MyApp();
//登録
$app->get("debug" , function() use ($app){  echo 1 ; });
$app->get("info" , 'phpinfo');

// 実行
$app->run();

```
リクエスト
```
curl http://[::1]/~takuya/?act=debug
1
```

