<?php

    /**
     * Diaspora pages
     */

    namespace IdnoPlugins\Diaspora\Pages {

        /**
         * Default class to serve Diaspora settings in administration
         */
        class Admin extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t = \Idno\Core\site()->template();
                $body = $t->draw('admin/diaspora');
                $t->__(array('title' => 'Diaspora', 'body' => $body))->drawPage();
            }

            function postContent() {
                $this->adminGatekeeper(); // Admins only
                $appId = $this->getInput('appId');
                $secret = $this->getInput('secret');
                \Idno\Core\site()->config->config['diaspora'] = array(
                    'appId' => $appId,
                    'secret' => $secret
                );
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Your Diaspora application details were saved.');
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/diaspora/');
            }

        }

    }
