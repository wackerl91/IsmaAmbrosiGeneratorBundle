<?php

namespace IsmaAmbrosi\Bundle\GeneratorBundle\Generator;

use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Generates a form class based on a Doctrine document.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 */
class DoctrineFormGenerator extends Generator
{

    private $filesystem;

    private $skeletonDir;

    private $className;

    private $classPath;

    public function __construct(Filesystem $filesystem, $skeletonDir)
    {
        $this->filesystem = $filesystem;
        $this->skeletonDir = $skeletonDir;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getClassPath()
    {
        return $this->classPath;
    }

    /**
     * Generates the document form class if it does not exist.
     *
     * @param BundleInterface   $bundle The bundle in which to create the class
     * @param string            $document The document relative class name
     * @param ClassMetadataInfo $metadata The document metadata class
     */
    public function generate(BundleInterface $bundle, $document, ClassMetadataInfo $metadata)
    {
        $parts = explode('\\', $document);
        $documentClass = array_pop($parts);

        $this->className = $documentClass.'Type';
        $dirPath = $bundle->getPath().'/Form';
        $this->classPath = $dirPath.'/'.str_replace('\\', '/', $document).'Type.php';

        if (file_exists($this->classPath)) {
            throw new \RuntimeException(sprintf('Unable to generate the %s form class as it already exists under the %s file', $this->className, $this->classPath));
        }

        if (count($metadata->identifier) > 1) {
            throw new \RuntimeException('The form generator does not support document classes with multiple primary keys.');
        }

        $parts = explode('\\', $document);
        array_pop($parts);

        $this->renderFile($this->skeletonDir, 'FormType.php', $this->classPath, array(
            'dir'                => $this->skeletonDir,
            'fields'             => $this->getFieldsFromMetadata($metadata),
            'namespace'          => $bundle->getNamespace(),
            'document_namespace' => implode('\\', $parts),
            'form_class'         => $this->className,
            'form_type_name'     => strtolower(str_replace('\\', '_', $bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$this->className),
        ));
    }

    /**
     * Returns an array of fields. Fields can be both column fields and
     * association fields.
     *
     * @param ClassMetadataInfo $metadata
     *
     * @return array $fields
     */
    private function getFieldsFromMetadata(ClassMetadataInfo $metadata)
    {
        $fields = (array)$metadata->getFieldNames();

        // Remove the primary key field if it's not managed manually
        if ($metadata->isIdGeneratorAuto()) {
            $fields = array_diff($fields, array($metadata->identifier));
        }

        return $fields;
    }
}