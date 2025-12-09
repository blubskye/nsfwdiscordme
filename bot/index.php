<?php
/**
 * Discord Bot using DiscordPHP v10
 *
 * This bot is used to create invite links for servers that use the "bot" invite type.
 *
 * Required Gateway Intents:
 * - GUILDS: To access guild information and channels
 *
 * Note: If you need to access message content or member lists, you'll need to enable
 * those privileged intents in the Discord Developer Portal.
 */

use Discord\Discord;
use Discord\Parts\User\Activity;
use Discord\WebSockets\Intents;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/bootstrap.php';

$discord = new Discord([
    'token' => $_SERVER['DISCORD_BOT_TOKEN'],
    'intents' => Intents::getDefaultIntents(),
    'loadAllMembers' => false,
]);

$discord->on('ready', function (Discord $discord) {
    echo "Bot is ready!" . PHP_EOL;
    echo "Logged in as: " . $discord->user->username . "#" . $discord->user->discriminator . PHP_EOL;
    echo "Bot ID: " . $discord->user->id . PHP_EOL;
    echo "Guilds: " . $discord->guilds->count() . PHP_EOL;

    // Set bot activity/status (optional)
    $activity = $discord->factory(Activity::class, [
        'name' => 'nsfwdiscord.me',
        'type' => Activity::TYPE_WATCHING,
    ]);
    $discord->updatePresence($activity);
});

// Handle errors
$discord->on('error', function (\Exception $e, Discord $discord) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
});

// Handle disconnections
$discord->on('closed', function (Discord $discord) {
    echo "WebSocket connection closed." . PHP_EOL;
});

// Start the bot
$discord->run();
