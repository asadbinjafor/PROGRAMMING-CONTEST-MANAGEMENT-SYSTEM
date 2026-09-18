<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\UserRepository;
use PCMS\Support\Auth;
use PCMS\Support\Flash;
use PCMS\Support\Validator;

final class ProfileController extends BaseController
{
    private UserRepository $users;
    public function __construct(){ $this->users=new UserRepository(); }

    public function show(): never { $this->page('profile/show',['title'=>'Profile','profile'=>$this->users->byId(Auth::id()),'errors'=>$this->errors()]); }

    public function update(): never
    {
        $name=trim((string)input('user_name'));$institution=trim((string)input('institution'))?:null;
        $v=(new Validator())->required('user_name',$name,'Name')->length('user_name',$name,2,100,'Name');
        if($v->fails())$this->fail('/profile','Check the profile.',$v->errors());
        $this->users->updateProfile(Auth::id(),$name,$institution);
        $fresh=$this->users->byId(Auth::id());if($fresh)Auth::login($fresh);
        Flash::add('success','Profile updated.');redirect('/profile');
    }

    public function password(): never
    {
        $current=(string)input('current_password');$password=(string)input('password');$stored=$this->users->byEmail((string)user()['email']);
        if(!$stored||!password_verify($current,$stored['password_hash'])||strlen($password)<10||$password!==(string)input('password_confirmation'))$this->fail('/profile','Current password or new password is invalid.');
        $this->users->changePassword(Auth::id(),password_hash($password,PASSWORD_DEFAULT));Flash::add('success','Password changed.');redirect('/profile');
    }
}

