<?php 
session_start();
require_once("vendor/autoload.php");
require_once("functions.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    $products = Product::listAll();
    $page = new Page();
    $page->setTpl("index", ['products'=>Product::checkList($products)]);
});

$app->get('/admin/users/:iduser/delete', function($iduser) {
    User::verifyLogin();
    $user = new User();
    $user->get((int)$iduser);
    $user->delete($iduser);
    header("Location: /admin/users");
    exit;
});

$app->get('/admin/users', function() {
    User::verifyLogin();
    $users = User::listAll();
    $page = new PageAdmin();
    $page->setTpl("users", array(
        "users"=>$users
    ));
});

$app->get('/admin/users/create', function() {
    User::verifyLogin();
    $error = User::getErrorRegister();
    $page = new PageAdmin();
    $page->setTpl("users-create",[
        'errorRegister'=>$error
    ]);
});

$app->post('/admin/users/create', function() {
    User::verifyLogin();
    $user = new User();
    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
    $user->setData($_POST);
    if (User::checkLoginExist($_POST['desemail']) === true) {
        User::setErrorRegister("Esse email já foi cadastrado por outro usuário!");
        header("Location: /admin/users/create");
        Exit;
    }
    $user->save();
    header("Location: /admin/users");
    exit;
});



$app->get('/admin/users/:iduser', function($iduser) {
    User::verifyLogin();
    $user = new User();
    $user->get((int)$iduser);
    $page = new PageAdmin();
    $page->setTpl("users-update", array(
        "user"=>$user->getValues()
    ));
});



$app->post('/admin/users/:iduser', function($iduser) {
    User::verifyLogin();
    $user = new User();
    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
    $user->get((int)$iduser);
    $user->setData($_POST);
    $user->update();
    header("Location: /admin/users");

    exit;
});


$app->get('/admin', function() {
    User::verifyLogin();
    $page = new PageAdmin();
    $page->setTpl("index");
});

