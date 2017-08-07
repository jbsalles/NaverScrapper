<?php
/**
 * Author: Jean-Baptiste
 * Date: 8/24/2016
 * Time: 1:55 PM
 */
error_reporting(E_ERROR);
if(isset($_GET['inf'])) {phpinfo(); die();}

class nhnScrapper {

  private $blogId;
  private $postId;
  private $type;

  public function __construct($blogId,$postId,$type) {
    $this->blogId = $blogId;
    $this->postId = $postId;
    $this->type = $type;
  }

  public function __call($method, $arguments) {
    $arguments = array_merge(array("stdObject" => $this), $arguments);
    if (isset($this->{$method}) && is_callable($this->{$method})) {
      return call_user_func_array($this->{$method}, $arguments);
    } else {
      throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
    }
  }

  public function curl($url,$tags){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, '5');
    $content = trim(curl_exec($ch));
    curl_close($ch);
    $content = mb_convert_encoding(strip_tags($content,$tags),'UTF-8','EUC-KR');
    $clean_html =  mb_convert_encoding($content,'HTML-ENTITIES','UTF-8') ;
    return $clean_html;
  }

  public function nhnCurl($type){

    $tags = ($type == 'comment')?'<a><table><li><tr><span><dd>':'<a><table><tr><span>';
    $url = ($type == 'comment')?'http://blog.naver.com/'.$this->blogId.'/CommentList.nhn?blogId='.$this->blogId.'&logNo='.$this->postId:'http://blog.naver.com/'.$this->blogId.'/SympathyHistoryList.nhn?blogId='.$this->blogId.'&logNo='.$this->postId;

    $clean_html =  $this->curl($url,$tags);
    $clean_html = str_replace('class=\'page-navigation\'', 'class=\'page-navigation\' id=\'pagin\'',$clean_html); //add unique ID to date element

    if($type != 'comment')
    {
      $dom = new DomDocument();
      $dom->loadHTML($clean_html);

      $pagin = $dom->getElementById('pagin') ? $dom->getElementById('pagin')->getElementsByTagName('a') : false;

      if( $pagin && $pagin->length > 1 )
      {
        $i = 2;
        while($i < ($pagin->length+1))
        {
          $url2 = $url."&currentPage=".$i;
          $clean_html .= $this->curl($url2,$tags);
          $i++;
        }
      }
    }
    else{

      $clean_html = str_replace('class="pcol2 fil7"', 'class="pcol2 fil7" id="page_num"',  $clean_html);
      $dom = new DomDocument();
      $dom->loadHTML($clean_html);

      $pagin = $dom->getElementById('page_num')?$dom->getElementById('page_num')->nodeValue:0;
      $pagin = str_replace('/','',strip_tags($pagin));

      if(  $pagin > 1 )
      {
        $i = 2;
        while($i < ($pagin+1))
        {
          $url2 = $url."&currentPage=".$i;
          $clean_html .= $this->curl($url2,$tags);
          $i++;
        }
      }

    }
    return $clean_html;
  }

  public function getCommentList(){
    $com = $this->nhnCurl( 'comment' );
    $commentList = explode("<li class=\"fil3 cline\"></li>", $com ); //split all comment
    $comments = [];
    $comments['count'] = 0;
    $comments['list'] = [];

    foreach($commentList as $i=>$c){

      $c = str_replace('class="date fil5 pcol2"', 'class="date fil5 pcol2" id="date_'.$i.'"',$c); //add unique ID to date element
      $commentList[$i] = $c;

      $dom = new DomDocument();
      $dom->loadHTML($commentList[$i]);
      $date = $dom->getElementById('date_'.$i)->nodeValue;

      if($date)
      {

        $j=$i+1;
        $comment = $dom->getElementById('comment'.$j)?$dom->getElementById('comment'.$j)->nodeValue:"hidden ".$j;
        $author = $dom->getElementById('nick'.$j)?$dom->getElementById('nick'.$j)->nodeValue:"hidden ".$j;
        $blog = $dom->getElementById('nick'.$j)?$dom->getElementById('nick'.$j)->getAttribute("href"):"hidden ".$j;

        $comments['list'][$comments['count']]['date']=$date;
        $comments['list'][$comments['count']]['comment']=trim($comment);
        $comments['list'][$comments['count']]['author']=$author;
        $comments['list'][$comments['count']]['blog_url']=$blog;
        $comments['count']++;

      }
    }
    return $comments;
  }

  public function getLikeList(){

    $likeList =  $this->nhnCurl( 'like' );
    $likeList = explode("</tr>", $likeList ); //split all likes
    $likes = [];
    $likes['count'] = 0;
    $likes['list'] = [];

    foreach($likeList as $i=>$c){

      $c = str_replace('class="date fil5 pcol2"', 'class="date fil5 pcol2" id="date_'.$i.'"',$c); //add unique ID to date element
      $like = explode("<tr>", $c ); //split all likes

      $dom = new DomDocument();
      $dom->loadHTML($like[1]);
      $date = $dom->getElementById('date_'.$i)->nodeValue;
      //echo $like[1];
      if($date)
      {
        //$author = $dom->getElementsByTagName('a')->item(1)->getAttribute("title");
        //$blog = $dom->getElementsByTagName('a')[0]->getAttribute("title");
        //$blog_url = $dom->getElementsByTagName('a')->item(1)->getAttribute("href");
        $likes['list'][$likes['count']]['date']=$date;
       // $likes['list'][$likes['count']]['blog']=$blog;
        $likes['list'][$likes['count']]['blog_url']="";//$blog_url;
        $likes['list'][$likes['count']]['author']="";//$author;
        $likes['count']++;
      }
    }

    return $likes;
  }

  public function scrapp()
  {
    header('Content-Type: application/json');
    if($this->type=='both')
    {
      $res['comments'] = $this->getCommentList();
      $res['likes'] = $this->getLikeList();
      echo json_encode ( $res );
     die();
    }
    $res = ($this->type=='comment') ? $this->getCommentList() : $this->getLikeList();
    echo json_encode ( $res );
    die();
  }

}

