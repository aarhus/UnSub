<?php

namespace Modules\UnSub\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

//use Modules\UnSub\Entities\;

define('UNSUB_MODULE', 'unsub');

class UnSubServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');



        \Eventy::addAction('conversation.created_by_customer', function ($conversation, $thread, $customer) {

            if (!isset($thread["headers"])) {
                return $conversation;
            }
            $headers = $this->getMyHeaders($thread["headers"]);

            if (isset($headers["List-Unsubscribe"])) {
                $thread->setMeta("List-Unsubscribe", $headers["List-Unsubscribe"]);
            }
            if (isset($headers["List-Unsubscribe-Post"])) {
                $thread->setMeta("List-Unsubscribe-Post", $headers["List-Unsubscribe-Post"]);
            }

            $thread->save();
            return $conversation;
        }, 20, 3);


        \Eventy::addFilter('email.reply_to_customer.subject', function ($subject, $conversation) {
            $details = $conversation->getMeta("List-Unsubscribe-Details");
            \Helper::Log("Wibble", json_encode(["meta" => $details, "subject" => $subject]));
            $conversation->setMeta("List-Unsubscribe-Details", null, true);
            return $details["subject"] ?? $subject;
        }, 99, 2);


        \Eventy::addFilter('email.auto_reply.subject', function ($subject, $conversation) {
            $details = $conversation->getMeta("List-Unsubscribe-Details");
            \Helper::Log("Wibble", json_encode(["meta" => $details, "subject" => $subject]));
            $conversation->setMeta("List-Unsubscribe-Details", null, true);
            return $details["subject"] ?? $subject;
        }, 99, 2);


        \Eventy::addFilter('thread.action_text', function ($did_this, $thread, $conversation_number, $escape) {

            if ($thread->action_type != 167 /*Unsubscriber::ACTION_TYPE_UNSUBSCRIBE*/) {
                return $did_this;
            }

            $meta = $thread->getMetas();

            $person = $thread->getActionPerson($conversation_number);


            $code = $meta["code"] ?? 0;

            if ($code > 199 && $code < 300 && $code != 202) {
                $did_this = __(':person successfully unsubscribed from this mailing list', ['person' => $person]);

                $did_this .= $thread->body;
            } else {
                $did_this = __(':person unsuccessfully unsubscribed from this mailing list', ['person' => $person]);
                $did_this .= print_r($meta, 1);
                $did_this .= $thread->body;
            }

            return ($escape) ? htmlspecialchars($did_this) : $did_this;
        }, 20, 4);



        \Eventy::addAction('conversation.after_subject', function ($conversation, $mailbox) {

            $c = $conversation->getMeta("List-Unsubscribe-Submitted");

            $status = $c["status"] ?? 0;

            if ($status > 199 && $status < 300 && $status != 202) {
                ?>
                <div class="conv-numnav" style="top: 2em;"> Unsubscribed </div>
                <?php
                return;
            }


            $thread = $conversation->getFirstThread();

            $unsub = $thread->getMeta("List-Unsubscribe", null);
            if ($unsub === null) {
                return;
            }

            $opts = array_map(function ($x) {
                return trim($x, " \n\r\t\v\x00,<"); }, explode(">", $unsub));

            $l = array_keys($opts);


            foreach ($l as $x) {
                $y = $opts[$x];
                unset($opts[$x]);

                if ($y) {
                    $bits = explode(":", $y, 2);
                    if (isset($bits[1])) {
                        $opts[$bits[0]] = $bits[1];
                    }
                }
            }

            if ((isset($opts["https"]) || isset($opts["http"]))) {

                ?>
                <div class="conv-numnav" style="top: 2em;"> <a href="/unsubscriber/<?= $mailbox->id; ?>/<?= $conversation->id; ?>">
                        Unsubscribe </a></div>
                <?php
                return;
            }

            ?>
            <div class="conv-numnav" style="top: 2em;"> Unsubscribe Not Available </a></div>
            <?php






        }, 20, 2);

        // Add module's css file to the application layout
        \Eventy::addFilter('stylesheets', function ($value) {
            array_push($value, '/modules/' . UNSUB_MODULE . '/css/style.css');
            return $value;
        }, 20, 1);

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('unsub.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'unsub'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/unsub');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/unsub';
        }, \Config::get('view.paths')), [$sourcePath]), 'unsub');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/unsub');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'unsub');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'unsub');
        }
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }


    function getMyHeaders($h)
    {
        $h_array = explode("\n", $h);

        foreach ($h_array as $h) {

            // Check if row start with a char
            if (preg_match("/^[A-Z]/i", $h)) {

                $tmp = explode(":", $h, 2);
                $header_name = $tmp[0];
                $header_value = array_reduce(
                    imap_mime_header_decode(trim($tmp[1])),
                    function ($carry, $key) {
                        return $carry . $key->text;
                    },
                    ""
                );

                $headers[$header_name] = $header_value;

            } else {
                // Append row to previous field

                if (!isset($header_name)) {
                    \Helper::Log("wibble", json_encode(["failed_to_find headername", $h, $h_array]));
                } else {

                    $headers[$header_name] = array_reduce(
                        imap_mime_header_decode(trim($h)),
                        function ($carry, $key) {
                            return $carry . $key->text;
                        },
                        $headers[$header_name] ?? []
                    );
                }
            }

        }
        return $headers ?? [];
    }
}
