<?php
/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午6:26
 */

class ClassInfoVisitor extends PhpParser\NodeVisitorAbstract
{
    public $classList = array();
    public function enterNode(PhpParser\Node $node){
        if($node->getType()=="Stmt_Class"){
            array_push($this->classList,$node);
            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }
}