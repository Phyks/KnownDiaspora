<?php

    /**
     * Diaspora pages
     */

    namespace IdnoPlugins\KnownDiaspora\Pages {

        /**
         * Default class to serve Diaspora-related account settings
         */
        class Account extends \Idno\Common\Page
        {

            function getContent()
            {
                if(isset($_GET['remove'])) {
                    unset(\Idno\Core\site()->session()->currentUser()->diaspora);
                    \Idno\Core\site()->config()->save();
                    \Idno\Core\site()->session()->addMessage('Your Diaspora credentials were removed.');
                    $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/diaspora/');
                }
                $this->gatekeeper(); // Logged-in users only
                $t = \Idno\Core\site()->template();
                $body = $t->__(array())->draw('account/diaspora');
                $t->__(array('title' => 'Diaspora', 'body' => $body))->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                $pod = $this->getInput('pod');
                $username = $this->getInput('user');
                $password = $this->getInput('pass');
                $user = \Idno\Core\site()->session()->currentUser();
                $user->diaspora = [
                    'diaspora_username' => $username,
                    'diaspora_pod' => $pod
                    ];
                if (empty($user->diaspora['diaspora_password']) || !empty($password) ) {
                    $user->diaspora['diaspora_password'] = $password;
                }
                else {
                    $user->diaspora['diaspora_password'] = $user->diaspora['diaspora_password'];
                }
                $user->save();
                \Idno\Core\site()->session()->addMessage('Your Diaspora credentials were saved.');
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/diaspora/');
            }

        }

    }
