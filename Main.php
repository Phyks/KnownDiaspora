<?php

    namespace IdnoPlugins\KnownDiaspora {

        class Main extends \Idno\Common\Plugin
        {

            public $endpoint = 'me';

            function registerPages()
            {
                // Register settings page
                \Idno\Core\site()->addPageHandler('account/diaspora', '\IdnoPlugins\KnownDiaspora\Pages\Account');

                /** Template extensions */
                // Add menu items to account screens
                \Idno\Core\site()->template()->extendTemplate('account/menu/items', 'account/diaspora/menu');
            }

            function registerEventHooks()
            {

                \Idno\Core\site()->syndication()->registerService('diaspora', function () {
                    return $this->hasDiaspora();
                }, array('note', 'article', 'image', 'media','rsvp', 'bookmark'));

                if ($this->hasDiaspora()) {
                    \Idno\Core\site()->syndication()->registerServiceAccount('diaspora', \Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'], 'Diaspora ('.\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'].')');
                }

                $notes_function = function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($this->hasDiaspora()) {
                        $object      = $eventdata['object'];
                        $diasporaAPI  = new DiasporaAPI(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_pod']);
                        $diasporaAPI->login(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'], \Idno\Core\site()->session()->currentUser()->diaspora['diaspora_password']);
                        if (!empty($diasporaAPI)) {
                            $message = preg_replace('/<[^\>]*>/', '', $object->getDescription()); //strip_tags($object->getDescription());

                            // Obey the IndieWeb reference setting
                            if (!substr_count($message, \Idno\Core\site()->config()->host) && \Idno\Core\site()->config()->indieweb_reference) {
                                $message .= "\n\n(<a href=\"http://" . $object->getShortURL(true, false) . "\">" . $object->getShortURL(true, false) . "</a>)";
                            }

                            if (!empty($message) && substr($message, 0, 1) != '@') {
                                try {
                                    $result = $diasporaAPI->post($message, 'KnownDiaspora');
                                    if (!empty($result['id'])) {
                                        $result['id'] = str_replace('_', '/posts/', $result['id']);
                                        $object->save();
                                    }
                                } catch (\Exception $e) {
                                    error_log('There was a problem posting to Diaspora: ' . $e->getMessage());
                                    \Idno\Core\site()->session()->addMessage('There was a problem posting to Diaspora: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                };

                // Push "notes" to Diaspora
                \Idno\Core\site()->addEventHook('post/note/diaspora', $notes_function);
                \Idno\Core\site()->addEventHook('post/bookmark/diaspora', $notes_function);

                // TODO
                $article_function = function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($this->hasFacebook()) {
                        if (!empty($eventdata['syndication_account'])) {
                            $facebookAPI  = $this->connect($eventdata['syndication_account']);
                        } else {
                            $facebookAPI  = $this->connect();
                        }
                        if (!empty($facebookAPI)) {
                            $result = $facebookAPI->api('/'.$this->endpoint.'/feed', 'POST',
                                array(
                                    'link'    => $object->getURL(),
                                    'message' => $object->getTitle(),
                                    'actions' => json_encode([['name' => 'See Original', 'link' => $object->getURL()]]),
                                ));
                            if (!empty($result['id'])) {
                                $result['id'] = str_replace('_', '/posts/', $result['id']);
                                $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id']);
                                $object->save();
                            }
                        }
                    }
                };

                // Push "articles" and "rsvps" to Facebook
                \Idno\Core\site()->addEventHook('post/rsvp/diaspora', $article_function);
                \Idno\Core\site()->addEventHook('post/article/diaspora', $article_function);

                // TODO
                // Push "media" to Facebook
                \Idno\Core\site()->addEventHook('post/media/diaspora', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($this->hasFacebook()) {
                        if (!empty($eventdata['syndication_account'])) {
                            $facebookAPI  = $this->connect($eventdata['syndication_account']);
                        } else {
                            $facebookAPI  = $this->connect();
                        }
                        if (!empty($facebookAPI)) {
                            $result = $facebookAPI->api('/'.$this->endpoint.'/feed', 'POST',
                                array(
                                    'link'    => $object->getURL(),
                                    'message' => $object->getTitle(),
                                    'actions' => json_encode([['name' => 'See Original', 'link' => $object->getURL()]]),
                                ));
                            if (!empty($result['id'])) {
                                $result['id'] = str_replace('_', '/posts/', $result['id']);
                                $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id']);
                                $object->save();
                            }
                        }
                    }
                });

                // TODO
                // Push "images" to Facebook
                \Idno\Core\site()->addEventHook('post/image/diaspora', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($attachments = $object->getAttachments()) {
                        foreach ($attachments as $attachment) {
                            if ($this->hasFacebook()) {
                                if (!empty($eventdata['syndication_account'])) {
                                    $facebookAPI  = $this->connect($eventdata['syndication_account']);
                                } else {
                                    $facebookAPI  = $this->connect();
                                }
                                if (!empty($facebookAPI)) {
                                    $message = strip_tags($object->getTitle()) . "\n\n" . strip_tags($object->getDescription());
                                    $message .= "\n\nOriginal: " . $object->getURL();
                                    try {
                                        //$facebookAPI->setFileUploadSupport(true);
                                        $response = $facebookAPI->api(
                                            '/'.$this->endpoint.'/photos/',
                                            'post',
                                            array(
                                                'message' => $message,
                                                'url'     => $attachment['url'],
                                                'actions' => json_encode([['name' => 'See Original', 'link' => $object->getURL()]]),
                                            )
                                        );
                                        if (!empty($response['id'])) {
                                            $result['id'] = str_replace('_', '/photos/', $response['id']);
                                            $object->setPosseLink('facebook', 'https://facebook.com/' . $response['id']);
                                            $object->save();
                                        }
                                    } catch (\FacebookApiException $e) {
                                        error_log('Could not post image to Facebook: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                });
            }

            /**
             * Check if Diaspora plugin is enabled.
             */
            function hasDiaspora() {
                return !empty(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username']) && !empty(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_pod']) && !empty(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_password']);
            }
        }

    }
