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
                    \Idno\Core\site()->config->config['diaspora'] = [
                        'diaspora_username' => '',
                        'diaspora_password' => '',
                        'diaspora_pod' => ''
                        ];
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
                \Idno\Core\site()->config->config['diaspora'] = [
                    'diaspora_username' => $username,
                    'diaspora_pod' => $pod
                    ];
                if (empty(\Idno\Core\site()->config->config['diaspora']['diaspora_password']) || !empty($password) ) {
                    \Idno\Core\site()->config->config['diaspora']['diaspora_password'] = $password;
                }
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Your Diaspora credentials were saved.');
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/diaspora/');
            }

        }

    }
