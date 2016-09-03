<?php
session_start();

require 'PHPMailerAutoload.php';
//require 'jwt_helper.php';

// Token
$decoded_token = null;

if (file_exists('../../../includes/MyDBi.php')) {
    require_once '../../../includes/MyDBi.php';
    require_once '../../../includes/utils.php';
} else {
    require_once 'MyDBi.php';
}


class Usuarios extends Main
{
    private static $instance;

    public static function init($decoded)
    {
        self::$instance = new Main(get_class(), $decoded['function']);
        try {
            call_user_func(get_class() . '::' . $decoded['function'], $decoded);
        } catch (Exception $e) {

            $file = 'error.log';
            $current = file_get_contents($file);
            $current .= date('Y-m-d H:i:s') . ": " . $e . "\n";
            file_put_contents($file, $current);

            header('HTTP/1.0 500 Internal Server Error');
            echo $e;
        }
    }


    /**
     * @description Obtiene todo los deudores.
     * TODO: Optimizar
     */
    function getDeudores()
    {


        $db = new MysqliDb();
        $deudores = array();

        $results = $db->rawQuery('Select usuario_id, nombre, apellido, saldo, 0 asientos from usuarios where saldo <= -1;');


        foreach ($results as $key => $row) {
//        $movimientos = $db->rawQuery("select movimiento_id from detallesmovimientos where detalle_tipo_id = 3 and valor = ".$row["cliente_id"].");");
            $asientos = $db->rawQuery("select asiento_id, fecha, cuenta_id, sucursal_id, importe, movimiento_id, 0 detalles
from movimientos where cuenta_id like '1.1.2.%' and movimiento_id in
(select movimiento_id from detallesmovimientos where detalle_tipo_id = 3 and valor = " . $row["cliente_id"] . ");");

            foreach ($asientos as $key_mov => $movimento) {
                $detalles = $db->rawQuery("select detalle_tipo_id,
                                      CASE when (detalle_tipo_id = 8) then
                                        (select concat(producto_id, ' - ' , nombre) from productos where producto_id = valor)
                                      when (detalle_tipo_id  != 8) then valor
                                      end valor from detallesmovimientos
                                      where movimiento_id = (select movimiento_id from movimientos where cuenta_id like '4.1.1.%' and asiento_id=" . $movimento["asiento_id"] . ");");
                $asientos[$key_mov]["detalles"] = $detalles;
            }

            $results[$key]["asientos"] = $asientos;
//        $row["detalles"] = $detalle;

//        array_push($deudores, $row);
        }

        echo json_encode($results);
    }

    /* @name: forgotPassword
     * @param $email = email del usuario
     * @description: Envia al usuario que lo solicita, un password aleatorio. El password se envía desde acá porque no debe
     * pasar por js, el js está en el cliente, lo cual podría dar un punto para conseguir un pass temporal.
     * todo: Agregar tiempo límite para el cambio. Agregar template de mail dinámico.
     */
    function forgotPassword($params)
    {

        $db = new MysqliDb();
        $options = ['cost' => 12];
        $new_password = randomPassword();

        $password = password_hash($new_password, PASSWORD_BCRYPT, $options);

        $data = array('password' => $password);

        $db->where('mail', $params["email"]);

        if ($db->update('usuarios', $data)) {
            $mail = new PHPMailer;
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = 'gator4184.hostgator.com';  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'ventas@ac-desarrollos.com';                 // SMTP username
            $mail->Password = 'ventas';                           // SMTP password
            $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';

            $mail->From = 'ventas@ac-desarrollos.com';
            $mail->FromName = 'UIGLP';
            $mail->addAddress($params["email"]);     // Add a recipient
            $mail->addAddress('arielcessario@gmail.com');     // Add a recipient
            $mail->addAddress('juan.dilello@gmail.com');               // Name is optional
            $mail->addAddress('diegoyankelevich@gmail.com');
            $mail->isHTML(true);    // Name is optional

            $mail->Subject = 'Recuperar Contraseña UGLP';
            $mail->Body = "
            <table>
                <tr>
                    <td>Te enviamos a continuación la siguiente contraseña.</td>
                </tr>
                <tr>
                    <td>Nueva Contraseña:</td>
                </tr>
                <tr>
                    <td>" . $new_password . "</td>
                </tr>
                <tr>
                    <td>UIGLP</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
            </table>";
            $mail->AltBody = "Nuevo Mail:" . $new_password;

            if (!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            } else {
                echo 'Message has been sent';
            }
        }
    }

    /* @name: randomPassword
     * @description: Genera password aleatorio.
     * @return: array(string) crea un array de 8 letra
     */
    function randomPassword()
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /* @name: createToken
     * @param
     * @description: Envia al usuario que lo solicita, un password aleatorio.
     * @return: JWT:string de token
     * todo: Agregar tiempos de expiración. Evaluar si hay que devolver algún dato dentro de data.
     */
    function createToken($user, $sucursal_id, $caja_id)
    {

        $tokenId = base64_encode(mcrypt_create_iv(32));
        $issuedAt = time();
        $notBefore = $issuedAt + 10;             //Adding 10 seconds
        $expire = $notBefore + 60;            // Adding 60 seconds
        global $serverName; // Retrieve the server name from config file
        $aud = $serverName;
//        $serverName = $config->get('serverName'); // Retrieve the server name from config file

        /*
         * Create the token as an array
         */
        $data = [
            'iat' => $issuedAt,         // Issued at: time when the token was generated
            'jti' => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss' => $serverName,       // Issuer
            'nbf' => $notBefore,        // Not before
            'exp' => $expire,           // Expire
            'aud' => $aud,           // Expire
            'data' => [                  // Data related to the signer user
                'id' => $user["usuario_id"], // userid from the users table
                'nombre' => $user["nombre"], // User name
                'apellido' => $user["apellido"], // User name
                'mail' => $user["mail"], // User name
                'sucursal_id' => $sucursal_id, // User name
                'caja_id' => $caja_id, // User name
                'rol' => $user["rol_id"] // Rol
            ]
        ];

        global $secret;
        return JWT::encode($data, $secret);
        /*
         * More code here...
         */
    }

    /* @name: remove
     * @param $usuario_id = id de usuario
     * @description: Borra un usuario y su dirección.
     * todo: Sacar dirección y crear sus propias clases dentro de este mismo módulo.
     */
    function remove($params)
    {

        $db = new MysqliDb();

        $db->where("usuario_id", $params["usuario_id"]);
        $results = $db->delete('usuarios');

        $db->where("usuario_id", $params["usuario_id"]);
        $results = $db->delete('direcciones');

        if ($results) {

            echo json_encode(1);
        } else {
            echo json_encode(-1);

        }
    }

    /* @name: get
     * @param
     * @description: Obtiene todos los usuario con sus direcciones.
     * todo: Sacar dirección y crear sus propias clases dentro de este mismo módulo.
     */
    function get()
    {
        $db = self::$instance->db;
        $results = $db->get('usuarios');

        foreach ($results as $key => $row) {
            $db->where('usuario_id', $row['usuario_id']);
            $results[$key]["password"] = '';
            $direcciones = $db->get('direcciones');
            $results[$key]['direcciones'] = $direcciones;
        }

        $res = array();
        foreach ($results as $row) {
            $res[$row['usuario_id']] = $row;
        }

        echo json_encode($results);
//        echo json_encode($res);
    }

    /* @name: login
     * @param $mail
     * @param $password
     * @param $sucursal_id
     * @description: Valida el ingreso de un usuario.
     * todo: Sacar dirección y crear sus propias clases dentro de este mismo módulo.
     */
    function login($params)
    {
        $db = self::$instance->db;
//        $db->where("mail", $params["mail"]);
//
//        $db->join("direcciones d", "d.usuario_id=u.usuario_id", "LEFT");
//        $results = $db->get("usuarios u");

        $results = $db->rawQuery('SELECT u.usuario_id, u.nombre, apellido, mail, rol_id, password, tipo_doc, nro_doc, marcado, saldo, social_login FROM usuarios u LEFT JOIN direcciones d on d.usuario_id=u.usuario_id WHERE  mail = "' . $params['mail'] . '"');


        global $jwt_enabled;
//        return;

        if ($db->count > 0) {

            if ($results[0]['social_login'] !== 0) {
                echo json_encode(-1);
                exit;
            }
            $hash = $results[0]['password'];
            if (password_verify($params["password"], $hash)) {
                $results[0]['password'] = '';
                header('HTTP/1.0 200 Ok');
                // Si la seguridad se encuentra habilitada, retorna el token y el usuario sin password
                //$results[0]->sucursal = $sucursal_id; //-1 == web
                //Comente la linea de arriba xq me saltaba error.
                if ($jwt_enabled) {
                    echo json_encode(
                        array(
                            'token' => self::createToken($results[0], $params['sucursal_id'], $params['caja_id']),
                            'user' => $results[0])
                    );
                } else {
                    echo json_encode(array('token' => '', 'user' => $results[0]));
                }
                self::addLogin($results[0]['usuario_id'], $params["sucursal_id"], $params["caja_id"], 1);

            } else {
                self::addLogin($results[0]['usuario_id'], $params["sucursal_id"], $params["caja_id"], 0);
                header('HTTP/1.0 500 Internal Server Error');
                echo "Password incorrecto";
            }
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo "No existe el usuario.";
        }
    }

    /**
     * @description Metodo para ingresar con una red social
     * @param $token_social
     * @param $user
     */
    function loginSocial($params)
    {
        require_once 'jwt_helper . php';
        // // validate the token
        $pre_token = str_replace('Bearer ', '', $params["token_social"]);
        $token = str_replace('"', '', $params["token_social"]);
        global $secret_social;
        global $decoded_token;
        try {
            $decoded_token = JWT::decode($token, base64_decode(strtr($secret_social, '-_', '+/')), true);

            $db = new MysqliDb();

            $user_decoded = json_decode($params["user"]);
            $db->where('mail', $user_decoded->mail);
            $results = $db->get('usuarios');

            $results[0]["password"] = '';
            echo json_encode(
                array(
                    'token' => createToken($results[0], -1, -1),
                    'user' => $results[0])
            );

        } catch (UnexpectedValueException $ex) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Invalid token";
            exit();
        }


    }

    /* @name: checkLastLogin
     * @param $userid
     * @description: --
     * todo: Este método podría volar, se puede verificar con jwt el último login.
     */
    function checkLastLogin($params)
    {
        $db = new MysqliDb();
        $results = $db->rawQuery('select TIME_TO_SEC(TIMEDIFF(now(), last_login)) diferencia from usuarios where usuario_id = ' . $userid);

        if ($db->count < 1) {
            $db->rawQuery('update usuarios set token ="" where usuario_id =' . $params["$userid"]);
            echo(json_encode(-1));
        } else {
            $diff = $results[0]["diferencia"];

            if (intval($diff) < 12960) {
                echo(json_encode($results[0]));
            } else {
                $db->rawQuery('update usuarios set token ="" where usuario_id =' . $params["$userid"]);
                echo(json_encode(-1));
            }
        }
    }

    /* @name: create
     * @param $user
     * @description: Crea un nuevo usuario y su dirección
     * todo: Sacar dirección, el usuario puede tener varias direcciones.
     */
    function create($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $user_decoded = self::checkUsuario(json_decode($params["user"]));
        $options = ['cost' => 12];
        $password = password_hash($user_decoded->password, PASSWORD_BCRYPT, $options);

        $data = array(
            'nombre' => $user_decoded->nombre,
            'apellido' => $user_decoded->apellido,
            'mail' => $user_decoded->mail,
            'nacionalidad_id' => $user_decoded->nacionalidad_id,
            'tipo_doc' => $user_decoded->tipo_doc,
            'nro_doc' => $user_decoded->nro_doc,
            'comentarios' => $user_decoded->comentarios,
            'marcado' => $user_decoded->marcado,
            'telefono' => $user_decoded->telefono,
            'fecha_nacimiento' => $user_decoded->fecha_nacimiento,
            'profesion_id' => $user_decoded->profesion_id,
            'saldo' => $user_decoded->saldo,
            'password' => $password,
            'rol_id' => $user_decoded->rol_id,
            'news_letter' => $user_decoded->news_letter,
            'cbu' => $user_decoded->cbu,
            'social_login' => $user_decoded->social_login,
        );

        $result = $db->insert('usuarios', $data);
        if ($result > -1) {

            $data = array(
                'usuario_id' => $result,
                'calle' => $user_decoded->calle,
                'nro' => $user_decoded->nro,
                'piso' => $user_decoded->piso,
                'puerta' => $user_decoded->puerta,
                'ciudad_id' => $user_decoded->ciudad_id
            );

            $dir = $db->insert('direcciones', $data);

            if ($dir > -1) {
                $db->commit();
                header('HTTP/1.0 200 Ok');
                echo json_encode($result);
            } else {
                $db->rollback();
                header('HTTP/1.0 500 Internal Server Error');
                echo $db->getLastError();
            }
        } else {
            $db->rollback();
            header('HTTP/1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /* @name: clientExist
     * @param $mail
     * @description: Verifica si un usuario existe
     * todo:
     */
    function userExist($params)
    {
        //Instancio la conexion con la DB
        $db = self::$instance->db;
        //Armo el filtro por email
        $db->where("mail", $params["mail"]);

        //Que me retorne el usuario filtrando por email
        $results = $db->get("usuarios");

        //retorno el resultado serializado
        if ($db->count > 0) {
            echo json_encode($db->count);
        } else {
            echo json_encode(-1);

        }
    }

    /* @name: changePassword
     * @param $usuario_id
     * @param $pass_old
     * @param $pass_new
     * @description: Cambia el password, puede verificar que el anterior sea correcto o simplemente hacer un update
     * (pass_old == ''), depende de la seguridad que se requiera.
     * todo:
     */
    function changePassword($usuario_id, $pass_old, $pass_new)
    {
        $db = self::$instance->db;

        $db->where('usuario_id', $usuario_id);
        $results = $db->get("usuarios");

        if ($db->count > 0) {
            $result = $results[0];

            if ($pass_old == '' || password_verify($pass_old, $result['password'])) {

                $options = ['cost' => 12];
                $password = password_hash($pass_new, PASSWORD_BCRYPT, $options);
                $db->where('usuario_id', $usuario_id);
                $data = array('password' => $password);
                if ($db->update('usuarios', $data)) {
                    echo json_encode(1);
                } else {
                    echo json_encode(-1);
                }
            }
        } else {
            echo json_encode(-1);
        }
    }

    /* @name: create
     * @param $user
     * @description: Update de usuario y dirección
     * todo: Sacar dirección, el usuario puede tener varias direcciones.
     */
    function update($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $user_decoded = self::checkUsuario(json_decode($params["user"]));

        if ($user_decoded->password != '') {
            self::changePassword($user_decoded->usuario_id, '', $user_decoded->password);
        }

        $db->where('usuario_id', $user_decoded->usuario_id);
        $data = array(
            'nombre' => $user_decoded->nombre,
            'apellido' => $user_decoded->apellido,
            'mail' => $user_decoded->mail,
            'nacionalidad_id' => $user_decoded->nacionalidad_id,
            'tipo_doc' => $user_decoded->tipo_doc,
            'nro_doc' => $user_decoded->nro_doc,
            'comentarios' => $user_decoded->comentarios,
            'marcado' => $user_decoded->marcado,
            'telefono' => $user_decoded->telefono,
            'fecha_nacimiento' => $user_decoded->fecha_nacimiento,
            'profesion_id' => $user_decoded->profesion_id,
            'saldo' => $user_decoded->saldo,
            'rol_id' => $user_decoded->rol_id,
            'news_letter' => $user_decoded->news_letter,
            'cbu' => $user_decoded->cbu,
            'social_login' => $user_decoded->social_login
        );

        if ($db->update('usuarios', $data)) {


            $db->where('usuario_id', $user_decoded->usuario_id);
            $data = array(
                'calle' => $user_decoded->calle,
                'nro' => $user_decoded->nro,
                'piso' => $user_decoded->piso,
                'puerta' => $user_decoded->puerta,
                'ciudad_id' => $user_decoded->ciudad_id
            );

            $dir = $db->update('direcciones', $data);

            if ($dir) {
                header('HTTP/1.0 200 Ok');
                $db->commit();
                echo json_encode('Guardado con éxito');
            } else {
                header('HTTP/1.0 500 Internal Server Error');
                $db->rollback();
                echo $db->getLastError();
            }

        } else {
            header('HTTP/1.0 500 Internal Server Error');
            $db->rollback();
            echo $db->getLastError();
        }
    }

    /**
     * @desciption Crea un registro de login en el histórico
     * @param $usuario_id
     * @param $sucursal_id
     * @param $ok
     */
    function addLogin($usuario_id, $sucursal_id, $caja_id, $ok)
    {
        $db = self::$instance->db;
        $data = array('usuario_id' => $usuario_id,
            'sucursal_id' => $sucursal_id,
            'caja_id' => $caja_id,
            'ok' => $ok);

        $db->insert('logins', $data);

    }

    /**
     * @description Verifica todos los campos de usuario para que existan
     * @param $usuario
     * @return mixed
     */
    function checkUsuario($usuario)
    {


        $usuario->nombre = (!array_key_exists("nombre", $usuario)) ? '' : $usuario->nombre;
        $usuario->apellido = (!array_key_exists("apellido", $usuario)) ? '' : $usuario->apellido;
        $usuario->mail = (!array_key_exists("mail", $usuario)) ? '' : $usuario->mail;
        $usuario->nacionalidad_id = (!array_key_exists("nacionalidad_id", $usuario)) ? 0 : $usuario->nacionalidad_id;
        $usuario->tipo_doc = (!array_key_exists("tipo_doc", $usuario)) ? 0 : $usuario->tipo_doc;
        $usuario->nro_doc = (!array_key_exists("nro_doc", $usuario)) ? '' : $usuario->nro_doc;
        $usuario->comentarios = (!array_key_exists("comentarios", $usuario)) ? '' : $usuario->comentarios;
        $usuario->marcado = (!array_key_exists("marcado", $usuario)) ? 0 : $usuario->marcado;
        $usuario->telefono = (!array_key_exists("telefono", $usuario)) ? '' : $usuario->telefono;
        $usuario->fecha_nacimiento = (!array_key_exists("fecha_nacimiento", $usuario)) ? '' : $usuario->fecha_nacimiento;
        $usuario->profesion_id = (!array_key_exists("profesion_id", $usuario)) ? 0 : $usuario->profesion_id;
        $usuario->saldo = (!array_key_exists("saldo", $usuario)) ? 0.0 : $usuario->saldo;
        $usuario->password = (!array_key_exists("password", $usuario)) ? '' : $usuario->password;
        $usuario->rol_id = (!array_key_exists("rol_id", $usuario)) ? 0 : $usuario->rol_id;
        $usuario->news_letter = (!array_key_exists("news_letter", $usuario)) ? 0 : $usuario->news_letter;
        $usuario->calle = (!array_key_exists("calle", $usuario)) ? '' : $usuario->calle;
        $usuario->puerta = (!array_key_exists("puerta", $usuario)) ? '' : $usuario->puerta;
        $usuario->piso = (!array_key_exists("piso", $usuario)) ? 0 : $usuario->piso;
        $usuario->nro = (!array_key_exists("nro", $usuario)) ? 0 : $usuario->nro;
        $usuario->ciudad_id = (!array_key_exists("ciudad_id", $usuario)) ? 0 : $usuario->ciudad_id;
        $usuario->cbu = (!array_key_exists("cbu", $usuario)) ? 0 : $usuario->cbu;
        $usuario->social_login = (!array_key_exists("social_login", $usuario)) ? 0 : $usuario->social_login;

        return $usuario;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents("php://input");
    $decoded = json_decode($data);
    Usuarios::init(json_decode(json_encode($decoded), true));
} else {
    Usuarios::init($_GET);
}



