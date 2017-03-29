<?php 


namespace takuya\SimpleWebApp\Tests;


use takuya\SimpleWebApp\SimpleRoutedClassWebApp;

class SimpleRoutedClassWebAppTests extends \PHPUnit_Framework_TestCase {
  public function setUp() {
    if ( ! function_exists("rm_f") ) {
     function rm_f( $path ){
       $dir = new \DirectoryIterator($path);
       if ( !$dir->isDir() ) { return false; }
       foreach($dir as $e ){
         if ( $e->isDot() ) continue;
         if ( $e->isDir() ) {
           rm_f($e->getPathName());
           rmdir($e->getPathName());
         }else{
           unlink ( $e->getPathName() ) ;
         }
       }
       return rmdir($path);
     }
   }
   if ( ! function_exists("tempDir") ) {
     function tempDir($prefix = "php-temp"){
       $tmp_file_name= tempnam(sys_get_temp_dir(), $prefix);
       @unlink($tmp_file_name);
       @mkdir($tmp_file_name);
       return $tmp_file_name;
     }
   }

  
  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
   public function test_custom_routing(){
     //ダミーリクエストデータ
     $_SERVER['REQUEST_METHOD'] = 'GET';
     $_SERVER['REQUEST_URI'] = '/debug?name=value';
     parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY) ,$a);

     $_GET = $a;
     $_POST = [];
     $_REQUEST= array_merge( $_GET,$_POST );

     
     /// preparing templates.
     $tmpname = tempDir('sample-phpunit');
     file_put_contents( "$tmpname/debug.php", '<?php
      use takuya\SimpleWebApp\SimpleRoutedClassWebApp;
      class Debug{
        public function get(){  
          $app= SimpleRoutedClassWebApp::getInstance();
          $params = $app->requests( ["name"=>""] );
          echo $params->name;
        }
      }
     
     ' );

     
     //メイン
     $app = new SimpleRoutedClassWebApp();
     $app->app_root = $tmpname;
     // 実行
     $app->run();
     
     // 出力をテスト
     $this->expectOutputString('value');

     // dispersing templates
     rm_f($tmpname);
   }

}


