<?php
/**
 * Copyright 2017 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace Facebook\Tests\Authentication;

use Facebook\Authentication\AccessTokenMetadata;
use PHPUnit\Framework\TestCase;

class AccessTokenMetadataTest extends TestCase
{
    protected $graphResponseData = [
        'data' => [
            'app_id'      => '123',
            'application' => 'Foo App',
            'error'       => [
                'code'    => 190,
                'message' => 'Foo error message.',
                'subcode' => 463,
            ],
            'issued_at'  => 1422110200,
            'expires_at' => 1422115200,
            'is_valid'   => false,
            'metadata'   => [
                'sso'        => 'iphone-sso',
                'auth_type'  => 'rerequest',
                'auth_nonce' => 'no-replicatey',
            ],
            'scopes'     => ['public_profile', 'basic_info', 'user_friends'],
            'profile_id' => '1000',
            'user_id'    => '1337',
        ],
    ];

    public function testDatesGetCastToDateTime()
    {
        $metadata = new AccessTokenMetadata($this->graphResponseData);

        $expires = $metadata->getExpiresAt();
        $issuedAt = $metadata->getIssuedAt();

        $this->assertInstanceOf(\DateTime::class, $expires);
        $this->assertInstanceOf(\DateTime::class, $issuedAt);
    }

    public function testAllTheGettersReturnTheProperValue()
    {
        $metadata = new AccessTokenMetadata($this->graphResponseData);

        $this->assertEquals('123', $metadata->getAppId());
        $this->assertEquals('Foo App', $metadata->getApplication());
        $this->assertTrue($metadata->isError(), 'Expected an error');
        $this->assertEquals('190', $metadata->getErrorCode());
        $this->assertEquals('Foo error message.', $metadata->getErrorMessage());
        $this->assertEquals('463', $metadata->getErrorSubcode());
        $this->assertFalse($metadata->getIsValid(), 'Expected the access token to not be valid');
        $this->assertEquals('iphone-sso', $metadata->getSso());
        $this->assertEquals('rerequest', $metadata->getAuthType());
        $this->assertEquals('no-replicatey', $metadata->getAuthNonce());
        $this->assertEquals('1000', $metadata->getProfileId());
        $this->assertEquals(['public_profile', 'basic_info', 'user_friends'], $metadata->getScopes());
        $this->assertEquals('1337', $metadata->getUserId());
    }

    public function testInvalidMetadataWillThrow()
    {
        $this->expectException(\Facebook\Exception\SDKException::class);
        new AccessTokenMetadata(['foo' => 'bar']);
    }

    public function testAnExpectedAppIdWillNotThrow()
    {
        $metadata = new AccessTokenMetadata($this->graphResponseData);
        $metadata->validateAppId('123');

        $this->assertTrue(true);
    }

    public function testAnUnexpectedAppIdWillThrow()
    {
        $this->expectException(\Facebook\Exception\SDKException::class);
        $metadata = new AccessTokenMetadata($this->graphResponseData);
        $metadata->validateAppId('foo');

        $this->assertTrue(true);
    }

    public function testAnExpectedUserIdWillNotThrow()
    {
        $metadata = new AccessTokenMetadata($this->graphResponseData);
        $metadata->validateUserId('1337');

        $this->assertTrue(true);
    }

    public function testAnUnexpectedUserIdWillThrow()
    {
        $this->expectException(\Facebook\Exception\SDKException::class);
        $metadata = new AccessTokenMetadata($this->graphResponseData);
        $metadata->validateUserId('foo');
    }

    public function testAnActiveAccessTokenWillNotThrow()
    {
        $this->graphResponseData['data']['expires_at'] = time() + 1000;
        $metadata = new AccessTokenMetadata($this->graphResponseData);
        $metadata->validateExpiration();

        $this->assertTrue(true);
    }

    public function testAnExpiredAccessTokenWillThrow()
    {
        $this->expectException(\Facebook\Exception\SDKException::class);
        $this->graphResponseData['data']['expires_at'] = time() - 1000;
        $metadata = new AccessTokenMetadata($this->graphResponseData);
        $metadata->validateExpiration();
    }
}
