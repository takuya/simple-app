### とりあえずPHPですぐ動かせるようなセット

- ファイルを置いたら動くように
- リクエストを容易に捌けるように
- フレームワークを使い始めると、アレコレ余計な作業が増えるので
- 目の前のことに集中できるように

なるべくなにもないように


#### index.php 
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



### ディレクトリ構成

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

