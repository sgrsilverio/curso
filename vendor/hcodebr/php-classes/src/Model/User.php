<?php

namespace Hcode\Model;

use \Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Mailer;


class User extends Model {

    const SESSION = "User";
    const SECRET = "Tld717@ft9551th6ap3zq9";
    const IV = "1611871819141829";

    public static function getFromSession(){
        $user = new User();
        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }
        return $user;
    }

    public static function checkLogin($inadmin = true){
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ) {
            //Não está logado
            return false;

        } else {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){
             return true;
            } else if ($inadmin === false){
                return true;
            } else {
                return false;
            }
        }
    }

    protected $fields = [
        "iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister"
    ];

    public static function login($login, $password):User
    {
        $db = new Sql();

        $results = $db->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));
        if (count($results) === 0) {
            throw new \Exception("Não foi possível fazer login.");
        }
        $data = $results[0];

        if (password_verify($password, $data["despassword"])) {
            $user = new User();
            $user->setData($data);
            $_SESSION[User::SESSION] = $user->getValues();
            return $user;
        } else {
            throw new \Exception("Não foi possível fazer login.");
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function listAll(){
        $sql = new sql();
        return $sql->select("select * from tb_users a INNER JOIN tb_persons b USING (idperson) ORDER BY b.desperson");
    }

    public function save(){
        $sql = new Sql();
       $results = $sql->select("CALL sp_users_save (:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>password_hash($this->gestdespassword(), PASSWORD_DEFAULT),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        $this->setData($results[0]);
    }

    public function get($iduser){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser ", array(
            ":iduser"=>$iduser
        ));
        $this->setData($results[0]);
    }

    public function update(){

        $sql = new Sql();
        $results = $sql->select("CALL sp_usersupdate_save (:iduser,:desperson,:deslogin,:despassword,:desemail,:nrphone,:inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        $this->setData($results[0]);
    }

    public function delete($iduser){
        $sql = new Sql();
        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$iduser
        ));
    }

    public static function getForgot($email){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b on a.idperson = b.idperson WHERE a.desemail = :email", array(
            ":email"=>$email
        ));
        if(count($results)=== 0) {
            throw new \Exception("Não foi possivel recuperar a senha -- linha 116 erro em getforgot .");
            //header("Location: /login");
        } else {
            $data = $results[0];
            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ));
            if (count($results2) === 0) {
                throw new \Exception("Não foi possivel recuperar a senha -- linha 125 erro em getforgot.");
                //header("Location: /login");
            }else {
                $dataRecovery = $results2[0];
                $vetor = $dataRecovery["idrecovery"]; //vou usar isso como vetor de inicializaçao
                $code = $dataRecovery["iduser"];
                //$code =  base64_encode(openssl_encrypt($dataRecovery["iduser"], "AES-256-CBC", user::SECRET, OPENSSL_RAW_DATA,user::IV));
                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                $mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir Senha","forgot", array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));
                var_dump($mailer);

                exit;
                //$mailer->send();
                //return $data;
            }
        }
    }

    public static function validForgotDecrypt($code){
        //$Resultado = base64_decode($code);
        $idrecovery = $code;
        //$idrecovery = /*$code;*/ (openssl_decrypt($code,"AES-256-CBC",user::SECRET, OPENSSL_RAW_DATA,user::IV));

        $sql = new Sql();
        $results = $sql ->select("SELECT * FROM tb_userspasswordsrecoveries a
        INNER JOIN tb_users b
        on a.iduser = b.iduser
        INNER JOIN tb_persons c
        on b.idperson = c.idperson
        WHERE a.iduser = :idrecovery AND a.dtrecovery IS NULL
        AND DATE_ADD(a.dtregister, INTERVAL 10 HOUR) >= NOW();", array(
            ":idrecovery"=>$idrecovery
        ));

        if (count($results) === 0) {
            throw new \Exception("Nao foi possivel recuperar a senha -- erro linha 161 validforgotdecrypt--");
        } else {
            return $results[0];
        }
    }

    public static function setForgotUsed($idrecovery){
        $sql =new Sql();
        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() 
        WHERE idrecovery = :idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));
    }

    public function setPassword($password,$iduser){

        $sql = new Sql();
        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
                        ":password"=>$password,
                        ":iduser"=>$iduser
        ));


    }

}

?>