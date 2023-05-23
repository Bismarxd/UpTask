<?php
namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController 
{
    public static function login(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            
            $alertas = $usuario->validarLogin();

            if (empty($alertas)) {
                //Verificar que el usuario exista
                $usuario = Usuario::where('email', $usuario->email);

                if (!$usuario || !$usuario->confirmado) 
                {
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                } else
                {
                    //El usuario existe
                    if (password_verify($_POST['password'], $usuario->password)) {
                        //Iniciar sesion
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //Redireccionar
                        header('Location: /dashboard');
                        
                    }else{
                        Usuario::setAlerta('error', 'Paaword incorrecto');
                    }
                }
            }
        }
        $alertas = Usuario::getAlertas();
        //render a la vista
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }

    public static function logout()
    {
        session_start();
        $_SESSION = [];
        header('Location: /');

    }

    public static function crear(Router $router)
    {
        $alertas =[];
        $usuario = new Usuario;

        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

          if (empty($alertas)) {
            $existeUsuario = Usuario::where('email', $usuario->email);
            
            if ($existeUsuario) {
                Usuario::setAlerta('error', 'El Usuario ya esta registrado');
                $alertas = Usuario::getAlertas();
            }
            else{
                //Hashear el Password
                $usuario->hashPassword();

                //Eliminar Paswword 2
                unset($usuario->password2);

                //Generar el token
                $usuario->crearToken();
               
                
                //Crear un nuevo usuario
                $resultado = $usuario->guardar();
                

                //Enviar Email
                $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                $email->enviarConfirmacion();

                if ($resultado) {
                    header('Location: /mensaje');
                }
            }
          }
        }

         //render a la vista
         $router->render('auth/crear', [
            'titulo' => 'Crea tu cuenta en uptask',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router)
    {
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if (empty($alertas)) {
               //Buscar el usuario
               $usuario = Usuario::where('email', $usuario->email);

               if ($usuario && $usuario->confirmado ) 
               {
                //Generar un nuevo token
                $usuario->crearToken();
                unset($usuario->password2);

                //Actualizar el usuario
                $usuario->guardar();

                //Enviar el email
                $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                $email->enviarInstrucciones();

                //Imprimir la alerta
                Usuario::setAlerta('exito', 'Hemos enviado las instrucciones  tu email');
                
               
               }else
               {
                Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                
               }
              
            }
            
        }

        $alertas = Usuario::getAlertas();

         //Muestra la vista
         $router->render('auth/olvide', [
            'titulo' => 'Olvide mi passsword',
            'alertas' => $alertas
        ]);
    }

    public static function reestablecer(Router $router)
    {

        $token = s($_GET['token']);
        $mostrar = true;
        $titulo = true;
        
        if (!$token) header('Location: /');

        //Identificar el usuario con el token
        $usuario = Usuario::where('token', $token);
        
        if (empty($usuario)) {
            Usuario::setAlerta('error', 'Token no Valido');
            $mostrar = false;
            $titulo = false;
        }

        $alertas = Usuario::getAlertas();

        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //añadir nuevo password
            $usuario->sincronizar($_POST);

            //VAlidar el password
            $alertas = $usuario->validarPassword();

            if (empty($alertas)) {
                //Hashear Password
                $usuario->hashPassword();

                //Eliminar Token
                $usuario->token= null;

                //Guardar Usuario
                $resultado = $usuario->guardar();

                //Redireccionar
                if ($resultado) {
                    header('Location: /');
                }
            }
            
        }

         //Muestra la vista
         $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer passsword',
            'titulo' => $titulo,
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function mensaje(Router $router)
    {

         //Muestra la vista
         $router->render('auth/mensaje', [
            'titulo' => 'Cuenta creada exitosamente'
        ]);
    }

    public static function confirmar(Router $router)
    {
        $token = s($_GET['token']);
        if (!$token) {
            header('Location: /');
        }

        //Encontrar al usuario con el token
        $usuario = Usuario::where('token', $token);
        if (empty($usuario)) {
            //No se encontro un usuario con ese token
            Usuario::setAlerta('error', 'Token no Valido');
        }else
        {
            //Confirmar la cuenta
            $usuario->confirmado = 1;
            $usuario->token=null;
            unset($usuario->password2);
            
            //guardar en la bd
            $usuario->guardar();

            Usuario::setAlerta('exito', 'Cuenta comprobada correctamente');
        }

        $alertas = Usuario::getAlertas();

         //Muestra la vista
         $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu cuenta Uptask',
            'alertas' => $alertas
        ]);
    }
}