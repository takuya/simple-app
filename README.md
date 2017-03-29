### とりあえずPHPですぐ動かせるようなセット

- ファイルを置いたら動くように
- リクエストを容易に捌けるように
- フレームワークを使い始めると、アレコレ余計な作業が増えるので
- 目の前のことに集中できるように

なるべく何も余計なことがないように。


## Directories

```
.
├── composer.json
├── composer.lock
├── composer.phar
├── .htaccess
├── public/
│   └── index.php
├── templates/
│   └── index.php
└── vendor/
```

## Install and Hello World.

### create directory
```
mkdir -p target/dir
cd target/dir
```
### composer.phar
```
curl -sS https://getcomposer.org/installer | php
```
### composer.json
```
echo '
{"repositories":[{"type":"git",
"url":"https://github.com/takuya/simple-app.git"}],
"require":{"takuya/simple-app":"dev-master"}}
' > composer.json
```
### composer install
```
./composer.phar install
```
### .htaccess
```
echo '
RewriteBase /target/dir
RewriteRule .*  public/index.php [QSA,L]
' > .htaccess
```
#### pubic/index.php
```
mkdir -p target/dir/public
echo '<?php

require __DIR__."/".'../vendor/autoload.php';
use takuya\SimpleWebApp\SimpleRoutedWebApp;

class MyApp extends SimpleRoutedWebApp {
  public function sample(){return 'sample';}
}
//main
$app = new MyApp();
$app->document_root = '/target/dir';
$app->get("/sample", [$app, 'sample']);
$app->get("/info" , 'phpinfo');
$app->run();
' > public/index.php
```
#### templates dir 

```
mkdir -p target/dir/templates
```
#### templates/sample.php
```
echo '<?php echo $contents;'> templates/sample.php  
```
#### render template
```
class MyApp extends SimpleRoutedWebApp {
  public function sample(){return ['contents':'hello world.'];}
}

```

### Routing Sample

#### sample uri 

- GET /~takuya/sample 
- GET /~takuya/user/:name
- GET /~takuya/list?limit=100
- POST /~takuya/sample

#### .htaccess
```
RewriteBase /~takuya/sample
RewriteRule .*  public/index.php [QSA,L]
```
#### public/index.php
```php
<?php 


require __DIR__."/".'../vendor/autoload.php';
use takuya\SimpleWebApp\SimpleRoutedWebApp;

class MyApp extends SimpleRoutedWebApp { }

// main 
$app = new MyApp();
$app->document_root = '/~takuya';
// set handlers
$app->post("/sample" ,function(){  echo 'Hello sample (POST)'; });
$app->get("/sample" ,function(){  echo 'Hello sample (GET)'; });
$app->get("/list",function() use ($app){
  $defaults = ['limit':10];
  $req= $app->requests($defaults);
  echo $req->limit;
}));
$app->post("/user/:name" ,function($param){ echo $params->name; }) );

// execute
$app->run();

```
### request sample
```sh
$ curl http://[::1]/~takuya/sample
Hello sample (GET)
$ curl http://[::1]/~takuya/sample -d name=value
Hello sample (POST)
$ curl http://[::1]/~takuya/user/alice
alice
$ curl http://[::1]/~takuya/list?limit=10
10
```

### 実行２: using GET parameter

### Sample URI

` /~takuya/?action=debug `  maping `$_GET['action']` to ` function ` .

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

// main 
$app = new MyApp();

// mapping handlers
$app->get("debug" , function() use ($app){  echo 1 ; });
$app->get("info" , 'phpinfo');
$app->post("info", "phpinfo");
$app->get("sample", function () use ($app) {
  $app->render("sample.php");
});

// run
$app->run();

```
### Request sample
```sh
$ curl http://[::1]/~takuya/?act=debug
1
```

## HTTP Request Paramters

PHP request using  symbols variables are too much...

```

if ( empty( $_GET['limit'] ) ){ // how many shifts needed...
}

$request->limit; // so simple.

```

```php
<?php 

use takuya\SimpleWebApp\SimpleRoutedWebApp;

class MyApp extends SimpleRoutedWebApp {
  public function sample(){
    $app = MyApp::getInstance();
    
    $defaults = [
      'search' => '',
      'limit' => 10,
      'offset' => 0,
    ]
    $req = $app->requests($defaults);

    //check range 
    $req->limit = $req->limit < 1 ?: $defaults['limit'];
    
    $ret = query( $req->search, $req->limit , $req->offset );
    // output
    $app->render('sample.php', ['records'=>$ret]);
  }
}

// instance
$app = new MyApp();
$app->document_root = '/~takuya';
// mappping handlers
$app->get("/sample" , array( $app, 'sample' ));

// run 
$app->run();


?>
```

