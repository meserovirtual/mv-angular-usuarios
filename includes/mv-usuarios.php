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
     * Genera una session para la mesa que lo esté solicitando, siempre tiene que entrar primero por acá.
     * Si el usuario cambia de mesa, pero ya está loggeado tiene que cambiar la session pero mantener el login,
     * por lo que debería generar un nuevo token y devolverlo
     * @param $mesa_id
     * TODO:
     *  - Se deberá agregar un tiempo de expiración al session
     */
    function generateSession($params)
    {
        $requestHeaders = apache_request_headers();
        $authorizationHeader = isset($requestHeaders['Authorization']) ? $requestHeaders['Authorization'] : null;
        if ($authorizationHeader == null) {
            // tengo que generar la session y devolverla
            $empty_user = array('id' => '', 'nombre' => '', 'apellido' => '', 'mail' => '', 'rol' => '');
            $session_id = generatePushId();
            $token = self::createTokenCliente($empty_user, $params["mesa_id"], $session_id);
        } else {
            // tengo que modificar la mesa y devolver el nuevo token

        }

        echo $token;
    }

    /**
     * @description Obtiene todo los deudores.
     * TODO: Optimizar
     */
    function getDeudores()
    {
        $db = self::$instance->db;
        $deudores = array();

        $results = $db->rawQuery('Select usuario_id, nombre, apellido, saldo, telefono, mail, nro_doc, 0 asientos from usuarios where saldo <= -1;');

        foreach ($results as $key => $row) {
//        $movimientos = $db->rawQuery("select movimiento_id from detallesmovimientos where detalle_tipo_id = 3 and valor = ".$row["cliente_id"].");");
            $asientos = $db->rawQuery("select asiento_id, fecha, cuenta_id, sucursal_id, importe, movimiento_id, 0 detalles
from movimientos where cuenta_id like '1.1.2.%' and movimiento_id in
(select movimiento_id from detallesmovimientos where detalle_tipo_id = 3 and valor = " . $row["usuario_id"] . ");");

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
    function createToken($user, $sucursal_id, $caja_id, $session_id = '')
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
                'rol' => $user["rol_id"], // Rol
                'session_id' => $session_id // Session
            ]
        ];

        global $secret;
        return JWT::encode($data, $secret);
        /*
         * More code here...
         */
    }

    /* @name: createToken
     * @param
     * @description: Envia al usuario que lo solicita, un password aleatorio.
     * @return: JWT:string de token
     * todo: Agregar tiempos de expiración. Evaluar si hay que devolver algún dato dentro de data.
     */
    function createTokenCliente($user, $mesa_id, $session_id = '')
    {

        $tokenId = base64_encode(mcrypt_create_iv(32));
        $issuedAt = time();
        $notBefore = $issuedAt + 10;             //Adding 10 seconds
        $expire = $notBefore + 60;            // Adding 60 seconds
        global $serverName; // Retrieve the server name from config file
        $aud = $serverName;
//        $serverName = $config->get('serverName'); // Retrieve the server name from config file

        if($session_id == ''){
            $session_id = generatePushId();
        }

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
                'mesa_id' => $mesa_id, // User name
                'rol' => $user["rol_id"], // Rol
                'session_id' => $session_id // Session
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
        $db = self::$instance->db;

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
    function get($params)
    {

        //$db = self::$instance->db;
        //if($params["all"] == "false")
        //    $db->where("status", 1);
        /*
        $db->where("rol_id",$params["rol_id"]);
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
*/


        $db = self::$instance->db;
        $results = $db->rawQuery('SELECT
                                    u.usuario_id,
                                    u.nombre,
                                    u.apellido,
                                    u.mail,
                                    u.nacionalidad_id,
                                    u.tipo_doc,
                                    u.nro_doc,
                                    u.comentarios,
                                    u.marcado,
                                    u.telefono,
                                    u.fecha_nacimiento,
                                    u.profesion_id,
                                    u.saldo,
                                    u.password,
                                    u.rol_id,
                                    u.news_letter,
                                    u.cbu,
                                    u.social_login,
                                    u.status,
                                    u.cta_cte,
                                    d.direccion_id,
                                    d.calle,
                                    d.nro,
                                    d.piso,
                                    d.puerta,
                                    d.ciudad_id
                                FROM usuarios u LEFT JOIN direcciones d ON d.usuario_id = u.usuario_id
                                WHERE u.rol_id IN (' . $params["rol_id"] . ') ORDER BY u.apellido, u.nombre');

        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["usuario_id"]])) {
                $final[$row["usuario_id"]] = array(
                    'usuario_id' => $row["usuario_id"],
                    'nombre' => $row["nombre"],
                    'apellido' => $row["apellido"],
                    'mail' => $row["mail"],
                    'nacionalidad_id' => $row["nacionalidad_id"],
                    'tipo_doc' => $row["tipo_doc"],
                    'nro_doc' => $row["nro_doc"],
                    'comentarios' => $row["comentarios"],
                    'marcado' => $row["marcado"],
                    'telefono' => $row["telefono"],
                    'fecha_nacimiento' => $row["fecha_nacimiento"],
                    'profesion_id' => $row["profesion_id"],
                    'saldo' => $row["saldo"],
                    //'password' => $row["password"],
                    'password' => '',
                    'rol_id' => $row["rol_id"],
                    'news_letter' => $row["news_letter"],
                    'cbu' => $row["cbu"],
                    'social_login' => $row["social_login"],
                    'status' => $row["status"],
                    'cta_cte' => $row["cta_cte"],
                    'direcciones' => array()
                );
            }

            $have_dir = false;
            if ($row["direccion_id"] !== null) {

                if (sizeof($final[$row['usuario_id']]['direcciones']) > 0) {
                    foreach ($final[$row['usuario_id']]['direcciones'] as $cat) {
                        if ($cat['direccion_id'] == $row["direccion_id"]) {
                            $have_dir = true;
                        }
                    }
                } else {
                    $final[$row['usuario_id']]['direcciones'][] = array(
                        'direccion_id' => $row['direccion_id'],
                        'calle' => $row['calle'],
                        'nro' => $row['nro'],
                        'piso' => $row['piso'],
                        'puerta' => $row['puerta'],
                        'ciudad_id' => $row['ciudad_id']
                    );

                    $have_dir = true;
                }

                if (!$have_dir) {
                    array_push($final[$row['usuario_id']]['direcciones'], array(
                        'direccion_id' => $row['direccion_id'],
                        'calle' => $row['calle'],
                        'nro' => $row['nro'],
                        'piso' => $row['piso'],
                        'puerta' => $row['puerta'],
                        'ciudad_id' => $row['ciudad_id']
                    ));
                }
            }

        }

        echo json_encode(array_values($final));

    }

    /* @name: login
     * @param $mail
     * @param $password
     * @param $sucursal_id
     * @description: Valida el ingreso de un usuario.
     * todo: Sacar dirección y crear sus propias clases dentro de este mismo módulo.
     */
    function loginCliente($params)
    {


        $db = self::$instance->db;
//        $db->where("mail", $params["mail"]);
//
//        $db->join("direcciones d", "d.usuario_id=u.usuario_id", "LEFT");
//        $results = $db->get("usuarios u");

        $results = $db->rawQuery('SELECT u.usuario_id, u.nombre, apellido, mail, rol_id, password, tipo_doc, nro_doc, marcado, saldo, social_login, status FROM usuarios u LEFT JOIN direcciones d on d.usuario_id=u.usuario_id WHERE  mail = "' . $params['mail'] . '"');


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
                if ($results[0]['status'] == 0) {
                    self::addLogin($results[0]['usuario_id'], getDataFromToken('mesa_id'), -1, 0);
                    header('HTTP/1.0 500 Internal Server Error');
                    echo "Usuario inhabilitado";
                } else {
                    if ($jwt_enabled) {
                        echo json_encode(
                            array(
                                'token' => self::createTokenCliente($results[0], getDataFromToken('mesa_id'), getDataFromToken('session_id')),
                                'user' => $results[0])
                        );
                    } else {
                        echo json_encode(array('token' => '', 'user' => $results[0]));
                    }
                    self::addLogin($results[0]['usuario_id'], getDataFromToken('mesa_id'), -1, 1);
                }
            } else {
                self::addLogin($results[0]['usuario_id'], getDataFromToken('mesa_id'), -1, 0);
                header('HTTP/1.0 500 Internal Server Error');
                echo "Password incorrecto";
            }
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo "No existe el usuario.";
        }
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

        $results = $db->rawQuery('SELECT u.usuario_id, u.nombre, apellido, mail, rol_id, password, tipo_doc, nro_doc, marcado, saldo, social_login, status FROM usuarios u LEFT JOIN direcciones d on d.usuario_id=u.usuario_id WHERE  mail = "' . $params['mail'] . '"');


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
                if ($results[0]['status'] == 0) {
                    self::addLogin($results[0]['usuario_id'], $params["sucursal_id"], $params["caja_id"], 0);
                    header('HTTP/1.0 500 Internal Server Error');
                    echo "Usuario inhabilitado";
                } else {
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
                }
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
        $user_decoded = self::checkUsuario(json_decode($params["user"]));
        $error = false;
        $error_code = 0;
        $message = '';

        if ($user_decoded->nro_doc != "") {
            $SQL = 'Select usuario_id from usuarios where nro_doc ="' . $user_decoded->nro_doc . '"';

            //$result = $db->rawQuery($SQL);
            $db->rawQuery($SQL);
            if ($db->count > 0) {
                //header('HTTP/1.0 500 Internal Server Error');
                //echo json_encode(1, 'Existe un usuario con el DNI o CUIT ingresado');
                //return;
                //echo json_encode(1);
                $error_code = 1;
                $message = 'Existe un usuario con el DNI o CUIT ingresado';
                $error = true;
            }
        }

        if (!$error) {
            if ($user_decoded->mail != "") {
                $SQL = 'Select usuario_id from usuarios where mail ="' . $user_decoded->mail . '"';
                //$result = $db->rawQuery($SQL);
                $db->rawQuery($SQL);
                if ($db->count > 0) {
                    //header('HTTP/1.0 500 Internal Server Error');
                    //echo json_encode(2, 'Existe un usuario con el Mail ingresado');
                    //return;
                    //echo json_encode(2);
                    //$error_code = 2;
                    $message = 'Existe un usuario con el Mail ingresado';
                    $error = true;
                }
            }
        }

        if (!$error) {
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
                'status' => $user_decoded->status,
                'cta_cte' => $user_decoded->cta_cte
            );

            $result = $db->insert('usuarios', $data);
            if ($result > -1) {

                foreach ($user_decoded->direcciones as $direccion) {
                    if (!self::createDirecciones($direccion, $result, $db)) {
                        $db->rollback();
                        //header('HTTP/1.0 500 Internal Server Error');
                        //echo $db->getLastError();
                        //return;
                        //echo json_encode(3);
                        $message = 'Error guardando la dirección';
                        $error = true;
                    }
                }

                if ($error) {
                    $db->rollback();
                    header('HTTP/1.0 500 Internal Server Error');
                } else {
                    $db->commit();
                    header('HTTP/1.0 200 Ok');
                    //echo json_encode($result);
                    //echo json_encode(4);
                    //$error_code = $result;
                    $message = 'La operación se realizo satisfactoriamente';
                    $error = false;
                }

            } else {
                $db->rollback();
                //header('HTTP/1.0 500 Internal Server Error');
                //echo $db->getLastError();
                //echo json_encode(3);
                $message = 'Error guardando el dato';
                $error = true;
            }
        }

        //echo json_encode($error_code);
        //echo json_encode(['error' => $error, 'results' => $error_code]);
        echo json_encode(['error' => $error, 'message' => $message]);
    }

    /*
     *
     */
    function createDirecciones($direccion, $usuario_id, $db)
    {
        $data = array(
            'usuario_id' => $usuario_id,
            'calle' => $direccion->calle,
            'nro' => $direccion->nro,
            'piso' => $direccion->piso,
            'puerta' => $direccion->puerta,
            'ciudad_id' => $direccion->ciudad_id
        );

        $dir = $db->insert('direcciones', $data);
        return ($dir > -1) ? true : false;
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
        $user_decoded = self::checkUsuario(json_decode($params["user"]));
        $error = false;
        $error_code = 0;
        $message = '';

        if ($user_decoded->nro_doc != "") {
            $SQL = 'Select usuario_id from usuarios where nro_doc ="' . $user_decoded->nro_doc . '" AND usuario_id != "' . $user_decoded->usuario_id . '"';
            $db->rawQuery($SQL);
            if ($db->count > 0) {
                //header('HTTP/1.0 500 Internal Server Error');
                //echo 'Existe un usuario con el DNI o CUIT ingresado';
                //return;
                $error_code = 1;
                $message = 'Existe un usuario con el DNI o CUIT ingresado';
                $error = true;
            }
        }

        if (!$error) {
            if ($user_decoded->mail != "") {
                $SQL = 'Select usuario_id from usuarios where mail ="' . $user_decoded->mail . '" AND usuario_id != "' . $user_decoded->usuario_id . '"';
                $db->rawQuery($SQL);
                if ($db->count > 0) {
                    //header('HTTP/1.0 500 Internal Server Error');
                    //echo 'Existe un usuario con el Mail ingresado';
                    //return;
                    //$error_code = 2;
                    $message = 'Existe un usuario con el Mail ingresado';
                    $error = true;
                }
            }
        }

        if (!$error) {
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
                'social_login' => $user_decoded->social_login,
                'status' => $user_decoded->status,
                'cta_cte' => $user_decoded->cta_cte
            );

            $result = $db->update('usuarios', $data);
            if ($result) {

                $db->where('usuario_id', $user_decoded->usuario_id);
                $db->delete('direcciones');

                foreach ($user_decoded->direcciones as $direccion) {
                    if (!self::createDirecciones($direccion, $user_decoded->usuario_id, $db)) {
                        $db->rollback();
                        //header('HTTP/1.0 500 Internal Server Error');
                        //echo $db->getLastError();
                        //return;
                        $message = 'Error guardando la dirección';
                        $error = true;
                    }
                }

                if ($error) {
                    $db->rollback();
                    header('HTTP/1.0 500 Internal Server Error');
                } else {
                    $db->commit();
                    header('HTTP/1.0 200 Ok');
                    //echo json_encode($result);
                    //echo json_encode(4);
                    //$error_code = $result;
                    $message = 'La operación se realizo satisfactoriamente';
                    $error = false;
                }

            } else {
                $db->rollback();
                //header('HTTP/1.0 500 Internal Server Error');
                //echo $db->getLastError();
                //echo json_encode(3);
                $message = 'Error guardando el dato';
                $error = true;
            }
        }

        echo json_encode(['error' => $error, 'message' => $message]);

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
        $usuario->cbu = (!array_key_exists("cbu", $usuario)) ? 0 : $usuario->cbu;
        $usuario->social_login = (!array_key_exists("social_login", $usuario)) ? 0 : $usuario->social_login;
        $usuario->status = (!array_key_exists("status", $usuario)) ? 0 : $usuario->status;
        $usuario->cta_cte = (!array_key_exists("cta_cte", $usuario)) ? 0 : $usuario->cta_cte;
        $usuario->direcciones = (!array_key_exists("direcciones", $usuario)) ? array() : self::checkDirecciones($usuario->direcciones);
        //$usuario->calle = (!array_key_exists("calle", $usuario)) ? '' : $usuario->calle;
        //$usuario->puerta = (!array_key_exists("puerta", $usuario)) ? '' : $usuario->puerta;
        //$usuario->piso = (!array_key_exists("piso", $usuario)) ? 0 : $usuario->piso;
        //$usuario->nro = (!array_key_exists("nro", $usuario)) ? 0 : $usuario->nro;
        //$usuario->ciudad_id = (!array_key_exists("ciudad_id", $usuario)) ? 0 : $usuario->ciudad_id;

        return $usuario;
    }

    /**
     * @param $direcciones
     * @return mixed
     */
    function checkDirecciones($direcciones)
    {
        foreach ($direcciones as $direccion) {
            $direccion->usuario_id = (!array_key_exists("usuario_id", $direccion)) ? 0 : $direccion->usuario_id;
            $direccion->direccion_id = (!array_key_exists("direccion_id", $direccion)) ? 0 : $direccion->direccion_id;
            $direccion->calle = (!array_key_exists("calle", $direccion)) ? '' : $direccion->calle;
            $direccion->puerta = (!array_key_exists("puerta", $direccion)) ? '' : $direccion->puerta;
            $direccion->piso = (!array_key_exists("piso", $direccion)) ? 0 : $direccion->piso;
            $direccion->nro = (!array_key_exists("nro", $direccion)) ? 0 : $direccion->nro;
            $direccion->ciudad_id = (!array_key_exists("ciudad_id", $direccion)) ? 0 : $direccion->ciudad_id;
        }

        return $direcciones;
    }

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents("php://input");
    $decoded = json_decode($data);
    Usuarios::init(json_decode(json_encode($decoded), true));
} else {
    Usuarios::init($_GET);
}



