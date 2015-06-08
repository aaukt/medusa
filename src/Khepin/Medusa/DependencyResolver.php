<?php
/**
 * @copyright 2013 Sébastien Armand
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Khepin\Medusa;

use Guzzle\Service\Client;

/**
 * Finds all the dependencies on which a given package relies
 */
class DependencyResolver
{
    protected $package;

    public function __construct($package)
    {
        $this->package = $package;
    }

    public function resolve()
    {
        $deps = array($this->package);
        $resolved = array();

        $guzzle = new Client('http://packagist.org');

        while (count($deps) > 0) {
            $package = $this->rename(array_pop($deps));

            if (!$package) {
                continue;
            }

            try {
                $response = $guzzle->get('/packages/'.$package.'.json')->send()->getBody(true);
            } catch (\Exception $e) {
                continue;
            }
            $package = json_decode($response);

            if (!is_null($package)) {
                foreach ($package->package->versions as $version) {
                    if (!isset($version->require)) {
                        continue;
                    }

                    foreach ($version->require as $dependency => $version) {
                        if (!in_array($dependency, $resolved) && !in_array($dependency, $deps)) {
                            $deps[] = $dependency;
                            $deps = array_unique($deps);
                        }
                    }
                }

                $resolved[] = $package->package->name;
            }
        }

        return $resolved;
    }

    private function rename($package)
    {
        static $packages = array(
            'facebook/php-webdriver' => 'instaclick/php-webdriver',
            'metadata/metadata' => 'jms/metadata',
            'symfony/doctrine-bundle' => 'doctrine/doctrine-bundle',
            'symfony/translator' => 'symfony/translation',
            'willdurand/expose-translation-bundle' => 'willdurand/js-translation-bundle',

            // obsolete
            'zendframework/zend-registry' => null,
            
            // some older phpdocumentor version require these
            'zendframework/zend-translator' => null,
            'zendframework/zend-locale' => null,
            'phpdocumentor/template-installer' => null,
            'pear-symfony/eventdispatcher' => null
        );

        if (array_key_exists($package, $packages)) {
            return $packages[$package];
        }

        return $package;
    }
}