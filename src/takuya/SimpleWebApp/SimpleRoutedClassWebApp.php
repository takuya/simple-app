<?php
/***
 * HTTPのメソッドとREQUEST_URIに応じたclassをロードしてコールバックする。
 */
 
namespace takuya\SimpleWebApp;


class SimpleRoutedClassWebApp extends SimpleRoutedWebApp {

  public $app_root;
  public function __construct(){
    $this->app_root = dirname(realpath( $_SERVER["SCRIPT_FILENAME"] )).DIRECTORY_SEPARATOR.'app';
    self::$_instance = $this;
  }
  
  //
  protected function default_routing($req_method){
    $name = $this->act_name();
    $name = preg_replace('/\..+/','', $name );
    $name = preg_replace('/^\//','', $name );
    $f_name =  $this->app_root.DIRECTORY_SEPARATOR.$name.".php";
    
    if(!file_exists($f_name)){
      throw new \Exception( 'class not found' );
    }
    //TODO: use namespace.
    //TODO: check include_path
    require_once $f_name;
    $class_name = ucfirst($name);
    return [ new $class_name, strtolower($req_method)];
  }
}
