<?php
/***
 * 大昔のフレームワークみたいに、GETのAction引数で処理を切り分ける
 *
 */
 
namespace takuya\SimpleWebApp;


class SimpleWebApp {

  protected $routes;
  protected $config;
  protected $filters;
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
    $this->get("index", function(){ echo "sample index.";});
    $this->get("static", [ $this, "send_static" ]);
    self::$_instance = $this;
    $this->add_pre_get_filter( array($this,'rescan_hash_string_in_query_string') );
  }
  //リクエストデータをフィルタリングして必要なものだけを取り出す。
  protected static function parse_request( $defaults, $request_arg ){
    $req = array_merge($defaults, $request_arg);
    $req = array_intersect_key($req, $defaults);
    $req = (object) $req;
    return $req;
  }
  //TODO : move to __call
  public static function get_request($defaults){
    return self::parse_request( $defaults, $_REQUEST);
  }
  //aliases
  public static function requests($defaults){
    return self::get_request( $defaults, $_REQUEST);
  }
  public static function get_params($defaults){
    return self::parse_request( $defaults, $_GET);
  }
  public static function post_params($defaults){
    return self::parse_request( $defaults, $_POST);
  }
  public static function requests_json($defaults){
    if( empty($_SERVER["CONTENT_TYPE"]) || strpos( $_SERVER["CONTENT_TYPE"],'/json') === false){
      return array();
    }
    $json_string = file_get_contents('php://input');
    return self::parse_request($defaults,json_decode($json_string));
  }

  // テンプレート処理
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
    // ob_start("ob_gzhandler");// Accept-Encding 見て判断してくれるhttpdに任せたほうが楽
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
    header("Cache-Control: $pragma_value, max-age=$lifetime", true); 
    header("Last-modified: ". gmdate( 'D, d M Y H:i:s', $last_modified ) . ' GMT', true );
    header("Expires: ". gmdate( 'D, d M Y H:i:s', time()+$lifetime ) . ' GMT' , true);
    
  }


  //処理登録
  protected function http_handler($method, $route, $func){
    $this->routes[$method][$route] = $func;
  }
  //TODO : move to __call
  public function get( $route, $func ){
    $this->http_handler( 'GET', $route, $func );
  }
  public function head( $route, $func ){
    $this->http_handler( 'HEAD', $route, $func );
  }
  public function post( $route, $func ){
    $this->http_handler( 'POST', $route, $func );
  }
  public function put( $route, $func ){
    $this->http_handler( 'PUT', $route, $func );
  }
  public function delete( $route, $func ){
    $this->http_handler( 'DELETE', $route, $func );
  }
  
  // フィルタ登録処理
  public function add_filter($point='pre', $method='GET', $func ){
    
    $method=strtoupper($method);
    $this->filters = !empty($this->filters) ?: array();
    $this->filters["$point$method"] = !empty($this->filters["$point$method"]) ?: array();
    $this->filters["$point$method"][] = $func;
    return $this;
  }
  //aliases
  //TODO : move to __call
  public function add_pre_get_filter($func){
    return $this->add_filter( 'pre', 'GET', $func );
  }
  
  //メイン処理
  public function run() {

    $req_method = $_SERVER["REQUEST_METHOD"];
    if( !empty( $_REQUEST["method"]) ) {
      $req_method = strtoupper($_REQUEST["method"]);
    }

    // プレ・フィルタープラグイン処理
    if ( method_exists($this, "do_pre${req_method}") ){
      $this->{"do_pre{$req_method}"}();
    }

    $func = $this->act_func($req_method);
    // Actionを決める
    if ( empty ( $func = $this->act_func($req_method) ) ){
      // methodが登録されてる？
      if ( empty($this->routes[$req_method]) ){
        $this->http_method_not_implemented();
        return ;
      }
      $this->action_not_found( $this->act_name() );
      return ;
    }
    //route 登録したハンドラを実行
    $ret = call_user_func_array($func, array());
    
    //文字列・オブジェクト・関数が返ってきたら実行する。
    if ($ret){
      $this->default_render( $ret );
    }

    // ポスト・フィルタープラグイン処理
    if ( method_exists($this, "do_post${req_method}") ){
      $this->{"do_post{$req_method}"}();
    }
    
  }
  
  protected function default_render($ret){
    switch (gettype($ret)) {
      case 'string':
        $ret =function() use($ret) {echo $ret;};
        break;
      case 'array':
        $ret = function()use($ret){
          $templage_ext = '.php';
          $f_name = preg_replace('/\..+/','', $this->act_name() ).$templage_ext;
          $this->render($f_name , $ret );
        };
        break;
    }    
    call_user_func_array( $ret, array() );      
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

  protected function act_func($req_method){
    $action_name = $this->act_name();

    if (empty( $this->routes[$req_method][$action_name] ) ) {
      return;
    }
    $action =  $this->routes[$req_method][$action_name];
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

  
  //処理へフィルタプラグインするためHTTP メソッド単位で分けた
  protected function do_filter($name){
    if ( empty( $this->filters[$name] ) )  {
      return;
    }
    foreach( $this->filters[$name] as $func ){
      call_user_func_array($func, array());
    }
    
  }
  //aliases
  //TODO : move to __call
  public function do_preGET  (){ $this->do_filter('preGET'  );}
  public function do_postGET (){ $this->do_filter('postGET' );}
  public function do_prePOST (){ $this->do_filter('prePOST' );}
  public function do_postPOST(){ $this->do_filter('postPOST');}


  //aliases for access class variables.
  public function __set($name,$var){
    if($name=='template_path'){
      $this->set_template_path($var);
      return;
    }    

    $this->$name = $var;
  }
  public function __get($name){
    if($name=='template_path'){
      return $this->get_template_path();
    }    
  }
  

  /****************
  **  幾つかのブラウザに見られるバグの強引な対応
  **  バグはハッシュ文字列について起きる。
  ** 
  **  $_SERVER['QUERY_STRING'] が'name=Value#hashname=value'を送信してくる
  **  これを 'name=value%23hashname=value' と送信してくる時がある。
  **  その為に'#' が混じる場合があるので処理が必要
  **  ただし、ハッシュが検索文字列として渡された場合に、誤作動する危険性もある。
  ** **************/
  protected function rescan_hash_string_in_query_string( ) {
    if ( empty ($_SERVER['QUERY_STRING']) || strpos($_SERVER['QUERY_STRING'], '%23') === false ){
      return;
    }
    
    $_SERVER['QUERY_STRING'] = str_replace( '%23', '#', $_SERVER['QUERY_STRING'] );
    $arr = parse_url($_SERVER['QUERY_STRING']);
    $_SERVER['QUERY_STRING'] = $arr['path'];
    parse_str($_SERVER['QUERY_STRING'], $_GET );
    $_REQUEST= array_merge( $_GET,$_POST );

  }


}
