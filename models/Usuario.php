<?php

namespace Model;

class Usuario extends ActiveRecord {
    protected static $tabla = 'usuarios';
    protected static $columnasDB = ['id', 'nombre', 'email', 'password', 'token', 'confirmado'];

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $password2;
    public $password_actual;
    public $password_nuevo;
    public $token;
    public $confirmado;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->password2 = $args['password2'] ?? null;
        $this->password_actual = $args['password_actual'] ?? '';
        $this->password_nuevo = $args['password_nuevo'] ?? '';
        $this->token = $args['token'] ?? '';
        $this->confirmado = $args['confirmado'] ?? 0;
    }

    //Validcion para cuentas nuevas
    public function validarNuevaCuenta()
    {
        if (!$this->nombre) {
            self::$alertas['error'][] = 'El Nombre del Usuario es Obligatorio';
        }
        if (!$this->email) {
            self::$alertas['error'][] = 'El Email del Usuario es Obligatorio';
        }
        if (!$this->password) {
            self::$alertas['error'][] = 'El Password no Puede ir Vacio';
        }
        if (strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El Password debe contener almenos 6 caracteres';
        }
        if ($this->password !== $this->password2) {
            self::$alertas['error'][] = 'Los Passwords son diferentes';
        }



        return self::$alertas;
    }
    //VAlidar login de usuarios
    public function validarLogin()
    {
       
        if (!$this->email) {
            self::$alertas['error'][] = 'El Email del Usuario es Obligatorio';
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'Email no valido';
        }
        if (!$this->password) {
            self::$alertas['error'][] = 'El Password no Puede ir Vacio';
        }
        return self::$alertas;
    }


    //Valida en Email
    public function validarEmail()
    {
        if (!$this->email) {
            self::$alertas['error'][] = 'El Email es obligatorio';
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'Email no valido';
        }
        return self::$alertas;
    }

    //Valida el Password
    public function validarPassword()
    {
        if (!$this->password) {
            self::$alertas['error'][] = 'El Password no Puede ir Vacio';
        }
        if (strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El Password debe contener almenos 6 caracteres';
        }
        return self::$alertas;
    }

    public function validarPerfil()
    {
        if (!$this->nombre) {
            self::$alertas['error'][] = 'El Nombre es Obligatorio';
        }
        if (!$this->email) {
            self::$alertas['error'][] = 'El Email es Obligatorio';
        }
        return self::$alertas;
    }
    public function nuevoPassword() :array{
        if (!$this->password_actual) {
            self::$alertas['error'][] = 'El Password Actual no Puede ir Vacio';
        }
        if (!$this->password_nuevo) {
            self::$alertas['error'][] = 'El Password Nuevo no Puede ir Vacio';
        }
        
        if (strlen($this->password_nuevo)<6) {
            self::$alertas['error'][] = 'El Password nuevo Debe contener almenos 6 caracteres';
        }
        return self::$alertas;
    }

    //Comprobar el pasword
    public function comprobar_password() :bool
    {
        return password_verify($this->password_actual, $this->password);
    }
    //Hashea Password
    public function hashPassword() :void
    {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    //Generar un token
    public function crearToken() :void
    {
        $this->token = uniqid();
    }
}