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

    /**
     * 回调函数，将当前class加入到队列中，
     * 并且添加识别魔术方法的回调函数。
     * @param \PhpParser\Node $node
     * @param ClassMethodVisitor $visitor
     * @param $pos
     */
    function stmtScan(PhpParser\Node $node, ClassMethodVisitor $visitor, $pos){
        if($node->getType()=="Stmt_Class"){
            $classMagics = new ClassMagics($node,$visitor->codeFile);
            $this->magicsList[] = $classMagics;
            $visitor->addEnterCallback([$classMagics,"parserClass"]);
            // 在处理完之后清楚自身，并且在退出当前节点时再添加自身。
            $visitor->removeEnterCallback($pos);
            $visitor->addLeaveCallback(function($node,$visitor,$pos,$leavingNode){
                if ($node===$leavingNode){
                    $visitor->addEnterCallback([$this,"stmtScan"]);
                    $visitor->removeLeaveCallback($pos);
                    $visitor->removeEnterCallback(end($this->magicsList)->callbackPos);
                }
            },$node);
        }
    }

    /**
     * 为了以后 classList 数据结构的更改，直接写一个遍历器
     * @param $callback // 回调方法名称
     * @param array ...$callbackArgs
     */
    function ergodicClassList($callback,...$callbackArgs)
    {
        foreach ($this->classList as $classMagics) {
            $callback($classMagics,...$callbackArgs);
        }
    }

    /**
     * 查询包含有某一魔术方法的类
     * 在第一次查询某方法时，将结果保存在$magicsList属性中，下次查询时直接从属性中获得
     * @param $funcName //要搜索的魔术方法名
     * @return mixed|null
     */
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