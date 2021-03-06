<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\ResourceRestBundle\Tests\Features\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use PHPCR\Util\NodeHelper;
use PHPCR\Util\PathHelper;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Behat\Context\Context;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ResourceContext implements Context, KernelAwareContext
{
    private $session;
    private $manager;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Return the path of the configuration file used by the AppKernel.
     *
     * @static
     *
     * @return string
     */
    public static function getConfigurationFile()
    {
        return __DIR__.'/../../Resources/app/cache/resource.yml';
    }

    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        if (file_exists(self::getConfigurationFile())) {
            unlink(self::getConfigurationFile());
        }

        $this->clearDiCache();

        $this->manager = $this->kernel->getContainer()->get('doctrine_phpcr.odm.document_manager');
        $this->session = $this->manager->getPhpcrSession();

        if ($this->session->getRootNode()->hasNode('tests')) {
            $this->session->removeItem('/tests');
            $this->session->save();
        }
    }

    /**
     * @Given the test application has the following configuration:
     */
    public function setApplicationConfig(PyStringNode $config)
    {
        file_put_contents(self::getConfigurationFile(), $config->getRaw());
    }

    /**
     * @Given there is a file named :filename with:
     */
    public function createFile($filename, PyStringNode $content)
    {
        $filesytem = new Filesystem();
        $file = str_replace('%kernel.root_dir%', $this->kernel->getRootDir(), $filename);
        $filesytem->mkdir(dirname($file));

        file_put_contents($file, (string) $content);
    }

    /**
     * @Given there exists a :class document at :path:
     */
    public function createDocument($class, $path, TableNode $fields)
    {
        $class = 'Symfony\\Cmf\\Bundle\\ResourceRestBundle\\Tests\\Resources\\TestBundle\\Document\\'.$class;
        $path = '/tests'.$path;

        $parentPath = PathHelper::getParentPath($path);

        if (!$this->session->nodeExists($parentPath)) {
            NodeHelper::createPath($this->session, $parentPath);
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf(
                'Class "%s" does not exist',
                $class
            ));
        }

        $document = new $class();
        $document->id = $path;

        foreach ($fields->getRowsHash() as $field => $value) {
            $document->$field = $value;
        }

        $this->manager->persist($document);
        $this->manager->flush();
    }

    private function clearDiCache()
    {
        $finder = new Finder();
        $finder->in($this->kernel->getCacheDir());
        $finder->name('*.php');
        $finder->name('*.php.meta');
        $filesystem = new Filesystem();
        $filesystem->remove($finder);
    }
}
