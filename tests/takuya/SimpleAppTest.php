<?php 


namespace takuya\SimpleWebApp\Tests;


use takuya\SimpleWebApp\SimpleWebApp;

class SimpleWebAppTests extends \PHPUnit_Framework_TestCase {
  public function setUp() {

  
  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
  public function test_requests(){
    //ダミーリクエストデータ
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [
      'action'=>'list',
      'limit'=>'100',
            ];
    $_POST = [
            ];
    $_REQUEST= array_merge( $_GET,$_POST );
    $app = new SimpleWebApp();
    
    $defaults = [
      'limit'=>10,
      'offset'=>0,
      'keywords' => '',
    ];
    // リクエスト処理
    $req = $app->requests( $defaults );
    // アサーション
    $this->assertObjectHasAttribute( 'limit', $req  );
    $this->assertObjectHasAttribute( 'offset', $req  );
    $this->assertObjectHasAttribute( 'keywords', $req  );
    $this->assertEquals( 100, $req->limit  );
  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
  public function test_main() {
    
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [
              'action'=>'debug',
            ];
    $_POST = [
            ];
    $_REQUEST= array_merge( $_GET,$_POST );
    
    
    $app = new SimpleWebApp();
    $app->get("debug" , function(){ echo '1'; });
    
    // 実行
    $app->run();
    
    // 出力をテスト
    $this->expectOutputString('1');

  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
  public function test_filter(){
    //ダミーリクエストデータ
    $_SERVER['QUERY_STRING']='name=value%23hashname=value';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    parse_str( $_SERVER['QUERY_STRING'] , $_GET );
    $_REQUEST= array_merge( $_GET,$_POST );

    // メイン処理
    $app = new SimpleWebApp();
    $app->get("index" , function() use ($app) { 
      $defaults = [
        'name'=>'',
      ];
      $req = $app->requests( $defaults );
      echo $req->name;
    });
    $app->run();
    
    // 出力をテスト
    $this->expectOutputString('value');
    
  }
  /**
   * @covers Api::output
   * @runInSeparateProcess
   */
   public function test_method(){
     //ダミーリクエストデータ
     $_SERVER['QUERY_STRING']='name=value&method=put';
     $_SERVER['REQUEST_METHOD'] = 'GET';
     parse_str( $_SERVER['QUERY_STRING'] , $_GET );
     $_REQUEST= array_merge( $_GET,$_POST );

     // メイン処理
     $app = new SimpleWebApp();
     $app->put("index" , function() use ($app) { 
       $defaults = [
         'name'=>'',
       ];
       $req = $app->requests( $defaults );
       echo $req->name;
     });
     $app->run();
     
     // 出力をテスト
     $this->expectOutputString('value');
     
   }

}


