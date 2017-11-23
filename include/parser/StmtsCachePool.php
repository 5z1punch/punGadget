<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/6
 * Time: 上午11:33
 * 将ast树序列化为字符串存入文件中，并保持于原文件名的对应关系；
 * 当需要使用某个文件的ast树时，通过hash表中的文件名对应的hash文件名提取序列化串反序列化为php对象。
 * 其实并没有用hash表。。。。。
 */
class StmtsCachePool
{
    private $cacheDirName;
    public $hashTable=[];
    private $baseDir = CACHE_STMTS_BASEDIR;
    public $loadFlag;
    private $projectName;
    private $cacheId;
    /*
     * 初始化方法，如果使用缓存，则加载最近生成的缓存文件；
     * 如果不使用缓存，则新建缓存。
     */
    function __construct($useCache, $projectName){
        $this->projectName = $projectName;
        $this->loadFlag = false;
        if($useCache){
            $this->loadFlag = $this->loadLastCache();
        }
        if(!$this->loadFlag){
            $this->cacheDirName = $this->project_name.'@'.time();
            mkdir($this->baseDir.$this->cacheDirName);
        }
    }

    // 为一个文件建立索引，并存储其序列化的ast树
    function pushStmts($fileName,$stmts){
        $hash = $this->cacheId;
        $this->hashTable[$fileName] = $hash;
        $this->cacheId++;
        file_put_contents($this->baseDir.$this->cacheDirName.'/'.$hash,serialize($stmts));
    }

    // 获取某文件的ast树对象
    function getStmts($fileName){
        if(isset($this->hashTable[$fileName])){
            return unserialize(file_get_contents($this->baseDir.$this->cacheDirName.'/'.$this->hashTable[$fileName]));
        }
        else{
            return false;
        }
    }

    // 析构时自动存储hash表，此处可能存在对象注入的潜在隐患，不管了
    function __destruct(){
        $this->saveHashTable();
    }

    // 存储 hash 表到当前缓存目录 ： hashTable
    function saveHashTable(){
        $saveFile = $this->baseDir.$this->cacheDirName.'/hashTable';
        file_put_contents($saveFile,serialize($this->hashTable));
    }

    // 分解缓存一级目录名，得到工程名和缓存的时间戳
    // 分解临时存储目录的目录名为 工程名@时间戳 的形式
    // input: dirname
    // output: array("projectName":string, "time": int )
    function getNameAndTime($dirname){
        $dirParser = explode('@',$dirname);
        $time = array_slice($dirParser,-1);
        array_pop($dirParser);
        $projectName = implode('@',$dirParser);
        $time = int($time);
        return [
            "projectName"=>$projectName,
            "time"=>$time
        ];
    }


    // 从 cache 目录查询出最新一次缓存，并反序列化为stmts数据
    function loadLastCache(){
        /*
        * todo
        * 此处应有一个中断的提示
         */
        $lastTime = 0;
        $lastDir = '';
        $bdir = dir($this->baseDir);
        while($file = $bdir->read()){
            if($file!='.' and $file!='..'){
                $currentFile = $this->baseDir.$file;
                if(is_dir($currentFile)){
                    $dirInfo = getNameAndTime($currentFile);
                    if($dirInfo["projectName"]==$this->projectName and $dirInfo["time"]>$lastTime){
                        $lastDir = $file;
                        $lastTime = $dirInfo["time"];
                    }
                }
            }
        }
        if($lastDir){
            $this->hashTable = unserialize(file_get_contents("{$this->baseDir}/$lastDir/hashTable"));
            $this->cacheDirName = $lastDir;
            return true;
        }
        else{
            return false;
        }
    }
}