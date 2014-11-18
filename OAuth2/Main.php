<?php

namespace IdnoPlugins\OAuth2 {

    class Main extends \Idno\Common\Plugin {

	function registerPages() {
	    \Idno\Core\site()->addPageHandler('/oauth2/authorise/?', '\IdnoPlugins\OAuth2\Pages\Authorisation');
	    \Idno\Core\site()->addPageHandler('/oauth2/access_token/?', '\IdnoPlugins\OAuth2\Pages\Token');
	    \Idno\Core\site()->addPageHandler('/oauth2/connect/?', '\IdnoPlugins\OAuth2\Pages\Connect');

	    // Adding OAuth2 app page
	    \Idno\Core\site()->addPageHandler('/account/oauth2/?', '\IdnoPlugins\OAuth2\Pages\Account\Applications');
	    \Idno\Core\site()->template()->extendTemplate('account/menu/items', 'account/oauth2/menu');
	}

	function registerEventHooks() {
	    
	    // Authenticate!
	    \Idno\Core\site()->addEventHook('user/auth', function(\Idno\Core\Event $event) { \IdnoPlugins\OAuth2\Main::authenticate(); }, 0);
	    \Idno\Core\site()->addEventHook('user/auth/api', function(\Idno\Core\Event $event) { \IdnoPlugins\OAuth2\Main::authenticate();  }, 0);
	}

	public static function authenticate() {

	    // Have we been provided with an access token
	    if ($access_token = \Idno\Core\site()->currentPage()->getInput('access_token')) {

		// Get token
		if ($token = Token::getOne(['access_token' => $access_token])) {

		    // Check expiry
		    if ($token->isValid()) {

			// Token still valid, get the owner
			$owner = $token->getOwner();
				
			if ($owner) {
			    
			    \Idno\Core\site()->session()->refreshSessionUser($owner); // Log user on, but avoid triggering hook and going into an infinite loop!
			    
			} else {
			    \Idno\Core\site()->triggerEvent('login/failure', array('user' => $owner));
			    
			    \Idno\Core\site()->logging()->log("Token user could not be retrieved.", LOGLEVEL_ERROR);
			}
		    } else {
			\Idno\Core\site()->logging()->log("Access token $access_token does not match any stored token.", LOGLEVEL_ERROR);
		    }
		} else {
		    \Idno\Core\site()->logging()->log("Access token $access_token does not match any stored token.", LOGLEVEL_ERROR);
		}
	    }
	}

    }

}
