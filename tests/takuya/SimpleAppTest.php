<?php 


namespace takuya\SimpleWebApp\Tests;


use takuya\SimpleWebApp\SimpleWebApp;

class SimpleWebAppTests extends \PHPUnit_Framework_TestCase {
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
   /**
    * @covers Api::output
    * @runInSeparateProcess
    */
    public function test_default_render_string(){
      $_SERVER['REQUEST_METHOD'] = 'GET';
      $_GET = [
                'action'=>'debug',
              ];
      $_POST = [
              ];
      $_REQUEST= array_merge( $_GET,$_POST );
      
      
      $app = new SimpleWebApp();
      $app->get("debug" , function(){ return '1'; });
      
      // 実行
      $app->run();
      
      // 出力をテスト
      $this->expectOutputString('1');
    }
    public function test_default_render_array(){
      
      // http request
      $_SERVER['REQUEST_METHOD'] = 'GET';
      $_GET = [
                'action'=>'debug',
              ];
      $_POST = [
              ];
      $_REQUEST= array_merge( $_GET,$_POST );
      
      /// preparing templates.
      $tmpname = tempDir('sample-phpunit');
      file_put_contents( "$tmpname/debug.php", '<?php echo $name;' );

      
      // main
      $app = new SimpleWebApp();
      $app->template_path = $tmpname;
      $app->get("debug" , function(){ return array('name'=>'value'); });
      
      // execute main
      $app->run();
      
      // assertion.
      $this->expectOutputString('value');
      
      
      // dispersing templates
      rm_f($tmpname);
      
    }

}


