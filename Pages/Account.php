<?php

    /**
     * Diaspora pages
     */

    namespace IdnoPlugins\Diaspora\Pages {

        /**
         * Default class to serve Diaspora-related account settings
         */
        class Account extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($diaspora = \Idno\Core\site()->plugins()->get('Diaspora')) {
                    $login_url = $diaspora->getAuthURL();
                }
                $t = \Idno\Core\site()->template();
                $body = $t->__(array('login_url' => $login_url))->draw('account/diaspora');
                $t->__(array('title' => 'Diaspora', 'body' => $body))->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                if (($this->getInput('remove'))) {
                    $user = \Idno\Core\site()->session()->currentUser();
                    $user->diaspora = array();
                    $user->save();
                    \Idno\Core\site()->session()->addMessage('Your Diaspora settings have been removed from your account.');
                }
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/diaspora/');
            }

        }

    }
