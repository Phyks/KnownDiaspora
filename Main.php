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
                                    $diasporaAPI->post($message, 'KnownDiaspora');
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

                $article_function = function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($this->hasDiaspora()) {
                        $diasporaAPI  = new DiasporaAPI(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_pod']);
                        $diasporaAPI->login(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'], \Idno\Core\site()->session()->currentUser()->diaspora['diaspora_password']);
                        if (!empty($diasporaAPI)) {
                            $message = $object->getTitle();
                            $message .= "\n\n(<a href=\"http://" . $object->getShortURL(true, false) . "\">" . $object->getShortURL(true, false) . "</a>)";
                            try {
                                $diasporaAPI->post($message, 'KnownDiaspora');
                            } catch (\Exception $e) {
                                error_log('There was a problem posting to Diaspora: ' . $e->getMessage());
                                \Idno\Core\site()->session()->addMessage('There was a problem posting to Diaspora: ' . $e->getMessage());
                            }
                        }
                    }
                };

                // Push "articles" and "rsvps" to Diaspora
                \Idno\Core\site()->addEventHook('post/rsvp/diaspora', $article_function);
                \Idno\Core\site()->addEventHook('post/article/diaspora', $article_function);

                // Push "media" to Diaspora
                \Idno\Core\site()->addEventHook('post/media/diaspora', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($this->hasDiaspora()) {
                        $diasporaAPI  = new DiasporaAPI(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_pod']);
                        $diasporaAPI->login(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'], \Idno\Core\site()->session()->currentUser()->diaspora['diaspora_password']);
                        if (!empty($diasporaAPI)) {
                            $message = strip_tags($object->getTitle()) . "\n\n" . strip_tags($object->getDescription());
                            try {
                                $diasporaAPI->post($message, 'KnownDiaspora');
                            } catch (\Exception $e) {
                                error_log('There was a problem posting to Diaspora: ' . $e->getMessage());
                                \Idno\Core\site()->session()->addMessage('There was a problem posting to Diaspora: ' . $e->getMessage());
                            }
                        }
                    }
                });

                // Push "images" to Diaspora
                \Idno\Core\site()->addEventHook('post/image/diaspora', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($attachments = $object->getAttachments()) {
                        foreach ($attachments as $attachment) {
                            if ($this->hasDiaspora()) {
                                $diasporaAPI  = new DiasporaAPI(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_pod']);
                                $diasporaAPI->login(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'], \Idno\Core\site()->session()->currentUser()->diaspora['diaspora_password']);
                                if (!empty($diasporaAPI)) {
                                    $message = strip_tags($object->getTitle()) . "\n\n<img src=\"".$attachment['url']."\"/>\n\n" . strip_tags($object->getDescription());
                                    $message .= "\n\nOriginal: " . $object->getURL();
                                    try {
                                        $diasporaAPI->post($message, 'KnownDiaspora');
                                    } catch (\Exception $e) {
                                        error_log('Could not post image to Diaspora: ' . $e->getMessage());
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
