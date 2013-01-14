<?php

/**
 * PHP Domain Parser: Public Suffix List based URL parsing
 *
 * @link      http://github.com/jeremykendall/php-domain-parser for the canonical source repository
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/php-domain-parser/blob/master/LICENSE MIT License
 */

namespace Pdp;

/**
 * Domain Parser
 *
 * This class is reponsible for domain parsing
 */
class DomainParser
{

    /**
     * @var PublicSuffixList Public Suffix List
     */
    protected $publicSuffixList;

    /**
     * Public constructor
     *
     * @codeCoverageIgnore
     * @param PublicSuffixList $publicSuffixList Instance of PublicSuffixList
     */
    public function __construct(PublicSuffixList $publicSuffixList)
    {
        $this->publicSuffixList = $publicSuffixList;
    }

    /**
     * Parses url
     *
     * @param  string $url Url to parse
     * @return Domain Parsed domain object
     */
    public function parse($url)
    {
        $parts = array();

        $parts['url'] = $url;

        preg_match('/^(\w.*):\/{2,3}/i', $parts['url'], $schemeMatches);

        if (empty($schemeMatches)) {
            $parts['scheme'] = null;
            $host = $url;
        } else {
            $parts['scheme'] = $schemeMatches[1];
            $host = str_replace($schemeMatches[0], '', $parts['url']);
        }

        if (strpos($host, '/') !== false) {
            $parts['path'] = substr($host, strpos($host, '/'));
            $split = explode('/', $host);
            $host = $split[0];
        } else {
            $parts['path'] = null;
        }

        if (strlen($parts['path']) <= 1) {
            $parts['path'] = null;
        }

        $parts['registerableDomain'] = $this->getRegisterableDomain($host);
        $parts['publicSuffix'] = $this->getPublicSuffix($host);

        $registerableDomainParts = explode('.', $parts['registerableDomain']);
        $hostParts = explode('.', $host);
        $subdomainParts = array_diff($hostParts, $registerableDomainParts);
        $parts['subdomain'] = implode('.', $subdomainParts);

        if (empty($parts['subdomain']) && !is_null($parts['subdomain'])) {
            $parts['subdomain'] = null;
        }

        return new Domain($parts);
    }

    /**
     * Returns registerable domain portion of provided domain
     *
     * This method is based heavily on the code found in regDomain.inc.php
     * @link https://github.com/usrflo/registered-domain-libs/blob/master/PHP/regDomain.inc.php
     * A copy of the Apache License, Version 2.0, is provided with this
     * distribution
     *
     * @param  string $domain Domain
     * @return string Registerable domain
     */
    public function getRegisterableDomain($domain)
    {
        $domainParts = explode('.', strtolower($domain));
        $registerableDomain = $this->breakdown($domainParts, $this->publicSuffixList);

        return $registerableDomain;
    }

    /**
     * Gets public suffix for provided domain
     *
     * This method is based heavily on the code found in regDomain.inc.php
     * @link https://github.com/usrflo/registered-domain-libs/blob/master/PHP/regDomain.inc.php
     * A copy of the Apache License, Version 2.0, is provided with this
     * distribution
     *
     * @param  string $domain Domain
     * @return string Public suffix
     */
    public function getPublicSuffix($domain)
    {
        $registerableDomain = $this->getRegisterableDomain($domain);
        $registerableDomainParts = explode(
            '.',
            strtolower($registerableDomain)
        );
        array_shift($registerableDomainParts);
        $publicSuffix = implode('.', $registerableDomainParts);

        return $publicSuffix;
    }

    /**
     * Compares domain parts to the Public Suffix List
     *
     * This method is based heavily on the code found in regDomain.inc.php.
     *
     * A copy of the Apache License, Version 2.0, is provided with this
     * distribution
     *
     * @link https://github.com/usrflo/registered-domain-libs/blob/master/PHP/regDomain.inc.php regDomain.inc.php
     *
     * @param array $domainParts      Domain parts as array
     * @param array $publicSuffixList Array representation of the Public Suffix
     * List
     * @return string Public suffix
     */
    public function breakdown(array $domainParts, $publicSuffixList)
    {
        $part = array_pop($domainParts);
        $result = null;

        if (array_key_exists($part, $publicSuffixList)) {
            $result = $this->breakdown($domainParts, $publicSuffixList[$part]);
        }

        if (array_key_exists('*', $publicSuffixList)) {
            $result = $this->breakdown($domainParts, $publicSuffixList['*']);
        }

        if ($result === null) {
            return $part;
        }

        return $result . '.' . $part;
    }

}
