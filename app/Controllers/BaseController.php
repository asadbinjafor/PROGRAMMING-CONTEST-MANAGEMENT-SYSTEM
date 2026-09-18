<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Support\Flash;
use PCMS\Support\View;

abstract class BaseController
{
    protected function page(string $view,array $data=[]): never
    {
        View::render($view,$data+['title'=>'PCMS']);
    }

    protected function fail(string $path,string $message,array $errors=[]): never
    {
        $_SESSION['_errors']=$errors;
        $_SESSION['_old']=$_POST;
        Flash::add('error',$message);
        redirect($path);
    }

    protected function errors(): array
    {
        $errors=$_SESSION['_errors']??[];unset($_SESSION['_errors']);return $errors;
    }

    protected function old(): array
    {
        $old=$_SESSION['_old']??[];unset($_SESSION['_old']);return $old;
    }
}

