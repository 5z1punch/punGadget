<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午9:24
 */
class InitFullVisitor extends PhpParser\NodeVisitorAbstract
{
    private $enterCallbackList = array();
    private $enterCallbackCount = 0;
    private $leaveCallbackList = array();
    private $leaveCallbackCount = 0;
    public $codeFile;

    /**
     * InitFullVisitor constructor.
     * 声明源码文件
     * @param $codeFile
     */
    function __construct($codeFile)
    {
        $this->codeFile = $codeFile;
    }

    /**
     * 添加一个回调函数到回调函数集
     * 有 enter 和 leave 两种类型，表示不同的回调事件
     * @param $addType
     * @param $callback
     * @param array ...$callbackArgs
     * @return mixed
     */
    private function addCallback($addType,$callback,...$callbackArgs){
        $callbackList = $addType."CallbackList";
        $callbackCount = $addType."CallbackCount";
        $this->$callbackList[$this->$callbackCount]["callback"] = $callback;
        $this->$callbackList[$this->$callbackCount]["callbackArgs"] = $callbackArgs;
        $this->$callbackCount += 1;
        return $this->$callbackCount-1;
    }

    /**
     * 添加 enter node 事件回调
     * @param $callback
     * @param array ...$callbackArgs
     * @return mixed
     */
    public function addEnterCallback($callback,...$callbackArgs){
        return $this->addCallback("enter",$callback,$callbackArgs);
    }

    /**
     * 添加 leave node 事件回调
     * @param $callback
     * @param array ...$callbackArgs
     * @return mixed
     */
    public function addLeaveCallback($callback,...$callbackArgs){
        return $this->addCallback("leave",$callback,$callbackArgs);
    }

    /**
     * 从回调函数集中删除某回调，
     * 有 enter 和 leave 两种类型，表示不同的回调事件
     * @param $removeType
     * @param $callbackPos
     */
    private function removeCallback($removeType,$callbackPos){
        $callbackList = $removeType."CallbackList";
        unset($this->$callbackList[$callbackPos]);
    }

    /**
     * 删除 leave node 事件回调
     * @param $callbackPos
     */
    public function removeLeaveCallback($callbackPos){
        $this->removeCallback("leave",$callbackPos);
    }

    /**
     * 删除 enter node 事件回调
     * @param $callbackPos
     */
    public function removeEnterCallback($callbackPos){
        $this->removeCallback("enter",$callbackPos);
    }

    /**
     * 将node节点传入回调函数，同时会传入该visitor对象本身，和当前callback的pos
     * 传入callback的第一个参数为node，第二个为this，第三个为pos
     * @param $type
     * @param \PhpParser\Node $node
     */
    private function callbackNode($type,PhpParser\Node $node){
        $callbackList = $type."CallbackList";
        $tmpCallbackList = $this->$callbackList;
        foreach ($tmpCallbackList as $pos => $callback){
            $tmp = $callback["callbackArgs"];
            array_unshift($tmp,$node, $this, $pos);
            call_user_func_array($callback["callback"],$tmp);
        }
    }

    /**
     * enter node 回调触发
     * @param \PhpParser\Node $node
     * @return false|int|null|\PhpParser\Node|\PhpParser\Node[]|void
     */
    public function enterNode(PhpParser\Node $node)
    {
        $this->callbackNode("enter",$node);
    }

    /**
     * leave node 回调触发
     * @param \PhpParser\Node $node
     * @return false|int|null|\PhpParser\Node|\PhpParser\Node[]|void
     */
    public function leaveNode(PhpParser\Node $node){
        $this->callbackNode("leave",$node);
    }

}