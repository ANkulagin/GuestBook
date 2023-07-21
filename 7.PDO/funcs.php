<?php
function debug($data){
    echo '<pre>'. print_r($data,1) .'</pre>';
}

function registration(): bool
{
    global $pdo;
    $pdo->exec('USE my_db');
/*
 * $pdo->exec('USE my_db'); - Выбирает базу данных с именем my_db. Это предполагает, что подключение к базе данных уже установлено и объект PDO находится в переменной $pdo.
 */
    $login =!empty($_POST['login']) ? trim($_POST['login']) : '';
    $pass =!empty($_POST['pass']) ? trim($_POST['pass']) : ''; //trim delete space
    if (empty($login) || empty($pass)){
        $_SESSION['errors'] = 'Поля логин/пароль обязательны';
        return false;
    }
/*
 * Данный участок кода выполняет проверку наличия уникального логина в базе данных перед регистрацией нового пользователя.
 */
    $res = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = ?");
    $res->execute([$login]);
/*
*
prepare - это метод объекта PDO (PHP Data Objects), который используется для подготовки SQL-запроса к выполнению. Когда вы создаете объект PDO, вы можете использовать его метод prepare, чтобы создать подготовленный запрос.

Подготовленные запросы - это специальные инструкции SQL, которые разделяют данные и запросы. Вместо вставки данных прямо в запрос SQL, вы используете плейсхолдеры, обозначенные символом ? или :name, чтобы указать места, в которых будут вставлены данные. Затем вы передаете значения этих плейсхолдеров в запрос с помощью метода execute, как это показано в вашем коде.

Таким образом, в строке $res = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = ?");:

"SELECT COUNT(*) FROM users WHERE login = ?" - это сам SQL-запрос с плейсхолдером ?, который говорит о том, что вместо этого символа будет подставлено значение логина.

? - это плейсхолдер. Он обозначает место, куда будет вставлено значение логина перед выполнением запроса. Значение логина будет передано позже, при вызове метода execute.

Преимущество подготовленных запросов заключается в том, что они позволяют предотвратить атаки SQL-инъекций, так как значения параметров автоматически экранируются, а также позволяют повторно использовать запросы с различными значениями параметров без необходимости каждый раз создавать новый запрос.
*/
    if ($res->fetchColumn()){
/*
 * if ($res->fetchColumn()) { ... } - Вызывает метод fetchColumn() для объекта результата запроса. Этот метод извлекает значение первой колонки первой строки результата запроса. Если значение существует и не равно нулю, то условие считается истинным, что означает, что логин уже существует в базе данных.
 */
        $_SESSION['errors']= 'Данное имя уже используется';
        return false;
    }

    $pass =password_hash($pass ,PASSWORD_DEFAULT);
    $res = $pdo->prepare("INSERT INTO users (login,pass) VALUES (?,?)");
    if ($res->execute([$login, $pass])){
        $_SESSION['success'] ='Успешная регестрация';
        return true;
    }else{
        $_SESSION['errors']='Ошибка регестрации';
        return false;
    }

}


function login(): bool{
    global $pdo;
    $pdo->exec('USE my_db');

    $login =!empty($_POST['login']) ? trim($_POST['login']) : '';
    $pass =!empty($_POST['pass']) ? trim($_POST['pass']) : ''; //trim delete space

    if (empty($login) || empty($pass)){
        $_SESSION['errors'] = 'Поля логин/пароль обязательны';
        return false;
    }

    $res =$pdo->prepare("SELECT * FROM users WHERE login= ?");
    $res->execute([$login]);
    if (!$user =$res->fetch()){
        $_SESSION['errors'] = 'Логин/пароль введены неверно';
        return false;
    }
    if (!password_verify($pass,$user['pass'])){
        $_SESSION['errors'] = 'Логин/пароль введены неверно';
        return false;
    }else{
        $_SESSION['success']= 'Вы успешно авторизовались';
        $_SESSION['user']['name']= $user['login'];
        $_SESSION['user']['id']= $user['id'];
        return true;
    }

}

function save_message(): bool
{
    global $pdo;
    $pdo->exec('USE my_db');
    $message =!empty($_POST['message']) ? trim($_POST['message']) : '';

    if (!isset($_SESSION['user']['name'])){
        $_SESSION['errors']='необходимо авторизоваться';
        return false;
    }
    if (empty($message) ){
        $_SESSION['errors'] = 'Введите текст сообщения';
        return false;
    }
    $res =$pdo->prepare("INSERT INTO messages (name,message) VALUES (?,?)");
    if ($res->execute([$_SESSION['user']['name'], $message])){
        $_SESSION['success']= 'Сообщение добавлено';
        return true;
    }else{
        $_SESSION['errors']='Ошибка';
        return false;
    }
}
function get_message(): array{
    global $pdo;
    $pdo->exec('USE my_db');
    $res=$pdo->query("SELECT * FROM messages");
    return $res->fetchAll();
}