<?php

class Usuarios_UsuariosController extends Zend_Controller_Action
{

    protected $_flashMessenger;
    protected $_tablaUsuarios;
    
    public function init()
    {
        //$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        $this->_tablaUsuarios = new Usuarios_Model_DbTable_Users();
    }

    // Formulario de registro de usuario
    public function registroAction()
    {

        $this->view->headTitle("Rexistro");
        $formularioRegistro = new Usuarios_Form_Registro();
        $mensaje = "";

        //Si se reciben datos por post
        if($this->getRequest()->isPost()){
            //Si los datos recibidos son válidos
            if($formularioRegistro->isValid($_POST)){
                //Datos recibidos del formulario
                $data = $formularioRegistro->getValues();
                
                if($this->_tablaUsuarios->existeEmail($data['email'])!=true){
                    
                    //Se inserta el usuario en la base de datos
                    $this->_tablaUsuarios->almacenarUsuario($data['email'], $data['name'], $data['password']);
                    $token = $this->_tablaUsuarios->getValidationTokenPorEmail($data['email']);

                    //Datos que van a ser enviados
                    $params=array(
                        'email' => $data['email'],
                        'name' => $data['name'],
                        'validation_token' => $token,
                    );
                    
                    $this->_helper->viewRenderer->setNoRender(true);
                    $this->_helper->redirector('enviaremail', 'email', 'email', $params);

                }else{
                    $mensaje = "La dirección de correo introducida ya existe.";
                }
            }
        }
        
        $this->view->form = $formularioRegistro;
        $this->view->mensaje = $mensaje;
    }

    // Iniciar sesión
    public function loginAction()
    {

        $auth = Zend_Auth::getInstance();
        
        if ($this->getRequest()->isPost()) {
                
            $email = $this->getRequest()->getPost('email');
            $pwd = $this->getRequest()->getPost('password');

            $authAdapter = new Usuarios_Model_AuthAdapter($email, $pwd);

            $result = $auth->authenticate($authAdapter);

            if(!$result->isValid()) {

                switch ($result->getCode()) {
                    case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                        $msg="Debe activar a conta";
                        break;
                    case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                        $msg="Login incorrecto";
                        break;
                        /*$this->_flashMessenger->addMessage('Login failure');
                        $this->view->messages = $this->_flashMessenger->getMessages();

                        $array=$this->_flashMessenger->getMessages();

                        foreach($array as $mi){
                            echo "fail: ".$mi;
                        }
                        echo "m: ".$array[0];*/
                }
                $this->view->msg = $msg;
                
            }else{

                $auth = Zend_Auth::getInstance();
                $userId = $this->_tablaUsuarios->getUserIdPorEmail($email);
                $nombre = $this->_tablaUsuarios->getNamePorEmail($email);
                $auth->getStorage()->write(array('email'=>$email, 'user_id' => $userId, 'name' => $nombre));
                //Successfully logged in

                $this->_redirect('/buscador/buscador/');
                
            }
        }
    }

    // Cerrar sesión
    public function logoutAction()
    {

        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_redirect('/buscador/buscador/');
        
    }

    // Formulario que se encarga de llamar a la función que envía el nuevo pass por email
    public function recuperarpasswordAction()
    {
        
        $this->view->headTitle("Recuperar contrasinal");

        $formularioNuevoPass = new Usuarios_Form_Nuevopassword();

        //Si se reciben datos por post
        if($this->getRequest()->isPost()){
            //Si los datos recibidos son válidos
            if($formularioNuevoPass->isValid($_POST)){

                $data = $formularioNuevoPass->getValues();

                $params=array(
                    'email' => $data['email'],
                );
                
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->redirector('enviarpassword', 'email', 'email', $params);
                 
            }
        }
        $this->view->form = $formularioNuevoPass;
    }

    
    public function modificardatosAction()
    {

        $this->view->headTitle("Modificar datos");

        if(Zend_Auth::getInstance()->hasIdentity()) {
            $auth=Zend_Auth::getInstance();
            $userData = $auth->getStorage()->read();

            $user = $this->_tablaUsuarios->getUserPorId($userData['user_id']);
            $formularioModificarDatos = new Usuarios_Form_Modificardatos($user);

            //Si se reciben datos por post
            if($this->getRequest()->isPost()){
                //Si los datos recibidos son válidos
                if($formularioModificarDatos->isValid($_POST)){

                    // @todo Insertar los datos modificados en BBD
                    // @todo Incluir opción de cambiar password
                    
                    $data = $formularioModificarDatos->getValues();

                    $this->_tablaUsuarios->actualizarDatosUsuario($data['email'], $data['name']);

                }
            }
            $this->view->form = $formularioModificarDatos;

        }

    }


}


