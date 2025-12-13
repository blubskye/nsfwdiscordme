<?php
namespace App\Services;

use App\Entity\AccessToken;
use App\Services\Exception\DiscordException;
use App\Services\Exception\DiscordRateLimitException;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * Class DiscordService
 */
class DiscordService
{
    use LoggerAwareTrait;

    public const API_BASE_URL = 'https://discord.com/api/v10';
    public const CDN_BASE_URL = 'https://cdn.discordapp.com';
    public const USER_AGENT   = 'nsfwdiscord.me (https://nsfwdiscord.me/, 1)';
    public const TIMEOUT      = 5.0;
    public const RETRY_LIMIT  = 3;
    public const CACHE_TIME   = 10;

    /** @var array<string, float> Tracks rate limit reset times per bucket */
    protected array $rateLimitBuckets = [];

    public function __construct(
        protected AdapterInterface $cache,
        protected string $clientID,
        protected string $clientSecret,
        protected string $botToken,
        protected string $defaultIcon
    ) {
    }

    /**
     * @param int|string $serverID
     *
     * @return array
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    public function fetchWidget(int|string $serverID): array
    {
        return $this->doRequest('GET', "guilds/{$serverID}/widget.json");
    }

    /**
     * @param string|int $userID
     *
     * @return array
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    public function fetchUser(string|int $userID): array
    {
        return $this->doRequest('GET', "users/{$userID}", null, true);
    }

    /**
     * @param string $serverID
     *
     * @return array
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    public function fetchGuild(string $serverID): array
    {
        return $this->doRequest('GET', "guilds/{$serverID}", null, true);
    }

    /**
     * @param AccessToken $token
     *
     * @return array
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    public function fetchMeGuilds(AccessToken $token): array
    {
        return $this->doRequest('GET', 'users/@me/guilds', null, $token);
    }

    /**
     * @param string $serverID
     *
     * @return array
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    public function fetchGuildChannels(string $serverID): array
    {
        return $this->doRequest('GET', "guilds/{$serverID}/channels", null, true);
    }

    /**
     * @param string $serverID
     *
     * @return array
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    public function fetchGuildMembers(string $serverID): array
    {
        return $this->doRequest('GET', "guilds/{$serverID}/members", null, true);
    }

    /**
     * @param string $serverID
     *
     * @return int
     * @throws Exception
     * @throws GuzzleException
     */
    public function fetchOnlineCount(string $serverID): int
    {
        try {
            $widget = $this->fetchWidget($serverID);
            if (is_array($widget) && isset($widget['members'])) {
                return count($widget['members']);
            }
        } catch (Exception) {}

        try {
            $members = $this->fetchGuildMembers($serverID);
            if (is_array($members)) {
                return count($members);
            }
        } catch (Exception) {}

        throw new Exception("Unable to fetch online count for {$serverID}.");
    }

    /**
     * @param string $channelID
     *
     * @return string
     * @throws Exception
     * @throws GuzzleException
     */
    public function createBotInviteURL(string $channelID): string
    {
        $invite = $this->doRequest('POST', "channels/{$channelID}/invites", [], true);
        if (!$invite || !isset($invite['code'])) {
            throw new Exception('Unable to generate invite.');
        }

        return "https://discord.gg/{$invite['code']}";
    }

    /**
     * @param string $serverID
     *
     * @return string
     * @throws Exception
     * @throws GuzzleException
     */
    public function createWidgetInviteURL(string $serverID): string
    {
        $widget = $this->fetchWidget($serverID);
        if (!$widget || !isset($widget['instant_invite'])) {
            throw new Exception('Unable to generate invite.');
        }

        return $widget['instant_invite'];
    }

    /**
     * @param string $serverID
     * @param string $iconHash
     * @param string $ext
     *
     * @return string
     */
    public function writeGuildIcon(string $serverID, ?string $iconHash, string $ext = 'png'): string
    {
        if (!$iconHash) {
            $tmp = tempnam(sys_get_temp_dir(), 'icon_');
            file_put_contents($tmp, file_get_contents($this->defaultIcon));

            return $tmp;
        }

        $url    = sprintf('%s/icons/%s/%s.%s', self::CDN_BASE_URL, $serverID, $iconHash, $ext);
        $client = new Guzzle([
            'timeout' => self::TIMEOUT
        ]);
        $resp = $client->get($url);
        $data = (string)$resp->getBody();

        $tmp = tempnam(sys_get_temp_dir(), 'icon_');
        file_put_contents($tmp, $data);

        return $tmp;
    }

    /**
     * Given a username#discriminator combination, returns an array with separate username and discriminator
     *
     * Throws an InvalidArgumentException when the given username is not valid.
     *
     * @see https://discord.com/developers/docs/resources/user#usernames-and-nicknames
     *
     * @param string $username
     *
     * @return array
     */
    public function extractUsernameAndDiscriminator(string $username): array
    {
        if (preg_match('/^([^@#:]{2,32})#([\d]{4})$/i', $username, $matches) && !str_contains($username, '```')) {
            $extractedUsername = $matches[1];
            $discriminator = (int)$matches[2];
            if (in_array(strtolower($extractedUsername), ['discordtag', 'everyone', 'here'])) {
                throw new InvalidArgumentException(
                    "Invalid username {$extractedUsername}."
                );
            }

            return [$extractedUsername, $discriminator];
        }

        throw new InvalidArgumentException(
            "Invalid username {$username}."
        );
    }

