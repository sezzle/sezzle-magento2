<?php

namespace Sezzle\Sezzlepay\Test\Unit\Helper;

use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Helper\Util;
use stdClass;
use TypeError;

/**
 * @covers \Sezzle\Sezzlepay\Helper\Util
 */
class UtilTest extends TestCase
{
    /**
     * Callers reach for this before they know whether an address exists at all, and
     * an address that is not there holds no shopper data.
     */
    public function testNullIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty(null));
    }

    public function testNoArgumentIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty());
    }

    /**
     * True means skip or omit, so the wrong type has to be loud. Answering "empty"
     * would drop billing_address from the Sezzle payload and re-point the identity
     * fields at shipping, far from whoever passed the wrong thing.
     */
    public function testUnrelatedObjectIsRejected(): void
    {
        $this->expectException(TypeError::class);

        Util::isAddressEmpty(new stdClass());
    }

    public function testStringIsRejected(): void
    {
        $this->expectException(TypeError::class);

        Util::isAddressEmpty('123 Main St');
    }

    public function testUntouchedAddressIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty($this->buildAddress([])));
    }

    /**
     * Magento pre-selects the store default country on every address form, so a
     * country is not evidence the shopper typed anything.
     */
    public function testCountryAloneIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty($this->buildAddress(['country_id' => 'US'])));
    }

    public function testBlankStreetLinesAreEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty($this->buildAddress(['street' => ['', '']])));
    }

    public function testWhitespaceIsEmpty(): void
    {
        $address = $this->buildAddress(['firstname' => '   ', 'city' => "\t"]);

        $this->assertTrue(Util::isAddressEmpty($address));
    }

    public function testEmptyStringsAreEmpty(): void
    {
        $address = $this->buildAddress(['firstname' => '', 'postcode' => '']);

        $this->assertTrue(Util::isAddressEmpty($address));
    }

    public function testASingleFieldIsEnoughToCount(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['firstname' => 'Jane'])));
    }

    /**
     * Street arrives as an array, so it is joined before being judged.
     */
    public function testStreetLineCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['street' => ['123 Main St']])));
    }

    public function testLaterStreetLineCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['street' => ['', 'Apt 4']])));
    }

    /**
     * A numeric field is still shopper data once cast to string.
     */
    public function testRegionIdCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['region_id' => 33])));
    }

    public function testCompanyCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['company' => 'Sezzle'])));
    }

    public function testVatIdCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['vat_id' => 'GB123'])));
    }

    /**
     * The ignored country must not mask a field that does count.
     */
    public function testCountryAlongsideRealDataCounts(): void
    {
        $address = $this->buildAddress(['country_id' => 'US', 'city' => 'Minneapolis']);

        $this->assertFalse(Util::isAddressEmpty($address));
    }

    /**
     * The exact payload AuthenticationService::getToken() hands the logger. Both the
     * merchant's private key and the bearer token minted from it used to be written to
     * var/log/sezzlepay.log in cleartext, which merchants email to support.
     */
    public function testAuthenticationPayloadHidesTheCredentialsButKeepsTheContext(): void
    {
        $redacted = Util::redactSensitive([
            'log_origin' => 'getToken',
            'request' => [
                'uri' => 'https://gateway.sezzle.com/v2/authentication',
                'body' => [
                    'public_key' => 'test-public-key',
                    'private_key' => 'test-private-key',
                    'cancel_url' => '/cancel'
                ]
            ],
            'response' => ['body' => ['token' => 'eyJhbGciOi.SECRET', 'merchant_uuid' => 'mu-123']]
        ]);

        $this->assertSame(Util::REDACTED, $redacted['request']['body']['private_key']);
        $this->assertSame(Util::REDACTED, $redacted['response']['body']['token']);

        // Everything support actually reads a log for has to survive redaction.
        $this->assertSame('getToken', $redacted['log_origin']);
        $this->assertSame('https://gateway.sezzle.com/v2/authentication', $redacted['request']['uri']);
        $this->assertSame('mu-123', $redacted['response']['body']['merchant_uuid']);
    }

    /**
     * The public key is already handed to the browser by the checkout config provider,
     * so it is not a secret - and it is what tells support which merchant account a log
     * line came from.
     */
    public function testPublicKeyIsNotRedacted(): void
    {
        $redacted = Util::redactSensitive(['public_key' => 'test-public-key']);

        $this->assertSame('test-public-key', $redacted['public_key']);
    }

    public function testMatchingIgnoresCaseAndReachesNestedValues(): void
    {
        $redacted = Util::redactSensitive(['Token' => 'x', 'a' => ['PRIVATE_KEY' => 'y', 'keep' => 'z']]);

        $this->assertSame(Util::REDACTED, $redacted['Token']);
        $this->assertSame(Util::REDACTED, $redacted['a']['PRIVATE_KEY']);
        $this->assertSame('z', $redacted['a']['keep']);
    }

    public function testNonStringValuesArePreserved(): void
    {
        $redacted = Util::redactSensitive(['amount' => 1234, 'ok' => true, 'missing' => null]);

        $this->assertSame(1234, $redacted['amount']);
        $this->assertTrue($redacted['ok']);
        $this->assertNull($redacted['missing']);
    }

    /**
     * Gateway\Http\Client logs the response body exactly as curl returned it, so the
     * credential arrives as raw JSON inside a string value rather than as an array key.
     */
    public function testCredentialsInsideASerializedStringAreRedacted(): void
    {
        $redacted = Util::redactSensitive([
            'response' => ['body' => '{"order":{"uuid":"abc"},"token":"eyJ0eXAi.RAW"}']
        ]);

        $this->assertStringNotContainsString('eyJ0eXAi.RAW', $redacted['response']['body']);
        $this->assertStringContainsString('"uuid":"abc"', $redacted['response']['body']);
    }

    /**
     * A value holding an escaped quote must not end the match early and leave the
     * remainder of the secret in the log.
     */
    public function testEscapedQuotesInsideASecretDoNotTruncateTheMatch(): void
    {
        $redacted = Util::redactSensitiveText('{"access_token":"a\\"bSECRET","keep":"yes"}');

        $this->assertStringNotContainsString('SECRET', $redacted);
        $this->assertStringContainsString('"keep":"yes"', $redacted);
    }

    /**
     * The sink cannot rely on call sites spelling a credential field the way this list does,
     * which was the whole reason for redacting here rather than at each call site.
     */
    #[DataProvider('credentialKeySpellingProvider')]
    public function testCredentialKeyVariantsAreRedacted(string $key): void
    {
        $redacted = Util::redactSensitive([$key => 'LEAK']);
        $this->assertSame(Util::REDACTED, $redacted[$key], $key . ' was left readable');

        $asText = Util::redactSensitiveText('{"' . $key . '":"LEAK"}');
        $this->assertStringNotContainsString('LEAK', $asText, $key . ' was left readable in raw JSON');
    }

    /**
     * @return array<string, string[]>
     */
    public static function credentialKeySpellingProvider(): array
    {
        return [
            'snake case' => ['private_key'],
            'camel case' => ['privateKey'],
            'prefixed' => ['sezzle_private_key'],
            'prefixed token' => ['merchant_token'],
            'header style' => ['X-Api-Key'],
            'upper case' => ['PASSWORD']
        ];
    }

    /**
     * Over-redaction is the safe direction, but not at the cost of the diagnostics support
     * reads. A key that merely contains a sensitive word is not itself a credential.
     */
    public function testKeysThatOnlyContainASensitiveWordStayReadable(): void
    {
        $redacted = Util::redactSensitive([
            'sezzle_tokenize_status' => 'complete',
            'public_key' => 'pk_visible',
            'publicKey' => 'pk_visible_too'
        ]);

        $this->assertSame('complete', $redacted['sezzle_tokenize_status']);
        $this->assertSame('pk_visible', $redacted['public_key']);
        $this->assertSame('pk_visible_too', $redacted['publicKey']);
    }

    /**
     * A non-string value is still a credential. Key matching covers this in an array payload,
     * but a body logged as raw JSON only has the text matcher to rely on.
     */
    public function testNonStringCredentialValuesInRawJsonAreRedacted(): void
    {
        $redacted = Util::redactSensitiveText('{"token":1234567,"secret":null,"amount":99}');

        $this->assertStringNotContainsString('1234567', $redacted);
        $this->assertStringNotContainsString('"secret":null', $redacted);
        // An ordinary numeric field is untouched.
        $this->assertStringContainsString('"amount":99', $redacted);
    }

    /**
     * Gateway\Http\Client logs curl's response body verbatim, so a body that itself contains a
     * JSON document arrives with its inner quotes escaped. The key is then \"token\" rather
     * than "token", which a pattern expecting a bare quote walks straight past.
     */
    public function testCredentialsInsideEscapedEmbeddedJsonAreRedacted(): void
    {
        $redacted = Util::redactSensitiveText(
            '{"uri":"/v2/authentication","body":"{\"token\":\"LEAKED\",\"public_key\":\"pk_ok\"}"}'
        );

        $this->assertStringNotContainsString('LEAKED', $redacted);
        // The document stays parseable and the identifying fields stay readable.
        $this->assertStringContainsString('pk_ok', $redacted);
        $this->assertStringContainsString('"uri":"/v2/authentication"', $redacted);
        $this->assertIsArray(json_decode($redacted, true));
    }

    /**
     * preg_replace_callback() returns null past pcre.backtrack_limit, which a large response
     * body can reach. Dropping the line is the safe direction, but a bare empty string leaves
     * a support engineer unable to tell a blank line from a lost one.
     */
    public function testAFailedRedactionSaysSoRatherThanWritingAnEmptyLine(): void
    {
        $limit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1');

        try {
            $result = Util::redactSensitiveText('{"token":"' . str_repeat('a', 5000) . '"}');
        } finally {
            ini_set('pcre.backtrack_limit', (string)$limit);
        }

        $this->assertSame(Util::REDACTION_FAILED, $result);
        $this->assertNotSame('', $result);
    }

    /**
     * Logging runs on the order-failure path ahead of the authorization release, so it
     * must not exhaust the stack whatever it is handed.
     */
    public function testDeeplyNestedPayloadIsCappedWithoutLeaking(): void
    {
        $payload = 'leaf';
        for ($i = 0; $i < 25; $i++) {
            $payload = ['private_key' => 'LEAK', 'next' => $payload];
        }

        $encoded = json_encode(Util::redactSensitive($payload));

        $this->assertStringNotContainsString('LEAK', $encoded);
    }

    /**
     * @param array $data
     * @return Address|MockObject
     */
    private function buildAddress(array $data)
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $address->method('getData')->willReturnCallback(
            static function ($field = null) use ($data) {
                return $field === null ? $data : ($data[$field] ?? null);
            }
        );

        return $address;
    }
}
