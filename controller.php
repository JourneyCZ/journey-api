<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
// header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header("Access-Control-Request-Method:GET,POST"); 
if(strtoupper($_SERVER['REQUEST_METHOD'])== 'OPTIONS'){ 
exit; 
} 

class Controller 
{
  protected $urlArr;    // url解析数组
  protected $paramArr;  // url参数数组
  protected $pdo;       // 数据库连接
  private $result;      // 返回结果

  function __construct()
  {
    $this->pdo = new PDO("mysql:host=127.0.0.1;dbname=journey","root","");

    // uri格式：/action/参数名/参数值...
    $this->urlArr = explode("/", $_SERVER['REQUEST_URI']);
    $action = $this->urlArr[1];
    for ($i = 2; $i+1 < count($this->urlArr); $i++) {
      $this->paramArr[$this->urlArr[$i]] = $this->urlArr[++$i];
    }

    if(!empty($action)) {
      $this->$action();
      echo json_encode($this->result);
    } else {
      $this->result["status"] = "201";
      $this->result["message"] = "Action is Empty!";
      die(json_encode($this->result));
    }
  }

  // 获取参数
  protected function _request($index){
    $value = null;

    if (isset($_REQUEST[$index])) {
      $value =  $_REQUEST[$index];

    } else if (isset($this->paramArr[$index])) {
      $value =  $this->paramArr[$index];
    }

    return $value;
  }
  protected function _post($index){
    $value = isset($_POST[$index]) ? $_POST[$index] : null;
    return $value;
  }
  protected function _get($index){
    $value = null;

    if (isset($_GET[$index])) {
      $value =  $_GET[$index];
      
    } else if (isset($this->paramArr[$index])) {
      $value =  $this->paramArr[$index];
    }

    return $value;
  }

  // 数据库操作
  protected function exec($sql)
  {
    if($this->pdo->exec($sql)){
      $this->result["status"] = "101";
      $this->result["message"] = "数据库操作成功！".$this->pdo->lastinsertid();
    }
  }
  protected function query($sql)
  {
    if ($rs = $this->pdo->query($sql)) {
      $this->result["status"] = '101';
      $this->result["data"] = [];
      while($row = $rs->fetch(PDO::FETCH_ASSOC)){
        array_push($this->result["data"], $row);
      }
    }
  }

  // 获取文章
  public function getArticle() {
    $where = "WHERE 1=1";
    $id = $this->_request("id");
    if ($id !== null) {
      $where .= " AND art.id = ".$id;
    }
    $type = $this->_request("type");
    if ($type !== null) {
      $where .= " AND art.type = ".$type;
    }
    $title = $this->_request("title");
    if ($title !== null) {
      $where .= " AND art.title LIKE '%".$title."%'";
    }
    $categoryId = $this->_request("categoryId");
    if ($categoryId !== null) {
      $where .= " AND  FIND_IN_SET(art.categoryId, getCategoryChild(".$categoryId."))";
    }

    $sql = "
    SELECT 
      art.*,
      cat.name AS categoryName
    FROM article art
      LEFT JOIN category cat ON art.categoryId = cat.id 
    ".$where."
    ORDER BY art.createTime DESC";

    $this->query($sql);
  }

  // 获取文章年份
  public function getArticleYear() {
    $where = "WHERE 1=1";
    $id = $this->_request("id");
    if ($id !== null) {
      $where .= " AND art.id = ".$id;
    }
    $type = $this->_request("type");
    if ($type !== null) {
      $where .= " AND art.type = ".$type;
    }
    $title = $this->_request("title");
    if ($title !== null) {
      $where .= " AND art.title LIKE '%".$title."%'";
    }
    $categoryId = $this->_request("categoryId");
    if ($categoryId !== null) {
      $where .= " AND  FIND_IN_SET(art.categoryId, getCategoryChild(".$categoryId."))";
    }

    $sql = "
    SELECT 
      LEFT(art.createTime, 4) AS articleYear
    FROM article art
      LEFT JOIN category cat ON art.categoryId = cat.id 
    ".$where."
    GROUP BY LEFT(art.createTime, 4)
    ORDER BY articleYear DESC";

    $this->query($sql);
  }

  // 获取类型
  public function getCategory() {
    $where = "WHERE 1=1";
    $id = $this->_request("id");
    if ($id !== null) {
      $where .= " AND id = ".$id;
    }
    $parentId = $this->_request("parentId");
    if ($parentId !== null) {
      $where .= " AND parentId = ".$parentId;
    }

    $sql = "SELECT * FROM category ".$where;

    $this->query($sql);
  }

  // 获取标签
  public function getTag() {
    $where = "WHERE 1=1";
    $id = $this->_request("id");
    if ($id !== null) {
      $where .= " AND tag.id = ".$id;
    }
    $name = $this->_request("name");
    if ($name !== null) {
      $where .= " AND tag.name LIKE '%".$name."%'";
    }
    $articleId = $this->_request("articleId");
    if ($articleId !== null) {
      $where .= " AND tar.articleId = ".$articleId;
    }

    $sql = "
    SELECT 
      tag.*
    FROM tag 
      LEFT JOIN tag_article_rel tar ON tag.id = tar.tagId 
    ".$where;

    $this->query($sql);
  }
}

$controller = new Controller();