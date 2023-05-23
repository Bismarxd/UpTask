<?php

namespace Controllers;

use Model\Proyecto;
use Model\Usuario;
use MVC\Router;


class DashboardController
{
    public static function index(Router $router)
    {
        session_start();
        isAuth();

        $id = $_SESSION['id'];

        $proyectos = Proyecto::belongsTo('propietarioId', $id);

       

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
            
        ]);
    }

    public static function crear_proyecto(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);
            
            //Validacion
            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {

                //Generar una url unica
                $hash = md5(uniqid());
                $proyecto->url = $hash;

                //Almacenar el crador del proyecto
                $proyecto->propietarioId = $_SESSION['id'];

                //Guardar el proyecto
                $proyecto->guardar();

                //Redireccionar
                header('Location: /proyecto?id=' . $proyecto->url);
            }
        }

        $router->render('dashboard/crear-proyecto', [
            'titulo' => 'Crear Proyecto',
            'alertas' => $alertas
            
        ]);
    }

    public static function proyecto(Router $router)
    {
        session_start();
        isAuth();

        $token = $_GET['id'];

        if (!$token) header('Location: /dashboard');

        //Revisar que la perosona del proyecto es quien la creo
        $proyecto = Proyecto::where('url', $token);
        if ($proyecto->propietarioId !== $_SESSION['id']) 
        {
            header('Location: /dashboard');
        }

        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
            
        ]);
    }

    public static function perfil(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];

        $usuario = Usuario::find($_SESSION['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            $usuario->sincronizar($_POST);

            $alertas = $usuario->validarPerfil();

            if (empty($alertas)) {
                //Verificar que no eista el usuario
                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario && $existeUsuario->id !== $usuario->id) {
                    //Mensaje de error
                    Usuario::setAlerta('error', 'Email no Valido, ya pertenece a otra cuenta');
                    $alertas = $usuario->getAlertas();
                }else{
                     //Guardar el usuario
                $usuario->guardar();

                
                Usuario::setAlerta('exito', 'Guardado Correctamente');
                $alertas = $usuario->getAlertas();

                //Asignar el nombre nuevo a la barra
                $_SESSION['nombre'] = $usuario->nombre;
                }

               
            }
           
        }
        
        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
            
        ]);
    }

    public static function cambiar_password(Router $router)
    {
        session_start();
        isAuth();

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            $usuario = Usuario::find($_SESSION['id']);

            //Sincronizar con la base de datos
            $usuario->sincronizar($_POST);

            $alertas=$usuario->nuevoPassword();

          if (empty($alertas)) {
            $resultado = $usuario->comprobar_password();

            if ($resultado) {

                $usuario->password = $usuario->password_nuevo;
                //Eliminar propiedades no necesarias
                unset($usuario->password_actual);
                unset($usuario->password_nuevo);

                //Hashear el nuevo password
                $usuario->hashPassword();

                $resultado = $usuario->guardar();
                if ($resultado) {
                    Usuario::setAlerta('exito', 'Password Guardado correctamente');
                    $alertas = $usuario->getAlertas();
                }

        


            }else{
                Usuario::setAlerta('error', 'Password Incorrecto');
                $alertas = $usuario->getAlertas();
            }
          }
        }

        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
        ]);
    }
}