$app->get('/admin/login', function() {

    $page = new PageAdmin([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("login");
});

$app->post('/admin/login',function (){
    User::login($_POST["login"],$_POST["password"]);
    header("location: /admin");
    exit;
});

$app->get('/admin/logout',function (){
    User::logout();
    header("Location:/admin/login");
    exit;
});

$app->get('/admin/forgot', function() {

    $page = new PageAdmin([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("forgot");
});

$app->post("/admin/forgot",function (){
    $user = User::getForgot($_POST["email"]);
    header("Location: /admin/forgot/sent");
    exit;

});

$app->get("/admin/forgot/sent",function(){
    $page = new PageAdmin([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function (){
    $user = User::validForgotDecrypt($_GET["code"]);

    $page = new PageAdmin([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("forgot-reset", array(
        "name"=>$user["desperson"],
        "code"=>$_GET["code"]
    ));

});

$app->post("/admin/forgot/reset", function (){
    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUsed($forgot["idrecovery"]);
    $user = new User();
    $user->get((int)$forgot["iduser"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT, [
        "cost"=>12
    ]);
    $iduser = $forgot["iduser"];
    $user->setPassword($password,$iduser);
    $page = new PageAdmin([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("forgot-reset-success");
});

$app->get("/admin/categories", function (){
    User::verifyLogin();
    $categories = Category::listAll();
    $page = new PageAdmin();
    $page->setTpl("categories", [
        'categories'=>$categories
    ]);

});

$app->get("/admin/categories/create", function(){
    User::verifyLogin();
    $page = new PageAdmin();
    $page->setTpl("categories-create");
});

$app->post("/admin/categories/create", function(){
    User::verifyLogin();
    $category = new Category();
    $category->setData($_POST);
    $category->save();
    header('Location: /admin/categories');
    exit;
});

$app->get("/admin/categories/:idcategory/delete", function ($idcategory){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    $category->delete();
    header('Location: /admin/categories');
    exit;
});

$app->get("/admin/categories/:idcategory", function ($idcategory){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    $page = new PageAdmin();
    $page->setTpl("categories-update",[
        'category'=>$category->getValues()
    ]);

});

$app->post("/admin/categories/:idcategory", function ($idcategory){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    $category->setData($_POST);
    $category->save();
    header('Location: /admin/categories');
    exit;
});

$app->get("/categories/:idcategory", function ($idcategory){
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $category = new Category();
    $category->get((int)$idcategory);
    $pagination = $category->getProductsPage($page);
    $pages = [];
    for ($i=1; $i <= $pagination['pages']; $i++) {
        array_push($pages, [
            'link'=>'/categories/' . $category->getidcategory() . '?page=' . $i,
            'page'=>$i
        ]);
    }
    $page = new Page();
    $page->setTpl("category",[
        'category'=>$category->getValues(),
        'products'=>$pagination["data"],
        'pages'=>$pages

    ]);
});

$app->get("/admin/products", function (){
    User::verifyLogin();
    $products = Product::listAll();
    $page= new PageAdmin();
    $page->setTpl("products",[
        "products"=>$products
    ]);
});

$app->get("/admin/products/create", function (){
    User::verifyLogin();
    $page= new PageAdmin();
    $page->setTpl("products-create");
});

$app->post("/admin/products/create", function (){
    User::verifyLogin();
    $product = new Product();
    $product->setData($_POST);
    $product->Save();
    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct", function ($idproduct){
    User::verifyLogin();
    $product = new Product();
    $product->get((int)$idproduct);
    $page= new PageAdmin();
    $page->setTpl("products-update", [
        'product'=>$product->getValues()
    ]);
});

$app->post("/admin/products/:idproduct", function ($idproduct){
    User::verifyLogin();
    $product = new Product();
    $product->get((int)$idproduct);
    $product->setData($_POST);
    $product->save();
    $product->setPhoto($_FILES["file"]);

    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct/delete", function ($idproduct){
   User::verifyLogin();
   $product = new Product();
   $product->get((int)$idproduct);
   $product->delete();
   header('Location: /admin/products');
   exit;
});

$app->get("/admin/categories/:idcategory/products", function($idcategory){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    $page = new PageAdmin();
    $page->setTpl("categories-products",[
        'category'=>$category->getValues(),
        'productsRelated'=>$category->getProducts(),
        'productsNotRelated'=>$category->getProducts(false)
    ]);
});

$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    $product = new Product();
    $product->get((int)$idproduct);
    $category->addProduct($product);

    header("location: /admin/categories/" . $idcategory . "/products");
    exit;
});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    $product = new Product();
    $product->get((int)$idproduct);
    $category->removeProduct($product);

    header("location: /admin/categories/" . $idcategory . "/products");
    exit;
});

$app->get("/products/:desurl", function ($desurl){
    $product = new Product();
    $product->getFromURL($desurl);
    $page = new Page();
    $page->setTpl("product-detail",[
        'product'=>$product->getValues(),
        'categories'=>$product->getCategories()
        ]);

});

$app->get("/cart", function (){
    $cart = Cart::getFromSession();
    $page = new Page();
    $page->setTpl("cart", [
        'cart'=>$cart->getValues(),
        'products'=>$cart->getProducts(),
        'error'=>Cart::getMsgError()
    ]);
});

$app->get("/cart/:idproduct/add", function ($idproduct){
    $product = new Product();
    $product->get((int)$idproduct);
    $cart = Cart::getFromSession();
    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
    for ($i = 0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/minus", function ($idproduct){
    $product = new Product();
    $product->get((int)$idproduct);
    $cart = Cart::getFromSession();
    $cart->removeProduct($product);
    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/remove", function ($idproduct){
    $product = new Product();
    $product->get((int)$idproduct);
    $cart = Cart::getFromSession();
    $cart->removeProduct($product, true);
    header("Location: /cart");
    exit;
});

$app->post("/cart/freight", function (){
   $cart = Cart::getFromSession();
   $cart->setFreight($_POST['zipcode']);
   header("Location: /cart");
   exit;
});

$app->get("/checkout", function (){
   $cart = Cart::getFromSession();
   User::verifyLogin(false);
   $address = new Address();

   if (isset($_GET['zipcode'])){
       $address->loadFromCEP($_GET['zipcode']);
       $cart->setdeszipcode($_GET['zipcode']);
       $cart->save();
       $cart->getCalculateTotal();
   }




   if (!$address->getdesaddress()) $address->setdesaddress('');
   if (!$address->getdescomplement()) $address->setdescomplement('');
   if (!$address->getdesdistrict()) $address->setdesdistrict('');
   if (!$address->getdescity()) $address->setdescity('');
   if (!$address->getdesstate()) $address->setdesstate('');
    if (!$address->getdescountry()) $address->setdescountry('');
    if (!$address->getdeszipcode()) $address->setdeszipcode('');
    if (Cart::getMsgError() !== '') {
        header("Location: /checkout");
        exit;
    };
   $page = new Page();
   $page->setTpl("checkout", [
        'cart'=>$cart->getValues(),
        'address'=>$address->getValues(),
        'products'=>$cart->getProducts(),
        'error'=>Cart::getMsgError()
        ]);
});


$app->post("/checkout", function (){
    User::verifyLogin(false);
    if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
        Address::setMsgError("informe o Cep!");
        header("Location: /checkout");
        exit;
    }
    if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
        Address::setMsgError("informe o endereço!");
        header("Location: /checkout");
        exit;
    }
    if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
        Address::setMsgError("informe o distrito!");
        header("Location: /checkout");
        exit;
    }
    if (!isset($_POST['descity']) || $_POST['descity'] === '') {
        Address::setMsgError("informe o Cep!");
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
        Address::setMsgError("informe o estado!");
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
        Address::setMsgError("informe o pais!");
        header("Location: /checkout");
        exit;
    }

    Address::clearMsgError();

    $user = User::getFromSession();
    $addres = new Address();
    $addres->setData($_POST);
    $_POST['deszipcode'] = $_POST['zipcode'];
    $_POST['idperson'] = $user->getidperson();
    $addres->save();


    header("Location: /order");
    exit;
});

$app->get("/login", function (){

    $page = new Page();
    $page->setTpl("login", [
        'error'=>User::getError(),
        'errorRegister'=>User::getErrorRegister(),
        'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : [
            'name'=>'',
            'email'=>'',
            'phone'=>''
        ]
        ]);
});

$app->post("/login", function (){


    try {
        User::login($_POST['login'], $_POST['password']);
    } catch (Exception $e) {
        User::setError($e->getMessage());
    }
    header("Location: /checkout");
    exit;

});

$app->get("/logout", function (){
    User::logout();
    header("Location: /login");
    exit;
});

$app->post("/register", function (){
    $_SESSION['registerValues'] = $_POST;


    if (!isset($_POST['name']) || $_POST['name'] == ''){
        User::setErrorRegister("Preencha o seu nome.");
        header("Location: /login");
        Exit;

    }

    if (!isset($_POST['email']) || $_POST['email'] == ''){
        User::setErrorRegister("Preencha o seu email.");
        header("Location: /login");
        Exit;

    }

    if (!isset($_POST['phone']) || $_POST['phone'] == ''){
        User::setErrorRegister("Preencha o seu telefone.");
        header("Location: /login");
        Exit;

    }

    if (!isset($_POST['password']) || strlen($_POST['password']) < 5 ){
        User::setErrorRegister("A senha deve ser conter no mínimo 5 caracteres! ");
        header("Location: /login");
        Exit;

    }



    if (User::checkLoginExist($_POST['email']) === true ){
        User::setErrorRegister("Esse email já está cadastrado!");
        header("Location: /login");

        Exit;

    }

   $user = new User();

   $user->setData([
       'inadmin'=>0,
       'deslogin'=>$_POST['email'],
       'desperson'=>$_POST['name'],
       'desemail'=>$_POST['email'],
       'despassword'=>$_POST['password'],
       'nrphone'=>$_POST['phone']
   ]);

      $user->save();
   User::login($_POST['email'], $_POST['password']);
   header('Location: /checkout');
   exit;
});

$app->get("/profile", function (){
    User::verifyLogin(false);
    $user = User::getFromSession();
    $page = new Page();


    $page->setTpl("profile", [
        'user'=>$user->getValues(),
        'profileMsg'=>User::getSuccess(),
        'profileError'=>User::getError()
    ]);
});

$app->post("/profile", function (){

    User::verifyLogin(false);
    if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
        User::setError("Preencha seu nome!");
        header("Location: /profile");
        Exit;
    }
    $user = User::getFromSession();


    if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
        User::setError("Preencha seu email!");
        header("Location: /profile");
        Exit;
    }



    if ($_POST['desemail'] !== $user->getdesemail()) {

        if (User::checkLoginExist($_POST['desemail']) === true) {
            User::setError("Esse e-mail já está cadastrado!");
            header("Location: /profile");
            Exit;
        }
    }

    $_POST['deslogin'] = $user->getdeslogin();
    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $user->setData($_POST);
    $_SESSION[User::SESSION] = $user->getValues();
    User::setSuccess("Dados atualizados com sucesso!");
    $user->update();
    header('Location: /profile');
    exit;
});


$app->run();

