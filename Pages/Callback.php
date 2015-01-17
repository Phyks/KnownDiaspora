<?php

    /**
     * Diaspora pages
     */

    namespace IdnoPlugins\Diaspora\Pages {

        /**
         * Default class to serve the Diaspora callback
         */
        class Callback extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($diaspora = \Idno\Core\site()->plugins()->get('Diaspora')) {
                    if ($diasporaAPI = $diaspora->connect()) {
                        /* @var \IdnoPlugins\diaspora\diasporaAPI $diasporaAPI */
                        if ($session = $diasporaAPI->getSessionOnLogin()) {
                            $user = \Idno\Core\site()->session()->currentUser();
                            $access_token = $session->getToken();
                            $diasporaAPI->setAccessToken($access_token);
                            if ($person = $diasporaAPI->api('/me','GET')) {
                                $name = $person['response']->getProperty('name');
                                $id = $person['response']->getProperty('id');
                                $user->diaspora[$id] = ['id' => $id, 'access_token' => $access_token, 'name' => $name];
                                \Idno\Core\site()->syndication()->registerServiceAccount('diaspora', $id, $name);
                                if (\Idno\Core\site()->config()->multipleSyndicationAccounts()) {
                                    if ($companies = $diasporaAPI->api('/me/accounts','GET')) {
                                        if (!empty($companies['response'])) {
                                            foreach($companies['response']->asArray() as $company_container) {
                                                foreach($company_container as $company) {
                                                    $company = (array) $company;
                                                    if ($perms = $company['perms']) {
                                                        if (in_array('CREATE_CONTENT', $perms) && !empty($company['name'])) {
                                                            $id = $company['id'];
                                                            $name = $company['name'];
                                                            $access_token = $company['access_token'];
                                                            $user->diaspora[$id] = ['id' => $id, 'access_token' => $access_token, 'name' => $name, 'page' => true];
                                                            \Idno\Core\site()->syndication()->registerServiceAccount('diaspora', $id, $name);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                $user->diaspora = array('access_token' => $access_token);
                            }
                            $user->save();
                        }
                    }
                }
                if (!empty($_SESSION['onboarding_passthrough'])) {
                    unset($_SESSION['onboarding_passthrough']);
                    $this->forward(\Idno\Core\site()->config()->getURL() . 'begin/connect-forwarder');
                }
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/diaspora/');
            }

        }

    }
