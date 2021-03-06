<?php 


namespace takuya\SimpleWebApp\Tests;


use takuya\SimpleWebApp\SimpleRoutedWebApp;

class SimpleRoutedWebAppTests extends \PHPUnit_Framework_TestCase {
  public function setUp() {

  
  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
  public function test_default_routing(){
    //ダミーリクエストデータ
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/debug?name=value';
    parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY) ,$a);

    $_GET = $a;
    $_POST = [];
    $_REQUEST= array_merge( $_GET,$_POST );
    
    //メイン
    $app = new SimpleRoutedWebApp();
    $app->get("/debug" , function(){ echo '1'; });
    
    // 実行
    $app->run();
    
    // 出力をテスト
    $this->expectOutputString('1');
 }
 /**
  * @covers Api::output
  * @runInSeparateProcess
  */
 public function test_custom_routing(){
   //ダミーリクエストデータ
   $_SERVER['REQUEST_METHOD'] = 'GET';
   $_SERVER['REQUEST_URI'] = '/~takuya/debug?name=value';
   parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY) ,$a);

   $_GET = $a;
   $_POST = [];
   $_REQUEST= array_merge( $_GET,$_POST );
   
   //メイン
   $app = new SimpleRoutedWebApp();
   $app->document_root = '/~takuya';
   $app->get("/debug" , function() use ($app){ 
     $req = $app->requests(['name'=>'']);
     echo $req->name;
    
     });
   
   // 実行
   $app->run();
   
   // 出力をテスト
   $this->expectOutputString('value');
  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
  public function test_custom_routing_with_parameter(){
    //ダミーリクエストデータ
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/~takuya/debug/12345?name=value';
    parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY) ,$a);

    $_GET = $a;
    $_POST = [];
    $_REQUEST= array_merge( $_GET,$_POST );
    
    //メイン
    $app = new SimpleRoutedWebApp();
    $app->document_root = '/~takuya';
    $app->get("/debug/:id" , function($args) use ($app){ 
      echo $args->id;
    });
    
    // 実行
    $app->run();
    
    // 出力をテスト
    $this->expectOutputString('12345');
   }
   /**
    * @covers Api::output
    * @runInSeparateProcess
    */
   public function test_custom_routing_with_multi_parameter(){
     //ダミーリクエストデータ
     $_SERVER['REQUEST_METHOD'] = 'GET';
     $_SERVER['REQUEST_URI'] = '/~takuya/debug/12345/name/takuya/pref/hyogo?name=value';
     parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY) ,$a);

     $_GET = $a;
     $_POST = [];
     $_REQUEST= array_merge( $_GET,$_POST );
     
     //メイン
     $app = new SimpleRoutedWebApp();
     $app->document_root = '/~takuya';
     $app->get("/debug/:id/name/:name/pref/:pref" , function($args) use ($app){ 
       echo $args->pref;
     });
     
     // 実行
     $app->run();
     
     // 出力をテスト
     $this->expectOutputString('hyogo');
    }
    /**
     * @covers Api::output
     * @runInSeparateProcess
     */
    public function test_custom_routing_exactly_match(){
      //ダミーリクエストデータ
      $_SERVER['REQUEST_METHOD'] = 'GET';
      $_SERVER['REQUEST_URI'] = '/~takuya/debug/12345/name/?name=value';
      parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY) ,$a);

      $_GET = $a;
      $_POST = [];
      $_REQUEST= array_merge( $_GET,$_POST );
      
      //メイン
      $app = new SimpleRoutedWebApp();
      $app->document_root = '/~takuya';
      $app->get("/debug/:id/name" , function($args) use ($app){;});
      
      // 実行
      $app->run();
      
      // 出力をテスト
      $this->expectOutputString('Action not implemented.');
     }


}