    /**
     * @param string           $method
     * @param string           $path
     * @param array|null       $body
     * @param AccessToken|bool $token
     *
     * @return mixed
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     * @throws Exception
     */
    protected function doRequest($method, $path, $body = null, $token = null)
    {
        $url       = sprintf('%s/%s', self::API_BASE_URL, $path);
        $cacheKey  = sprintf('discord.%s.%s', $method, md5($url));
        $cacheItem = null;

        try {
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            $this->logger->warning($e->getMessage());
        }

        $tries   = 0;
        $data    = [];
        $options = $this->buildRequestOptions($body, $token);
        $client  = new Guzzle();

        while(true) {
            // Check if we need to wait for a rate limit reset before making the request
            $this->waitForRateLimit($path);

            $this->logger->debug(
                sprintf('Discord: %s %s', $method, $url),
                $options
            );

            $response   = $client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $data       = json_decode((string)$response->getBody(), true);

            // Process rate limit headers from the response
            $this->processRateLimitHeaders($response, $path);

            if (!is_array($data)) {
                throw new DiscordException('Discord: Received invalid response.');
            }

            if ($statusCode === 429) {
                if (++$tries > self::RETRY_LIMIT) {
                    throw new DiscordRateLimitException();
                }

                // Get retry time from response body or headers
                $retryAfterSeconds = $this->getRetryAfter($response, $data);

                $this->logger->debug(
                    sprintf('Discord: Rate limited. Sleeping for %.2f seconds.', $retryAfterSeconds)
                );

                // Convert seconds to microseconds for usleep
                usleep((int)($retryAfterSeconds * 1000000));
                continue;
            } else if ($statusCode !== 200) {
                $this->logger->debug(
                    sprintf('Discord: Received status code %d.', $statusCode),
                    $data
                );
                throw new DiscordException($statusCode);
            }

            break;
        }

        if (!$cacheItem) {
            $cacheItem = new CacheItem();
        }
        $cacheItem->set($data)->expiresAfter(self::CACHE_TIME);
        $this->cache->save($cacheItem);

        return $data;
    }

    /**
     * @param mixed $body
     * @param mixed $token
     *
     * @return array
     */
    protected function buildRequestOptions($body, $token)
    {
        $headers = [
            'User-Agent'   => self::USER_AGENT,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        ];
        if ($token) {
            if ($token instanceof AccessToken) {
                $headers['Authorization'] = sprintf('%s %s', $token->getType(), $token->getToken());
            } else {
                $headers['Authorization'] = sprintf('Bot %s', $this->botToken);
            }
        }

        $options = [
            'headers'     => $headers,
            'http_errors' => false,
            'timeout'     => self::TIMEOUT
        ];
        if ($body !== null) {
            $options['body'] = json_encode($body);
        }

        return $options;
    }

    /**
     * Wait for rate limit to reset if we're currently limited on this endpoint
     *
     * @param string $path The API endpoint path
     */
    protected function waitForRateLimit(string $path): void
    {
        $bucket = $this->getBucketKey($path);

        if (isset($this->rateLimitBuckets[$bucket])) {
            $resetTime = $this->rateLimitBuckets[$bucket];
            $now = microtime(true);

            if ($resetTime > $now) {
                $waitTime = $resetTime - $now;
                $this->logger->debug(
                    sprintf('Discord: Proactively waiting %.2f seconds for rate limit reset on %s', $waitTime, $bucket)
                );
                usleep((int)($waitTime * 1000000));
            }
        }
    }

    /**
     * Process rate limit headers from Discord's response
     *
     * Discord sends these headers:
     * - X-RateLimit-Limit: Number of requests allowed per window
     * - X-RateLimit-Remaining: Number of requests remaining in current window
     * - X-RateLimit-Reset: Unix timestamp when the rate limit resets
     * - X-RateLimit-Reset-After: Seconds until the rate limit resets
     * - X-RateLimit-Bucket: Unique identifier for this rate limit bucket
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $path
     */
    protected function processRateLimitHeaders($response, string $path): void
    {
        $remaining = $response->getHeaderLine('X-RateLimit-Remaining');
        $resetAfter = $response->getHeaderLine('X-RateLimit-Reset-After');
        $bucket = $response->getHeaderLine('X-RateLimit-Bucket') ?: $this->getBucketKey($path);

        // If we have 0 remaining requests, store when we can make requests again
        if ($remaining !== '' && (int)$remaining === 0 && $resetAfter !== '') {
            $this->rateLimitBuckets[$bucket] = microtime(true) + (float)$resetAfter;
            $this->logger->debug(
                sprintf('Discord: Rate limit exhausted on bucket %s, reset in %.2f seconds', $bucket, (float)$resetAfter)
            );
        } elseif ($remaining !== '' && (int)$remaining > 0) {
            // Clear any stored rate limit for this bucket
            unset($this->rateLimitBuckets[$bucket]);
        }
    }

    /**
     * Get retry time from 429 response (body or headers)
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $data Response body data
     * @return float Seconds to wait
     */
    protected function getRetryAfter($response, array $data): float
    {
        // First check response body (most accurate for 429s)
        if (isset($data['retry_after'])) {
            return (float)$data['retry_after'];
        }

        // Fall back to Retry-After header
        $retryAfterHeader = $response->getHeaderLine('Retry-After');
        if ($retryAfterHeader !== '') {
            return (float)$retryAfterHeader;
        }

        // Fall back to X-RateLimit-Reset-After header
        $resetAfter = $response->getHeaderLine('X-RateLimit-Reset-After');
        if ($resetAfter !== '') {
            return (float)$resetAfter;
        }

        // Default fallback: 1 second
        return 1.0;
    }

    /**
     * Generate a bucket key for a given API path
     * Groups similar endpoints together for rate limiting
     *
     * @param string $path
     * @return string
     */
    protected function getBucketKey(string $path): string
    {
        // Replace snowflake IDs with placeholder to group similar endpoints
        // e.g., "guilds/123456789/widget.json" -> "guilds/:id/widget.json"
        return preg_replace('/\/\d{17,}/', '/:id', $path);
    }
}
