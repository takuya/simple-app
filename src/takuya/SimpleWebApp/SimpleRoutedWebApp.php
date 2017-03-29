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
  public function act_name() {
    $request_path = parse_url($_SERVER['REQUEST_URI'])['path'];
    $path = str_replace($this->document_root,'' , $request_path);
    if ( empty( $path  ) ) {
      return $this->default_route; 
    }
    return $path[0] == '/' ? $path : "/$path";
  }


}
