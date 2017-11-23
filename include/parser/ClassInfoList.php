<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午6:20
 * ClassMagics 列表
 */
class ClassInfoList
{
    private $classList = array();
    private $magicsList = array(
        "__construct"=>array(),
        "__destruct"=>array(),
        "__call"=>array(),
        "__callStatic"=>array(),
        "__get"=>array(),
        "__set"=>array(),
        "__isset"=>array(),
        "__unset"=>array(),
        "__sleep"=>array(),
        "__wakeup"=>array(),
        "__toString"=>array(),
        "__invoke"=>array(),
        "__set_state"=>array(),
        "__clone"=>array(),
        "__debugInfo"=>array()
    );
    private $traverser;
    function __construct()
    {
        $this->traverser = new PhpParser\NodeTraverser;
    }
    function stmtScan($nodesArray, $codeFile){
        $classInfoVisitor = new ClassInfoVisitor();
        $this->traverser->addVisitor($classInfoVisitor);
        $this->traverser->traverse($nodesArray);
        foreach ($classInfoVisitor->classList as $classNode) {
            $classMagics = new ClassMagics($classNode,$codeFile);
            array_push($this->classList,$classMagics);
        }
        $this->traverser->removeVisitor($classInfoVisitor);
    }
    // 为了以后 classList 数据结构的更改，直接写一个遍历器
    // callback：回调方法名称
    function ergodicClassList($callback,...$callbackArgs)
    {
        foreach ($this->classList as $classMagics) {
            $callback($classMagics,...$callbackArgs);
        }
    }
    // 查询包含有某一魔术方法的类
    // 在第一次查询某方法时，将结果保存在$magicsList属性中，下次查询时直接从属性中获得
    // string: funcName 要搜索的魔术方法名
    function getClassByMagicFuncName($funcName){
        if(isset($this->magicsList[$funcName]) && !is_null($this->magicsList[$funcName])){
            if($this->magicsList[$funcName]){
                return $this->magicsList[$funcName];
            }
            else {
                function callback($classMagics,$funcName,&$returnList){
                    if($classMagics->getMethod($funcName)){
                        array_push($returnList,$classMagics);
                    }
                }
                $this->ergodicClassList(callback,$funcName,$this->magicsList[$funcName]);
            }
        }
        else{
            return null;
        }
    }
}