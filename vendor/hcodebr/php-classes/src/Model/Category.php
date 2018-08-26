<?php


namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Category extends Model {

    public static function listAll(){
        $sql = new sql();
        return $sql->select("select * from tb_categories ORDER BY descategory");
    }

    public function Save(){
        $sql = new Sql();
        $results = $sql->select("CALL sp_categories_save (:pidcategory, :pdescategory)", array(
            ":pidcategory"=>$this->getidcategory(),
            ":pdescategory"=>$this->getdescategory()

        ));
        $this->setData($results[0]);
        Category::updatefile();
    }

    public function get($idcategory){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
            ':idcategory'=>$idcategory
        ]);
        $this->setData($results[0]);
    }

    public function delete(){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
           ':idcategory'=>$this->getidcategory()
        ]);
        Category::updatefile();
    }

    public static function updatefile(){
        $categories = Category::listAll();
        $html = [];
        foreach ($categories as $row) {
            array_push($html,'<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
        }
        file_put_contents($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."categories-menu.html",implode('',$html));
    }



}