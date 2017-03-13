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
  public function test_main() {

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [
              'action'=>'debug',
            ];
    $_POST = [
            ];
    $_REQUEST= array_merge( $_GET,$_POST );


    $app = new SimpleWebApp();
    $app->get("debug" , function(){ return 1; });
    
    // 実行
    $app->run();
  }

}


