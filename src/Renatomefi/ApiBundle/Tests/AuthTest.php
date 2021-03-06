<?php

namespace Renatomefi\ApiBundle\Tests;

use OAuth2\OAuth2;
use Renatomefi\ApiBundle\Tests\Auth\AssertClientCredentials;
use Renatomefi\ApiBundle\Tests\Auth\AssertClientCredentialsInterface;
use Renatomefi\ApiBundle\Tests\Auth\AssertUserInfo;
use Renatomefi\ApiBundle\Tests\Auth\AssertUserInfoInterface;
use Renatomefi\ApiBundle\Tests\Auth\OAuthClient;
use Renatomefi\ApiBundle\Tests\Auth\OAuthClientInterface;
use Renatomefi\TestBundle\Rest\AssertRestUtils;
use Renatomefi\TestBundle\Rest\AssertRestUtilsInterface;
use Renatomefi\UserBundle\DataFixtures\MongoDB\LoadUsers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthTest
 * @package Renatomefi\ApiBundle\Tests
 */
class AuthTest extends WebTestCase implements AssertUserInfoInterface, AssertClientCredentialsInterface, OAuthClientInterface, AssertRestUtilsInterface
{

    use AssertUserInfo, AssertClientCredentials, AssertRestUtils, OAuthClient;

    /**
     * OAuth2 Client ID
     * @var
     */
    protected $_clientId;
    /**
     * OAuth2 Client Secret
     * @var
     */
    protected $_clientSecret;

    /**
     * @return \Renatomefi\ApiBundle\Document\Client
     */
    public function setUp()
    {
        $client = $this->getOAuthClient();

        $this->_clientId = $client->getPublicId();
        $this->_clientSecret = $client->getSecret();

        return $client;
    }

    /**
     * @return mixed
     */
    public function testAnonymousOAuth()
    {
        $client = static::createClient();

        $client->request('POST', '/oauth/v2/token',
            [
                'client_id' => $this->_clientId,
                'client_secret' => $this->_clientSecret,
                'grant_type' => OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS
            ], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response = $client->getResponse();

        $clientCredentials = $this->assertJsonResponse($response, Response::HTTP_OK, true);

        $this->assertClientCredentialsObjStructure($clientCredentials);
        $this->assertClientCredentialsToken($clientCredentials);
        $this->assertObjectNotHasAttribute('refresh_token', $clientCredentials);

        $this->assertEquals('bearer', $clientCredentials->token_type);

        return $clientCredentials;
    }

    /**
     * @return mixed
     */
    public function testPasswordOAuth()
    {
        $client = static::createClient();

        $client->request('POST', '/oauth/v2/token',
            [
                'client_id' => $this->_clientId,
                'client_secret' => $this->_clientSecret,
                'grant_type' => OAuth2::GRANT_TYPE_USER_CREDENTIALS,
                'username' => LoadUsers::USER_USERNAME,
                'password' => LoadUsers::USER_PASSWORD
            ], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response = $client->getResponse();

        $clientCredentials = $this->assertJsonResponse($response, Response::HTTP_OK, true);

        $this->assertClientCredentialsObjStructure($clientCredentials);
        $this->assertClientCredentialsToken($clientCredentials, 'access_token');
        $this->assertClientCredentialsToken($clientCredentials, 'refresh_token');

        return $clientCredentials;
    }

    /**
     * @param \StdClass $clientCredentials
     *
     * @depends testPasswordOAuth
     */
    public function testUserSession($clientCredentials)
    {
        $client = static::createClient();

        $client->request('GET', '/api/user/info', ['access_token' => $clientCredentials->access_token]);

        $response = $client->getResponse();

        $userInfo = $this->assertJsonResponse($response, Response::HTTP_OK, true);

        $this->assertUserInfoObjStructure($userInfo);
        $this->assertUserInfoObjAdminAuth($userInfo);
    }

    /**
     * @depends      testPasswordOAuth
     *
     * @param \StdClass $clientCredentials
     * @return \StdClass $refreshClientCredentials
     */
    public function testOAuthRefreshToken($clientCredentials)
    {
        $client = static::createClient();

        $client->request('POST', '/oauth/v2/token',
            [
                'client_id' => $this->_clientId,
                'client_secret' => $this->_clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $clientCredentials->refresh_token
            ], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response = $client->getResponse();

        $refreshClientCredentials = $this->assertJsonResponse($response, Response::HTTP_OK, true);

        $this->assertClientCredentialsObjStructure($refreshClientCredentials);
        $this->assertClientCredentialsToken($refreshClientCredentials, 'access_token');
        $this->assertClientCredentialsToken($refreshClientCredentials, 'refresh_token');

        // Old and New Tokens should not be the same
        $this->assertNotEquals($clientCredentials->access_token, $refreshClientCredentials->access_token);
        $this->assertNotEquals($clientCredentials->refresh_token, $refreshClientCredentials->refresh_token);

        return $refreshClientCredentials;
    }

    /**
     *
     */
    public function testEmptySession()
    {
        $client = static::createClient();

        $client->request('GET', '/api/user/info');

        $response = $client->getResponse();

        $userInfo = $this->assertJsonResponse($response, Response::HTTP_OK, true);

        $this->assertUserInfoObjStructure($userInfo);
        $this->assertUserInfoObjNoAuth($userInfo);
    }

    /**
     * @param \StdClass $clientCredentials
     *
     * @depends      testAnonymousOAuth
     */
    public function testAnonymousSession($clientCredentials)
    {
        $client = static::createClient();

        $client->request('GET', '/api/user/info',
            ['access_token' => $clientCredentials->access_token]
        );

        $response = $client->getResponse();

        $userInfo = $this->assertJsonResponse($response, Response::HTTP_OK, true);

        $this->assertUserInfoObjStructure($userInfo);

        $this->assertTrue($userInfo->authenticated);
        $this->assertTrue($userInfo->authenticated_fully);
        $this->assertTrue($userInfo->authenticated_anonymously);
        $this->assertTrue($userInfo->role_anonymous);
        $this->assertFalse($userInfo->role_user);
        $this->assertFalse($userInfo->role_admin);
        $this->assertEmpty($userInfo->user);
        $this->assertNotEmpty($userInfo->client);
    }

}
