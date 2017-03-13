<?php
/***
 * 大昔のフレームワークみたいに、GETのAction引数で処理を切り分ける
 *
 */
 
namespace takuya\SimpleWebApp;


class SimpleWebApp {

  protected $routes;
  protected $act_key_name;
  protected $app_temp_dir;
  protected $app_static_dir;
  public $default_act;
  public static $_instance;
  public static function getInstance(){
    return self::$_instance;
  }
  public function __construct(){
    $this->routes = array();
    $this->act_key_name = "action"; 
    $this->default_act = "index";
    $this->get("index", function(){ echo "index";});
    $this->get("static", [ $this, "send_static" ]);
    self::$_instance = $this;
  }
  public function set_template_path($arg){
    if( !is_dir($arg ) ){
      throw new \Exception("$arg does not exist.");
    }
    $this->config["template_path"] =  $arg;
  }
  public function get_app_temp_dir(){
    if ( !empty($this->app_temp_dir) && is_writable($this->app_temp_dir ) ) {
      return $this->app_temp_dir;
    }
    return "/tmp" ;
  }
  public function get_app_static_dir(){
    if ( !empty($this->app_static_dir) && is_readable($this->app_static_dir ) ) {
      return $this->app_static_dir;
    }
    return dirname(realpath( $_SERVER["SCRIPT_FILENAME"] ));
  }
  public function set_app_temp_dir($dir_name){
    $this->app_temp_dir=$dir_name;
  }
  public function set_app_static_dir($dir_name){
    $this->app_static_dir=$dir_name;
  }
  // Staticファイル送信
  public function send_static(){
    $f_name = "";
    if ( empty($_GET["path"]) 
      || !file_exists( $f_name = $this->get_app_static_dir()."/".$_GET["path"] )  // 1. ファイル有無
      || strpos( realpath($f_name), $this->get_app_static_dir()  ) !== 0          // 2. 指定フォルダの中にあるか
      || !is_readable( $f_name )                                                  // 3. 権限があるか
    ) 
    {
      header("HTTP/1.1 404 file not found ");
      echo "File not found.";
      exit;
    }
    $this->send_content($f_name);
  }
  // テンプレ処理
  public function render( $f_name , $params=[], $auto_flush = true) {
    if( !empty($params )){
      extract($params);
    }
    $path =  $this->config["template_path"]."/".$f_name;
    if( !file_exists($path) ){
      throw new \Exception("template $f_name dose not found.");
      return ;
    }
    return $this->send_content($path, $params, $auto_flush);
  }
  //ファイルを送信する処理
  public function send_content( $path, $params=[], $auto_flush = true){
    if( !empty( $params )){ extract($params); }
    ob_clean();
    ob_start();
    // ob_start("ob_gzhandler");// Accept-Encding 見て判断してくれるが、Apacheに任せたほうが楽
    include ($path);
    if($auto_flush){
      return ob_end_flush();
    }else{
      $str = ob_get_contents();
      ob_end_clean();
      return $str;
    }
  }
  // 強制gzip 処理をする
  // たとえjpeg でも10%前後は小さくなる
  // Content-type:text/html はApacheがやるので特に必要ない
  public function send_content_gzip($content){
    $gz_data = gzencode( $content , 9);
    // 画像の送信
    header('Content-Encoding: gzip');
    header("Content-Length: ". strlen($gz_data) );
    ob_start();
    echo ($gz_data);
    ob_end_flush();
  }
  public function send_file_cache_header( $path, $lifetime=null, $pragma_value='public' ) {
    $lifetime = @$lifetime ?: 60*60*24*10;
    $last_modified = filemtime($path);
    //// ブラウザキャッシュがある場合
    ///  キャッシュ更新確認リクエストなら
    if( !empty($_SERVER["HTTP_IF_MODIFIED_SINCE"] ) ) {
      $str_time = $_SERVER["HTTP_IF_MODIFIED_SINCE"];
      $last_modified_since = strtotime($str_time);
      if($last_modified_since == $last_modified  ) {
        //ファイル更新がなければ、キャッシュ有効を返す。
        // header("content-type: image/$type_name; ");
        header("HTTP/1.1 304 image not modified");
        header("Last-modified: ". gmdate( 'D, d M Y H:i:s', $last_modified ) . ' GMT' );
        header("Cache-Control: max-age=".$lifetime); // 追加１０日キャッシュしていい
        exit;
      }
    }
    // キャッシュなし、キャッシュ切れの場合、キャッシュ許可を再送する
    header("Pragma : $pragma_value", true);
    header("Cache-Control: $pragma_value, max-age=$lifetime", true); 
    header("Last-modified: ". gmdate( 'D, d M Y H:i:s', $last_modified ) . ' GMT', true );
    header("Expires: ". gmdate( 'D, d M Y H:i:s', time()+$lifetime ) . ' GMT' , true);
    
  }


  //処理登録
  public function get( $name, $func ){
    $this->routes["GET"][$name] = $func;
  }
  public function head( $name, $func ){
    $this->routes["HEAD"][$name] = $func;
  }
  
  
  //メイン処理
  public function run() {
    $req_method = $_SERVER["REQUEST_METHOD"];
    if( !empty( $_REQUEST["method"]) ) {
      $req_method = $_REQUEST["method"];
    }
    // method が登録されてる
    if ( empty($this->routes[$req_method]) ){
      $this->http_method_not_implemented();
      return ;
    }
    // Actionを決める
    $func = $this->act_key();
    if ( empty ( $func ) ) {
      $act_name = $this->act_name() ? $this->act_name() : 'null';
      $this->action_not_found( $this->act_name() );
      return ;
    }
    // フィルタープラグイン処理
    if ( method_exists($this, "do_${req_method}") ){
      $this->{"do_{$req_method}"}();
    }
    //route 登録したハンドラを実行
    call_user_func_array($func, array());
  }


  public function act_name() {
    $req = $_REQUEST;
    if ( empty( $req[$this->act_key_name]  ) ) {
      $req[$this->act_key_name] = $this->default_act; 
    }
    $action_name = $req[$this->act_key_name];
    // ブラウザのバグ？で、'name=Value#hashname=value'
    // これを 'name=value%23hashname=value' と送信してくる
    // その為に'#' が混じる場合があるので処理が必要
    $action_name = explode('#', $action_name)[0];
    return $action_name;
  }

  protected function act_key(){
    $action_name = $this->act_name();

    if (empty( $this->routes[$_SERVER["REQUEST_METHOD"]][$action_name] ) ) {
      return;
    }
    $action =  $this->routes[$_SERVER["REQUEST_METHOD"]][$action_name];
    return $action;
  }
  
  //HTTP method が見つからない時のアクション
  protected function http_method_not_implemented  () {
    //未登録のリクエストメソッドの場合
    header("HTTP/1.1 404 http method not implemented");
    header("X-MESSAGE: http method not implemented");
    echo "http method not implemented.";
    return;
  }
  //action が見つからない時のアクション
  protected function action_not_found ( $act_name ) {
    header("HTTP/1.1 404 action not found  ");
    header("X-MESSAGE: action not action ");
    echo "Action not implemented.";
    return ;
  }

  
  //将来的に処理へプラグインするためHTTP メソッド単位で分けた
  public function do_GET(){

  }
  public function do_HEAD(){
  }
}
