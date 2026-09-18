<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\UserRepository;
use PCMS\Support\Auth;
use PCMS\Support\Env;
use PCMS\Support\Flash;
use PCMS\Support\Validator;

final class AuthController extends BaseController
{
    private UserRepository $users;
    public function __construct(){ $this->users=new UserRepository(); }

    public function loginForm(): never { $this->page('auth/login',['title'=>'Sign in','errors'=>$this->errors(),'old'=>$this->old()]); }

    public function login(): never
    {
        $email=trim((string)input('email'));$password=(string)input('password');
        $attemptKey=hash('sha256',strtolower($email).'|'.($_SERVER['REMOTE_ADDR']??'local'));
        $attempt=$_SESSION['_login_attempts'][$attemptKey]??['count'=>0,'since'=>time()];
        if(time()-(int)$attempt['since']>900)$attempt=['count'=>0,'since'=>time()];
        if((int)$attempt['count']>=5)$this->fail('/login','Too many sign-in attempts. Try again after 15 minutes.');
        $v=(new Validator())->required('email',$email,'Email')->email('email',$email)->required('password',$password,'Password');
        if($v->fails())$this->fail('/login','Check the highlighted fields.',$v->errors());
        $user=$this->users->byEmail($email);
        if(!$user||$user['account_status']!=='ACTIVE'||!password_verify($password,$user['password_hash'])){
            $attempt['count']++;$_SESSION['_login_attempts'][$attemptKey]=$attempt;usleep(250000);$this->fail('/login','The email or password is incorrect.');
        }
        unset($_SESSION['_login_attempts'][$attemptKey]);
        unset($user['password_hash']);
        Auth::login($user);
        if(input('remember')==='1'){
            $selector=bin2hex(random_bytes(9));$validator=bin2hex(random_bytes(32));
            $expires=time()+((int)Env::get('REMEMBER_DAYS','30')*86400);
            $this->users->setRememberToken((int)$user['user_id'],$selector,password_hash($validator,PASSWORD_DEFAULT),date('Y-m-d H:i:s P',$expires));
            setcookie('pcms_remember',$selector.':'.$validator,['expires'=>$expires,'path'=>'/','secure'=>Env::bool('SESSION_SECURE'),'httponly'=>true,'samesite'=>'Lax']);
        }
        Flash::add('success','Welcome back, '.$user['user_name'].'!');
        redirect('/dashboard');
    }

    public function registerForm(): never { $this->page('auth/register',['title'=>'Create account','errors'=>$this->errors(),'old'=>$this->old()]); }

    public function register(): never
    {
        $d=['user_name'=>trim((string)input('user_name')),'email'=>trim((string)input('email')),'institution'=>trim((string)input('institution')),'password'=>(string)input('password')];
        $v=(new Validator())->required('user_name',$d['user_name'],'Name')->length('user_name',$d['user_name'],2,100,'Name')->required('email',$d['email'],'Email')->email('email',$d['email'])->required('password',$d['password'],'Password')->length('password',$d['password'],10,200,'Password');
        if($d['password']!==(string)input('password_confirmation'))$errors=$v->errors()+['password_confirmation'=>'Passwords do not match.'];else $errors=$v->errors();
        if($errors)$this->fail('/register','Check the highlighted fields.',$errors);
        if($this->users->byEmail($d['email']))$this->fail('/register','An account cannot be created with those details.',['email'=>'Email is unavailable.']);
        $d['password_hash']=password_hash($d['password'],PASSWORD_DEFAULT);
        $this->users->create($d);
        Flash::add('success','Account created. You can now sign in.');redirect('/login');
    }

    public function forgotForm(): never { $this->page('auth/forgot',['title'=>'Forgot password','errors'=>$this->errors()]); }

    public function forgot(): never
    {
        $email=trim((string)input('email'));$user=$this->users->byEmail($email);
        if($user){
            $selector=bin2hex(random_bytes(9));$validator=bin2hex(random_bytes(32));$expires=time()+3600;
            $this->users->createResetToken((int)$user['user_id'],$selector,password_hash($validator,PASSWORD_DEFAULT),date('Y-m-d H:i:s P',$expires));
            if(Env::bool('APP_DEBUG')) Flash::add('info','Development reset link: '.url('/reset-password?token='.urlencode($selector.':'.$validator)));
        }
        Flash::add('success','If the address is registered, a reset link has been prepared.');redirect('/forgot-password');
    }

    public function resetForm(): never { $this->page('auth/reset',['title'=>'Reset password','token'=>(string)($_GET['token']??''),'errors'=>$this->errors()]); }

    public function reset(): never
    {
        $token=(string)input('token');$parts=explode(':',$token,2);$password=(string)input('password');
        if(count($parts)!==2||strlen($password)<10||$password!==(string)input('password_confirmation'))$this->fail('/reset-password?token='.urlencode($token),'The reset link or password is invalid.');
        [$selector,$validator]=$parts;$record=$this->users->resetToken($selector);
        if(!$record||!password_verify($validator,$record['validator_hash']))$this->fail('/forgot-password','The reset link is invalid or expired.');
        $this->users->consumeReset((int)$record['token_id'],(int)$record['user_id'],password_hash($password,PASSWORD_DEFAULT));
        Flash::add('success','Password changed. Sign in with the new password.');redirect('/login');
    }

    public function logout(): never
    {
        if(Auth::id())$this->users->clearRememberToken(Auth::id());
        setcookie('pcms_remember','',['expires'=>time()-3600,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
        Auth::logout();redirect('/login');
    }
}
