<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午4:25
 * 存储类信息：类名、类中的魔术方法、类所在的文件
 */
class ClassMagics
{
    private $className;
    private $magicFuncList;
    private $codeFile;
    public $callbackPos;
    private $checkFunc = [ "__construct()", "__destruct()", "__call()", "__callStatic()", "__get()", "__set()", "__isset()", "__unset()", "__sleep()", "__wakeup()", "__toString()", "__invoke()", "__set_state()", "__clone()", "__debugInfo()"];

    /**
     * ClassMagics constructor.
     * 获取当前classname和源码位置。
     * @param \PhpParser\Node $classNode
     * @param $codeFile
     */
    function __construct(PhpParser\Node $classNode, $codeFile)
    {
        $this->codeFile = $codeFile;
        $this->className = $classNode->name;
    }

    /**
     * 回调函数，扫描魔术方法
     * @param \PhpParser\Node $node
     * @param ClassMethodVisitor $visitor
     * @param $pos
     */
    function parserClass(PhpParser\Node $node,ClassMethodVisitor $visitor,$pos){
        if($node->getType()=="Stmt_ClassMethod" && in_array($node->name,$this->checkFunc)) {
            $this->magicFuncList[$node->name] = $node;
            // 完成后清楚自身，并在退出该节点时，添加自身
            $visitor->removeEnterCallback($pos);
            $visitor->addLeaveCallback(function($node,$visitor,$pos, $leavingNode){
                if ($node===$leavingNode){
                    $this->callbackPos = $visitor->addEnterCallback([$this,"parserClass"]);
                    $visitor->removeLeaveCallback($pos);
                }
            }, $node);
        }
    }

    /**
     * 获得该类的某一魔术方法的ast
     * @param $methodName
     * @return null
     */
    function getMethod($methodName){
        if(isset($this->magicFuncList[$methodName])) {
            return $this->magicFuncList[$methodName];
        }
        else{
            return null;
        }
    }
}