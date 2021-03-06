<?php

class UserController extends BaseController {
            
  protected $user;
  
  /**
  * Inject the models.
  * @param User $user  
  */
  public function __construct(User $user)
  {
    parent::__construct();
    
    $this->user = $user;
  }
                  
  /**
   * Displays the form for account creation
   */
  public function create()
  {
    return View::make('user.signup');
  }

  /**
   * Stores new account
   */
  public function store()
  {
    $user = new User;

    $user->username               = Input::get('username');
    $user->email                  = Input::get('email');
    $user->password               = Input::get('password');
    $user->password_confirmation  = Input::get('password_confirmation');
    $user->timezone               = Input::get('timezone');
    $user->language               = Input::get('language');

    $user->save();

    if ($user->id)
    {
      $notice = trans('confide::confide.alerts.account_created');
      
      if ( !Config::get('confide::signup_confirm') )
      {
        $this->user->activate_automatically($user->id);
        
        $notice .= ' ' . trans('common.can_login_now'); 
      }
      else
      {
        $notice .= ' ' . trans('common.confirmation_sent'); 
      }
      
      return Redirect::action('UserController@login')
                      ->with('notice', $notice);
    }
    else
    {                     
      $error = $user->errors()->all(':message');

      return Redirect::action('UserController@create')
                      ->withInput(Input::except('password'))
                      ->withErrors($error);
    }
  }

  /**
   * Displays the login form
   */
  public function login()
  {
    if ( Confide::user() )
    {
      return Redirect::to('/');
    }
    else
    {
      return View::make('user.login');
    }
  }

  /**
   * Attempt to do login
   */
  public function do_login()
  {
    $input = array(
      'email'    => Input::get('email'), // may be also username
      'username' => Input::get('email'),
      'password' => Input::get('password'),
      'remember' => Input::get('remember'),
    );

    if ( Confide::logAttempt($input, Config::get('confide::signup_confirm')) ) 
    {
      return Redirect::intended('/');
    }
    else
    {
      $user = new User;

      // Check if there was too many login attempts
      if ( Confide::isThrottled($input) )
      {
        $error = trans('confide::confide.alerts.too_many_attempts');
      }
      else if ( $user->checkUserExists($input) && !$user->isConfirmed($input) )
      {
        $error = trans('confide::confide.alerts.not_confirmed');
      }
      else
      {
        $error = trans('confide::confide.alerts.wrong_credentials');
      }

      return Redirect::action('UserController@login')
                      ->withInput(Input::except('password')) 
                      ->withErrors($error);
    }
  }

  /**
   * Attempt to confirm account with code
   *
   * @param  string  $code
   */
  public function confirm($code)
  {
    if ( Confide::confirm($code) )
    {
      $notice = trans('confide::confide.alerts.confirmation');
      return Redirect::action('UserController@login')
                      ->with('notice', $notice);
    }
    else
    {
      $error = trans('confide::confide.alerts.wrong_confirmation');
      return Redirect::action('UserController@login')
                      ->withErrors($error);
    }
  }

  /**
   * Displays the forgot password form
   */
  public function forgot_password()
  {
    return View::make('user.forgot_password');
  }

  /**
   * Attempt to send change password link to the given email
   */
  public function do_forgot_password()
  {
    if ( Confide::forgotPassword( Input::get('email') ) )
    {
      $notice = trans('confide::confide.alerts.password_forgot');
      return Redirect::action('UserController@login')
                      ->with('notice', $notice);
    }
    else
    {
      $error = trans('confide::confide.alerts.wrong_password_forgot');
      return Redirect::action('UserController@forgot_password')
                      ->withInput()
                      ->withErrors($error);
    }
  }

  /**
   * Shows the change password form with the given token
   */
  public function reset_password($token)
  {
    return View::make('user.reset_password')
                ->with('token', $token);
  }

  /**
   * Attempt change password of the user
   */
  public function do_reset_password()
  {
    $input = array(
      'token'                 => Input::get('token'),
      'password'              => Input::get('password'),
      'password_confirmation' => Input::get('password_confirmation'),
    );

    if( Confide::resetPassword($input) )
    {
      $notice = trans('confide::confide.alerts.password_reset');
      return Redirect::action('UserController@login')
                      ->with('notice', $notice);
    }
    else
    {
      $error = trans('confide::confide.alerts.wrong_password_reset');
      return Redirect::action('UserController@reset_password', array('token' => $input['token']))
                      ->withInput()
                      ->withErrors($error);
    }
  }

  /**
   * Log the user out of the application.
   */
  public function logout()
  {
    Confide::logout();
    
    return Redirect::to('/');
  }
  
  /**
   * Shows the settings form
   */
  public function settings()
  {
    return View::make('user.settings')
                ->with('user', Confide::User());
  }

  /**
   * Attempt change settings of the user
   */
  public function do_settings()
  {           
		$rules = array(
      'timezone' => 'required',
      'language' => 'required',
		);
    
    if ( Confide::User()->email != Input::get('email') )
    {
      $rules['email'] = 'required|email|unique:users';
    }
    
		$validator = Validator::make(Input::all(), $rules);
    
    if ($validator->fails())
    {
			return Redirect::to('user/settings')
                      ->withInput(Input::except('email'))
                      ->withErrors($validator);
		}
    else
    {    
      $user = User::find(Confide::User()->id);
      
      $user->email    = Input::get('email');
      $user->timezone = Input::get('timezone');
      $user->language = Input::get('language');

	    $user->updateUniques();
      
      return Redirect::to('user/settings')
                      ->with('notice', trans('common.settings_changed')); 
		}
  }
  
  /**
   * Attempt change password of the user
   */
  public function change_password()
  {           
		$rules = array(
      'password'              => 'required|min:4|confirmed',
      'password_confirmation' => 'min:4',
		);
    
		$validator = Validator::make(Input::all(), $rules);
    
    if ($validator->fails())
    {
			return Redirect::action('UserController@settings')
                      ->withErrors($validator);
		}
    else
    {    
      $user = User::find(Confide::User()->id);
      
      $current_password = Input::get('current_password');
      
      if ( Hash::check($current_password, $user->password) )
      {
        $password = Input::get('password');
        
        if ( $current_password == $password )
        {
          return Redirect::action('UserController@settings')
                          ->with('notice', trans('common.password_not_changed'));
        }
        else
        {
          $hashed_password = Hash::make($password);
          
          $this->user->change_password(Confide::User()->id, $hashed_password);
          
          return Redirect::action('UserController@settings')
                          ->with('notice', trans('common.password_changed'));
        }
      }
      else
      {
        return Redirect::action('UserController@settings')
                        ->withErrors(trans('common.wrong_password'));
      }
		}
  }
  
  /**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy()
	{
    $user = User::find(Confide::User()->id);
    
    $password = Input::get('password');
    
    if ( Hash::check($password, $user->password) )
    {
      $this->user->delete_account($user->id);
      
      return Redirect::action('UserController@login')
                      ->with('notice', trans('common.account_deleted'));
    }
    else
    {
      return Redirect::action('UserController@settings')
                      ->withErrors(trans('common.wrong_password'));
    }

	}

}
