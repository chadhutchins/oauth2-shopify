<?php

namespace Chadhutchins\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Shopify extends AbstractProvider
{

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';

    /**
     * @var string This will be prepended to the base uri.
     * @link https://help.shopify.com/api/guides/authentication/oauth#asking-for-permission
     */
    protected $shop;

    /**
     * @var string If set, this will be sent to shopify as the "per-user" parameter.
     * @link https://help.shopify.com/api/guides/authentication/oauth#asking-for-permission
     */
    protected $accessType;

    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options An array of options to set on this provider.
     *     Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *     Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, `httpClient`, and `randomFactory`.
     *     Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (empty($this->shop)) {
            throw new IdentityProviderException(
                'No shop has been configured for this Shopify provider; it has to have a shop.',
                0,
                []
            );
        }
    }

    public function getBaseAuthorizationUrl()
    {
        return 'https://'.$this->shop.'.myshopify.com/admin/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://'.$this->shop.'.myshopify.com/admin/oauth/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://'.$this->shop.'.myshopify.com/admin/shop.json';
    }

    public function getAuthorizationParameters(array $options)
    {
        $option = (!empty($this->accessType) && $this->accessType != 'offline') ? 'per-user' : null;
        $params = array_merge(
            parent::getAuthorizationParameters($options),
            array_filter([
                'option' => $option
            ])
        );

        return $params;
    }

    public function getDefaultScopes()
    {
        return [
            'read_content',
            'read_products',
            'read_customers',
            'read_orders',
        ];
    }

    public function getScopeSeparator()
    {
        return ',';
    }

    public function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['errors'])) {
            throw new IdentityProviderException($data['errors'], 0, $data);
        }

        return $data;
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ShopifyStore($response);
    }

    /**
     * Returns the authorization headers used by this provider.
     *
     * Typically this is "Bearer" or "MAC". For more information see:
     * http://tools.ietf.org/html/rfc6749#section-7.1
     *
     * No default is provided, providers must overload this method to activate
     * authorization headers.
     *
     * @param  mixed|null $token Either a string or an access token instance
     * @return array
     */
    public function getAuthorizationHeaders($token = null)
    {
        return array('X-Shopify-Access-Token' => $token->getToken());
    }
}
