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

            /*function registerEventHooks()
            { // TODO

                \Idno\Core\site()->syndication()->registerService('diaspora', function () {
                    return $this->hasDiaspora();
                }, array('note', 'article', 'image', 'media','rsvp', 'bookmark'));

                if ($this->hasDiaspora()) {
                    if (is_array(\Idno\Core\site()->session()->currentUser()->diaspora) && !array_key_exists('access_token', \Idno\Core\site()->session()->currentUser()->facebook)) {
                        foreach(\Idno\Core\site()->session()->currentUser()->facebook as $username => $details) {
                            \Idno\Core\site()->syndication()->registerServiceAccount('facebook', $username, $details['name']);
                        }
                    }
                }

                $notes_function = function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($this->hasFacebook()) {
                        $object      = $eventdata['object'];
                        if (!empty($eventdata['syndication_account'])) {
                            $facebookAPI  = $this->connect($eventdata['syndication_account']);
                        } else {
                            $facebookAPI  = $this->connect();
                        }
                        if (!empty($facebookAPI)) {
                            $message = preg_replace('/<[^\>]*>/', '', $object->getDescription()); //strip_tags($object->getDescription());

                            // Obey the IndieWeb reference setting
                            if (!substr_count($message, \Idno\Core\site()->config()->host) && \Idno\Core\site()->config()->indieweb_reference) {
                                $message .= "\n\n(" . $object->getShortURL(true, false) . ")";
                            }

                            if (!empty($message) && substr($message, 0, 1) != '@') {
                                $params = array(
                                    'message' => $message,
                                    'actions' => json_encode([['name' => 'See Original', 'link' => $object->getURL()]]),
                                );
                                if (preg_match('/(?<!=)(?<!["\'])((ht|f)tps?:\/\/[^\s\r\n\t<>"\'\(\)]+)/i', $message, $matches)) {
                                    $params['link'] = $matches[0]; // Set the first discovered link as the match
                                }
                                try {
                                    $result = $facebookAPI->api('/'.$this->endpoint.'/feed', 'POST', $params);
                                    if (!empty($result['id'])) {
                                        $result['id'] = str_replace('_', '/posts/', $result['id']);
                                        $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id']);
                                        $object->save();
                                    }
                                } catch (\Exception $e) {
                                    error_log('There was a problem posting to Facebook: ' . $e->getMessage());
                                    \Idno\Core\site()->session()->addMessage('There was a problem posting to Facebook: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                };

                // Push "notes" to Facebook
                \Idno\Core\site()->addEventHook('post/note/facebook', $notes_function);
                \Idno\Core\site()->addEventHook('post/bookmark/facebook', $notes_function);

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
                \Idno\Core\site()->addEventHook('post/rsvp/facebook', $article_function);
                \Idno\Core\site()->addEventHook('post/article/facebook', $article_function);

                // Push "media" to Facebook
                \Idno\Core\site()->addEventHook('post/media/facebook', function (\Idno\Core\Event $event) {
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

                // Push "images" to Facebook
                \Idno\Core\site()->addEventHook('post/image/facebook', function (\Idno\Core\Event $event) {
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
            }*/
        }

    }
