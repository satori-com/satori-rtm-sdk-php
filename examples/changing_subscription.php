<?php

require('./src/autoloader.php');

error_reporting(E_ALL);

use RtmClient\RtmClient;
use RtmClient\Auth\RoleAuth;
use RtmClient\Subscription\Events;

const ENDPOINT = 'YOUR_ENDPOINT';
const APP_KEY = 'YOUR_APPKEY';
const ROLE = 'YOUR_ROLE';
const ROLE_SECRET_KEY = 'YOUR_ROLE_SECRET';

$options = array(
    'auth' => new RoleAuth(ROLE, ROLE_SECRET_KEY),
);
$client = new RtmClient(ENDPOINT, APP_KEY, $options);
$client->onConnected(function () {
    echo 'Connected to Satori RTM and authenticated as ' . ROLE . PHP_EOL;
})->onError(function ($type, $error) {
    echo "Type: $type; Error: $error[message] ($error[code])" . PHP_EOL;
});

$client->connect() or die;

$callback = function ($ctx, $type, $data) {
    switch ($type) {
        case Events::DATA:
            foreach ($data['messages'] as $message) {
                echo 'Got message: ' . json_encode($message) . PHP_EOL;
            }
            break;
        case Events::ERROR:
            echo 'Failed to subscribe! Error: ' . $data['error'] . '; Reason: ' . $data['reason'] . PHP_EOL;
            break;
        case Events::SUBSCRIBED:
            echo 'Subscribed to: ' . $data['subscription_id'] . PHP_EOL;
            $opts = $ctx->options;
            if (!empty($opts['filter'])) {
                echo '    using view: ' . $opts['filter'] . PHP_EOL;
            } else {
                echo '    without view' . PHP_EOL;
            }
            break;
    }
};
$client->subscribe('animals', $callback);

// Wait for subscribe confirmation message from RTM
$client->waitAllReplies();

// Resubscribe using filter
$client->subscribe('animals', $callback, array(
    'filter' => "select * from animals where who like 'z%'",
    'force' => true,
));

while (true) {
    // Read all incoming messages
    $client->sockReadSync();
}
