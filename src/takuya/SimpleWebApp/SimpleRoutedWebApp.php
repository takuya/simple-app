<?php
/***
 * 大昔のフレームワークみたいに、リクエストのパス名でルーティングする。
 *
 */
 
namespace takuya\SimpleWebApp;


class SimpleRoutedWebApp extends SimpleWebApp {

  public $document_root;
  public function __construct(){
    $this->routes = array();
    $this->document_root = "/"; //default root
    $this->default_route  = "/";
    $this->get("/", function(){ echo "sample index.";});
    $this->get("/static", [ $this, "send_static" ]);
    self::$_instance = $this;
    $this->add_pre_get_filter( array($this,'rescan_hash_string_in_query_string') );
  }
  protected function act_func($req_method){
    $action = parent::act_func($req_method);
    if (!$action){
      return $this->default_routing($req_method);
    }
    return $action;
  }

  protected function default_routing($req_method){
    $routes = $this->routes[$req_method];
    $path = $this->act_name();
    //マッチ対象を文字列の長さ順に並べておく.
    $patterns = array_keys($routes);
    array_multisort(array_map('strlen', $patterns),SORT_DESC, $patterns);
    
    foreach ($patterns as $key) {
      $regex= preg_replace( '/:([^\/]+)/','(?<$1>[[:alnum:]]+)', $key );
      $regex= preg_replace( '/\//','\\/', $regex);
      $regex="/$regex/";
      if( preg_match_all($regex,$path,$matches) ){
        //TODO:: from 5.6 replace this foreach to array_filter
        $matches = array_map(function($v){return $v[0];},$matches);
        foreach ($matches as $k => $v) {
          if (is_int($k)) { unset($matches[$k]);}
        }
        $func = $routes[$key];
        return function() use ($func,$matches){
           return call_user_func_array($func, [(object)$matches]);
        };
      }
    }
  }
  
  public function act_name() {
    $request_path = parse_url($_SERVER['REQUEST_URI'])['path'];
    $path = str_replace($this->document_root,'' , $request_path);
    if ( empty( $path  ) ) {
      return $this->default_route; 
    }
    return $path[0] == '/' ? $path : "/$path";
  }


}
