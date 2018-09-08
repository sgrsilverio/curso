<?php
/**
 * Created by PhpStorm.
 * User: shalon
 * Date: 08/09/2018
 * Time: 16:46
 */

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;


class Cart extends Model {

    const SESSION = "Cart";

    public static function getFromSession(){
        $cart = new Cart();
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {
            $cart->getFromSessionID();
            if (!(int)$cart->getidcart() > 0 ){
                $data = [
                    'dessessionid'=>session_id()
                ];
                if (User::checkLogin(false)){
                    $user = User::getFromSession();
                    $data['iduser'] = $user->getiduser();
                }
                $cart->setData($data);

                $cart->save();
                $cart->setToSession();

            }
        }
        return $cart;
    }

    public function setToSession(){
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionID(){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid",[
            ':dessessionid'=>session_id()
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }


    public function save(){
        $sql = new Sql();
        $sql->select("CALL sp_carts_save(:pidcart, :pdessessionid, :piduser, :pdeszipcode, :pvlfreight, :pnrdays)" , [
            ':pidcart'=>$this->getidcart,
            ':pdessessionid'=>$this->getdessessionid,
            ':piduser'=>$this->getiduser,
            ':pdeszipcode'=>$this->getdeszipcode,
            ':pvlfreight'=>$this->getvlfreight,
            ':pnrdays'=>$this->getnrdays

        ]);

    }

    public function get(int $idcart) {
        $sql = new Sql();
        $results = $sql->select(" SELECT * FROM tb_carts WHERE idcart = :idcart", [
           ':idcart'=>$idcart
        ]);
        if (count($results) > 0) {
            $this->setData($results[0]);
        }

    }

}