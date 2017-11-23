<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午5:22
 * ast遍历器
 */

class ClassMethodVisitor extends PhpParser\NodeVisitorAbstract
{
    private $getMethodNames;
    public $methodStmtsList = array();
    public function __construct($methodNames)
    {
        $this->getMethodNames = $methodNames;
    }

    public function enterNode(PhpParser\Node $node){
        if($node->getType()=="Stmt_ClassMethod" && in_array($node->name,$this->getMethodNames)){
            $this->methodStmtsList[$node->name] = $node;
            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }
}