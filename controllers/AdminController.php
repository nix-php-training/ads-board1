<?php
use application\core\BaseController;
use application\classes\Registry;

class AdminController extends BaseController
{
    private $db;
    private $admin;
    public function __construct($request)
    {
        $this->db = Registry::get('database');
        parent::__construct($request);
        $this->admin = new Users();

    }
    public function regiserAction()
    {
        if ($this->getRequest()->isPost())
        {
            $r = $this->admin->authorize();
            if (Users::isAuthorized())
            {
                header('Location: /admin');
            }
        }
        $this->render('admin/login_adm', ['error' => $r]);
    }
    public function is_adm()
    {
        $array = $this->admin->get();
        if($array['role'] == 'admin')
            return true;
        else return false;
    }

    public function panelAction()
    {
        if($this->is_adm())
        {
            $row = $this->db->query("SELECT users.id, login, status, users.phone, users.date_create,
                                COUNT(ads.user_id) AS  caunt
                                FROM users LEFT JOIN ads ON(users.id=ads.user_id) GROUP BY users.id", []);
            $this->render('admin/panel', ['row'=>$row]);
        }
        else
            $res = 'Для входа вам нужны права администратора!';
        $this->render('admin/login_adm', ['error'=>$res]);

    }

    public function banAction()
    {
        $par = $this->getRequest()->getParams();
        foreach ($par as $k) {
            $this->db->update('users', ['status' => 'banned'], ['id' => intval($k)]);
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin");
        }
    }
    public function unbanAction()
    {
        $par = $this->getRequest()->getParams();
        foreach ($par as $k) {
            $this->db->update('users', ['status' => 'confirmed'], ['id' => intval($k)]);
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin");
        }
    }

    public function searchAction()
    {
        if(isset($_POST['search']) && iconv_strlen($_POST['search'])>1){
            $search = $_POST['search'];
            $search = preg_replace("#[^0-9a-z]#i", "", $search);
            $finder = $this->db->query("SELECT users.login, categories.name, ads.title, ads.text, ads.phone, status
              FROM users LEFT JOIN ads ON(ads.user_id=users.id)
              LEFT JOIN categories ON(categories.id=ads.category_id)
              WHERE login LIKE '%$search%'
              OR categories.name LIKE '%$search%'
              OR title LIKE '%$search%'", array('login'=>$search));
            if(empty($finder))
            {
                echo '<p><strong>попробуйте другое ключевое слово</strong></p><br />';
            }
            else{
                foreach ($finder as $tt) {
                    echo
                        '<tr><td>'.$tt["login"].'</td>
            <td>'.$tt["phone"].'</td>
            <td>'.$tt["status"].'</td>
            <td>'.$tt["name"].'</td>
            <td>'.$tt["title"].'</td></tr>';
                }}}}

    public function categoriesAction()
    {
        if($this->is_adm())
        {
            $row = $this->db->query("SELECT categories.id, categories.name, categories.description, COUNT(ads.category_id) AS count1
                                    FROM categories LEFT JOIN ads ON(categories.id=ads.category_id) GROUP BY categories.id", []);
            $this->render('admin/categories', ['row'=>$row]);
        }
        else
        {
            $res = 'Для входа вам нужны права администратора!';
            $this->render('admin/login_adm', ['error'=>$res]);
        }
    }

    public function addCatAction()
    {
        $name = $_POST['name'];
        $desc = $_POST['desc'];

        $this->db->insert('categories', ['name'=>$name, 'description'=>$desc]);
        header("Location: http://{$_SERVER['SERVER_NAME']}/categories");
    }

    public function showAction()
    {
        if(isset($_POST['formSubmit']))
        {
            $form = $_POST['form'];
            $sett = array_shift($form);
            $sett = intval($sett);
            if($sett == 777)
            {
                header("Location: http://{$_SERVER['SERVER_NAME']}/categories/show");
            }
            else
            {
                $row = $this->db->query("SELECT title, text, ads.date_create, categories.name, categories.id,
                                users.login, users.status FROM ads
                                LEFT JOIN categories ON(ads.category_id=categories.id)
                                LEFT JOIN users ON(ads.user_id=users.id) WHERE ads.category_id='$sett'", []);
                $this->render('admin/show', ['row'=>$row]);
            }
        }
        if($this->is_adm())
        {
            $row = $this->db->query("SELECT title, text, ads.date_create, categories.name, categories.id,
                                users.login, users.status FROM ads
                                LEFT JOIN categories ON(ads.category_id=categories.id)
                                LEFT JOIN users ON(ads.user_id=users.id)", []);
            $this->render('admin/show', ['row'=>$row]);
        }
        else
        {
            $res = 'Для входа вам нужны права администратора!';
            $this->render('admin/login_adm', ['error'=>$res]);
        }
    }
}