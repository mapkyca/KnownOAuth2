<?php

namespace IdnoPlugins\OAuth2 {

    use Firebase\JWT\JWT;
    use Idno\Core\TokenProvider;

    class Token extends \Idno\Common\Entity
    {

        function __construct($token_type = 'grant', $expires_in = 2419200)
        {

            parent::__construct();

            $this->access_token = hash('sha256', mt_rand() . microtime(true));
            $this->refresh_token = hash('sha256', mt_rand() . microtime(true));
            $this->expires_in = $expires_in; // Default expires is 1 month, like facebook
            $this->token_type = $token_type;

            $this->setTitle($this->access_token); // better stub generation, not that it matters
        }

        /**
         * Check whether a token is valid (i.e. not expired) and that an application with the given key exists.
         */
        function isValid()
        {

            if (!\IdnoPlugins\OAuth2\Application::getOne(['key' => $this->key])) return false;
            return ($this->created + $this->expires_in > time());
        }

        /**
         * Saves changes to this object based on user input
         * @return true|false
         */
        function saveDataFromInput()
        {

            if (empty($this->_id)) {
                $new = true;
            } else {
                $new = false;
            }

            $this->setAccess('PUBLIC');
            return $this->save();
        }

        function jsonSerialize()
        {  
            // Code is only ever serialised as part of something else
            $return = [
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => $this->expires_in,
            'token_Type' => $this->token_type
            ];
            
            // Return OIDC token of own if there's an owner TODO: - needs a public key generated
            if (!empty($this->getOwner())) {
                
                // See if we've asked for an open ID token in scope
                if (strpos($this->scope, 'openid') !== false) {
                    
                    $nonce = new TokenProvider();
                    
                    $oidc = [
                        'iss' => \Idno\Core\Idno::site()->config()->getDisplayURL(), // Issuer site
                        'sub' => $this->getOwnerID(), // Return the SUBJECT id
                        'aud' => $this->key,    // Audience (client ID)
                        'exp' => time() + $this->expires_in, // Expires in
                        'iat' => time(), // Issue time
                        'nonce' => $nonce->generateHexToken(4), // Add a nonce
                    ];
                    
                    
                    // Have we asked for email address?
                    if (strpos($this->scope, 'email') !== false) {
                        $oidc['email'] = $this->getOwner()->email;
                    } 
                    
                    // Add some profile information if asked for
                    if (strpos($this->scope, 'profile') !== false) {
                        
                        $oidc['preferred_username'] = $this->getOwner()->getHandle();
                        $oidc['name'] = $this->getOwner()->getName();
                        $oidc['picture'] = $this->getOwner()->getIcon();
                        $oidc['website'] = $this->getOwner()->getURL();
                    }
                    
                    // Find Private key
                    $privatekey = \Idno\Core\Idno::site()->config()->oauth2Server['privatekey'];
                    if (empty($privatekey)) {
                        throw new OAuth2Exception(\Idno\Core\Idno::site()->language()->_("No private key could be found"));
                    }
                    
                    // Now generate a JWT
                    $jwt = JWT::encode($oidc, $privatekey, 'RS256');
                    if (empty($jwt)) {
                        throw new OAuth2Exception(\Idno\Core\Idno::site()->language()->_("There was a problem generating a OIDC token"));
                    }
                   
                    $return['id_token'] = $jwt;
                }
               
            }
            
            if ($this->state) $return['state'] = $this->state;
            if ($this->scope) $return['scope'] = $this->scope;

            return $return;
        }


    }

}
