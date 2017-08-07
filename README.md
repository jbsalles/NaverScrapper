## About Laravel
Naver scrapper parse the Naver blog DOM to extract likes and Comments

## Usage


```php
//   String $blogId : ID of the blog 
//   String $postId = ID of the post;
//   $string $type: type "comment", "like" or "both";
//   return JSON collection of like or/and comments

$nhnScrapper = new nhnScrapper($blogId,$postId,$type);
```

ex: http://lab.thedmajor.com/nhn/?blogId=1hyun&postId=221050232823